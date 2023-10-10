<?php

use App\Models\AccountInfo;
use App\Models\ErrandKey;
use App\Models\ProvidusBank;
use App\Models\Setting;
use App\Models\Terminal;
use App\Models\User;
use App\Models\VfdBank;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\Auth;






if (!function_exists('main_account')) {



    function main_account()
    {
        $user = Auth::user();
        return $user->main_wallet;
    }
}

if (!function_exists('user_status')) {

    function user_status()
    {
        $user = Auth::user();
        return $user->status;
    }
}

if (!function_exists('bonus_account')) {

    function bonus_account()
    {
        $user = Auth::user();
        return $user->bonus_wallet;
    }
}

if (!function_exists('user_email')) {

    function user_email()
    {
        $user = Auth::user();
        return $user->email;
    }
}

if (!function_exists('user_phone')) {

    function user_phone()
    {
        $user = Auth::user();
        return $user->phone;
    }
}

if (!function_exists('user_bvn')) {

    function user_bvn()
    {
        $user = Auth::user();
        return $user->bvn;
    }
}

if (!function_exists('first_name')) {

    function first_name()
    {
        $user = Auth::user();
        return $user->first_name;
    }
}

if (!function_exists('last_name')) {

    function last_name()
    {
        $user = Auth::user();
        return $user->last_name;
    }
}

if (!function_exists('user_status')) {

    function user_status()
    {
        $user = Auth::user();
        return $user->status;
    }
}

if (!function_exists('select_account')) {

    function select_account()
    {



        $account = User::where('id', Auth::id())->first();

        //dd($account);

        $account_array = array();
        $account_array[0] = [
            "title" => "Main Account",
            "amount" => $account->main_wallet,
            "key" => "main_account",

        ];
        $account_array[1] = [
            "title" => "Bonus Account",
            "amount" => $account->bonus_wallet,
            "key" => "bonus_account",
        ];

        return $account_array;
    }
}



if (!function_exists('user_virtual_account_list')) {

    function virtual_account()
    {


        $account = VirtualAccount::where('user_id', Auth::id())->get() ?? null;


        if ($account !== null) {


            foreach ($account as $item) {
                $account_array[] = array(
                    "bank_name" => $item['v_bank_name'],
                    "account_no" => $item['v_account_no'],
                    "account_name" => $item['v_account_name'],
                );
            }

            // $account_array = array();
            // $account_array[0] = [
            //     "bank_name" => $account[0]['v_bank_name'],
            //     "account_no" => $account[0]['v_account_no'],
            //     "account_name" => $account[0]['v_account_name'],

            // ];


            // $account_array[1] = [
            //     "bank_name" => $account[1]['v_bank_name'],
            //     "account_no" => $account[1]['v_account_no'],
            //     "account_name" => $account[1]['v_account_name'],
            // ];


            return $account_array ?? [];
        }



        return [];
    }
}





if (!function_exists('terminal_info')) {


    function terminal_info()
    {

        $tm = Terminal::select('merchantNo', 'terminalNo', 'merchantName', 'deviceSN')->where('user_id', Auth::id())->first() ?? null;
        if ($tm != null) {
            return $tm;
        }

        return $tm;
    }
}

if (!function_exists('send_error')) {

    function send_error($message)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.telegram.org/bot6140179825:AAGfAmHK6JQTLegsdpnaklnhBZ4qA1m2c64/sendMessage?chat_id=1316552414',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'chat_id' => "1316552414",
                'text' => $message,

            ),
            CURLOPT_HTTPHEADER => array(),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
    }
}


if (!function_exists('send_notification')) {

    function send_notification($message)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.telegram.org/bot6140179825:AAGfAmHK6JQTLegsdpnaklnhBZ4qA1m2c64/sendMessage?chat_id=1316552414',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'chat_id' => "1316552414",
                'text' => $message,

            ),
            CURLOPT_HTTPHEADER => array(),
        ));

        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
    }
}


if (!function_exists('trx')) {

    function trx()
    {

        $refcode = "ENK-" . random_int(10, 99).date('YmdHis');

        return $refcode;

    }
}


if (!function_exists('store_vfd_banks')) {
    function store_vfd_banks()
    {

        $errand_key = ErrandKey::where('id', 1)->first()->errand_key ?? null;


        if ($errand_key == null) {
            $response1 = errand_api_key();
            $update = ErrandKey::where('id', 1)
                ->update([
                    'errand_key' => $response1[0],
                ]);
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/ApiGetBanks',
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

        $result = $var->data ?? null;

        $status = $var->code ?? null;

        $chk_bank = VfdBank::select('*')->first()->bank_code ?? null;
        if ($chk_bank == null || empty($chk_bank)) {
            $history = [];
            foreach ($var->data as $key => $value) {
                $history[] = array(
                    "bank_name" => $value->bankName,
                    "code" => $value->code,
                );
            }

            DB::table('vfd_banks')->insert($history);
        }
    }
}


if (!function_exists('store_providus_banks')) {
    function store_providus_banks()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://154.113.16.142:8882/postingrest/GetNIPBanks',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',

        ));

        $var = curl_exec($curl);

        curl_close($curl);
        $var = json_decode($var);


        $result = $var->banks ?? null;

        $status = $var->code ?? null;

        $chk_bank = ProvidusBank::select('*')->first()->bank_code ?? null;
        if ($chk_bank == null || empty($chk_bank)) {
            $history = [];
            foreach ($var->banks as $key => $value) {
                $history[] = array(
                    "bank_name" => $value->bankName,
                    "code" => $value->bankCode,
                );
            }

            $rr =  DB::table('providus_banks')->insert($history);

            return  $rr;
        }
    }
}


if (!function_exists('get_banks')) {
    function get_banks()
    {



        $set = Setting::where('id', 1)->first();

        if ($set->bank == 'vfd') {
            $get_banks = VfdBank::select('bankName', 'code')->get();


            return $get_banks;
        }


        // if($set->bank == 'manuel'){
        //     $get_banks = ProvidusBank::select('bankName', 'code')->get();
        //     return $get_banks;
        // }



        if ($set->bank == 'manuel') {
            $get_banks = VfdBank::select('bankName', 'code')->get();

            return $get_banks;
        }





        if ($set->bank == 'pbank') {
            $get_banks = ProvidusBank::select('bankName', 'code')->get();



            return $get_banks;
        }
    }
}



if (!function_exists('resolve_bank')) {
    function resolve_bank($bank_code, $account_number)
    {



        $set = Setting::where('id', 1)->first();

        if ($set->bank == 'manuel') {

            $databody = array(

                'accountNumber' => $account_number,
                'institutionCode' => $bank_code,
                'channel' => "Bank",

            );

            $body = json_encode($databody);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/AccountNameVerification',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);
            $var = json_decode($var);

            $customer_name = $var->data->name ?? null;
            $error = $var->error->message ?? null;

            $status = $var->code ?? null;

            if ($status == 200) {

                return $customer_name;
            }

            return $error;
        }

        if ($set->bank == 'pbank') {

            $databody = array(

                'accountNumber' => $account_number,
                'institutionCode' => $bank_code,
                'channel' => "Bank",

            );

            $body = json_encode($databody);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/AccountNameVerification',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);
            $var = json_decode($var);

            $customer_name = $var->data->name ?? null;
            $error = $var->error->message ?? null;

            $status = $var->code ?? null;

            if ($status == 200) {

                return $customer_name;
            }

            return $error;
        }


        if ($set->bank == 'vfd') {


            $customer_name = AccountInfo::where('account_no', $account_number)
                ->where('code', $bank_code)->first()->customer_name ?? null;

            if ($customer_name != null) {
                return $customer_name;
            }




            if (!empty($customer_name) || $customer_name == null) {

                $databody = array(

                    'accountNumber' => $account_number,
                    'institutionCode' => $bank_code,
                    'channel' => "Bank",

                );

                $body = json_encode($databody);
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/AccountNameVerification',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $body,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                    ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);
                $var = json_decode($var);

                $customer_name = $var->data->name ?? null;
                $error = $var->error->message ?? null;

                $status = $var->code ?? null;

                $bankName = VfdBank::where('code', $bank_code)->first()->bankName;

                if ($status == 200) {

                    $sv = new AccountInfo();
                    $sv->account_no = $account_number;
                    $sv->code = $bank_code;
                    $sv->bankName = $bankName;
                    $sv->customer_name = $customer_name;
                    $sv->save();

                    return $customer_name;
                }

                return $error;
            }
        }
    }
}



if (!function_exists('create_v_account')) {
    function create_v_account($user_id)
    {

        $errand_key = errand_api_key();
        $errand_user_id = errand_id();
        $bvn = user_bvn() ?? null;

        $user_id = User::where('bvn', $bvn)->first()->id ?? null;



        $chk_V_account = VirtualAccount::where('user_id', $user_id)->where('v_bank_name', 'VFD MFB')->first() ?? null;
        if (empty($chk_V_account) || $chk_V_account == null) {
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

            if (user_status() == 0) {

                return response()->json([
                    'status' => false,
                    'message' => 'User has been restricted on ENKPAY',
                ], 500);
            }

            if (user_status() == 1) {

                return response()->json([
                    'status' => false,
                    'message' => 'Please complete your KYC',
                ], 500);
            }

            if (user_bvn() == null) {

                return response()->json([
                    'status' => false,
                    'message' => 'We need your BVN to generate an account for you',
                ], 500);
            }

            if (Auth::user()->v_account_number !== null) {

                return response()->json([
                    'status' => false,
                    'message' => 'You already own account number',
                ], 500);
            }

            if ($bvn == null) {

                return response()->json([
                    'status' => false,
                    'message' => 'BVN not verified, Kindly update your BVN',
                ], 500);
            }

            $curl = curl_init();
            $data = array(

                "userId" => $errand_user_id,
                "customerBvn" => $bvn,
                "phoneNumber" => $phone,
                "customerName" => $name,

            );

            $databody = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/CreateVirtualAccount',
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
                    "Authorization: Bearer $errand_key",
                ),
            ));

            $var = curl_exec($curl);


            curl_close($curl);
            $var = json_decode($var);


            $status = $var->code ?? null;
            $acct_no = $var->data->accountNumber ?? null;
            $acct_name = $var->data->accountName ?? null;
            $error = $var->error->message ?? null;

            $bank = "VFD MFB";

            if ($status == 200) {

                $create = new VirtualAccount();
                $create->v_account_no = $acct_no;
                $create->v_account_name = $acct_name;
                $create->v_bank_name = $bank;
                $create->user_id = Auth::id();
                $create->save();

                $user = User::find(Auth::id());
                $user->v_account_no = $acct_no;
                $user->v_account_name = $acct_name;
                $user->save();

                $get_user = User::find(Auth::id())->first();

                $message = "VFD Account Created | $name";
                send_notification($message);
                return 200;
            } else {
                $message = "VFD ERROR | $name | " . $error ?? null;
                send_notification($message);
                return 500;
            }
        }
    }
}

function create_p_account()
{
    if (!function_exists('create_p_account')) {

        $bvn = user_bvn() ?? null;
        $user_id = User::where('bvn', $bvn)->first()->id ?? null;


        dd('hellop');




        $client = env('CLIENTID');
        $xauth = env('HASHKEY');

        $user_id = User::where('bvn', $bvn)->first()->id ?? null;

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

        dd($var);

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

            return 200;
        } else {


            $error = "Providus account Error | $name | $error";
            send_notification($error);

            return 400;
        }
    }
}




    if (!function_exists('decryption')) {

        function decryption($encryptedStr) {
            $s_key = env('SKEY');
            $INV_key = env('INVKEY');

            $key = base64_decode($s_key);
            $iv = base64_decode($INV_key);
            $cipherText = base64_decode($encryptedStr);
            $decryptedText = openssl_decrypt($cipherText, 'AES-256-CFB', $key, OPENSSL_RAW_DATA, $iv);
            return $decryptedText;
        }

    }




    if (!function_exists('encrypt')) {

        function encrypt($strToEncrypt) {


        $s_key = env('SKEY');
        $INV_key = env('INVKEY');

                $key = base64_decode($s_key);
                $iv = base64_decode($INV_key);
                $cipherText = openssl_encrypt($strToEncrypt, 'AES-256-CFB', $key, OPENSSL_RAW_DATA, $iv);
                return base64_encode($cipherText);


        }


    }


    function guid() {
        function s4() {
            return substr(md5(uniqid(rand(), true)), 0, 4);
        }

        return s4() . s4() . s4() . s4() . s4() . s4() . s4() . s4();
    }

    function timestamp() {
        return substr(strval(time()), 0, 10);
    }

    function sha512($message) {
        return hash('sha512', $message);
    }







