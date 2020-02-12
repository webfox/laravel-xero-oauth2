<?php


namespace Webfox\Xero;


use Illuminate\Support\Collection;
use XeroAPI\XeroPHP\Api\AccountingApi;

class WebhookEvent
{

    /** @var Collection  */
    protected $properties;

    /** @var AccountingApi  */
    protected $accountingApi;

    /** @var OauthCredentialManager  */
    protected $credentialManager;

    public function __construct(OauthCredentialManager $credentialManager, AccountingApi $accountingApi, $event)
    {
        $this->accountingApi = $accountingApi;
        $this->properties = new Collection($event);

        if (!$this->properties->has(['resourceUrl', 'resourceId', 'eventDateUtc', 'eventType', 'eventCategory', 'tenantId', 'tenantType',])) {
            throw new \Exception("The event payload was malformed; missing required field");
        }
        $this->credentialManager = $credentialManager;
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

    public function getEventClass()
    {
        if ($this->getEventCategory() === 'INVOICE') {
            return \XeroApi\XeroPHP\Models\Accounting\Invoice::class;
        }
        if ($this->getEventCategory() === 'CONTACT') {
            return \XeroApi\XeroPHP\Models\Accounting\Contact::class;
        }

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