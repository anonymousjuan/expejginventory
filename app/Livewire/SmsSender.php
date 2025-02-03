<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Carbon\Carbon;

class SmsSender extends Component
{
    private $apiUrl;
    private $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.philsms.url');
        $this->apiToken = config('services.philsms.token');
        $this->sender_id = config('services.philsms.sender_id');
    }

    public function send($name, $balance, $contact)
    {
        $currentDateTime = Carbon::now()->format('d M H:i');
        $formattedBalance = @money($balance);

        $message = <<<EOT
{$currentDateTime}: Hi {$name}! This is a friendly reminder from Jenny Grace Furniture Homestore.

Your outstanding balance of {$formattedBalance} is due today. Please settle your balance at your earliest convenience. Please disregard if already paid.
EOT;

        return $this->sendSms($contact, $message);
    }

    public function sendBirthday($name, $contact)
    {
        $currentDateTime = Carbon::now()->format('d M H:i');

        $message = <<<EOT
{$currentDateTime}: Hi {$name}!

We would like to wish you a Happy Birthday! May your day be filled with joy and happiness.

Greetings from Jenny Grace Furniture Homestore.
EOT;

        return $this->sendSms($contact, $message);
    }

    private function sendSms($recipient, $message)
    {
        $data = [
            'sender_id' => $this->sender_id,
            'recipient' => $recipient,
            'message' => $message,
        ];

        $response = Http::withToken($this->apiToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl, $data);

        if ($response->successful()) {
            return true;
        }

        logger()->error('SMS sending failed', [
            'recipient' => $recipient,
            'response' => $response->body()
        ]);

        return false;
    }

    public function render()
    {
        return view('livewire.sms-sender');
    }
}
