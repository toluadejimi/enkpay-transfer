<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\DeletedUser;
use App\Models\ErrandKey;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use App\Models\VirtualAccount;
use App\Models\Terminal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mail;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{

    public $success = true;
    public $failed = false;

    public function contact()
    {

        try {

            $contact = Contact::where('id', 1)->first();

            return response()->json([
                'status' => $this->success,
                'data' => $contact,

            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }






    public function reset_pin(request $request)
    {

        $email = $request->email;


        return view('reset-pin', compact('email'));
    }


    public function reset_password(request $request)
    {

        $email = $request->email;


        return view('reset-password', compact('email'));
    }



    public function success()
    {

        return view('success');
    }


    public function reset_pin_now(Request $request)
    {

        $email = $request->email;



        $input = $request->validate([
            'password' => ['required', 'confirmed', 'int'],
        ]);

        $pin = Hash::make($request->password);


        $chk_pin_length = strlen($request->password);


        if ($chk_pin_length > 4) {
            return back()->with('error', 'Your pin digit is more than 4');
        }

        $check_email = User::where('email', $email)->first();


        if ($check_email == null) {

            return back()->with('error', 'User not found');
        }

        $update_pin = User::where('email', $email)
            ->update(['pin' => $pin]);


        return redirect('success')->with('message', 'Your pin has been successfully updated');
    }



    public function reset_password_now(Request $request)
    {

        $email = $request->email;



        $input = $request->validate([
            'password' => ['required', 'confirmed', 'string'],
        ]);

        $password = Hash::make($request->password);


        $check_email = User::where('email', $email)->first();


        if ($check_email == null) {

            return back()->with('error', 'User not found');
        }

        $update_pin = User::where('email', $email)
            ->update(['password' => $password]);


        return redirect('success')->with('message', 'Your password has been successfully updated');
    }







    public function user_info(request $request)
    {

        try {

            $GetToken = $request->header('Authorization');

            $string = $GetToken;
            $toBeRemoved = "Bearer ";
            $token = str_replace($toBeRemoved, "", $string);

            $virtual_account = virtual_account();

            $user = Auth()->user();
            $user['token'] = $token;
            $user['user_virtual_account_list'] = $virtual_account;
            $user['terminal_info'] = terminal_info();


            return response()->json([
                'status' => $this->success,
                'data' => $user,

            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function update_user(request $request)
    {

        try {

            //$data1 = $response1[1]

            $errand_key = ErrandKey::where('id', 1)->first()->errand_key ?? null;

            if ($errand_key == null) {
                $response1 = errand_api_key();
                $update = ErrandKey::where('id', 1)
                    ->update([
                        'errand_key' => $response1[0],
                    ]);
            }

            $databody = array(

                'userId' => Auth::id(),
                'customerBvn' => Auth::user()->identification_number,
                'phoneNumber' => Auth::user()->phone,
                'customerName' => Auth::user()->first_name . " " . Auth::user()->last_name,

            );

            $body = json_encode($databody);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.errandpay.com/epagentservice/api/v1/CreateVirtualAccount',
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
                    "Authorization: Bearer $errand_key",
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            dd($var);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function update_info(request $request)
    {

        try {

            $data = $request->all();

            $errand_key = ErrandKey::where('id', 1)->first()->errand_key ?? null;


            $update = User::where('id', Auth::id())
                ->update([

                    'identification_type' => $request->$data['identification_type'],
                    'identification_number' => $request->$data['identification_number'],

                ]);

            $databody = array(

                'userId' => Auth::id(),
                'kycType' => "BVN",
                'token' => Auth::user()->identification_type,
                'bankCode' => null,



            );

            $body = json_encode($databody);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/GetKycDetails',
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
                    "Authorization: Bearer $errand_key",
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);

            $var = json_decode($var);

            dd($var);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function verify_info(request $request)
    {

        try {









            $bank_code = $request->bank_code;
            $account_number = $request->account_number;
            $bvn = $request->bvn;

            $databody = array(

                'accountNumber' => $account_number,
                'institutionCode' => $bank_code,
                'channel' => "Bank",

            );

            $body = json_encode($databody);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://stagingapi.errandpay.com/epagentservice/api/v1/AccountNameVerification',
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

            $get_string = User::where('id', Auth::id())
                ->first()->first_name;
            $get_string2 = User::where('id', Auth::id())
                ->first()->last_name;

            $verify_name = $var->data->name;

            $first_name = strtoupper($get_string);
            $last_name = strtoupper($get_string2);

            if (str_contains($verify_name, $first_name) && str_contains($verify_name, $last_name)) {

                $update = User::where('id', Auth::id())
                    ->update([
                        'is_identification_verified' => 1,
                        'bvn' => $bvn,

                    ]);

                return response()->json([
                    'status' => $this->success,
                    'message' => "Account has been successfully verified",

                ], 200);
            }

            return response()->json([
                'status' => $this->failed,
                'message' => "Sorry we could not verify your account information",

            ], 500);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function verify_identity(request $request)
    {



        if(Auth::user()->email == null){

            return response()->json([
                'status' => true,
                'message' => 'Verify your email',
            ], 500);

        }


        $phone = User::where('id', Auth::id())->first()->phone ?? null;

        if($phone == null) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Please update your phone number",
            ], 500);

        }

        $identity_type = $request->identity_type;
        $identity_number = $request->identity_number;


        $key = env('BKEY');


        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path() . 'upload/selfie';
            $request->photo->move(public_path('upload/selfie'), $fileName);
            $file_url = url('') . "/public/upload/selfie/$fileName";
        } else {
            $fileName =  Auth::user()->identification_image;
            $file_url = url('') . "/public/upload/selfie/$fileName";
        }


        $databody = array(

            "first_name" => Auth::user()->first_name,
            "last_name" => Auth::user()->last_name,

            "address" => array(
                "address" => Auth::user()->address_line1,
                "city" =>   Auth::user()->city,
                "state" =>  Auth::user()->state,
                "country" => "Nigeria",
                "postal_code" => random_int(1000, 9999),
                "house_no" => random_int(10, 99),
            ),

            "phone" => Auth::user()->phone,
            "email_address" => Auth::user()->email,

            "identity" => array(
                "id_type" => "NIGERIAN_BVN_VERIFICATION",
                "selfie_image" => $file_url,
                "bvn" => $identity_number,

            ),

            "meta_data" => array(
                "user_id" => Auth::id(),
            ),


        );



        $body = json_encode($databody);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cardholder/register_cardholder_synchronously',
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
                "token: Bearer $key"
            ),
        ));



        $result = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($result);


        $error = $var->message ?? $result ?? null;
        $status = $var->status ?? null;

       


        if($error == "The cardholders name doesn't match the name on the BVN provided"){

            $name = Auth::user()->first_name. "  ".Auth::user()->last_name;

            return response()->json([
                'status' => true,
                'message' => "$name does not match with the name on the BVN provided",
            ], 500);

        }


        if($error == "A cardholder already exists with this BVN"){

            return response()->json([
                'status' => true,
                'message' => 'Bvn has been successfully verified',
            ], 200);

        }


        // $id = $var[0]->id;
        if ($status == "success") {

            User::where('id', Auth::user()->id)
                ->update([
                    'identification_image' => $file_url,
                    'card_holder_id' => $var->data->cardholder_id,
                    'is_kyc_verified' => 1,
                    'is_bvn_verified' => 1,
                    'is_identification_verified' => 2,
                    'bvn'=> $identity_number,


                ]);

            $message = " BVN Verification Successful |"  . Auth::user()->first_name . " " .  Auth::user()->last_name;
            send_notification($message);

            return response()->json([
                'status' => true,
                'message' => 'Bvn has been successfully verified',
            ], 200);
        }

        $alert = " Bvn verification Error |"  . Auth::user()->first_name . " " .  Auth::user()->last_name . "| $error";
        send_notification($alert);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }

    public function upload_identity(request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'utility_bill' => 'required',
                'identification_image' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => $this->failed,
                    'message' => $validator->messages()->first(),
                ], 500);
            }


            $is_identification_verified = Auth::user()->is_identification_verified;
            $ver = new Verification();
            $ver->name = Auth::user()->first_name. " ".Auth::user()->last_name;
            $ver->phone = Auth::user()->phone ?? null;
            $ver->phone = Auth::user()->email ?? null;
            $ver->user_id = Auth::user()->id ?? null;
            $ver->save();



            if ($is_identification_verified == 2) {

                return response()->json([
                    'status' => $this->success,
                    'message' => "We are still verifying your profile, Please wait",
                ], 200);
            }

            $file = $request->file('utility_bill');
            $utility_bill_fileName = $file->getClientOriginalName();
            $destinationPath = public_path() . 'upload/utilitybill';
            $request->utility_bill->move(public_path('upload/utilitybill'), $utility_bill_fileName);

            $file = $request->file('identification_image');
            $identification_image_fileName = $file->getClientOriginalName();
            $destinationPath = public_path() . 'upload/identification_image';
            $request->identification_image->move(public_path('upload/identification_image'), $identification_image_fileName);

            // $file = $request->file('selfie');
            // $selfie_fileName = $file->getClientOriginalName();
            // $destinationPath = public_path() . 'upload/selfie';
            // $request->selfie->move(public_path('upload/selfie'), $selfie_fileName);

            $update = User::where('id', Auth::id())
                ->update([
                    // 'image' => $selfie_fileName,
                    'utility_bill' => $utility_bill_fileName,
                    'identification_image' => $identification_image_fileName,
                    'is_identification_verified' => 2,
                ]);

            $message = "New upload of identity image from" . " " . Auth::user()->first_name. " | " .Auth::user()->last_name ;
            send_notification($message);

            return response()->json([
                "status" => $this->success,
                "message" => "Identification uploaded successful, Your request will be reviewed.",
            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function update_bank_info(request $request)
    {

        try {

            $bank_code = $request->bank_code;
            $account_number = $request->account_number;
            $account_name = $request->account_name;

            $update = User::where('id', Auth::id())
                ->update([
                    'c_account_number' => $account_number,
                    'c_account_name' => $account_name,
                    'c_bank_code' => $bank_code,

                ]);

            return response()->json([
                'status' => $this->success,
                'message' => "Account has been successfully updated",

            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function update_account_info(request $request)
    {

        try {

            $first_name = $request->first_name;
            $last_name = $request->last_name;
            $address = $request->address;
            $state = $request->state;
            $city = $request->city;
            $lga = $request->lga;

            $update = User::where('id', Auth::id())
                ->update([

                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'address_line1' => $address,
                    'state' => $state,
                    'city' => $city,
                    'lga' => $lga,

                ]);

            return response()->json([
                'status' => $this->success,
                'message' => "Account has been successfully updated",

            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function update_business(request $request)
    {

        try {

            $b_name = $request->b_name;
            $b_number = $request->b_number;
            $b_address = $request->b_address;

            $update = User::where('id', Auth::id())
                ->update([

                    'b_name' => $b_name,
                    'b_number' => $b_number,
                    'b_address' => $b_address,

                ]);

            return response()->json([
                'status' => $this->success,
                'message' => "Business Details has been successfully updated",

            ], 200);
        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }

    public function forgot_pin(Request $request)
    {

        try {

            $email = $request->email;

            if (Auth::user()->email != $email) {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'Please enter the email attached to this acccount',

                ], 500);
            }

            $check = User::where('email', $email)
                ->first()->email ?? null;

            $first_name = User::where('email', $email)
                ->first()->first_name ?? null;

            if ($check == $email) {

                //send email
                $data = array(
                    'fromsender' => 'noreply@enkpayapp.enkwave.com', 'EnkPay',
                    'subject' => "Reset Pin",
                    'toreceiver' => $email,
                    'first_name' => $first_name,
                    'link' => url('') . "/reset-pin/?email=$email",
                );

                Mail::send('emails.notify.pinlink', ["data1" => $data], function ($message) use ($data) {
                    $message->from($data['fromsender']);
                    $message->to($data['toreceiver']);
                    $message->subject($data['subject']);
                });

                return response()->json([
                    'status' => $this->success,
                    'message' => 'Check your inbox or spam for instructions',
                ], 200);
            } else {

                return response()->json([

                    'status' => $this->failed,
                    'message' => 'User not found on our system',

                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => $this->failed,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function view_agent_account(Request $request)
    {

        // try {


        $serial_no = $request->SerialNumber;


        $check_serial = Terminal::where('serial_no', $serial_no)
            ->first()->serial_no ?? null;


        if ($check_serial == null) {

            return response()->json([
                'status' => $this->failed,
                'message' => "Account no available on ENKPAY",
            ], 500);
        }

        $user_id = Terminal::where('serial_no', $serial_no)
            ->first()->user_id;




        $firstName = User::where('id', $user_id)
            ->first()->first_name ?? null;


        $lastName = User::where('id', $user_id)
            ->first()->last_name ?? null;

        $bvn = User::where('id', $user_id)
            ->first()->bvn ?? null;

        $b_name = User::where('id', $user_id)
            ->first()->v_account_name ?? null;


        $accountNumber = VirtualAccount::where('user_id', $user_id)
            ->where('serial_no', $serial_no)
            ->first()->v_account_no ?? null;

        $bankName = VirtualAccount::where('user_id', $user_id)
            ->where('serial_no', $serial_no)
            ->first()->v_bank_name ?? null;

        $name = VirtualAccount::where('user_id', $user_id)
            ->where('serial_no', $serial_no)
            ->first()->v_account_name ?? null;

        $data = User::where('id', $user_id)->first();

        // if($b_name == null){
        //     $name = $firstName." ".$lastName;
        // }else{
        //     $name = $b_name;
        // }

        $data_array = array();
        $data_array[0] = [
            "firstName" => $name,
            //"lastName" => $data->last_name,
            "bvn" => $bvn,
            "accountNumber" => $accountNumber,
            "bankName" => $bankName,
        ];

        return response()->json([
            'code' => 200,
            'message' => "success",
            'data' => $data_array,

        ], 200);

        // } catch (\Exception$th) {
        //     return $th->getMessage();
        // }

    }



    public function delete_account(request $request)
    {


        $cr = new DeletedUser();
        $cr->phone = Auth::user()->phone;
        $cr->email = Auth::user()->email;
        $cr->bvn = Auth::user()->bvn;
        $cr->save();

        User::where('id', Auth::id())->delete();

        $request->user()->token()->revoke();


        return response()->json([
            'status' => true,
            'message' => "We are not happy to see you leave our platform",
        ], 200);
    }
}
