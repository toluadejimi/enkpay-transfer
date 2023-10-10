<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Charge;
use App\Models\Terminal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Webtransfer;
use DateTimeZone;
use Illuminate\Support\Facades\Hash;
use Mail;
use Illuminate\Support\Facades\DB;



class WebpaymentController extends Controller
{

    public $success = true;
    public $failed = false;


    public function confirm_pay(request $request){


        $data = $request->data;
        $wallet = $request->wallet;
        $decrypt= Crypt::decryptString($data);
        $string = $decrypt; // a string

        $arrays = explode(" ", $string );



        $id = $arrays[0];
        $payable_amount =$arrays[1];
        $trans_id =$arrays[2];


        $web_charge = Charge::where('title', 'webcharge')->first()->amount;

        $amount = $payable_amount - $web_charge;




        $marchant_account_name = VirtualAccount::where('user_id', $id)->first()->v_account_name;



        if ($wallet == 'main_account') {
            $user_wallet_banlance = main_account();
        } else {
            $user_wallet_banlance = bonus_account();
        }


        if ($amount > $user_wallet_banlance) {

            return response()->json([

                'status' => $this->failed,
                'message' => 'Insufficient Funds, fund your account',

            ], 500);

        }


        if (Hash::check($request->pin, Auth::user()->pin) == false) {

            return response()->json([

                'status' => $this->failed,
                'message' => 'Invalid Pin, Please try again',

            ], 500);
        }





        $charged = $user_wallet_banlance - $payable_amount;
        if ($wallet == 'main_account') {

            $update = User::where('id', Auth::id())
                ->update([
                    'main_wallet' => $charged,
                ]);

        } else {

            $update = User::where('id', Auth::id())
                ->update([
                    'bonus_wallet' => $charged,
                ]);
        }


        $update = Webtransfer::find($trans_id);
        $update->status = 1;
        $update->update();


        dd($update);





        $credit_marchant = User::where('id',$id)->increment('main_wallet', $amount);

        $marchant_balance = User::where('id',$id)->first()->main_wallet;




        $trasnaction = new Transaction();
        $trasnaction->user_id = Auth::id();
        $trasnaction->ref_trans_id = $trans_id;
        $trasnaction->type = "Webpayment";
        $trasnaction->title = "Web Payment with QR code";
        $trasnaction->main_type = "qrcode";
        $trasnaction->transaction_type = "WebTransfer";
        $trasnaction->title = "Web Transfer";
        $trasnaction->debit = $amount;
        $trasnaction->amount = $amount;
        $trasnaction->note = "Qr Payment to " . "|" . $marchant_account_name;
        $trasnaction->fee = 0;
        $trasnaction->enkPay_Cashout_profit = 50;
        $trasnaction->status = 1;
        $trasnaction->save();


        $trasnaction = new Transaction();
        $trasnaction->user_id = $id;
        $trasnaction->ref_trans_id = $trans_id;
        $trasnaction->type = "Webpayment";
        $trasnaction->title = "Payment received with QR code";
        $trasnaction->main_type = "qrcode";
        $trasnaction->transaction_type = "WebTransfer";
        $trasnaction->title = "Web Transfer";
        $trasnaction->debit = $amount;
        $trasnaction->amount = $amount;
        $trasnaction->note = "Qr Payment from " . "|" . Auth::user()->first_name;
        $trasnaction->fee = 0;
        $trasnaction->enkPay_Cashout_profit = 0;
        $trasnaction->status = 1;
        $trasnaction->save();


        return response()->json([

            'status' => $this->success,
            'marchant_balance' => $marchant_balance,
            'user_balance' => $user_wallet_banlance,
            'message' => "Transaction Successful",

        ], 200);






    }







}
