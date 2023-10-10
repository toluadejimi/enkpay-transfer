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

class DataController extends Controller
{

    public $success = true;
    public $failed = false;

    public function get_data()
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=mtn-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_mtn_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=glo-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_glo_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=airtel-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_airtel_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=etisalat-data');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_9mobile_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=smile-direct');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_smile_network = $result->content->variations;

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=spectranet');
            $response = $request->getBody();
            $result = json_decode($response);
            $get_spectranet_network = $result->content->variations;

            return response()->json([
                'status' => $this->success,
                'mtn_data' => $get_mtn_network,
                'glo_data' => $get_glo_network,
                'airtel_data' => $get_airtel_network,
                '9mobile_data' => $get_9mobile_network,
                'smile_data' => $get_smile_network,
                'spectranet_data' => $get_spectranet_network,
                'account' => $account,
            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function buy_data(Request $request)
    {





        if (Auth::user()->status == 7) {


            return response()->json([

                'status' => $this->failed,
                'message' => "You can not make any transaction at the moment, \n\n Please contact  support",

            ], 500);
        }


        if (Auth::user()->status != 2) {

            $message = Auth::user()->first_name. " ".Auth::user()->last_name. " | Unverified Account trying to buy data";
            send_notification($message);

            return response()->json([
                'status' => $this->failed,
                'message' => 'Please verify your account to enjoy enkpay full service',
            ], 500);
        }



         return response()->json([

                    'status' => $this->failed,
                    'message' => 'Service unavailable at the moment!',

                ], 500);

        try {

            $referenceCode = "ENK-" . random_int(1000000, 999999999);

            $auth = env('VTAUTH');

            $request_id = date('YmdHis') . Str::random(4);

            $serviceid = $request->service_id;

            $biller_code = preg_replace('/[^0-9]/', '', $request->phone);

            $phone = preg_replace('/[^0-9]/', '', $request->phone);

            $variation_code = $request->variation_code;

            $amount = round($request->variation_amount);

            $wallet = $request->wallet;

            $pin = $request->pin;


            $user_blance = Auth::user()->main_wallet;

            if ($amount > $user_blance) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Insufficient Funds, Fund your main wallet',
                ], 500);

            }



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

                if (!empty(user_email())) {

                    $data = array(
                        'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                        'subject' => "Low Balance",
                        'toreceiver' => user_email(),
                        'first_name' => first_name(),
                        'amount' => $amount,
                        'phone' => $phone,
                        'balance' => $user_wallet_banlance,

                    );

                    Mail::send('emails.notify.lowbalalce', ["data1" => $data], function ($message) use ($data) {
                        $message->from($data['fromsender']);
                        $message->to($data['toreceiver']);
                        $message->subject($data['subject']);
                    });
                }

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

            if ($status == 'TRANSACTION SUCCESSFUL') {

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

                $transaction = new Transaction();
                $transaction->user_id = Auth::id();
                $transaction->ref_trans_id = $referenceCode;
                $transaction->transaction_type = "VasData";
                $transaction->type = "vas";
                $transaction->balance = $balance;
                $transaction->debit = $amount;
                $transaction->amount = $amount;
                $transaction->main_type = "vtpass";
                $transaction->status = 1;
                $transaction->note = "Data Bundle| $variation_code | $phone";
                $transaction->save();

                $title = "Data VAS";

                $update = Transaction::where('ref_trans_id', $referenceCode)
                    ->update([

                        'title' => $title,
                        'main_type' => "enkpay_vas",

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

                return response()->json([

                    'status' => $this->success,
                    'message' => 'Data Bundle Purchase Successfull',

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
