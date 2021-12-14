<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ImageController;
use App\Models\Customers;
use App\Models\Winners;
use App\Models\Transactions;
use App\Models\Resevations;

define("MIN_TRANS_COUNT", 3);
define("MIN_TRANS_AMOUNT", 100);
//status of reservations table
define("AVAILABLE", 0);
define("REDEEMED", 1);
define("RESERVED",2);

class CampaignController extends Controller
{
    
    function validateEligibility(Request $req){

        try{

            $user_name = $req->user_name;

            //identifying whether user input a mobile numer OR email
            if(is_numeric($user_name)){

                //mobile related validations
                if(preg_match('/^[0-9]{8}+$/', $user_name)){
                    $customer_id = DB::table('customers')->where('contact_number', $user_name)->value('id');
                    if (!$customer_id) {
                        // user doesn't exist
                        return ['Success'=>false, "error"=>"Sorry you are not eligible to participate in this campaign"];  
                    }
                }else{
                    return ['Success'=>false, "error"=>"Please enter a valid mobile number"];
                }

            }else{
                //email related validations

                if(filter_var($user_name, FILTER_VALIDATE_EMAIL)){
                $customer_id = DB::table('customers')->where('email', $user_name)->value('id');
                if (!$customer_id) {
                        // user doesn't exist
                        return ['Success'=>false, "error"=>"Sorry you are not eligible to participate in this campaign"];  
                    }
                }else{
                    return ['Success'=>false, "error"=>"Please enter a valid email address"];    
                }

            }


            //validation to check the redeem attempt
            $is_redeemed = DB::table('winners')->where('customer_id', $customer_id)->value('customer_id');

            if($is_redeemed){
                return ['Success'=>false, "error"=>"You are allowed to redeem only one voucher"]; 
            }

            //check whether already 1000 vouchers are redeemed or not
            $count = DB::table('winners')->count();
            if($count == 1000){
                return ['Success'=>false, "error"=>"Sorry, Campaign is over..!"]; 
            }

            // checking transaction related conditions
            $to_date = date("Y-m-d H:i:s");
            $from_date = date("Y-m-d 00:00:00",strtotime('-30 days'));
            
            $records = DB::table('transactions')->where('customer_id', $customer_id)->whereBetween('transaction_at',[$from_date, $to_date])->get();

            if(isset($records) && !empty($records)){

                if(count($records) >= MIN_TRANS_COUNT){ 
                    $total = 0.0;
                    foreach($records as $record){
                        $total = $total + $record->total_spent;
                        
                    }

                    if($total >= MIN_TRANS_AMOUNT){

                        //reserve a vocher - change the status to 'Reserved' in the reservations table
                        DB::beginTransaction();

                        $voucher_id = DB::table('reservations')
                                    ->where('status', '=', AVAILABLE) 
                                    ->limit(1)
                                    ->lockForUpdate()
                                    ->value('voucher_id');

                        if(!$voucher_id){
                            // if no vouchers available to lock
                            DB::rollback();
                            return ['Success'=>false, "error"=>"System busy, please try again"]; 

                        }

                        $update_status = DB::table('reservations')
                                    ->where('voucher_id', $voucher_id)
                                    ->update(['status' => RESERVED, 'transaction_at' => date('Y-m-d H:i:s')]); 
                        
                        
                        DB::commit();
                        return ['Success'=>true, 'customer_id'=>$customer_id, 'voucher_id'=>$voucher_id, 'message'=>"please update your selfie within 10 minutes to redeem the gift voucher"];                

                    }else{
                        return ['Success'=>false, "error"=>"Sorry you are not eligible to participate in this campaign since you are not satisfying the transaction requirements [total spent less than 100]"]; 
                    }

                }else{
                    return ['Success'=>false, "error"=>"Sorry you are not eligible to participate in this campaign since you are not satisfying the transaction requirements.[Count less thn 3]"]; 
                }

            }else{
                return ['Success'=>false, "error"=>"Sorry you are not eligible to participate in this campaign since you are not satisfying the transaction requirements [no transactions within 30 days] "];    
            }
        
        }catch (\Throwable $e) {
            DB::rollback();
            throw $e;
            return ['Success'=>false, "error"=>"System error"];
        }          

    }



    function redeemVoucher(Request $req){
        
        try{
            $customer_id = $req->customer_id;
            $voucher_id = $req->voucher_id;
            $image = $req->image;

            //checking image validity
            $img_controller = new ImageController;
            $response = $img_controller->validateImage($image);

            //if ImageValidation API fails showing an error message
            if(isset($response['success']) && !($response['success'])){
                $update_reservation = DB::table('reservations')
                                ->where('voucher_id', $voucher_id)
                                ->update(['status' => AVAILABLE, 'transaction_at' => date('Y-m-d H:i:s')]);

                return ['Success'=>false, "error"=>"Image validation failed"];
            }

            // time validation
            $to_time = date('Y-m-d H:i:s');
            $from_time =date("Y-m-d H:i:s",strtotime('-10 minutes'));

            $record = DB::table('reservations')->where('voucher_id', $voucher_id)->where('status', RESERVED)->whereBetween('transaction_at',[$from_time, $to_time])->value('status');

            if($record){

                $update_reservation = DB::table('reservations')
                                ->where('voucher_id', $voucher_id)
                                ->update(['status' => REDEEMED, 'transaction_at' => date('Y-m-d H:i:s')]);

                $insert_record = DB::table('winners')->insert([
                                    'voucher_id' => $voucher_id,
                                    'customer_id' => $customer_id
                                ]);  

                return ["Success"=>true, "voucher_id"=>$voucher_id, "message"=>"Congratulations…!, you have successfully redeemed a voucher"];

            }else{
                $update_reservation = DB::table('reservations')
                                ->where('voucher_id', $voucher_id)
                                ->update(['status' => AVAILABLE, 'transaction_at' => date('Y-m-d H:i:s')]);

                return ["success"=>false, "error"=>"You are failed to submit your selfie within 10 minutes. Please try again"];
            }

        } catch (\Throwable $e) {
                    DB::rollback();
                    throw $e;
                    return ['Success'=>false, "error"=>"System error"];
        }            
            
    }
    
}

