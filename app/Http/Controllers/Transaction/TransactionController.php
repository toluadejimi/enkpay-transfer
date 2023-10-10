<?php

namespace App\Http\Controllers\Transaction;
use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\User;
use Illuminate\Http\Request;


class TransactionController extends Controller
{
    public function transfer(request $request)
    {

        dd('Hello');
        $trasnaction = new PendingTransaction();
        $trasnaction->user_id = $request->user_id;
        $trasnaction->ref_trans_id = $request->ref_trans_id;
        $trasnaction->debit = $request->debit;
        $trasnaction->amount = $request->amount;
        $trasnaction->bank_code = $request->bank_code;
        $trasnaction->receiver_name = $request->receiver_name;
        $trasnaction->receiver_account_no = $request->receiver_account_no;
        $trasnaction->receiver_name = $request->receiver_name;
        $trasnaction->save();
        
        return response()->json([
            'status' => 200,
            'message' => "Transaction processing",

        ], 200);

    }


   
}
