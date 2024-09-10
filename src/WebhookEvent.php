<?php

namespace Webfox\Xero;

use Illuminate\Support\Collection;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;

class WebhookEvent
{
    protected Collection $properties;

    public function __construct(protected OauthCredentialManager $credentialManager, protected AccountingApi $accountingApi, protected array $event)
    {
        $this->properties = new Collection($event);

        if (! $this->properties->has(['resourceUrl', 'resourceId', 'eventDateUtc', 'eventType', 'eventCategory', 'tenantId', 'tenantType'])) {
            throw new \Exception('The event payload was malformed; missing required field');
        }
    }

    public function getResourceUrl()
    {
        return $this->properties->get('resourceUrl');
    }

    public function getResourceId()
    {
        return $this->properties->get('resourceId');
    }

    public function getEventDateUtc()
    {
        return $this->properties->get('eventDateUtc');
    }

    public function getEventDate()
    {
        return new \DateTime($this->getEventDateUtc());
    }

    public function getEventType()
    {
        return $this->properties->get('eventType');
    }

    public function getEventCategory()
    {
        return $this->properties->get('eventCategory');
    }

    public function getEventClass(): ?string
    {
        if ($this->getEventCategory() === 'INVOICE') {
            return Invoice::class;
        }

        if ($this->getEventCategory() === 'CONTACT') {
            return Contact::class;
        }

        return null;
    }

    public function getTenantId()
    {
        return $this->properties->get('tenantId');
    }

    public function getTenantType()
    {
        return $this->properties->get('tenantType');
    }

    public function getResource()
    {
        if ($this->getEventCategory() === 'INVOICE') {
            return $this->accountingApi
                ->getInvoice($this->credentialManager->getTenantId(), $this->getResourceId())
                ->getInvoices()[0];
        }
        if ($this->getEventCategory() === 'CONTACT') {
            return $this->accountingApi
                ->getContact($this->credentialManager->getTenantId(), $this->getResourceId())
                ->getContacts()[0];
        }
    }
}
