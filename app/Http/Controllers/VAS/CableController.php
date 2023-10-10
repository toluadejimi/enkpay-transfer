<?php

namespace App\Http\Controllers\VAS;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class CableController extends Controller
{

    public $success = true;
    public $failed = false;

    public function get_cable_plan()
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=dstv');
            $response = $request->getBody();
            $result = json_decode($response);
            $dstv = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=gotv');
            $response = $request->getBody();
            $result = json_decode($response);
            $gotv = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=startimes');
            $response = $request->getBody();
            $result = json_decode($response);
            $startimes = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=showmax');
            $response = $request->getBody();
            $result = json_decode($response);
            $showmax = $result->content->variations;



            return response()->json([
                'status' => $this->success,
                'dstv' => $dstv,
                'gotv' => $gotv,
                'startimes' => $startimes,
                'showmax' => $showmax,
                'account' => $account,
            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }


    public function buy_cable(Request $request)
    {

        try {





            if (Auth::user()->status == 7) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => "You can not make any transaction at the moment, \n\n Please contact  support",

                ], 500);
            }




            if (Auth::user()->status != 2) {

                $message = Auth::user()->first_name. " ".Auth::user()->last_name. " | Unverified Account trying to buy cable";
                send_notification($message);

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please verify your account to enjoy enkpay full service',
                ], 500);
            }

            $referenceCode = trx();

            $auth = env('VTAUTH');

            $request_id = date('YmdHis') . Str::random(4);

            $serviceid = $request->service_id;

            $biller_code = preg_replace('/[^0-9]/', '', $request->biller_code);

            $phone = preg_replace('/[^0-9]/', '', $request->phone);

            $variation_code = $request->variation_code;

            $amount = round($request->amount);

            $wallet = $request->wallet;

            $pin = $request->pin;

            if ($wallet == 'main_account') {
                $user_wallet_banlance = main_account();
            } else {
                $user_wallet_banlance = bonus_account();
            }

            $user_pin = Auth()->user()->pin;

            if (Hash::check($pin, $user_pin) == false) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Invalid Pin, Please try again',

                ], 500);
            }



            if (Auth::user()->b_number == 6) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'You dont have the permission to make transfer',

                ], 500);
            }






            if ($amount > $user_wallet_banlance) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, Fund your wallet',

                ], 500);

            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://vtpass.com/api/pay',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'request_id' => $request_id,
                    'variation_code' => $variation_code,
                    'serviceID' => $serviceid,
                    'amount' => $amount,
                    'biller_code' => $biller_code,
                    'phone' => $phone,
                ),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic $auth=",
                    'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            $trx_id = $var->requestId ?? null;

            $get_message = $var->response_description ?? null;

            $message = "Error Mesage from VAS DATA BUNDLE - $get_message";

            $status = $var->response_description ?? null;

            $charge = Charge::where('title', 'eletricity_charges')
            ->first()->amount;

            $debited_amount = $amount + $charge;

            if ($status == 'TRANSACTION SUCCESSFUL') {



                $debit = $user_wallet_banlance - $debited_amount ;

                if ($wallet == 'main_account') {

                    $update = User::where('id', Auth::id())
                        ->update([
                            'main_wallet' => $debit,
                        ]);

                } else {
                    $update = User::where('id', Auth::id())
                        ->update([
                            'bonus_wallet' => $debit,
                        ]);
                }



                if ($wallet == 'main_account') {

                    $balance = $user_wallet_banlance - $debit;

                } else {

                    $balance = $user_wallet_banlance - $debit;

                }

                $transaction = new Transaction();
                $transaction->user_id = Auth::id();
                $transaction->ref_trans_id = $referenceCode;
                $transaction->transaction_type = "VasCable";
                //$trasnaction->title = "Cable VAS";
                $transaction->type = "vas";
                $transaction->balance = $balance;
                $transaction->debit = $amount;
                $transaction->amount = $amount;
                $transaction->main_type = "vtpass";
                $transaction->status = 1;
                $transaction->note = "Cable Subscription | $variation_code | $biller_code | $amount";
                $transaction->save();


                $title = "Cable VAS";

                $update = Transaction::where('ref_trans_id',$referenceCode)
                ->update([

                    'title' => $title,
                    'main_type' => "enkpay_vas"

                ]);



                if (!empty(user_email())) {
                    //send email
                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Cable Purchase",
                        'toreceiver' => user_email(),
                        'first_name' => first_name(),
                        'amount' => $amount,
                        'phone' => $phone,
                        'biller_code' => $biller_code,

                    );

                    Mail::send('emails.vas.cable', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });

                }

                return response()->json([

                    'status' => $this->success,
                    'message' => 'Cable Purchase Successfull',

                ], 200);

            }

            send_error($message);

            return response()->json([

                'status' => $this->failed,
                'message' => 'Service unavilable please try again later',

            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }








}
