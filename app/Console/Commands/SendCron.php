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


                $curl = curl_init();
                    $data = array(

                    'ref_trans_id' => $trx->ref_trans_id,
                    'TransactionReference' => $TransactionReference,

                );

                    $post_data = json_encode($data);

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://enkpayapp.enkwave.com/api/pending-transaction',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $post_data,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                        ),
                    ));

                    $var = curl_exec($curl);
                    curl_close($curl);
                    $var = json_decode($var);


                    $message = "Transaction reversed | Our API  sent reversal | $error ";
    
    
                    $result = " Message========> " . $message;
                    send_notification($result);



                $message = "Transaction |  $TransactionReference | NGN $trx->amount | has been sent to VFD ";
                send_notification($message);


            } else {


                $curl = curl_init();
                    $data = array(

                    "user_id" => $trx->user_id,
                    "ref_trans_id" => $trx->ref_trans_id,
                    "amount" => $trx->amount,

                    );

                    $post_data = json_encode($data);

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://enkpayapp.enkwave.com/api/transfer-reverse',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $post_data,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                        ),
                    ));

                    $var = curl_exec($curl);
                    curl_close($curl);
                    $var = json_decode($var);


                    $message = "Transaction reversed | Our API  sent reversal | $error ";
    
    
                    $result = " Message========> " . $message;
                    send_notification($result);

            }


        }







        //
    }
}
