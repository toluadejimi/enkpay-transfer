<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailController extends Controller
{
    Public function sendEmail(Request $request)

{

/* This method will call SendEmailJob Job*/

dispatch(new SendEmailJob($request));

}
}
