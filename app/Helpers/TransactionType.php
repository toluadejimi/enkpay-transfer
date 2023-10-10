
<?php


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;

if(!function_exists('transacation_type')){



    function transacation_type($type){

        try {


            if($type == 'BLE1'){

                return "Balance check";
            }





        } catch (\Exception $th) {
            return $th->getMessage();
        }
    }




}
