<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class WebhookController extends Controller
{
    public function awsSes(Request $request)
    {
        // Get raw body (important for AWS SNS/SES)
        $payload = $request->getContent();

        // Decode JSON
        $data = json_decode($payload, true);

        // Log everything
        Log::info('AWS SES Webhook:', [
            'headers' => $request->headers->all(),
            'body' => $data,
            'raw' => $payload,
        ]);

        // Handle subscription confirmation
        if (isset($data['Type']) && $data['Type'] === 'SubscriptionConfirmation') {
            file_get_contents($data['SubscribeURL']); // confirm subscription
            Log::info('AWS SES Subscription confirmed.');
        }

        return response()->json(['status' => 'ok']);
    }
}
