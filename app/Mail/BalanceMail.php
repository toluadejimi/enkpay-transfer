<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BalanceMail extends Mailable
{


    use Queueable, SerializesModels;

     /**
     * The order instance.
     *
     * @var Details
     */
    protected $details;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Details $details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notify.lowbalalce')
                    ->subject('Low Balance Noftification')
                    ->with([
                        'balance' => $this->details->main_wallet,
                        'first_name' => $this->details->first_name,
                    ]);
    }




    




}
