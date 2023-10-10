<?php

namespace App\Http\Controllers\VAS;

use App\Http\Controllers\Controller;
use App\Jobs\SendVerificationEmail;
use App\Models\Charge;
use App\Models\Power;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PowerController extends Controller
{

    public $success = true;
    public $failed = false;

    public function get_eletric_company(request $request)
    {

        $account = select_account();

        $data1 = Power::select('name', 'code')->get();

        return response()->json([
            'status' => $this->success,
            'data' => $data1,
            'account' => $account,
        ], 200);
    }

    public function verify_account(request $request)
    {

        try {

            $auth = env('VTAUTH');

            $billersCode = $request->biller_code;
            $serviceID = $request->service_id;
            $type = $request->type;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://vtpass.com/api/merchant-verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'billersCode' => $billersCode,
                    'serviceID' => $serviceID,
                    'type' => $type,
                ),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic $auth=",
                    'Cookie: laravel_session=eyJpdiI6IlBkTGc5emRPMmhyQVwvb096YkVKV2RnPT0iLCJ2YWx1ZSI6IkNvSytPVTV5TW52K2tBRlp1R2pqaUpnRDk5YnFRbEhuTHhaNktFcnBhMFRHTlNzRWIrejJxT05kM1wvM1hEYktPT2JKT2dJWHQzdFVaYnZrRytwZ2NmQT09IiwibWFjIjoiZWM5ZjI3NzBmZTBmOTZmZDg3ZTUxMDBjODYxMzQ3OTkxN2M4YTAxNjNmMWY2YjAxZTIzNmNmNWNhOWExNzJmOCJ9',
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            //dd($var);

            $status = $var->content->WrongBillersCode ?? null;

            $status1 = $var->content->error ?? null;

            if ($status == true) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => $status1,
                ], 500);
            }

            if ($status1 !== null) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => $status1,
                ], 500);
            }

            if ($var->code == 000) {

                $customer_name = $var->content->Customer_Name ?? null;
                $eletric_address = $var->content->Address ?? null;
                $meter_no = $var->content->Meter_Number ?? $var->content->MeterNumber ?? null;

                $update = User::where('id', Auth::id())
                    ->update([
                        'meter_number' => $meter_no,
                        'eletric_company' => $serviceID,
                        'eletric_type' => $type,
                        'eletric_address' => $eletric_address,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'data' => $customer_name,
                ], 200);
            }
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function buy_power(request $request)
    {

        try {



            if (Auth::user()->status == 7) {


                return response()->json([

                    'status' => $this->failed,
                    'message' => "You can not make any transaction at the moment, \n\n Please contact  support",

                ], 500);
            }




            if (Auth::user()->status != 2) {

                $message = Auth::user()->first_name."  ".Auth::user()->last_name. " | Unverified Account trying to buy power";
                send_notification($message);

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please verify your account to enjoy enkpay full service',
                ], 500);
            }



        $auth = env('VTAUTH');

        $request_id = date('YmdHis') . Str::random(4);

        $referenceCode = trx();

        $serviceid = User::where('id', Auth::id())
            ->first()->eletric_company;

        $biller_code = User::where('id', Auth::id())
            ->first()->meter_number;

        $variation_code = User::where('id', Auth::id())
            ->first()->eletric_type;

        $phone = User::where('id', Auth::id())
            ->first()->phone;

        $amount = $request->amount;

        $variation_code = $request->variation_code;

        $wallet = $request->wallet;

        $pin = $request->pin;



        $user_blance = Auth::user()->main_wallet;

        if ($amount > $user_blance) {
            return response()->json([
                'status' => $this->failed,
                'message' => 'Insufficient Funds, Fund your main wallet',
            ], 500);

        }



        $eletricity_charges = Charge::where('id', 4)
            ->first()->amount;

        if ($amount < 100) {

            return response()->json([

                'status' => $this->failed,
                'message' => 'Amount must not be less than NGN 500',

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
                'serviceID' => $serviceid,
                'billersCode' => $biller_code,
                'variation_code' => $variation_code,
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

        //dd($var, $token = $var->purchased_code);


        $token = $var->purchased_code ?? null;

        //$get_message = $var->content ?? null;

        $get_var_status = $var->response_description;

        // $status = 'TRANSACTION SUCCESSFUL';
        // $token = "11182766373746646";

        $message = "Error Message from VAS ELECTRIC";

        if ($get_var_status == 'TRANSACTION SUCCESSFUL') {

            $new_amount = $amount + $eletricity_charges;
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

            $title = "Electric VAS";

            $transaction = new Transaction();
            $transaction->ref_trans_id = $referenceCode;
            $transaction->user_id = Auth::id();
            $transaction->transaction_type = "VasEletric";
            // $trasnaction->title = "Eletric VAS";
            $transaction->debit = $new_amount;
            $transaction->balance = $debit;
            $transaction->e_charges = $eletricity_charges;
            $transaction->amount = $amount;
            $transaction->main_type = "vtpass";
            $transaction->type = 'vas';
            $transaction->note = "Token Purchase | $token";
            $transaction->save();

            $update = Transaction::where('ref_trans_id', $referenceCode)
                ->update([

                    'title' => $title,
                    'main_type' => "enkpay_vas",

                ]);

            $email = User::where('id', Auth::id())
                ->first()->email;

            $f_name = User::where('id', Auth::id())
                ->first()->f_name;

            //send recepit
            $email = User::where('id', Auth::id())
                ->first()->email;

            $recepit = random_int(10000, 99999);

            $date = date('Y-m-d H:i:s');

            $f_name = User::where('id', Auth::id())
                ->first()->f_name;

            $l_name = User::where('id', Auth::id())
                ->first()->l_name;

            $eletric_address = User::where('id', Auth::id())
                ->first()->eletric_address;

            $phone = User::where('id', Auth::id())
                ->first()->phone;



            if (!empty(user_email())) {

                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Electricity Receipt",
                    'toreceiver' => $email,
                    'recepit' => $recepit,
                    'date' => $date,
                    'f_name' => $f_name,
                    'l_name' => $l_name,
                    'eletric_address' => $eletric_address,
                    'phone' => $phone,
                    'token' => $token,
                    'new_amount' => $new_amount,
                );


                Mail::send('emails.transaction.eletricty-recepit', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });


                return response()->json([

                    'status' => $this->success,
                    'message' => 'Token Purchase Successful, Check your email for token',

                ], 200);
            }
        }

        send_error($message);

        return response()->json([

            'status' => $this->failed,
            'message' => 'Service unavailable please try again later',

        ], 200);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }
}
