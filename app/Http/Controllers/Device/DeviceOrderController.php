<?php

namespace App\Http\Controllers\Device;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Wallet;

use App\Models\Transaction;
use App\Models\TDevice;
use App\Models\OrderDevice;
use App\Models\Bank;
use App\Models\PickUpLocation;





use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class DeviceOrderController extends Controller
{

    public $success = true;
    public $failed = false;

    public function order_device(Request $request){

    try{

        $fullname = $request->fullname;
        $address = $request->address;
        $state = $request->state;
        $lga = $request->lga;
        $phone_no = $request->phone_no;


        $order_id = 'EK-'.random_int(1000, 9999);


        $order_amount =  TDevice::where('id', 1)->first()->amount;

        $device = new OrderDevice();
        $device->fullname =$fullname;
        $device->address =$address;
        $device->state =$state;
        $device->phone_no =$phone_no;
        $device->lga =$lga;
        $device->order_amount =$order_amount;
        $device->order_id =$order_id;
        $device->status = 0;
        $device->save();


        $bank = Bank::where('id', 1)
        ->first();




        return response()->json([
            'status'=> $this->success,
            'message' => 'Your order has been successfully placed',
            'payment_ref' => $order_id,
            'bank' => $bank,
            'amount' => $order_amount
        ],200);





    } catch (\Exception $th) {
        return $th->getMessage();
    }

}


public function pos_properties(){


    return response()->json([

        'status' => true,
        'status_code' => 200

    ]);


}

public function bank_details(){


    try{
    $bank = Bank::all();

    return response()->json([
        'status'=> $this->success,
        'data' => $bank
    ],200);

} catch (\Exception $th) {
    return $th->getMessage();
}


}

public function all_pick_up_location(Request $request){

    try{

    $all_pick_up = PickUpLocation::where('status', 1)->get();


    return response()->json([
        'status'=> $this->success,
        'data' => $all_pick_up,

    ],200);

    } catch (\Exception $th) {
        return $th->getMessage();
    }


}

public function state_pick_up_location(Request $request){

    try{

    $state = $request->state;

    $state_pick_up = PickUpLocation::where([
        'state' => $state,
        'status' => 1
    ])->get();



    return response()->json([
        'status'=> $this->success,
        'data' => $state_pick_up,
    ],200);

    } catch (\Exception $th) {
        return $th->getMessage();
    }


}


public function lga_pick_up_location(Request $request){

    try{

    $lga = $request->lga;

    $lga_pick_up = PickUpLocation::where([
        'lga' => $lga,
        'status' => 1
    ])->get();

    return response()->json([
        'status'=> $this->success,
        'data' => $lga_pick_up
    ],200);

    } catch (\Exception $th) {
        return $th->getMessage();
    }







}


public function order_complete(Request $request){

    try{


        $ref_no = $request->ref_no;


        $name = OrderDevice::where('order_id', $ref_no)
        ->first()->fullname ?? null;

        $phone = OrderDevice::where('order_id', $ref_no)
        ->first()->phone_no ?? null;


        if($name == null){

            return response()->json([
                'status'=> $this->failed,
                'message' => 'Order not found'
            ],500);

        }

        $order = OrderDevice::where('order_id', $ref_no)
        ->update([

            'status' => 1,

        ]);

        $message = "New Terminal Order from ". " ".$name.  " ". "Phone Number - $phone" ;

        send_notification($message);

    return response()->json([
        'status'=> $this->success,
        'message' => 'We have received your request, our representative will get back to you shortly'
    ],200);

    } catch (\Exception $th) {
        return $th->getMessage();
    }



}



}
