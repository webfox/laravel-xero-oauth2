<?php

namespace Webfox\Xero;

use Illuminate\Support\Collection;
use XeroAPI\XeroPHP\Api\AccountingApi;

class Webhook
{

    protected $signingKey;

    protected $payload;

    protected $properties;

    protected $events;

    protected $accountingApi;

    protected $credentialManager;

    public function __construct(OauthCredentialManager $credentialManager, AccountingApi $accountingApi, string $payload, string $signingKey)
    {
        $this->accountingApi     = $accountingApi;
        $this->credentialManager = $credentialManager;
        $this->payload           = $payload;
        $this->signingKey        = $signingKey;
        $this->properties        = new Collection(json_decode($payload, true));

        // bail if json_decode fails
        if ($this->properties->isEmpty()) {
            throw new \Exception('The webhook payload could not be decoded: ' . json_last_error_msg());
        }

        // bail if we don't have all the fields we are expecting
        if (!$this->properties->has(['events', 'firstEventSequence', 'lastEventSequence'])) {
            throw new \Exception('The webhook payload was malformed');
        }

        $this->events = new Collection(array_map(function($event) {
            return new WebhookEvent($this->credentialManager, $this->accountingApi, $event);
        }, $this->properties->get('events')));
    }

    public function getSignature()
    {
        return base64_encode(hash_hmac('sha256', $this->payload, $this->signingKey, true));
    }

    public function validate($signature)
    {
        return hash_equals($this->getSignature(), $signature);
    }

    /**
     * @return int
     */
    public function getFirstEventSequence()
    {
        return $this->properties->get('firstEventSequence');
    }

    /**
     * @return int
     */
    public function getLastEventSequence()
    {
        return $this->properties->get('lastEventSequence');
    }

    /**
     * @return \Webfox\Xero\WebhookEvent[]|\Illuminate\Support\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }
}
