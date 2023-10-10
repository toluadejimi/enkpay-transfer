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


class InsuranceController extends Controller
{

    public $success = true;
    public $failed = false;

    public function third_party_motor(request $request)
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=ui-insure');
            $response = $request->getBody();
            $result = json_decode($response);

            $data = $result->content->variations ?? null;

            $status = $result->response_description ?? null;


            $get_message = $result->content->errors ?? null;

            $message = "Error from Eduction - $get_message";

            if($status == null){

                return response()->json([
                    'status' => $this->success,
                    'message' => "Service not available, Please try again later",
                ], 500);

            }


            if ($status == 000) {

                return response()->json([
                    'status' => $this->success,
                    'data' => $data,
                    'account' => $account,
                ], 200);

            }

            send_error($message);

            return response()->json([
                'status' => $this->failed,
                'message' => "Service not available, Please try again later",
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }


    public function health_insurance(request $request)
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=health-insurance-rhl');
            $response = $request->getBody();
            $result = json_decode($response);

            $data = $result->content->variations ?? null;

            $status = $result->response_description ?? null;

            $get_message = $result->content->errors ?? null;

            $message = "Error from Eduction - $get_message";

            if($status == null){

                return response()->json([
                    'status' => $this->success,
                    'message' => "Service not available, Please try again later",
                ], 500);

            }


            if ($status == 000) {

                return response()->json([
                    'status' => $this->success,
                    'data' => $data,
                    'account' => $account,
                ], 200);

            }

            send_error($message);

            return response()->json([
                'status' => $this->failed,
                'message' => "Service not available, Please try again later",
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }


    public function personal_accident_insurance(request $request)
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=personal-accident-insurance');
            $response = $request->getBody();
            $result = json_decode($response);

            $data = $result->content->variations ?? null;

            $status = $result->response_description ?? null;

            $get_message = $result->content->errors ?? null;

            $message = "Error from Eduction - $get_message";

            if($status == null){

                return response()->json([
                    'status' => $this->success,
                    'message' => "Service not available, Please try again later",
                ], 500);

            }


            if ($status == 000) {

                return response()->json([
                    'status' => $this->success,
                    'data' => $data,
                    'account' => $account,
                ], 200);

            }

            send_error($message);

            return response()->json([
                'status' => $this->failed,
                'message' => "Service not available, Please try again later",
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }


    public function home_cover_insurance(request $request)
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/service-variations?serviceID=home-cover-insurance');
            $response = $request->getBody();
            $result = json_decode($response);

            $data = $result->content->variations ?? null;

            $status = $result->response_description ?? null;

            $get_message = $result->content->errors ?? null;

            $message = "Error from Eduction - $get_message";

            if($status == null){

                return response()->json([
                    'status' => $this->success,
                    'message' => "Service not available, Please try again later",
                ], 500);

            }


            if ($status == 000) {

                return response()->json([
                    'status' => $this->success,
                    'data' => $data,
                    'account' => $account,
                ], 200);

            }

            send_error($message);

            return response()->json([
                'status' => $this->failed,
                'message' => "Service not available, Please try again later",
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }



    public function extra_home_cover_insurance(request $request)
    {

        try {

            $account = select_account();

            $client = new \GuzzleHttp\Client();
            $request = $client->get('https://vtpass.com/api/extra-fields?serviceID=home-cover-insurance');
            $response = $request->getBody();
            $result = json_decode($response);

            $data = $result->content->variations ?? null;

            $status = $result->response_description ?? null;

            $get_message = $result->content->errors ?? null;

            $message = "Error from Eduction - $get_message";

            if($status == null){

                return response()->json([
                    'status' => $this->success,
                    'message' => "Service not available, Please try again later",
                ], 500);

            }


            if ($status == 000) {

                return response()->json([
                    'status' => $this->success,
                    'data' => $data,
                    'account' => $account,
                ], 200);

            }

            send_error($message);

            return response()->json([
                'status' => $this->failed,
                'message' => "Service not available, Please try again later",
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }









    public function buy_waec(request $request)
    {

        try {

            $auth = env('VTAUTH');

            $request_id = date('YmdHis') . Str::random(4);

            $serviceid = $request->service_id;

            $biller_code = preg_replace('/[^0-9]/', '', $request->phone);

            $phone = preg_replace('/[^0-9]/', '', $request->phone);

            $variation_code = $request->variation_code;

            $amount = $request->amount;

            $wallet = $request->wallet;

            $quantity = $request->quantity;

            $pin = $request->pin;

            $education_charges = Charge::where('id', 7)
            ->first()->amount;


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
                    'quantity' => $quantity,
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

            $status = $var->response_description ?? null;

            $get_message = $var->response_description ?? null;

            $get_message2 = $var->content->errors ?? null;


            $message = "Error Mesage from VAS BUY WAEC EDUCATION - $get_message2";


            $p_code = $var->purchased_code;

            if ($status == 'TRANSACTION SUCCESSFUL') {

                $new_amount = $amount + $education_charges;
                $debit = $user_wallet_banlance - $new_amount;

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
                $transaction->ref_trans_id = $var->$content->$transactionId;
                $transaction->transaction_type = "VasEducation";
                $trasnaction->title = "Education VAS";
                $transaction->e_charges = $education_charges;
                $transaction->type = "vas";
                $transaction->balance = $balance;
                $transaction->debit = $amount;
                $transaction->status = 1;
                $transaction->e_ref = $var->requestId;


                $transaction->note = "Data Bundle Purchase to $p_code";
                $transaction->save();

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

            ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function quary(request $request){

    }

}
