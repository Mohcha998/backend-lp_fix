<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $sid;
    protected $authToken;
    protected $twilioPhoneNumber;

    public function __construct()
    {
        $this->sid = env('TWILIO_SID');
        $this->authToken = env('TWILIO_AUTH_TOKEN');
        $this->twilioPhoneNumber = env('TWILIO_PHONE_NUMBER');
    }

    public function sendWhatsAppBlast(array $recipients, string $message)
    {
        $client = new Client($this->sid, $this->authToken);
        $results = [];

        foreach ($recipients as $recipient) {
            try {
                $response = $client->messages->create(
                    'whatsapp:' . $recipient,
                    [
                        'from' => $this->twilioPhoneNumber,
                        'body' => $message,
                    ]
                );
                $results[] = [
                    'recipient' => $recipient,
                    'status' => 'success',
                    'message_sid' => $response->sid,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'recipient' => $recipient,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
