<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendVerificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;


    // public $tries = -1;
    // public $backoff = 1;


    public function __construct($details)
    {
        $this->data = $details;
    }


    public function handle(): void
    {

        $data = array(
            'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
            'subject' => "Electricity Receipt",
            'toreceiver' => $this->data['email'],
            // 'recepit' => $recepit,
            // 'date' => $date,
            // 'f_name' => $f_name,
            // 'l_name' => $l_name,
            // 'eletric_address' => $eletric_address,
            // 'phone' => $phone,
            // 'token' => $token,
            // 'new_amount' => $new_amount,
        );

        Mail::send('emails.transaction.eletricty-recepit', ["data1" => $data], function ($message) use ($data) {
            $message->from($data['fromsender']);
            $message->to($data['toreceiver']);
            $message->subject($data['subject']);
        });
    }


    // public function retryUntil()
    // {
    //     return now()->addSeconds(30);
    // }
}
