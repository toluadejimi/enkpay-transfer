<?php

namespace App\Http\Controllers\Virtual;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Terminal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Validtransfer;
use App\Models\VirtualAccount;
use App\Models\Webkey;
use App\Models\Webtransfer;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Type\NullType;

class VirtualaccountController extends Controller
{
    public $success = true;
    public $failed = false;

    public function create_account(request $request)
    {

        try {




            $bvn = user_bvn() ?? null;
            $user_id = User::where('bvn', $bvn)->first()->id ?? null;


            if ($bvn == null) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please complete your verification before creating an account',
                ], 500);
            }



            $client = env('CLIENTID');
            $xauth = env('HASHKEY');

            $user_id = User::where('bvn', $bvn)->first()->id ?? null;
            $chk_p_account = VirtualAccount::where('user_id', $user_id)->where('v_bank_name', 'PROVIDUS BANK')->first() ?? null;
            if (empty($chk_p_account) || $chk_p_account == null) {

                if (Auth::user()->b_name == null) {
                    $name = first_name() . " " . last_name();
                } else {
                    $name = Auth::user()->b_name;
                }

                if (Auth::user()->b_phone == null) {
                    $phone = Auth::user()->phone;
                } else {
                    $phone = Auth::user()->b_phone;
                }

                $curl = curl_init();
                $data = array(
                    "account_name" => $name,
                    "bvn" => $bvn,
                );



                $databody = json_encode($data);
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://vps.providusbank.com/vps/api/PiPCreateReservedAccountNumber',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $databody,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Accept: application/json',
                        "Client-Id: $client",
                        "X-Auth-Signature: $xauth",
                    ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);
                $var = json_decode($var);


                $status = $var->responseCode ?? null;
                $p_acct_no = $var->account_number ?? null;
                $p_acct_name = $var->account_name ?? null;
                $error = $var->responseMessage ?? null;

                $pbank = "PROVIDUS BANK";

                if ($status == 00) {

                    $create = new VirtualAccount();
                    $create->v_account_no = $p_acct_no;
                    $create->v_account_name = $p_acct_name;
                    $create->v_bank_name = $pbank;
                    $create->user_id = Auth::id();
                    $create->save();

                    $message = "Providus Account Created | $name";
                    send_notification($message);

                    $get_user = User::find(Auth::id())->first();
                    return response()->json([

                        'status' => $this->success,
                        'message' => "Your account has been created successfully",
                        'data' => $get_user,

                    ], 200);
                } else {


                    $error = "Vaccount Error | $error";
                    send_notification($error);

                    return response()->json([

                        'status' => $this->failed,
                        'message' => 'Error please try again after some time',

                    ], 500);
                }
            }
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function get_created_account()
    {

        try {

            $errand_key = errand_api_key();

            $b_code = env('BCODE');

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.errandpay.com/epagentservice/api/v1/GetSubAccounts?businessCode=$b_code",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(

                    "Authorization: Bearer $errand_key",

                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);

            if ($var->code == 200) {

                return response()->json([

                    'status' => $this->success,
                    'data' => $var->data,

                ], 200);
            }

            return response()->json([

                'status' => $this->failed,
                'data' => $var->error->message,

            ], 500);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function cash_in_webhook(Request $request)
    {

        try {

            $header = $request->header('errand-pay-header');

            $StatusCode = $request->StatusCode;
            $StatusDescription = $request->StatusDescription;
            $VirtualCustomerAccount = $request->VirtualCustomerAccount;
            $Amount = $request->Amount;
            $Currency = $request->Currency;
            $TransactionDate = $request->TransactionDate;
            $TransactionTime = $request->TransactionTime;
            $TransactionType = $request->TransactionType;
            $ServiceCode = $request->ServiceCode;
            $Fee = $request->Fee;
            $PostingType = $request->PostingType;
            $TransactionReference = $request->TransactionReference;
            $sender_account_no = $request->OriginatorAccountNumber;
            $sender_name = $request->OriginatorAccountName;
            $sender_bank = $request->OriginatorBank;

            $key = env('ERIP');

            $deposit_charges = Charge::where('id', 2)->first()->amount;

            $trans_id = trx();
            $verify1 = hash('sha512', $key);

            if ($verify1 == $header) {

                if ($StatusCode == 00) {

                    $deposit_charges = Charge::where('id', 2)->first()->amount;

                    $user_id = VirtualAccount::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->user_id ?? null;

                    $main_wallet = User::where('id', $user_id)
                        ->first()->main_wallet ?? null;

                    $user_id = User::where('id', $user_id)
                        ->first()->id ?? null;

                    $user_email = User::where('id', $user_id)
                        ->first()->email ?? null;


                    $device_id = User::where('id', $user_id)
                        ->first()->device_id ?? null;


                    $first_name = User::where('id', $user_id)
                        ->first()->first_name ?? null;

                    $last_name = User::where('id', $user_id)
                        ->first()->last_name ?? null;

                    $check_status = User::where('id', $user_id)->first()->status ?? null;

                    $serial_no = Terminal::where('v_account_no', $VirtualCustomerAccount)
                        ->first()->serial_no ?? null;

                    if ($main_wallet == null && $user_id == null) {

                        return response()->json([
                            'status' => false,
                            'message' => 'V Account not registred on Enkpay',
                        ], 500);
                    }

                    if ($check_status == 3) {

                        return response()->json([
                            'status' => $this->failed,
                            'message' => 'Account has been Restricted on ENKPAY',
                        ], 500);
                    }

                    $enkpay_profit = $deposit_charges - 10;

                    $message_amount = $Amount - 10;

                    //credit
                    $enkpay_debit = $Amount - $deposit_charges;
                    $amt_to_credit = $enkpay_debit - 2;
                    $updated_amount = $main_wallet + $amt_to_credit;

                    User::where('id', 95)->increment('bonus_wallet', 1);
                    User::where('id', 109)->increment('bonus_wallet', 1);

                    $main_wallet = User::where('id', $user_id)
                        ->update([
                            'main_wallet' => $updated_amount,
                        ]);

                    if ($TransactionType == 'FundWallet') {

                        //update Transactions
                        $trasnaction = new Transaction();
                        $trasnaction->user_id = $user_id;
                        $trasnaction->ref_trans_id = $trans_id;
                        $trasnaction->e_ref = $TransactionReference;
                        $trasnaction->type = $TransactionType;
                        $trasnaction->transaction_type = "VirtualFundWallet";
                        $trasnaction->title = "Wallet Funding";
                        $trasnaction->main_type = "Transfer";
                        $trasnaction->credit = $enkpay_debit;
                        $trasnaction->note = "$sender_name | Wallet Funding";
                        $trasnaction->fee = $Fee;
                        $trasnaction->amount = $Amount;
                        $trasnaction->e_charges = $deposit_charges;
                        $trasnaction->enkPay_Cashout_profit = $enkpay_profit;
                        $trasnaction->trx_date = $TransactionDate;
                        $trasnaction->trx_time = $TransactionTime;
                        $trasnaction->sender_name = $sender_name;
                        $trasnaction->sender_bank = $sender_bank;
                        $trasnaction->serial_no = $serial_no;
                        $trasnaction->sender_account_no = $sender_account_no;
                        $trasnaction->balance = $updated_amount;
                        $trasnaction->status = 1;
                        $trasnaction->save();

                        $errand_key = errand_api_key();

                        $b_code = env('BCODE');

                        $acct_no = $request->acct_no;

                        $curl = curl_init();

                        $datetime = new \DateTime("now", new DateTimeZone("Europe/Bucharest"));

                        $date1 = $datetime->format('Y-m-d');
                        $date2 = $datetime->format('H:i:s');

                        $data = array(

                            "Amount" => $Amount,
                            "DateOfTransaction" => $date1 . "T" . $date2 . "+" . "01:00",
                            "SenderAccountNumber" => $sender_account_no,
                            "SenderAccountName" => $sender_name,
                            "OriginatorBank" => $sender_bank,
                            "RecipientAccountNumber" => $VirtualCustomerAccount,
                            "RecipientAccountName" => $first_name . " " . $last_name,

                        );

                        $post_data = json_encode($data);

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/Webhook/Notify',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => $post_data,
                            CURLOPT_HTTPHEADER => array(
                                'epKey: ep_live_jFrIZdxqSzAdraLqbvhUfVYs',
                                'Content-Type: application/json',
                            ),
                        ));

                        $var = curl_exec($curl);
                        curl_close($curl);
                        $var = json_decode($var);


                        if ($device_id != null) {

                            $data = [

                                "registration_ids" => array($device_id),

                                "notification" => [
                                    "title" => "Incoming Transfer",
                                    "body" => $sender_name . "| sent | NGN" . number_format($Amount),
                                    "icon" => "ic_notification",
                                    "click_action" => "OPEN_CHAT_ACTIVITY",

                                ],

                                "data" => [
                                    "sender_name" => "$sender_name",
                                    "sender_bank" => "$sender_bank",
                                    "amount" => "$Amount"
                                ],

                            ];

                            $dataString = json_encode($data);

                            $SERVER_API_KEY = env('FCM_SERVER_KEY');

                            $headers = [
                                'Authorization: key=' . $SERVER_API_KEY,
                                'Content-Type: application/json',
                            ];


                            $ch = curl_init();

                            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

                            $get_response = curl_exec($ch);


                            //dd($get_response, $dataString, $headers);
                            curl_close($curl);
                        }
                    }


                    $message = $first_name . " " . $last_name . "has been credited |  $message_amount | from VFD Virtual account";
                    send_notification($message);


                    $get_web_transfer = Webtransfer::where('v_account_no', $VirtualCustomerAccount)
                        ->where('payable_amount', $Amount)->first()->status ?? null;

                    if ($get_web_transfer == 0) {

                        Webtransfer::where('v_account_no', $VirtualCustomerAccount)
                            ->where('payable_amount', $Amount)
                            ->update(['status' => 1]);
                    }

                    //send to user

                    if ($user_email !== null) {

                        $data = array(
                            'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                            'subject' => "Virtual Account Credited",
                            'toreceiver' => $user_email,
                            'amount' => $enkpay_debit,
                            'first_name' => $first_name,
                        );

                        Mail::send('emails.transaction.virtual-credit', ["data1" => $data], function ($message) use ($data) {
                            $message->from($data['fromsender']);
                            $message->to($data['toreceiver']);
                            $message->subject($data['subject']);
                        });
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Tranasaction Successsfull',
                    ], 200);
                }
            }

            return response()->json([
                'status' => false,
                'message' => 'Key not Authorized',
            ], 500);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function get_virtual_account(request $request)
    {

        try {

            $bank = "VFD MICROFINANCE BANK";

            $get_account = User::select('v_account_no', 'v_account_name')->where('id', Auth::id())
                ->first() ?? null;

            $account = $get_account;
            $account['bank'] = $bank;

            if ($account !== null) {
                return response()->json([

                    'status' => $this->success,
                    'data' => $account,

                ], 200);
            }

            return response()->json([

                'status' => $this->failed,
                'data' => "Contact support to create your bank account",

            ], 500);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function virtual_acct_history(Request $request)
    {

        try {

            $errand_key = errand_api_key();

            $b_code = env('BCODE');

            $acct_no = $request->acct_no;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.errandpay.com/epagentservice/api/v1/GetSubAccountHistory?businessCode=$b_code&accountNumber=$acct_no&pageNumber=1&pageSize=50",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $errand_key",
                ),
            ));

            $var = curl_exec($curl);

            curl_close($curl);
            $var = json_decode($var);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    //PROVIDUS VIRTUAL ACCOUNT

    public function providusCashIn(request $request)
    {

        $parametersJson = json_encode($request->all());
        $headers = json_encode($request->headers->all());
        $message = 'Log 1';
        $ip = $request->ip();

        $ip2 = env('PROIP');
        $ip3 = env('PROIP2');


        $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
        send_notification($result);



        $key = env('HASHKEY');
        $key2 = env('HASHKEY2');

        $header = $request->header('X-Auth-Signature');

        if ($header == null) {

            return response()->json([
                'requestSuccessful' => true,
                'responseMessage' => 'Key Can not be empty',
                'responseCode' => "02",
            ], 200);
        }

        if ($key == $header || $key2 == $header) {

            $sessionId = $request->sessionId;
            $accountNumber = $request->accountNumber;
            $tranRemarks = $request->tranRemarks;
            $settledAmount = $request->settledAmount;
            $transactionAmount = $request->transactionAmount;
            $feeAmount = $request->feeAmount;
            $TransactionTime = $request->TransactionTime;
            $initiationTranRef = $request->initiationTranRef;
            $settlementId = $request->settlementId;
            $sourceAccountNumber = $request->sourceAccountNumber;
            $PostingType = $request->PostingType;
            $TransactionReference = $request->TransactionReference;
            $sourceAccountName = $request->sourceAccountName;
            $sourceBankName = $request->sourceBankName;
            $channelId = $request->channelId;
            $tranDateTime = $request->tranDateTime;



            if ($sourceAccountName == 'null' || $sourceAccountName == "null" || $sourceAccountName == null) {

                $from = $tranRemarks;
            } else {

                $from = $sourceAccountName;
            }


            $trans_id = trx();

            // $verify1 = hash('sha512', $key);

            // dd($verify1, $header);

            // $verify2 = strtoupper($verify1);

            // dd($key, $verify2, $verify1, $header);


            $deposit_charges = Charge::where('title', 'bwebpay')->first()->amount;


            $user_id = VirtualAccount::where('v_account_no', $accountNumber)
                ->first()->user_id ?? null;

            $main_wallet = User::where('id', $user_id)
                ->first()->main_wallet ?? null;

            $user_id = User::where('id', $user_id)
                ->first()->id ?? null;

            $user_email = User::where('id', $user_id)
                ->first()->email ?? null;

            $first_name = User::where('id', $user_id)
                ->first()->first_name ?? null;


            $device_id = User::where('id', $user_id)
                ->first()->device_id ?? null;


            $last_name = User::where('id', $user_id)
                ->first()->last_name ?? null;

            $SerialNumber = Terminal::where('user_id', $user_id)
                ->first()->serial_no ?? null;

            $check_status = User::where('id', $user_id)->first()->status ?? null;

            $VirtualCustomerAccount = User::where('v_account_no', $accountNumber)->first()->v_account_no ?? null;

            $get_session = Transaction::where('e_ref', $settlementId)->first()->e_ref ?? null;

            if ($main_wallet == null && $user_id == null) {


                $message = 'V Account no registered on ENKPAY';
                $result = $message;
                send_notification($result);



                return response()->json([
                    'requestSuccessful' => true,
                    'sessionId' => $sessionId,
                    'responseMessage' => 'V Account no registered on ENKPAY',
                    'responseCode' => "02",
                ], 200);
            }

            if ($get_session == $settlementId) {

                $message = 'duplicate transaction';
                $result = $message;
                send_notification($result);

                return response()->json([
                    'requestSuccessful' => true,
                    'sessionId' => $sessionId,
                    'responseMessage' => 'duplicate transaction',
                    'responseCode' => "01",
                ], 200);
            }

            if ($check_status == 3) {

                $message = 'Account has been Restricted on ENKPAY';
                $result = $message;
                send_notification($result);

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Account has been Restricted on ENKPAY',
                ], 500);
            }


            //Business Information
            $web_commission = Charge::where('title', 'bwebpay')->first()->amount;
            //Both Commission
            $amount1 = $web_commission / 100;
            $amount2 = $amount1 * $transactionAmount;
            $both_commmission = number_format($amount2, 3);


            //enkpay commission
            $commison_subtract = $web_commission - 0.5;
            $enkPayPaypercent = $commison_subtract / 100;
            $enkPay_amount = $enkPayPaypercent * $transactionAmount;
            $enkpay_commision_amount = number_format($enkPay_amount, 3);


            $p_cap = Charge::where('title', 'p_cap')
                ->first()->amount;

            if ($both_commmission > $p_cap) {

                $removed_comm =  $p_cap;
            } else {
                $removed_comm =  $both_commmission;
            }

            $business_id = VirtualAccount::where('v_account_no', $accountNumber)->first()->business_id ?? null;


            if (!empty($business_id) || $business_id != null) {
                $charge_status = Webkey::where('key', $key)->first()->charge_status ?? null;
                $amt_to_credit = (int)$transactionAmount - (int)$removed_comm;
                $amt1 = (int)$amt_to_credit - 2;

                User::where('business_id', $business_id)->increment('main_wallet', $amt1);
                User::where('id', 95)->increment('bonus_wallet', 1);
                User::where('id', 109)->increment('bonus_wallet', 1);


                $first_name = User::where('business_id', $business_id)->first()->first_name ?? null;
                $last_name = User::where('business_id', $business_id)->first()->last_name ?? null;
                $balance = User::where('business_id', $business_id)->first()->main_wallet;


                $status = WebTransfer::latest()->where([

                    'v_account_no' => $accountNumber,
                    'payable_amount' => $transactionAmount,
                    'status' => 0,


                ])->update(['status' => 1]) ?? null;





                $web_trans_id = WebTransfer::where('v_account_no', $accountNumber)
                ->where('payable_amount', $transactionAmount)->first()->trans_id ?? null;

                if( $web_trans_id == null){
                    $refid = $trans_id;
                }else{
                    $refid = $web_trans_id;
                }


                //update Transactions
                $trasnaction = new Transaction();
                $trasnaction->user_id = $user_id;
                $trasnaction->ref_trans_id = $refid;
                $trasnaction->e_ref = $settlementId;
                $trasnaction->type = "webpay";
                $trasnaction->transaction_type = "VirtualFundWallet";
                $trasnaction->title = "Wallet Funding";
                $trasnaction->main_type = "Transfer";
                $trasnaction->credit = $amt_to_credit;
                $trasnaction->note = "$from | Web Pay";
                $trasnaction->fee = $feeAmount;
                $trasnaction->amount = $transactionAmount;
                $trasnaction->e_charges = $deposit_charges;
                $trasnaction->enkPay_Cashout_profit = $enkpay_commision_amount;
                $trasnaction->trx_date = $tranDateTime;
                $trasnaction->p_sessionId = $sessionId;
                $trasnaction->trx_time = $tranDateTime;
                $trasnaction->sender_name = $from;
                $trasnaction->sender_bank = $sourceBankName;
                $trasnaction->serial_no = $SerialNumber;
                $trasnaction->sender_account_no = $sourceAccountNumber;
                $trasnaction->receiver_account_no = $accountNumber;
                $trasnaction->balance = $balance;
                $trasnaction->status = 1;
                $trasnaction->save();

                $message = "Business funded | $amt_to_credit | $first_name " . " " . $last_name;
                send_notification($message);

                return response()->json([
                    'requestSuccessful' => true,
                    'sessionId' => $sessionId,
                    'responseMessage' => 'success',
                    'responseCode' => "00",
                ], 200);
            }




            // $n_cap = Charge::where('title', 'n_cap')->first()->amount;

            // if ($both_commmission > $p_cap) {

            //     $removed_comm =  $n_cap;
            // } else {
            //     $removed_comm =  $both_commmission;
            // }


            if ($settledAmount > 9999) {
                $charges_to_remove = 58;
            } else {
                $charges_to_remove = 13;
            }







            $amt_to_credit = $settledAmount - $charges_to_remove;
            $amt1 = $amt_to_credit - 2;

            User::where('id', $user_id)->increment('main_wallet', $amt1);
            User::where('id', 95)->increment('bonus_wallet', 1);
            User::where('id', 109)->increment('bonus_wallet', 1);


            $balance = User::where('id', $user_id)->first()->main_wallet;

            //update Transactions
            $trasnaction = new Transaction();
            $trasnaction->user_id = $user_id;
            $trasnaction->ref_trans_id = $trans_id;
            $trasnaction->e_ref = $settlementId;
            $trasnaction->type = "webpay";
            $trasnaction->transaction_type = "VirtualFundWallet";
            $trasnaction->title = "Wallet Funding";
            $trasnaction->main_type = "Transfer";
            $trasnaction->credit = $amt_to_credit;
            $trasnaction->note = "$from |  NGN $transactionAmount  | Funds Transfer";
            $trasnaction->fee = 0;
            $trasnaction->amount = $transactionAmount;
            $trasnaction->e_charges = $deposit_charges;
            $trasnaction->enkPay_Cashout_profit = 10;
            $trasnaction->trx_date = $tranDateTime;
            $trasnaction->p_sessionId = $sessionId;
            $trasnaction->trx_time = $tranDateTime;
            $trasnaction->sender_name = $from;
            $trasnaction->sender_bank = $sourceBankName;
            $trasnaction->sender_bank = $sourceBankName;
            $trasnaction->serial_no = $SerialNumber;
            $trasnaction->sender_account_no = $sourceAccountNumber;
            $trasnaction->receiver_account_no = $accountNumber;
            $trasnaction->balance = $balance;
            $trasnaction->status = 1;
            $trasnaction->save();


            $b_code = env('BCODE');

            $acct_no = $request->acct_no;



            // Send to Andriod Phones
            if ($device_id != null) {

                $data = [

                    "registration_ids" => array($device_id),

                    "notification" => [
                        "title" => "Incoming Transfer",
                        "body" => $from . "| sent | NGN" . number_format($transactionAmount),
                        "icon" => "ic_notification",
                        "click_action" => "OPEN_CHAT_ACTIVITY",

                    ],

                    "data" => [
                        "sender_name" => "$from",
                        "sender_bank" => "$sourceBankName",
                        "amount" => "$transactionAmount"
                    ],

                ];

                $dataString = json_encode($data);

                $SERVER_API_KEY = env('FCM_SERVER_KEY');

                $headers = [
                    'Authorization: key=' . $SERVER_API_KEY,
                    'Content-Type: application/json',
                ];


                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

                $get_response = curl_exec($ch);


                //dd($get_response, $dataString, $headers);
                curl_close($ch);
            }



            // Send to Iphones
            if ($device_id != null) {

                $data = [

                    "registration_ids" => array($device_id),

                    "notification" => [
                        "title" => "Incoming Transfer",
                        "body" =>  $from . "| sent | NGN" . number_format($transactionAmount),
                        "icon" => "ic_notification",
                        "click_action" => "OPEN_CHAT_ACTIVITY",

                    ],

                    "data" => [
                        "sender_name" => "$from",
                        "sender_bank" => "$sourceBankName",
                        "amount" => "$transactionAmount"
                    ],

                ];

                $dataString = json_encode($data);

                $SERVER_API_KEY = env('IPHONE_FCM_SERVER_KEY');

                $headers = [
                    'Authorization: key=' . $SERVER_API_KEY,
                    'Content-Type: application/json',
                ];


                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

                $get_response = curl_exec($ch);


                //dd($get_response, $dataString, $headers);
                curl_close($ch);
            }




            //Send to Terminal
            $curl = curl_init();

            $datetime = new \DateTime("now", new DateTimeZone("Europe/Bucharest"));

            $date1 = $datetime->format('Y-m-d');
            $date2 = $datetime->format('H:i:s');

            $serial_no = VirtualAccount::where('v_account_no', $accountNumber)
                ->first()->serial_no ?? null;

            $epKey = env('EPKEY');

            if (!empty($serial_no) || $serial_no != null) {

                $data = array(

                    "Amount" => $transactionAmount,
                    "DateOfTransaction" => $date1 . "T" . $date2 . "+" . "01:00",
                    "SenderAccountNumber" => $sourceAccountNumber,
                    "SenderAccountName" => $from,
                    "OriginatorBank" => $sourceBankName,
                    "RecipientAccountNumber" => $VirtualCustomerAccount,
                    "RecipientAccountName" => $first_name . " " . $last_name,

                );

                $post_data = json_encode($data);

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/Webhook/Notify',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $post_data,
                    CURLOPT_HTTPHEADER => array(
                        "epKey: $epKey",
                        'Content-Type: application/json',
                    ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);
                $var = json_decode($var);
            }

            $message = $first_name . " " . $last_name . "been credited |  $transactionAmount | from PROVIDUS Virtual account";
            send_notification($message);

            //send to user
            if ($user_email !== null) {

                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Virtual Account Credited",
                    'toreceiver' => $user_email,
                    'amount' => $transactionAmount,
                    'first_name' => $first_name,
                );

                Mail::send('emails.transaction.virtual-credit', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });
            }


            return response()->json([
                'requestSuccessful' => true,
                'sessionId' => $sessionId,
                'responseMessage' => 'success',
                'responseCode' => "00",
            ], 200);
        }

        $sessionId = $request->sessionId;

        $parametersJson = json_encode($request->all());
        $headers = json_encode($request->headers->all());
        $message = 'ip does not match';
        $ip = $request->ip();
        $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
        send_notification($result);

        return response()->json([
            'requestSuccessful' => true,
            'sessionId' => $sessionId,
            'responseMessage' => 'Key not authorized',
            'responseCode' => "02",
        ], 200);



    }






}







    // $sessionId = $request->sessionId;
    // $accountNumber = $request->accountNumber;
    // $tranRemarks = $request->tranRemarks;
    // $settledAmount = $request->settledAmount;
    // $transactionAmount = $request->transactionAmount;
    // $feeAmount = $request->feeAmount;
    // $TransactionTime = $request->TransactionTime;
    // $initiationTranRef = $request->initiationTranRef;
    // $settlementId = $request->settlementId;
    // $sourceAccountNumber = $request->sourceAccountNumber;
    // $PostingType = $request->PostingType;
    // $TransactionReference = $request->TransactionReference;
    // $sourceAccountName = $request->sourceAccountName;
    // $sourceBankName = $request->sourceBankName;
    // $channelId = $request->channelId;
    // $tranDateTime = $request->tranDateTime;


    // $parametersJson = json_encode($request->all());
    // $headers = json_encode($request->headers->all());
    // $message = 'Key not Authorized';
    // $ip = $request->ip();

    // $result = " Header========> " . $headers . "\n\n Body========> " . $parametersJson . "\n\n Message========> " . $message . "\n\nIP========> " . $ip;
    // send_notification($result);



    // } catch (\Exception $th) {
    //     return $th->getMessage();
    // }


