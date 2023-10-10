

<?php


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;




if(!function_exists('errand_api_key')){

    function errand_api_key(){

        try {

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.errandpay.com/epagentauth/api/v1/login',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "loginToken": "info@enkwave.com",
          "password": "Tolulope2580@"
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);


        $var = json_decode($var);




        $response1 = $var->data->accessToken ?? null;
        $exp = $var->data->expiresIn ?? null;

        $respose2 = 'ERA 001 Please try again later';
        $response3 =  $var->error->message ?? null;



        if($var->code == 200){
            return $response1;

        }

        if($var->code == 400){
            return $response3 ;
        }

        return $respose2;



    } catch (\Exception $th) {
        return $th->getMessage();
    }


    }

}

if(!function_exists('errand_id')){

    function errand_id(){

        try {

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.errandpay.com/epagentauth/api/v1/login',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "loginToken": "info@enkwave.com",
          "password": "Tolulope2580@"
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));

        $var = curl_exec($curl);
        curl_close($curl);


        $var = json_decode($var);



        $response1 = $var->data->id ?? null;

        $respose2 = 'ERA 001 Please try again later';
        $response3 =  $var->error->message ?? null;



        if($var->code == 200){
            return $response1;

        }

        if($var->code == 400){
            return $response3 ;
        }

        return $respose2;



    } catch (\Exception $th) {
        return $th->getMessage();
    }


    }

}


