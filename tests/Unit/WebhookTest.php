<?php

namespace Tests\Webfox\Xero\Unit;

use Illuminate\Support\Facades\Route;
use Tests\Webfox\Xero\TestCase;
use Tests\Webfox\Xero\TestSupport\XeroOAuth;
use Webfox\Xero\OauthCredentialManager;
use Webfox\Xero\Webhook;
use Webfox\Xero\WebhookEvent;
use XeroAPI\XeroPHP\Api\AccountingApi;

class WebhookTest extends TestCase
{
    public function test_it_cannot_be_empty()
    {
        XeroOAuth::fake();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The webhook payload could not be decoded: No error');

        new Webhook(
            app(OauthCredentialManager::class),
            app(AccountingApi::class),
            json_encode([]),
            'signing-key'
        );
    }

    public function test_it_can_be_malformed()
    {
        XeroOAuth::fake();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The webhook payload was malformed');

        new Webhook(
            app(OauthCredentialManager::class),
            app(AccountingApi::class),
            json_encode([
                'differentArrayKeys' => 'nope'
            ]),
            'signing-key'
        );
    }

    public function test_you_can_get_webhook()
    {
        XeroOAuth::fake();

        $sut = new Webhook(
            app(OauthCredentialManager::class),
            app(AccountingApi::class),
            json_encode([
                'events' => [
                    $invoiceEvent = [
                        'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Invoices/123',
                        'resourceId' => '123',
                        'eventDateUtc' => '2021-01-01T00:00:00.000Z',
                        'eventType' => 'CREATE',
                        'eventCategory' => 'INVOICE',
                        'tenantId' => '456',
                        'tenantType' => 'ORGANISATION',
                    ],
                    $contactEvent = [
                        'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Contacts/123',
                        'resourceId' => '123',
                        'eventDateUtc' => '2021-01-01T00:00:00.000Z',
                        'eventType' => 'CREATE',
                        'eventCategory' => 'CONTACT',
                        'tenantId' => '456',
                        'tenantType' => 'ORGANISATION',
                    ],
                ],
                'firstEventSequence' => 1,
                'lastEventSequence' => 2,
            ]),
            'signing-key'
        );

        $this->assertEquals(1, $sut->getFirstEventSequence());
        $this->assertEquals(2, $sut->getLastEventSequence());

        $this->assertEquals('ezQ/dMl1V+ryBlIQPVfG5y8mF3X9Pg4e95SyJZg2dXw=', $sut->getSignature());
        $this->assertTrue($sut->validate('ezQ/dMl1V+ryBlIQPVfG5y8mF3X9Pg4e95SyJZg2dXw='));

        $events = $sut->getEvents();

        $this->assertCount(2, $events);

        tap($events->first(), function($event) use($invoiceEvent){
            $this->assertEquals(new WebhookEvent(app(OauthCredentialManager::class), app(AccountingApi::class), $invoiceEvent), $event);
        });

        tap($events->last(), function($event) use($contactEvent){
            $this->assertEquals(new WebhookEvent(app(OauthCredentialManager::class), app(AccountingApi::class), $contactEvent), $event);
        });
    }

    public function test_you_can_resolve_webhooks_from_container()
    {
        XeroOAuth::fake();

        config()->set('xero.oauth.webhook_signing_key', 'signing-key');

        Route::post('/some-test-route', function(Webhook $webhook) {
            $this->assertInstanceOf(Webhook::class, $webhook);
            $this->assertCount(2, $webhook->getEvents());
            $this->assertTrue($webhook->validate('ezQ/dMl1V+ryBlIQPVfG5y8mF3X9Pg4e95SyJZg2dXw='));
        });

        $this->postJson('/some-test-route', [
            'events' => [
                [
                    'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Invoices/123',
                    'resourceId' => '123',
                    'eventDateUtc' => '2021-01-01T00:00:00.000Z',
                    'eventType' => 'CREATE',
                    'eventCategory' => 'INVOICE',
                    'tenantId' => '456',
                    'tenantType' => 'ORGANISATION',
                ],
                [
                    'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Contacts/123',
                    'resourceId' => '123',
                    'eventDateUtc' => '2021-01-01T00:00:00.000Z',
                    'eventType' => 'CREATE',
                    'eventCategory' => 'CONTACT',
                    'tenantId' => '456',
                    'tenantType' => 'ORGANISATION',
                ],
            ],
            'firstEventSequence' => 1,
            'lastEventSequence' => 2,
        ])->assertOk();
    }
}