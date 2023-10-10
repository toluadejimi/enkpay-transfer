<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Log;
use Mail;
use App\Mail\ExceptionOccured;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array>

     */
    protected $dontReport = [

    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array

     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $this->sendEmail($e);
        });
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function sendEmail(Throwable $exception)
    {
       try {

            $message = $content['message'] = $exception->getMessage();
            $file = $content['file'] = $exception->getFile();
            $line = $content['line'] = $exception->getLine();
            $trace = $content['trace'] = $exception->getTrace();

            $url = $content['url'] = request()->url();
            $body = $content['body'] = request()->all();
            $ip = $content['ip'] = request()->ip();



            $message2 = "Error Message on ENKPAY APP";
            $message = $message2. "\n\nMessage========>" . $message . "\n\nLine========>" . $line . "\n\nFile========>" . $file . "\n\nURL========>" . $url . "\n\nIP========> " . $ip;

            //$message = "Error Message on ENKPAY APP";


            send_notification($message);







           // Mail::to('enkwavedevops@gmail.com')->send(new ExceptionOccured($content));

        } catch (Throwable $exception) {
            Log::error($exception);
        }
    }
}
