<?php

namespace App\Console\Commands;

use App\Models\Charge;
use App\Models\PendingTransaction;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;

class SendCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        // $anchorTime = Carbon::createFromFormat("Y-m-d H:i:s", $created_at);
        // $currentTime = Carbon::now();

        // $minuteDiff = $anchorTime->diffInMinutes($currentTime);

        // if($minuteDiff > 2){

        //     dd($minuteDiff, $currentTime, "Send");

        // ->where('created_at', '<', Carbon::now()->subDay())

        // where('created_at','<=',$time)->first();
        // }






        $trx = PendingTransaction::where('status', 0)
        ->where('created_at','<', Carbon::now()->subMinutes(1))->first() ?? null;



        if (!empty($trx) || $trx != null) {

            $ref = $trx->ref_trans_id;

            $erran_api_key = errand_api_key();

            $epkey = env('EPKEY');

            $curl = curl_init();
            $data = array(

                "amount" => $trx->amount,
                "destinationAccountNumber" => $trx->receiver_account_no,
                "destinationBankCode" => $trx->bank_code,
                "destinationAccountName" => $trx->receiver_name,

            );

            $post_data = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/ApiFundTransfer',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $erran_api_key",
                    "EpKey: $epkey",
                    'Content-Type: application/json',
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);

            $var = json_decode($var);


            $error = $var->error->message ?? null;
            $TransactionReference = $var->data->reference ?? null;
            $status = $var->code ?? null;

            if ($status == 200) {


                Transfer::where('ref_trans_id', $trx->ref_trans_id)->update(['status' => 0, 'e_ref' => $TransactionReference]);
                Transaction::where('ref_trans_id', $trx->ref_trans_id)->update(['status' => 0, 'e_ref' => $TransactionReference]);
                PendingTransaction::where('ref_trans_id', $trx->ref_trans_id)->delete();
                $user_id = PendingTransaction::where('ref_trans_id', $trx->ref_trans_id)->first()->user_id ?? null;
                PendingTransaction::where('user_id', $user_id)->delete();


                $message = "Transaction |  $TransactionReference | NGN $trx->amount | has been sent to VFD ";

                send_notification($message);


            } else {

                $balance = User::where('id', $trx->user_id)->first()->main_wallet;
                PendingTransaction::where('ref_trans_id', $trx->ref_trans_id)->update(['status' => 3]);
                Transaction::where('ref_trans_id', $trx->ref_trans_id)->update(['status' => 3]);
                $transfer_charges = Charge::where('title', 'transfer_fee')->first()->amount;
                $user_wallet_banlance = User::where('id', $trx->user_id)->first()->main_wallet;


                //credit
                $credit = $user_wallet_banlance + $trx->amount + $transfer_charges;
                $update = User::where('id', $trx->user_id)
                    ->update([
                        'main_wallet' => $credit,
                    ]);


                    $trasnaction = new Transaction();
                    $trasnaction->user_id = $trx->user_id;
                    $trasnaction->ref_trans_id = $trx->ref_trans_id;
                    $trasnaction->transaction_type = "Reversal";
                    $trasnaction->debit = 0;
                    $trasnaction->amount = $trx->amount;
                    $trasnaction->serial_no = 0;
                    $trasnaction->title = "Reversal";
                    $trasnaction->note = "Reversal";
                    $trasnaction->fee = 25;
                    $trasnaction->balance = $credit;
                    $trasnaction->main_type = "Reversal";
                    $trasnaction->status = 3;
                    $trasnaction->save();


                $usr = User::where('id', $trx->user_id)->first();
                $message = "Transaction reversed | $error ";
                $full_name = $usr->first_name . "  " . $usr->last_name;


                $result = " Message========> " . $message . "\n\nCustomer Name========> " . $full_name;
                send_notification($result);
            }


        }









        //
    }
}
