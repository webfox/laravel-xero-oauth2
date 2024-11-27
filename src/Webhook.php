<?php

namespace Webfox\Xero;

use Illuminate\Support\Collection;
use Webfox\Xero\Exceptions\XeroFailedToDecodeWebhookEvent;
use Webfox\Xero\Exceptions\XeroMalformedWebhook;
use Webfox\Xero\Exceptions\XeroMalformedWebhookEvent;
use XeroAPI\XeroPHP\Api\AccountingApi;

class Webhook
{
    protected Collection $properties;

    protected Collection $events;

    /**
     * @throws XeroMalformedWebhook
     * @throws XeroMalformedWebhookEvent
     * @throws XeroFailedToDecodeWebhookEvent
     */
    public function __construct(protected OauthCredentialManager $credentialManager, protected AccountingApi $accountingApi, protected string $payload, protected string $signingKey)
    {
        $this->properties = new Collection(json_decode($payload, true));

        // bail if json_decode fails
        if ($this->properties->isEmpty()) {
            throw XeroFailedToDecodeWebhookEvent::make();
        }

        // bail if we don't have all the fields we are expecting
        if (! $this->properties->has(['events', 'firstEventSequence', 'lastEventSequence'])) {
            throw XeroMalformedWebhook::make();
        }

        $this->events = new Collection(array_map(function ($event) {
            return new WebhookEvent($this->credentialManager, $this->accountingApi, $event);
        }, $this->properties->get('events')));
    }

    public function getSignature(): string
    {
        return base64_encode(hash_hmac('sha256', $this->payload, $this->signingKey, true));
    }

    public function validate(string $signature): bool
    {
        return hash_equals($this->getSignature(), $signature);
    }

    public function getFirstEventSequence(): int
    {
        return $this->properties->get('firstEventSequence');
    }

    public function getLastEventSequence(): int
    {
        return $this->properties->get('lastEventSequence');
    }

    /**
     * @return \Illuminate\Support\Collection<\Webfox\Xero\WebhookEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }
}
