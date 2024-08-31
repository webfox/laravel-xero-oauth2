## Using Webhooks

On your application in the Xero developer portal create a webhook to get your webhook key.

You can then add this to your `.env` file as

```
XERO_WEBHOOK_KEY=...
```

You can then set up a controller to handle your webhook and inject `\Webfox\Xero\Webhook` e.g.

```php
<?php

namespace App\Http\Controllers;

use Webfox\Xero\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use XeroApi\XeroPHP\Models\Accounting\Contact;
use XeroApi\XeroPHP\Models\Accounting\Invoice;

class XeroWebhookController extends Controller
{
    public function __invoke(Request $request, Webhook $webhook)
    {

        // The following lines are required for Xero's 'itent to receive' validation
        if (!$webhook->validate($request->header('x-xero-signature'))) {
            // We can't use abort here, since Xero expects no response body
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        // A single webhook trigger can contain multiple events, so we must loop over them
        foreach ($webhook->getEvents() as $event) {
            if ($event->getEventType() === 'CREATE' && $event->getEventCategory() === 'INVOICE') {
                $this->invoiceCreated($request, $event->getResource());
            } elseif ($event->getEventType() === 'CREATE' && $event->getEventCategory() === 'CONTACT') {
                $this->contactCreated($request, $event->getResource());
            } elseif ($event->getEventType() === 'UPDATE' && $event->getEventCategory() === 'INVOICE') {
                $this->invoiceUpdated($request, $event->getResource());
            } elseif ($event->getEventType() === 'UPDATE' && $event->getEventCategory() === 'CONTACT') {
                $this->contactUpdated($request, $event->getResource());
            }
        }

        return response('', Response::HTTP_OK);
    }

    protected function invoiceCreated(Request $request, Invoice $invoice)
    {
    }

    protected function contactCreated(Request $request, Contact $contact)
    {
    }

    protected function invoiceUpdated(Request $request, Invoice $invoice)
    {
    }

    protected function contactUpdated(Request $request, Contact $contact)
    {
    }

}
```