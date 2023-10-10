<?php

namespace App\Http\Controllers\VAS;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mail;

class AirtimeController extends Controller
{

    public $success = true;
    public $failed = false;

    public function buy_airtime(Request $request)
    {

        try {


            if (Auth::user()->status == 7) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => "You can not make any transaction at the moment, \n\n Please contact  support",

                ], 500);
            }


            if (Auth::user()->status != 2) {

                $message = Auth::user()->first_name. " ".Auth::user()->last_name. " | Unverified Account trying to buy airtime";
                send_notification($message);

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please verify your account to enjoy enkpay full service',
                ], 500);
            }

            $auth = env('VTAUTH');

            $request_id = date('YmdHis') . Str::random(4);

            $referenceCode = "ENK-" . random_int(1000000, 999999999);

            $serviceid = $request->service_id;

            $amount = $request->amount;

            $phone = $request->phone;

            $pin = $request->pin;

            $wallet = $request->wallet;

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

            if ($amount < 100) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Amount must not be less than NGN 100',

                ], 500);
            }

            $user_blance = Auth::user()->main_wallet;

            if ($amount > $user_blance) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, Fund your main wallet',
                ], 500);

            }


            if ($amount > $user_wallet_banlance) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, Fund your wallet',

                ], 500);

            }

            $user_blance = Auth::user()->main_wallet;

            if ($amount > $user_blance) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, Fund your main wallet',
                ], 500);

            }


            if ($amount > $user_blance) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, Fund your main wallet',
                ], 500);

            }



            $debit = $user_wallet_banlance - $amount;

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

                $balance = $user_wallet_banlance - $amount;

            } else {

                $balance = $user_wallet_banlance - $amount;

            }


            $name = Auth::user()->first_name." ".Auth::user()->last_name;
            $message = $name. " Wants to buy recharge card | NGN". $amount. " | " .$phone;
            send_error($message);




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
                    'serviceID' => $serviceid,
                    'amount' => $amount,
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

            $status = $var->response_description ?? null;

            $message = "Error Mesage from VAS AIRTIME - $get_message";

            if ($status == 'TRANSACTION SUCCESSFUL') {


                $title = "Airtime VAS";

                $transaction = new Transaction();
                $transaction->user_id = Auth::id();
                $transaction->ref_trans_id = $referenceCode;
                $transaction->transaction_type = "VasAirtime";
                $transaction->type = "vas";
                $transaction->balance = $balance;
                $transaction->debit = $amount;
                $transaction->status = 1;
                $transaction->amount = $amount;
                $transaction->main_type = "vtpass";
                $transaction->main_type = "enkpay_vas";
                $transaction->note = "Airtime Purchase to $phone";
                //$trasnaction->title = $title;
                $transaction->save();

                $update = Transaction::where('ref_trans_id', $referenceCode)
                    ->update([

                        'title' => $title,

                    ]);

                if (!empty(user_email())) {
                    //send email
                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Airtime Purchase",
                        'toreceiver' => user_email(),
                        'first_name' => first_name(),
                        'amount' => $amount,
                        'phone' => $phone,

                    );

                    Mail::send('emails.vas.airtime', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });

                }



                $name = Auth::user()->first_name." ".Auth::user()->last_name;
                $message = $name. " | NGN". $amount. " | " .$phone;
                send_error($message);

                return response()->json([

                    'status' => $this->success,
                    'message' => 'Airtime Purchase Successful',

                ], 200);

            }

            send_error($message);

            User::where('id', Auth::id())->increment('main_wallet', $amount);

            return response()->json([

                'status' => $this->failed,
                'message' => "Service unavailable please try again later, \n\n NGN $amount has been refunded back to your wallet",

            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

}
