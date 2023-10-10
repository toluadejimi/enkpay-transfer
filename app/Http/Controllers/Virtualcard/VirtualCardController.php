<?php

namespace App\Http\Controllers\Virtualcard;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\Transactions;
use App\Models\User;
use App\Models\VCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VirtualCardController extends Controller
{


    public function verify_card_identity(Request $request)
    {


        $key = env('BKEY');

        if (Auth::user()->bvn == null) {

            return response()->json([
                'status' => true,
                'message' => 'please verify your account',
            ], 500);
        }

        if (Auth::user()->identification_image == null) {

            return response()->json([
                'status' => true,
                'message' => 'please verify your account',
            ], 500);
        }


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







        // User::where('id', Auth::user()->id)
        // ->update([
        //     'identification_type' => $request->identification_type,
        //     'identification_number' => $request->identification_number,
        //     'bvn' => $request->bvn,
        //     'identification_image' => $file_url,

        // ]);



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
                "bvn" => Auth::user()->bvn,

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



        // $id = $var[0]->id;
        if ($status == "success") {

            User::where('id', Auth::user()->id)
                ->update([
                    'identification_image' => $file_url,
                    'card_holder_id' => $var->data->cardholder_id,
                    'is_kyc_verified' => 1,
                    'is_identification_verified' => 1,
                    'status' => 2,


                ]);

            $message = " Vcard verification |"  . Auth::user()->first_name . " " .  Auth::user()->last_name;
            send_notification($message);

            return response()->json([
                'status' => true,
                'message' => 'Account has been successfully verified',
            ], 200);
        }

        $alert = " Vcard verification Error |"  . Auth::user()->first_name . " " .  Auth::user()->last_name . "| $error";
        send_notification($alert);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }





    public function fund_card(Request $request)
    {



        if (Auth::user()->status != 2) {

            $message = Auth::user()->first_name. " ".Auth::user()->last_name. " | Unverified Account trying to func card";
            send_notification($message);

            return response()->json([
                'status' => false,
                'message' => 'Please verify your account to enjoy enkpay full service',
            ], 500);
        }



        $set = Settings::first();
        $user = User::find(Auth::user()->id);
        $key = env('BKEY');

        $amount_to_charge = $request->amount + $set->ngn_rate;

        $user_wallet = User::where('id', Auth::id())->first()->main_wallet;

        $user_blance = Auth::user()->main_wallet;



        if (Auth::user()->main_wallet < $amount_to_charge ) {

            return response()->json([
                'status' => false,
                'message' => 'Account balance is insufficient, Fund your wallet',
            ], 500);
        }


        $e_ref = trx();



        $ref = "CAD-".random_int(1000000, 9999999);

        //fund card
        $get_card_id = VCard::select('*')->where('user_id', Auth::id())->first()->card_id;
        $amount_in_usd = round($request->amount / $set->ngn_rate * 100);

        $curl = curl_init();
        $data = [

            "card_id" => $get_card_id,
            "amount" => $amount_in_usd,
            "transaction_reference" => $e_ref,
            "currency" => "USD"

        ];
        $post_data = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cards/fund_card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "token: Bearer $key"
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $status = $var->status ?? null;
        $ref = $var->data->transaction_reference ?? null;
        $message = "Error from Virtual Card Funding" . "|" . $var->message ?? null;

        if ($status == 'success') {


            Vcard::where('card_id', $get_card_id)->update([

                'amount' => $amount_in_usd / 100,
            ]);


            $balance = $user_wallet - $amount_to_charge;
            User::where('id', Auth::id())->decrement('main_wallet', $amount_to_charge );

            $trasnaction = new Transactions();
            $trasnaction->user_id = Auth::id();
            $trasnaction->e_ref = $ref;
            $trasnaction->ref_trans_id = $ref;
            $trasnaction->transaction_type = "CardFunding";
            $trasnaction->title = "USD Card Funding";
            $trasnaction->type = "CardFunding";
            $trasnaction->amount = $amount_to_charge;
            $trasnaction->note = "USD CARD FUNDING | USD ".$amount_in_usd /100;
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->balance = $balance;
            $trasnaction->status = 1;
            $trasnaction->save();

            return response()->json([
                'status' => true,
                'message' => 'Your card has been funded successfully | USD $' . number_format($amount_in_usd / 100, 2),

            ], 200);


        } else {

            send_notification($message);
            return response()->json([
                'status' => false,
                'message' => 'Service not available at the moment, Please try again later',
            ], 500);
        }
    }




    public function block_card(request $request)
    {
        $set = Settings::first();
        $user = User::find(Auth::user()->id);
        $card = VCard::where('user_id', Auth::id())->first();

        $key = env('BKEY');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://issuecards.api.bridgecard.co/v1/issuing/cards/freeze_card?card_id=$card->card_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "token: Bearer " . env('BKEY')
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $status = $var->status ?? null;
        $message = "Error from V Card Fund" . "|" . $var->message ?? null;
        $error = $var->message ?? null;

        // $status ="success";

        if ($status == 'success') {

            VCard::where('user_id', Auth::id())->update([

                'status' => 2,

            ]);

            return response()->json([
                'status' => true,
                'message' => 'You card shas been successfully blocked',
            ], 200);
        }

        send_notification($message);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }


    public function unblock_card(request $request)
    {
        $set = Settings::first();
        $user = User::find(Auth::user()->id);
        $card = VCard::where('user_id', Auth::id())->first();

        $key = env('BKEY');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://issuecards.api.bridgecard.co/v1/issuing/cards/unfreeze_card?card_id=$card->card_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "token: Bearer " . env('BKEY')
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $status = $var->status ?? null;
        $message = "Error from V Card Fund" . "|" . $var->message ?? null;
        $error =  $var->message ?? null;



        // $status ="success";


        if ($status == 'success') {

            VCard::where('user_id', Auth::id())->update([

                'status' => 1,

            ]);

            return response()->json([
                'status' => true,
                'message' => 'You card has been successfully unblocked',
            ], 200);
        }

        send_notification($message);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }





    public function liquidate_card(Request $request)
    {

        if (Auth::user()->status != 2) {

            $message = Auth::user()->first_name. " ".Auth::user()->last_name. " | Unverified Account trying to liqidate card";
            send_notification($message);

            return response()->json([
                'status' => false,
                'message' => 'Please verify your account to enjoy enkpay full service',
            ], 500);
        }

        $set = Settings::first();
        $key = env('BKEY');
        $card = VCard::where('user_id', Auth::id())->first();
        $amt_in_naira = $set->w_rate * $request->amount;

        $amount_in_usd = round($request->amount / $set->w_rate) * 100;



        $curl = curl_init();
        $data = [

            "card_id" => $card->card_id,
            "amount" => $amount_in_usd,
            "transaction_reference" => random_int(1000000, 9999999),
            "currency" => "USD"

        ];
        $post_data = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cards/unload_card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "token: Bearer $key"
            ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $error = $var->message ?? null;
        $status = $var->status ?? null;






        if ($status == 'success') {
            // User::where('id', Auth::id())->increment('main_wallet', $amt_in_naira);

            $balance = User::where('id', Auth::id())->first()->main_wallet;

            //update Transaction
            $trasnaction = new Transactions();
            $trasnaction->user_id = Auth::id();
            $trasnaction->transaction_type = "CardWithdraw";
            $trasnaction->amount = $amt_in_naira;
            $trasnaction->note = "Card Liquidation | NGN " . number_format($amt_in_naira, 2);
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->balance = $balance;
            $trasnaction->status = 1;
            $trasnaction->save();

            return response()->json([
                'status' => true,
                'message' => 'Card has been successfully liquidated',
            ], 200);
        }



        $mymessage = "VCARD ERROR " . "|" . $error;
        send_notification($mymessage);

        return response()->json([
            'status' => false,
            'message' => "$error",
        ], 500);
    }



    public function create_details(Request $request)
    {


        $card = Vcard::where('user_id', Auth::id())->first() ?? null;

        if ($card == null) {

            return response()->json([
                'status' => false,
                'message' => "No card found",
            ], 500);
        } else {


            return response()->json([
                'status' => true,
                'card_number' => $card->MaskedPAN,
            ], 500);
        }
    }





    public function create_card(Request $request)
    {


        if (Auth::user()->status != 2) {

            $message = Auth::user()->first_name. " ".Auth::user()->last_name. " | Unverified Account trying to create card";
            send_notification($message);

            return response()->json([
                'status' => false,
                'message' => 'Please verify your account to enjoy enkpay full service',
            ], 500);
        }


        $user = User::find(Auth::user()->id);
        $key = Settings::first();
        $bkey = env('BKEY');
        $card_fee_ngn =  $key->ngn_rate * $key->virtual_createcharge;




        if (Auth::user()->main_wallet < $card_fee_ngn) {

            return response()->json([
                'status' => false,
                'message' => "Account balance is insufficient, Fund your wallet",
            ], 500);
        }


        $chk_card = VCard::where('user_id', $user->id)->first()->user_id ?? null;
        if ($chk_card == Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => "You already have a usd card",
            ], 500);
        }

        //create card


        $curl = curl_init();
        $data = array(
            "cardholder_id" => Auth::user()->card_holder_id,
            "card_type" => "virtual",
            "card_brand" => "Visa",
            "card_currency" => "USD",
            "meta_data" => array(
                "user_id" => Auth::id()
            ),
        );
        $post_data = json_encode($data);
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://issuecards.api.bridgecard.co/v1/issuing/cards/create_card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => 0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "token: Bearer $bkey"
            ),
        ));
        $var = curl_exec($curl);
        curl_close($curl);

        $var = json_decode($var);
        $status = $var->status ?? null;
        $message = "VCard Error | " .  $var->message ?? null;

        if ($status == "success") {

            //save card
            $vcard = new VCard();
            $vcard->user_id = Auth::id();
            $vcard->first_name = $request->first_name;
            $vcard->last_name = $request->last_name;
            $vcard->bg = $request->bg;
            $vcard->card_id = $var->data->card_id;
            $vcard->currency = $var->data->currency;
            $vcard->save();

            $balance = Auth::user()->main_wallet  - $card_fee_ngn;

            User::where('id', Auth::id())->decrement('main_wallet', $card_fee_ngn);


            $trasnaction = new Transactions();
            $trasnaction->user_id = Auth::id();
            $trasnaction->transaction_type = "CardCreation";
            $trasnaction->amount = $card_fee_ngn;
            $trasnaction->note = "USD Creation Fee | USD $key->virtual_createcharge ";
            $trasnaction->fee = 0;
            $trasnaction->e_charges = 0;
            $trasnaction->balance = $balance;
            $trasnaction->status = 1;
            $trasnaction->save();

            $message = "A card was created just now";
            send_notification($message);

            return response()->json([
                'status' => true,
                'message' => "Your card has successfully created",
            ], 200);
        } else {
            send_notification($message);
            return response()->json([
                'status' => false,
                'message' => "Card creation not available at the moment try again later",
            ], 500);
        }
    }


    public function card_details(Request $request)
    {

        $card_id = VCard::where('user_id', Auth::id())->first()->card_id ?? null;
        $set = Settings::where('id', 1)->first();
        $card = Vcard::where('user_id', Auth::id())->first() ?? null;


        $key = env('BKEY');


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://issuecards-api-bridgecard-co.relay.evervault.com/v1/issuing/cards/get_card_details?card_id=$card_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(

                "Content-Type: application/json",
                "token: Bearer $key",

            ),
        ));


        $var = curl_exec($curl);
        curl_close($curl);
        $var = json_decode($var);
        $amount = $var->data->balance ?? null;

        if ($card->masked_card == null) {

            Vcard::where('card_id', $card_id)->update([

                'masked_card' => $var->data->card_number,
                'cvv' => $var->data->cvv,
                'expiration' => $var->data->expiry_month. " / " .$var->data->expiry_year,
                'card_type' => $var->data->brand,
                'name_on_card' => $var->data->card_name,
                'amount' => $var->data->balance/100,
                'city' => $var->data->billing_address->billing_city,
                'state' => $var->data->billing_address->state,
                'address' => $var->data->billing_address->billing_address1,
                'zip_code' => $var->data->billing_address->billing_zip_code,
                'status' => 1

            ]);
        }



        if ($card->masked_card != null) {

            Vcard::where('card_id', $card_id)->update([
                'amount' => $var->data->balance/100

            ]);
        }


        if ($card == null) {

            $card_details = [];


        } else {

            $card_details = array([
                'card_number' => $card->masked_card,
                'cvv' => $card->cvv,
                'expiration' => $card->expiration,
                'card_type' => $card->card_type,
                'name_on_card' => $card->name_on_card,
                'amount' => $card->amount,
                'city' => $card->city,
                'state' => $card->state,
                'address' => $card->address,
                'zip_code' => $card->zip_code,
                'status' => $card->status,
            ]);
        }






        if ($card_id != null) {


            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://issuecards.api.bridgecard.co/v1/issuing/cards/get_card_transactions?card_id=$card_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "token: Bearer $key",
                ),
            ));

            $var = curl_exec($curl);
            curl_close($curl);
            $var = json_decode($var);
            $card_data = $var->data->transactions ?? null;


            if ($card_data == null) {
                $data =  [];
            } else {
                $data = $card_data;
            }
        } else {

            $data = [];
        }


        return response()->json([
            'status' => true,
            'creation_charge' => $set->virtual_createcharge,
            'rate' => "$set->ngn_rate",
            'w_rate' => $set->w_rate,
            'card_transaction' => $data,
            'card_details' => $card_details,

        ], 200);
    }
}
