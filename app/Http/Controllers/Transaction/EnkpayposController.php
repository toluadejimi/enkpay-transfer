<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\PosLog;
use App\Models\Terminal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Support\Facades\Hash;

class EnkpayposController extends Controller
{
    //ENPAY POS



     public function enkpayPosLogs(request $request)
    {


        $key = $request->header('dataKey');
        $RRN = $request->RRN;
        $userID = $request->UserID;
        $STAN = $request->STAN;
        $amount = $request->amount;
        $expireDate = $request->expireDate;
        $message = $request->message;
        $pan = $request->pan;
        $responseCode = $request->responseCode;
        $terminalID = $request->terminalID;
        $transactionType = $request->transactionType;
        $cardName = $request->cardName;
        $userID = $request->UserID;
        $DataKey = env('DATAKEY');


        if($key == null){

            $result = "No Key Passed";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Empty Key',
            ], 500);

        }


        if($key != $DataKey){

            $result = "Invalid Key | $key";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Invalid Request',
            ], 500);

        }


          //update Transactions
          $trasnaction = new PosLog();
          $trasnaction->user_id = $userID;
          $trasnaction->e_ref = $RRN;
          $trasnaction->transaction_type = $transactionType;
          $trasnaction->title = "POS Transasction Log";
          $trasnaction->amount = $amount;
          $trasnaction->sender_name = $pan;
          $trasnaction->serial_no = $terminalID;
          $trasnaction->sender_account_no = $pan;
          $trasnaction->status = 1;
          $trasnaction->save();



          return response()->json([
            'status' => true,
            'message' => 'Log saved Successfully',
        ], 200);





    }

    public function enkpayPos(request $request)
    {



        $key = $request->header('dataKey');
        $RRN = $request->RRN;
        $userID = $request->UserID;
        $STAN = $request->STAN;
        $amount = $request->amount;
        $expireDate = $request->expireDate;
        $message = $request->message;
        $pan = $request->pan;
        $responseCode = $request->responseCode;
        $terminalID = $request->terminalID;
        $transactionType = $request->transactionType;
        $cardName = $request->cardName;
        $DataKey = env('DATAKEY');



        if($key == null){

            $result = "No Key Passed";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Empty Key',
            ], 500);

        }


        if($key != $DataKey){

            $result = "Invalid Key | $key";
            send_notification($result);

            return response()->json([
                'status' => false,
                'message' => 'Invalid Request',
            ], 500);

        }




        // $encryptedStr = $request->data;
        // $encrypted =  decryption($encryptedStr);
        // $resust = json_decode($encrypted);
        // $jsonData = trim($encrypted, "\x04\x03\x02\x01\x00\x05\x06\x07\x08\x09");
        // $jsonString = iconv('UTF-8', 'ISO-8859-1//IGNORE', $jsonData);
        // $decodedData = json_decode($jsonString, true);

        // $RRN = $decodedData['RRN'];
        // $userID = $decodedData['UserID'];
        // $STAN = $decodedData['STAN'];
        // $amount = $decodedData['amount'];
        // $expireDate = $decodedData['expireDate'];
        // $message = $decodedData['message'];
        // $pan = $decodedData['pan'];
        // $responseCode = $decodedData['responseCode'];
        // $terminalID = $decodedData['terminalID'];
        // $transactionType = $decodedData['transactionType'];
        // $cardName = $decodedData['cardName'];





        $trans_id = trx();

        //$verify1 = hash('sha512', $key);

        $comission = Charge::where('title', 'both_commission')
            ->first()->amount;

        // if ($eip == $ip) {

        //Get user ID
        $user_id = $userID;
        //Main Wallet
        $main_wallet = User::where('id', $user_id)
            ->first()->main_wallet ?? null;

        $type = User::where('id', $user_id)
            ->first()->type ?? null;

        if ($main_wallet == null && $user_id == null) {

            return response()->json([
                'status' => false,
                'message' => 'Customer not registred on Enkpay',
            ], 500);
        }

        //Both Commission
        $amount1 = $comission / 100;
        $amount2 = $amount1 * $amount;
        $both_commmission = number_format($amount2, 3);


        $business_commission_cap = Charge::where('title', 'business_cap')
            ->first()->amount;

        $agent_commission_cap = Charge::where('title', 'agent_cap')
            ->first()->amount;

        if ($both_commmission >= $agent_commission_cap && $type == 1) {

            $removed_comission = $amount - $agent_commission_cap;

            $enkpay_profit = $agent_commission_cap - 75;
        } elseif ($both_commmission >= $business_commission_cap && $type == 3) {

            $removed_comission = $amount - $business_commission_cap;

            $enkpay_profit = $business_commission_cap - 75;
        } else {

            $removed_comission = $amount - $both_commmission;

            $enkpay_profit = $both_commmission;
        }




        if ($responseCode == 00) {

            $updated_amount = $main_wallet + $removed_comission;

            $main_wallet = User::where('id', $user_id)
                ->update([
                    'main_wallet' => $updated_amount,
                ]);

            //update Transactions
            $trasnaction = new Transaction();
            $trasnaction->user_id = $user_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->e_ref = $RRN;
            $trasnaction->transaction_type = $transactionType;
            $trasnaction->credit = round($removed_comission, 2);
            $trasnaction->e_charges = $enkpay_profit;
            $trasnaction->title = "POS Transasction";
            $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message";
            $trasnaction->amount = $amount;
            $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
            $trasnaction->balance = $updated_amount;
            $trasnaction->sender_name = $pan;
            $trasnaction->serial_no = $terminalID;
            $trasnaction->sender_account_no = $pan;
            $trasnaction->status = 1;
            $trasnaction->save();


            $f_name = User::where('id', $user_id)->first()->first_name ?? null;
            $l_name = User::where('id', $user_id)->first()->last_name ?? null;

            $ip = $request->ip();
            $amount4 = number_format($removed_comission, 2);
            $result = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | using ENKPPAY POS" . "\n\nIP========> " . $ip;
            send_notification($result);



            return response()->json([
                'status' => true,
                'message' => 'Transaction Successful',
            ], 200);



        }else{
            //update Transactions
            $trasnaction = new Transaction();
            $trasnaction->user_id = $user_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->e_ref = $RRN;
            $trasnaction->transaction_type = $transactionType;
            $trasnaction->credit = 0;
            $trasnaction->e_charges = $enkpay_profit;
            $trasnaction->title = "POS Transasction";
            $trasnaction->note = "ENKPAY POS | $cardName | $pan | $message  ";
            $trasnaction->amount = $amount;
            $trasnaction->enkPay_Cashout_profit = round($enkpay_profit, 2);
            $trasnaction->balance = 0;
            $trasnaction->sender_name = $pan;
            $trasnaction->serial_no = $terminalID;
            $trasnaction->sender_account_no = $pan;
            $trasnaction->status = 4;
            $trasnaction->save();

            $f_name = User::where('id', $user_id)->first()->first_name ?? null;
            $l_name = User::where('id', $user_id)->first()->last_name ?? null;

            $ip = $request->ip();
            $amount4 = number_format($removed_comission, 2);
            $result = $f_name . " " . $l_name . "| fund NGN " . $amount4 . " | Failed on ENKPAY POS" . "\n\nIP========> " . $ip;
            send_notification($result);


            return response()->json([
                'status' => false,
                'message' => 'Transaction Failed',
            ], 500);

        }


    }
}
