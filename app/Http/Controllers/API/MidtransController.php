<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Midtrans\Notification;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MidtransController extends Controller
{
    public function callback(){
        //Config Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        //Instance Midtrans Notification
        $notification = new Notification();

        //Assign Var for easy code
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        //Get Transaction Id
        $order = explode('-', $order_id);

        //Find Transaction by Id
        $transaction = Transaction::findOrFail($order[1]);

        //Handle Notification status Midtrans
        if($status == 'capture'){
            if($type == 'credit_card') {
                if($fraud == 'challenge'){
                    $transaction->status = 'PENDING';
                }else{
                    $transaction->status = 'SUCCESS';
                }
            }
        }
        else if($status == 'settlement'){
            $transaction->status = 'SUCCESS';
        }
        else if($status == 'pending'){
            $transaction->status = 'PENDING';
        }
        else if($status == 'deny'){
            $transaction->status = 'PENDING';
        }
        else if($status == 'expire'){
            $transaction->status = 'CANCELLED';
        }
        else if($status == 'cancel'){
            $transaction->status = 'CANCELLED';
        }

        //Save Transaction
        $transaction->save();

        //Return response for Midtrans
        return response()->json([
            'meta' => [
                'code' => 200,
                'message' => 'Midtrans Notification Success!'
            ]
        ]);
    }
}
