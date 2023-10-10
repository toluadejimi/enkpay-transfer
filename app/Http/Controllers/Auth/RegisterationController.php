<?php

namespace App\Http\Controllers\Auth;

use AfricasTalking\SDK\AfricasTalking;
use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\StateLga;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Mail;

class RegisterationController extends Controller
{

    public $success = true;
    public $failed = false;

    public function get_states()
    {

        try {

            $get_states = State::all();

            return response()->json([
                'status' => $this->success,
                'data' => $get_states,

            ], 200);

        } catch (\Exception$e) {
            return $e->getMessage();
        }

    }

    public function get_lga(request $request)
    {

        try {

            $state = $request->state;

            $get_lga = StateLga::where('state', $state)
                ->get();

            $state = array();
            foreach ($get_lga as $div) {
                $state[] = $div->lga;
            }

            return response()->json([
                'status' => $this->success,
                'data' => $state,

            ], 200);

        } catch (\Exception$e) {
            return $e->getMessage();
        }

    }

    public function phone_verification(Request $request)
    {

        try {

            $get_phone_no = $request->phone_no;
            $trimed_no = substr($get_phone_no, 1);

            $user_id = Auth::id() ?? null;

            if (str_contains($get_phone_no, '+234')) {
                $phone_no = $get_phone_no;
            } else {
                $phone_no = "+234" . $trimed_no;
            }

            $sms_code = random_int(1000, 9999);

            $check_phone_verification = User::where('phone', $phone_no)->first()->is_phone_verified ?? null;
            $check_phone = User::where('phone', $phone_no)->first()->phone ?? null;
            $check_status = User::where('phone', $phone_no)->first()->status ?? null;

            if ($check_phone == $phone_no && $check_status == 3) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Phone number has been Restricted on ENKPAY',
                ], 500);

            }

            if ($check_phone == $phone_no && $check_phone_verification == 1) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Phone Number Already Exist',
                ], 500);

            }

            if ($check_phone == null && $check_phone_verification == null && $user_id == null) {

                $user = new User();
                $user->phone = $phone_no;
                $user->sms_code = $sms_code;
                $user->save();

                $token = $user->createToken('API Token')->accessToken;

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://5vk4rx.api.infobip.com/sms/2/text/advanced',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{"messages":[{"destinations":[{"to":"2348105059613"}],"from":"InfoSMS","text":"This is a sample message"}]}',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: a357f536d0bdb20b921ec7612edc9a17-c9ce65d7-4deb-42ba-ade8-796a539929a1',
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ),
                ));

                $response = curl_exec($curl);

                $result = json_decode($response);

                curl_close($curl);

                dd($result);

                // $client = new GuzzleClient([
                //     'headers' => $headers,
                // ]);

                // $response = $client->request('POST', 'https://api.ng.termii.com/api/sms/send',
                //     [
                //         'body' => json_encode([
                //             "api_key" => "TLxF6Jauos8AJq6pKztkbxaQJbQjZzs43vJLOsXk8fHcUez3mBolehZGGzTwnF",
                //             "to" => $phone_no,
                //             "from" => "N-Alert",
                //             "sms" => "Your Enkwave confirmation code is $sms_code. active for 5 minutes, one-time use only",
                //             "type" => "plain",
                //             "channel" => "dnd",

                //         ]),

                //     ]);

                // $body = $response->getBody();
                // $result = json_decode($body);

                $status = $result['status'];

                if ($status == 'success') {

                    return response()->json([
                        'status' => $this->success,
                        'message' => 'OTP Code has been sent succesfully',
                    ], 200);

                }

            }

            if ($check_phone == $phone_no && $check_phone_verification == 0) {

                $update_code = User::where('phone', $phone_no)
                    ->update([
                        'sms_code' => $sms_code,
                    ]);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://5vk4rx.api.infobip.com/sms/2/text/advanced',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => '{"messages":[{"destinations":[{"to":"2348105059613"}],"from":"InfoSMS","text":"This is a sample message"}]}',
                        CURLOPT_HTTPHEADER => array(
                            'Authorization:App a357f536d0bdb20b921ec7612edc9a17-c9ce65d7-4deb-42ba-ade8-796a539929a1',
                            'Content-Type: application/json',
                            'Accept: application/json',
                        ),
                    ));

                    $response = curl_exec($curl);

                    $result = json_decode($response);

                    curl_close($curl);

                    dd($result);
                $status = $result['status'];

                if ($status == 'success') {

                    return response()->json([
                        'status' => $this->success,
                        'message' => 'OTP Code has been sent succesfully',
                    ], 200);

                }

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Service not available at the moment, Please try using Email',
                ], 500);

            }

        } catch (\Exception$e) {
            return $e->getMessage();
        }

    }

    public function email_verification(Request $request)
    {

        try {

            $user_id = Auth::id();

            $email = $request->email;

            $sms_code = random_int(1000, 9999);

            $check_status = User::where('email', $email)->first()->status ?? null;
            $check_email = User::where('email', $email)->first()->email ?? null;
            $check_email_verification = User::where('email', $email)->first()->is_email_verified ?? null;

            if ($check_email == $email && $check_status == 3) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Account has been Restricted on ENKPAY',
                ], 500);

            }

            if ($check_email == $email && $check_email_verification == 1) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Email Already Exist',
                ], 500);

            }

            if ($check_email == null && $check_email_verification == null && $user_id == null) {

                $user = new User();
                $user->email = $email;
                $user->sms_code = $sms_code;
                $user->save();

                $token = $user->createToken('API Token')->accessToken;



                // $data = array(
                //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                //     'subject' => "One Time Password",
                //     'toreceiver' => $email,
                //     'sms_code' => $sms_code,
                // );

                // Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                //     $message->from($data['fromsender']);
                //     $message->to($data['toreceiver']);
                //     $message->subject($data['subject']);
                // });

                // return response()->json([
                //     'status' => $this->success,
                //     'message' => "OTP Code has been sent succesfully to $email",
                // ], 200);



            }

            if ($check_email == $email && $check_email_verification == 0) {

                $update_code = User::where('email', $email)
                    ->update([
                        'sms_code' => $sms_code,
                    ]);

                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "One Time Password",
                    'toreceiver' => $email,
                    'sms_code' => $sms_code,
                );

                Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code has been sent succesfully',
                ], 200);

            }


            $update_code = User::where('email', $email)
            ->update([
                'sms_code' => $sms_code,
            ]);

            $data = array(
                'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                'subject' => "One Time Password",
                'toreceiver' => $email,
                'sms_code' => $sms_code,
            );

            Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                $message->from($data['fromsender']);
                $message->to($data['toreceiver']);
                $message->subject($data['subject']);
            });

            return response()->json([
                'status' => $this->success,
                'message' => 'OTP Code has been sent succesfully',
            ], 200);


        } catch (\Exception$e) {
            return $e->getMessage();
        }

    }

    public function auth_email_verification(Request $request)
    {

        try {

            $user_id = Auth::id();

            $email = $request->email;

            $sms_code = random_int(1000, 9999);

            $check_status = User::where('email', $email)->first()->status ?? null;
            $check_email = User::where('email', $email)->first()->email ?? null;
            $check_email_verification = User::where('email', $email)->first()->is_email_verified ?? null;

            if ($check_email == $email && $check_status == 3) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Account has been Restricted on ENKPAY',
                ], 500);

            }

            if ($check_email == $email && $check_email_verification == 1) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Email Already Exist',
                ], 500);

            }

            if ($check_email == null && $check_email_verification == null && $user_id == Auth::id()) {

                $update = User::where('id', $user_id)
                    ->update([
                        'email' => $email,
                    ]);

                // $data = array(
                //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                //     'subject' => "One Time Password",
                //     'toreceiver' => $email,
                //     'sms_code' => $sms_code,
                // );

                // Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                //     $message->from($data['fromsender']);
                //     $message->to($data['toreceiver']);
                //     $message->subject($data['subject']);
                // });

                // return response()->json([
                //     'status' => $this->success,
                //     'message' => "OTP Code has been sent succesfully to $email",
                // ], 200);

                $api_key = env('EMAILKEY');

                $from = env('FROM_API');

                $email = $request->email;

                $sms_code = random_int(1000, 9999);

                $check_email = User::where('email', $email)->first()->email ?? null;

                if ($check_email == $email) {

                    $update_code = User::where('email', $email)
                        ->update([
                            'sms_code' => $sms_code,
                        ]);



                    $client = new Client([
                        'base_uri' => 'https://api.elasticemail.com',
                    ]);

                    // The response to get
                    $res = $client->request('GET', '/v2/email/send', [
                        'query' => [

                            'apikey' => "$api_key",
                            'from' => "$from",
                            'fromName' => 'ENKPAY',
                            'sender' => "$email",
                            'senderName' => 'ENKPAY',
                            'subject' => 'Verification Code',
                            'to' => "$email",
                            'bodyHtml' => view('emails.registration.otpcode', compact('sms_code'))->render(),
                            'encodingType' => 0,

                        ],
                    ]);

                    $body = $res->getBody();
                    $array_body = json_decode($body);

                    return response()->json([
                        'status' => $this->success,
                        'message' => "OTP Code has been sent succesfully to $email",
                    ], 200);

                } else {

                    return response()->json([
                        'status' => $this->failed,
                        'message' => 'Email could not be found on Enkpay',
                    ], 500);
                }

            }

            if ($check_email == $email && $check_email_verification == 0) {

                $update_code = User::where('id', $user_id)
                    ->update([
                        'sms_code' => $sms_code,
                    ]);

                // $data = array(
                //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                //     'subject' => "One Time Password",
                //     'toreceiver' => $email,
                //     'sms_code' => $sms_code,
                // );

                // Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                //     $message->from($data['fromsender']);
                //     $message->to($data['toreceiver']);
                //     $message->subject($data['subject']);
                // });

                // return response()->json([
                //     'status' => $this->success,
                //     'message' => 'OTP Code has been sent succesfully',
                // ], 200);


                $api_key = env('EMAILKEY');

                $from = env('FROM_API');

                $email = $request->email;

                $sms_code = random_int(1000, 9999);

                $check_email = User::where('email', $email)->first()->email ?? null;

                if ($check_email == $email) {

                    $update_code = User::where('email', $email)
                        ->update([
                            'sms_code' => $sms_code,
                        ]);



                    $client = new Client([
                        'base_uri' => 'https://api.elasticemail.com',
                    ]);

                    // The response to get
                    $res = $client->request('GET', '/v2/email/send', [
                        'query' => [

                            'apikey' => "$api_key",
                            'from' => "$from",
                            'fromName' => 'ENKPAY',
                            'sender' => "$email",
                            'senderName' => 'ENKPAY',
                            'subject' => 'Verification Code',
                            'to' => "$email",
                            'bodyHtml' => view('emails.registration.otpcode', compact('sms_code'))->render(),
                            'encodingType' => 0,

                        ],
                    ]);

                    $body = $res->getBody();
                    $array_body = json_decode($body);


                    return response()->json([
                        'status' => $this->success,
                        'message' => "OTP Code has been sent succesfully to $email",
                    ], 200);

                } else {

                    return response()->json([
                        'status' => $this->failed,
                        'message' => 'Email could not be found on Enkpay',
                    ], 500);
                }

            }

        } catch (\Exception$e) {
            return $e->getMessage();
        }

    }

    public function auth_phone_verification(Request $request)
    {

        try {

            $get_phone_no = $request->phone_no;
            $trimed_no = substr($get_phone_no, 1);

            $user_id = Auth::id() ?? null;

            if (str_contains($get_phone_no, '+234')) {
                $phone_no = $get_phone_no;
            } else {
                $phone_no = "+234" . $trimed_no;
            }

            $sms_code = random_int(1000, 9999);

            $check_phone_verification = User::where('phone', $phone_no)->first()->is_phone_verified ?? null;
            $check_phone = User::where('phone', $phone_no)->first()->phone ?? null;
            $check_status = User::where('phone', $phone_no)->first()->status ?? null;

            if ($check_phone == $phone_no && $check_status == 3) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Phone number has been Restricted on ENKPAY',
                ], 500);

            }

            if ($check_phone == $phone_no && $check_phone_verification == 1) {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Phone Number Already Exist',
                ], 500);

            }

            if ($check_phone == null && $check_phone_verification == null && $user_id == Auth::id()) {

                $update_code = User::where('id', $user_id)
                    ->update([
                        'phone' => $phone_no,
                    ]);

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "http://login.betasms.com/api/?username=toluadejimi@gmail.com&password=Tolulope2580@&message=Your%20ENKPAY%20Verification%20is%$sms_code&sender=ENKPAY&mobiles=$phone_no",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $body = $response->getBody();
                $result = json_decode($body);

                dd($result);

                // $headers = [
                //     'Accept' => 'application/json',
                //     'Content-Type' => 'application/json',
                //     'Authorization' => 'Bearer FLWSECK-043cf4e9dd848683c6b157c234ba2fb8-X',
                // ];

                // $client = new GuzzleClient([
                //     'headers' => $headers,
                // ]);

                // $response = $client->request('POST', 'https://api.ng.termii.com/api/sms/send',
                //     [
                //         'body' => json_encode([
                //             "api_key" => "TLxF6Jauos8AJq6pKztkbxaQJbQjZzs43vJLOsXk8fHcUez3mBolehZGGzTwnF",
                //             "to" => $phone_no,
                //             "from" => "N-Alert",
                //             "sms" => "Your Enkwave confirmation code is $sms_code. active for 5 minutes, one-time use only",
                //             "type" => "plain",
                //             "channel" => "dnd",

                //         ]),

                //     ]);

                // $body = $response->getBody();
                // $result = json_decode($body);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code has been sent succesfully',
                ], 200);

            }

            if ($check_phone == $phone_no && $check_phone_verification == 0) {

                $update_code = User::where('id', $user_id)
                    ->update([
                        'sms_code' => $sms_code,
                    ]);

                $curl = curl_init();
                $data = array(

                    "api_key" => "TLxF6Jauos8AJq6pKztkbxaQJbQjZzs43vJLOsXk8fHcUez3mBolehZGGzTwnF",
                    "to" => $phone_no,
                    "from" => "N-Alert",
                    "sms" => "Your Verification Code is $sms_code",
                    "type" => "plain",
                    "channel" => "generic",

                );

                $post_data = json_encode($data);

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.ng.termii.com/api/sms/send",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $post_data,
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: application/json",
                    ),
                ));

                $var = curl_exec($curl);
                curl_close($curl);

                $var = json_decode($var);

                $status = $var->message;

                if ($status == 'Successfully Sent') {

                    return response()->json([
                        'status' => $this->success,
                        'message' => 'OTP Code has been sent succesfully',
                    ], 200);

                }

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Service not available at the moment, Please try using Email',
                ], 500);

            }

        } catch (\Exception$e) {
            return $e->getMessage();
        }

    }

    public function resend_email_otp(Request $request)
    {




        try {

            $api_key = env('EMAILKEY');

            $from = env('FROM_API');

            $email = $request->email;

            $sms_code = random_int(1000, 9999);

            $check_email = User::where('email', $email)->first()->email ?? null;

            if ($check_email == $email) {

                $update_code = User::where('email', $email)
                    ->update([
                        'sms_code' => $sms_code,
                    ]);

                // $data = array(
                //     'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                //     'subject' => "One Time Password",
                //     'toreceiver' => $email,
                //     'sms_code' => $sms_code,
                // );

                // Mail::send('emails.registration.otpcode', ["data1" => $data], function ($message) use ($data) {
                //     $message->from($data['fromsender']);
                //     $message->to($data['toreceiver']);
                //     $message->subject($data['subject']);
                // });


                $client = new Client([
                    'base_uri' => 'https://api.elasticemail.com',
                ]);

                // The response to get
                $res = $client->request('GET', '/v2/email/send', [
                    'query' => [

                        'apikey' => "$api_key",
                        'from' => "$from",
                        'fromName' => 'ENKPAY',
                        'sender' => "$email",
                        'senderName' => 'ENKPAY',
                        'subject' => 'Verification Code',
                        'to' => "$email",
                        'bodyHtml' => view('emails.registration.otpcode', compact('sms_code'))->render(),
                        'encodingType' => 0,

                    ],
                ]);

                $body = $res->getBody();
                $array_body = json_decode($body);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code has been sent succesfully',
                ], 200);

            } else {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Email could not be found on Enkpay',
                ], 500);
            }

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function resend_phone_otp(Request $request)
    {

        try {

            $phone_no = $request->phone_no;

            $sms_code = random_int(1000, 9999);

            $update_code = User::where('phone', $phone_no)
                ->update([
                    'sms_code' => $sms_code,
                ]);

            $curl = curl_init();
            $data = array(

                "api_key" => "TLxF6Jauos8AJq6pKztkbxaQJbQjZzs43vJLOsXk8fHcUez3mBolehZGGzTwnF",
                "to" => $phone_no,
                "from" => "N-Alert",
                "sms" => "Your Verification Code is $sms_code",
                "type" => "plain",
                "channel" => "generic",

            );

            $post_data = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ng.termii.com/api/sms/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            $status = $var->message;

            if ($status == 'Successfully Sent') {

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code has been sent succesfully',
                ], 200);

            }

            return response()->json([
                'status' => $this->failed,
                'message' => 'Service not available at the moment, Please try using Email verification',
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }

    }

    public function verify_phone_otp(Request $request)
    {

        try {

            $user_id = Auth::id() ?? null;

            $phone_no = $request->phone_no;
            $code = $request->code;

            $get_auth_code = User::where('id', $user_id)->first()->sms_code ?? null;

            $get_code = User::where('phone', $phone_no)->first()->sms_code ?? null;

            if ($code == $get_code && $user_id == null) {

                $update = User::where('phone', $phone_no)
                    ->update([

                        'is_phone_verified' => 1,
                        'status' => 1,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code verified successfully',
                ], 200);

            }

            return response()->json([
                'status' => $this->failed,
                'message' => 'Invalid code, try again',
            ], 500);

            if ($code == $get_auth_code && $user_id == Auth::id()) {

                $update = User::where('id', $user_id)
                    ->update([

                        'is_phone_verified' => 1,
                        'status' => 1,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code verified successfully',
                ], 200);

            }

            return response()->json([
                'status' => $this->failed,
                'message' => 'Invalid code, try again',
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }
    }

    public function verify_email_otp(Request $request)
    {

        try {

            $user_id = Auth::id() ?? null;

            $email = $request->email;
            $code = $request->code;

            $get_auth_code = User::where('id', $user_id)->first()->sms_code ?? null;

            $get_code = User::where('email', $email)->first()->sms_code ?? null;

            if ($code == $get_code && $user_id == null) {

                $update = User::where('email', $email)
                    ->update([

                        'is_email_verified' => 1,
                        'status' => 1,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code verified successfully',
                ], 200);

            }

            return response()->json([
                'status' => $this->failed,
                'message' => 'Invalid code, try again',
            ], 500);

            if ($code == $get_auth_code && $user_id == Auth::id()) {

                $update = User::where('id', $user_id)
                    ->update([

                        'is_email_verified' => 1,
                        'status' => 1,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'OTP Code verified successfully',
                ], 200);

            }

            return response()->json([
                'status' => $this->failed,
                'message' => 'Invalid code, try again',
            ], 500);

        } catch (\Exception$th) {
            return $th->getMessage();
        }
    }




    public function register(Request $request)
    {



        // try {

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'dob' => 'required|string|max:50',
                'state' => 'required|string|max:18',
                'gender' => 'required|string|max:50',
                'street' => 'required|string|max:500',
                //'lga' => 'required|string|max:18',
                'password' => 'required',
                'pin' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => $validator->messages()->first(),
                ], 500);
            }

            $check_phone = User::where('phone', $request->phone_no)
            ->first()->phone ?? null;


            $check_email = User::where('email', $request->email)->first()->email ?? null;
            $check_status = User::where('phone', $request->phone_no)->first()->status ?? null;

            // if($request->phone_no == $check_phone){

            //     return response()->json([
            //         'status' => $this->failed,
            //         'message' => 'Phone Number has been taken',
            //     ], 500);

            // }






            // if($request->email != null  &&  $request->email  == $check_email){

            //     return response()->json([
            //         'status' => $this->failed,
            //         'message' => 'Account with email already exist',
            //     ], 500);

            // }

            $email = $request->email;
            $devide_id = $request->devide_id;
            $phone_no = $request->phone_no;
            $first_name = $request->first_name;
            $last_name = $request->last_name;
            $gender = $request->gender;

            //$dob = $request->dob;

            $date = str_replace('/', '-', $request->dob);
            $dob = date('Y-m-d', strtotime($date));


            $street = $request->street;
            $state = $request->state;
            $lga = $request->lga;
            $password = $request->password;
            $pin = $request->pin;
            $device_id = $request->device_id;
            $devide_id = $request->devide_id;
            $city = $request->city;
            $get_phone = $phone_no ?? null;
            $get_email = $email ?? null;



            if($check_status == "2"){

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Please Login with your phone number or email',
                ], 500);

            }

            if($check_phone == null && $check_email == null ){


                $create = new User();
                $create->first_name = $first_name;
                $create->phone = $phone_no;
                $create->last_name = $last_name;
                // $create->dob = $dob;

                  //$dob = $request->dob;

                $date = str_replace('/', '-', $request->dob);
                $dob = date('Y-m-d', strtotime($date));

                
                $create->gender = $gender;
                $create->email = $email;
                $create->email = $device_id;
                $create->street = $street;
                $create->address_line1 = $street;
                $create->city = $lga;
                $create->state = $state;
                $create->lga = $lga;
                $create->is_phone_verified = 1;
                $create->device_id = $devide_id;
                $create->password = bcrypt($password);
                $create->pin = bcrypt($pin);
                $create->save();

                return response()->json([
                    'status' => $this->success,
                    'message' => 'Your account has been successfully created',
                ], 200);

            }

            if ($get_phone !== null) {

                $update = User::where('email', $email)
                    ->update([
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'dob' => $dob,
                        'gender' => $gender,
                        'email' => $email,
                        'street' => $street,
                        'address_line1' => $street,
                        'city' => $lga,
                        'state' => $state,
                        'lga' => $lga,
                        'password' => bcrypt($password),
                        'pin' => bcrypt($pin),
                        'device_id' => $devide_id,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'Your account has been successfully created',
                ], 200);

            } elseif ($get_email !== null) {

                $update = User::where('phone', $phone_no)
                    ->update([
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'dob' => $dob,
                        'gender' => $gender,
                        'email' => $email,
                        'address_line1' => $street,
                        'street' => $street,
                        'city' => $lga,
                        'state' => $state,
                        'lga' => $lga,
                        'password' => bcrypt($password),
                        'pin' => bcrypt($pin),
                        'device_id' => $devide_id,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'message' => 'Your account has been successfully created',
                ], 200);

            } else {

                return response()->json([
                    'status' => $this->failed,
                    'message' => 'Email or Phone number not found',
                ], 500);

            }

        // } catch (\Exception$e) {
        //     return $e->getMessage();
        // }

    }



    public function forgot_password(request $request){


        try {

            $email = $request->email;

            $check = User::where('email', $email)
            ->first()->email ?? null;


            if($email == null){

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Account not found, please sign up',

                ], 500);


            }


            $first_name = User::where('email', $email)
                ->first()->first_name ?? null;

            if ($check == $email) {

                //send email
                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Reset Password",
                    'toreceiver' => $email,
                    'first_name' => $first_name,
                    'link' => url('') . "/reset-password/?email=$email",
                );

                Mail::send('emails.notify.passwordlink', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });

                return response()->json([
                    'status' => $this->success,
                    'message' => 'Check your inbox or spam for futher instructions',
                ], 200);

            } else {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'User not found on our system',

                ], 500);

            }

        } catch (\Exception$e) {
            return response()->json([
                'status' => $this->failed,
                'message' => $e->getMessage(),
            ], 500);
        }



    }

}
