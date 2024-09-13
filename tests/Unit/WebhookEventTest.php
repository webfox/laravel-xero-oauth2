<?php

namespace Tests\Webfox\Xero\Unit;

use DateTime;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery\MockInterface;
use Tests\Webfox\Xero\TestCase;
use Tests\Webfox\Xero\TestSupport\XeroOAuth;
use Webfox\Xero\Clients\AccountAPIClient;
use Webfox\Xero\OauthCredentialManager;
use Webfox\Xero\WebhookEvent;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;

class WebhookEventTest extends TestCase
{
    public function test_it_can_be_malformed()
    {
        XeroOAuth::fake();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The event payload was malformed; missing required field');

        new WebhookEvent(app(OauthCredentialManager::class), app(AccountingApi::class), []);
    }

    public function test_you_can_get_a_webhook_for_invoice()
    {
        XeroOAuth::fake();

        $sut = new WebhookEvent(app(OauthCredentialManager::class), app(AccountingApi::class), [
            'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Invoices/123',
            'resourceId' => '123',
            'eventDateUtc' => '2021-01-01T00:00:00.000Z',
            'eventType' => 'CREATE',
            'eventCategory' => 'INVOICE',
            'tenantId' => 'webhook-tenant-id',
            'tenantType' => 'ORGANISATION',
        ]);

        $this->assertEquals('https://api.xero.com/api.xro/2.0/Invoices/123', $sut->getResourceUrl());
        $this->assertEquals('123', $sut->getResourceId());
        $this->assertInstanceOf(DateTime::class, $sut->getEventDate());
        $this->assertEquals('2021-01-01T00:00:00.000Z', $sut->getEventDateUtc());
        $this->assertEquals('CREATE', $sut->getEventType());
        $this->assertEquals('INVOICE', $sut->getEventCategory());
        $this->assertEquals('webhook-tenant-id', $sut->getTenantId());
        $this->assertEquals('ORGANISATION', $sut->getTenantType());
        $this->assertEquals(Invoice::class, $sut->getEventClass());
    }

    public function test_you_can_get_a_webhook_for_contact()
    {
        XeroOAuth::fake();

        $sut = new WebhookEvent(app(OauthCredentialManager::class), app(AccountingApi::class), [
            'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Contacts/123',
            'resourceId' => '123',
            'eventDateUtc' => '2021-01-01T00:00:00.000Z',
            'eventType' => 'CREATE',
            'eventCategory' => 'CONTACT',
            'tenantId' => 'webhook-tenant-id',
            'tenantType' => 'ORGANISATION',
        ]);

        $this->assertEquals('https://api.xero.com/api.xro/2.0/Contacts/123', $sut->getResourceUrl());
        $this->assertEquals('123', $sut->getResourceId());
        $this->assertInstanceOf(DateTime::class, $sut->getEventDate());
        $this->assertEquals('2021-01-01T00:00:00.000Z', $sut->getEventDateUtc());
        $this->assertEquals('CREATE', $sut->getEventType());
        $this->assertEquals('CONTACT', $sut->getEventCategory());
        $this->assertEquals('webhook-tenant-id', $sut->getTenantId());
        $this->assertEquals('ORGANISATION', $sut->getTenantType());
        $this->assertEquals(Contact::class, $sut->getEventClass());
    }

    public function test_you_can_get_resource_for_invoice()
    {
        XeroOAuth::fake();

        $this->mock(OauthCredentialManager::class, function (MockInterface $mock) {
            $mock->shouldReceive('exists')->once()->andReturnTrue();
            $mock->shouldReceive('getTenantId')->never()->andReturn('oauth-tenant-id');
            $mock->shouldReceive('getAccessToken')->once()->andReturn('oauth-access-token');
        });

        AccountAPIClient::getHttpClient()
            ->shouldReceive('send')
            ->withArgs(function (Request $request) {
                $this->assertEquals('GET', $request->getMethod());
                $this->assertEquals('https://api.xero.com/api.xro/2.0/Invoices/123', (string) $request->getUri());
                $this->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $this->assertEquals('webhook-tenant-id', $request->getHeader('xero-tenant-id')[0]);
                $this->assertEquals('Bearer oauth-access-token', $request->getHeader('Authorization')[0]);

                return true;
            })
            ->once()
            ->andReturn(new Response(200, [], file_get_contents(__DIR__.'/../TestSupport/Stubs/invoices-request.json')));

        $webhookEvent = new WebhookEvent(app(OauthCredentialManager::class), app(AccountingApi::class), [
            'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Invoices/123',
            'resourceId' => '123',
            'eventDateUtc' => '2021-01-01T00:00:00.000Z',
            'eventType' => 'CREATE',
            'eventCategory' => 'INVOICE',
            'tenantId' => 'webhook-tenant-id',
            'tenantType' => 'ORGANISATION',
        ]);

        $sut = $webhookEvent->getResource();

        $this->assertInstanceOf(Invoice::class, $sut);
        $this->assertEquals('243216c5-369e-4056-ac67-05388f86dc81', $sut->getInvoiceId());
    }

    public function test_you_can_get_resource_for_contact()
    {
        XeroOAuth::fake();

        $this->mock(OauthCredentialManager::class, function (MockInterface $mock) {
            $mock->shouldReceive('exists')->once()->andReturnTrue();
            $mock->shouldReceive('getTenantId')->never()->andReturn('oauth-tenant-id');
            $mock->shouldReceive('getAccessToken')->once()->andReturn('oauth-access-token');
        });

        AccountAPIClient::getHttpClient()
            ->shouldReceive('send')
            ->withArgs(function (Request $request) {
                $this->assertEquals('GET', $request->getMethod());
                $this->assertEquals('https://api.xero.com/api.xro/2.0/Contacts/123', (string) $request->getUri());
                $this->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
                $this->assertEquals('webhook-tenant-id', $request->getHeader('xero-tenant-id')[0]);
                $this->assertEquals('Bearer oauth-access-token', $request->getHeader('Authorization')[0]);

                return true;
            })
            ->once()
            ->andReturn(new Response(200, [], file_get_contents(__DIR__.'/../TestSupport/Stubs/contacts-request.json')));

        $webhookEvent = new WebhookEvent(app(OauthCredentialManager::class), app(AccountingApi::class), [
            'resourceUrl' => 'https://api.xero.com/api.xro/2.0/Contacts/123',
            'resourceId' => '123',
            'eventDateUtc' => '2021-01-01T00:00:00.000Z',
            'eventType' => 'CREATE',
            'eventCategory' => 'CONTACT',
            'tenantId' => 'webhook-tenant-id',
            'tenantType' => 'ORGANISATION',
        ]);

        $sut = $webhookEvent->getResource();

        $this->assertInstanceOf(Contact::class, $sut);
        $this->assertEquals('bd2270c3-8706-4c11-9cfb-000b551c3f51', $sut->getContactId());
    }
}
