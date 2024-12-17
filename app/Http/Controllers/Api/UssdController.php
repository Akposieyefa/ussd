<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;


class UssdController extends Controller
{
    public function __construct() {}

    public function handleUssd(Request $request)
    {
        Log::info("************ USSD *****************");
        Log::info($request->all());
        Log::info("************ USSD *****************");

        // Get user input from the USSD request
        $userInput = $request->input('text');
        $sessionId = $request->input('sessionId');
        $phoneNumber = substr($request->input('phoneNumber'), 4);
        $phoneNumber = '0'.$phoneNumber;
        $serviceCode = $request->input('serviceCode');

        // Process the user input
        $response = $this->processUssdRequest($userInput, $sessionId, $phoneNumber, $serviceCode);

        // Return the USSD response
        return response($response)->header('Content-Type', 'text/plain');
    }

    private function processUssdRequest($userInput, $sessionId, $phoneNumber, $serviceCode)
    {
      
      $customer = DB::table('users')->where('phone_number', '=', $phoneNumber)->first();
      
      if(!$customer) {
        return "END Signup with VETKONECT mobile app to use this service.";
      }
      
      
        if(!$customer->accounts) {
            return "END Account not found.";
        }


        $amount = substr($userInput, 4);
        $accountNumber = substr($userInput, 6);
        $transactionPin = substr($userInput, 8);
        $network = null;
        $operation = null;

        if(!$sourceAccount) {
          return "END Account not found.";
        }

        $arr = explode('*', $userInput);
        \Log::info($arr);

        switch (substr($userInput, 0, 1)) {
            case '1':
                $operation = 'account_balance';
                break;
            case '2':
                $operation = 'fund_transfer';
                if(count($arr) > 4) {
                    $transactionPin = $arr[4];
                }
                break;
            case '3':
                $operation = 'bills_payment';
                if(count($arr) > 5) {
                    Log::info($arr[5]);
                    $transactionPin = $arr[5];
                }
                break;
            
            default:
                $operation = null;
                break;
        }
        
        if(strlen($userInput) > 4) {
            $amount = (float) explode('*', $userInput)[2];
        }

        if(strlen($userInput) > 5) {
            // $arr = explode('*', $userInput);
            if(count($arr) > 3) {
                $accountNumber = $arr[3];
            }
        }

        // Process USSD logic based on user input
        switch ($userInput) {
            case '':
                // Initial screen, show a welcome message
                $response = "CON Welcome to VetKonect $customer->first_name \n";
                $response .= "1. Register\n";
                $response .= "2. Veterinary Services\n";
                $response .= "3. Training & Resource\n";
                $response .= "4. Vaccination Schedule";
                $response .= "5.  Call Support/Helpline";
                $response .= "0. Exit";
                break;

            case '1':
                // Registration
                $response = "CON Register as \n";
                $response .= "1. Veterinarian/VPP \n";
                $response .= "2. Livestock Farmer \n";
                $response .= "3. Pet Owner \n";
                $response .= "4. Vendor \n";
                break;
            case '1*1': 
                $response = "CON Enter your name \n";
                break;
            case '1*2' : 
                $response = "CON Enter your name \n";
                break;
            case '1*3' : 
                $response = "CON Enter your name \n";
                break;
            case '1*4' : 
                $response = "CON Enter your name \n";
                break;

            case '2':
                // Veterinary Service
                $response = "CON Which Veterinary service do you need  \n";
                $response .= "1. Request Vet Visit  \n";
                $response .= "2. Emergency Services \n";
                $response .= "3. Health Tips \n";
                $response .= "0. Back \n";
                break;

            case '2*1':
                $response = "CON Enter reason for visit:";
                break;

            case '2*2':
                $response = "CON Enter emergency reason:"; 
                break;

            case '2*3':
                $response = "CON Enter health tips:"; 
                break;

            case '3':
                // Training Resource
                $operation = 'bills_payment';
                $response = "CON Training & Resources \n";
                $response .= "1. Upcoming Training \n";
                $response .= "2. Access Resources \n";
                break;

            case '3*1':
                $response = "CON Training Schedule";
                //check database for data
                $response .= "1. Poultry Farming Basics - 20th July \n";
                $response .= "2. Advanced Cattle Care - 25th July \n";
                break;

            case '3*1*1':
                $response = "CON Enter phone number";
                break;

            case '3*1*'.$amount.'*'.$accountNumber:
                $operation = 'bills_payment';
                  $response = "CON Select Network \n";
                  $response .= "1. GLO \n";
                  $response .= "2. MTN \n";
                  $response .= "3. Airtel \n";
                  $response .= "4. 9Mobile \n";
                break;

            case '3*1*'.$amount.'*'.$accountNumber.'*1':
            case '3*1*'.$amount.'*'.$accountNumber.'*2':
            case '3*1*'.$amount.'*'.$accountNumber.'*3':
            case '3*1*'.$amount.'*'.$accountNumber.'*4':
                    $response = "CON You are sending N".number_format($amount, 2)." worth of airtime to $accountNumber \n Enter your 4 digits transaction PIN:";
                break;

            case '3*1*'.$amount.''.$accountNumber.'*1'.$transactionPin:
            case '3*1*'.$amount.''.$accountNumber.'*2'.$transactionPin:
            case '3*1*'.$amount.''.$accountNumber.'*3'.$transactionPin:
            case '3*1*'.$amount.''.$accountNumber.'*4'.$transactionPin:
              if($userInput === '3*1*'.$amount.''.$accountNumber.'*1'.$transactionPin) {
                $network = 'glo';
              }
              elseif($userInput === '3*1*'.$amount.''.$accountNumber.'*2'.$transactionPin) {
                $network = 'mtn';
              }
              elseif($userInput === '3*1*'.$amount.''.$accountNumber.'*3'.$transactionPin) {
                $network = 'airtel';
              }
              elseif($userInput === '3*1*'.$amount.''.$accountNumber.'*4'.$transactionPin) {
                $network = '9mobile';
              }
                $result = $this->handleBillsPayment($amount, $accountNumber, 'airtime', $sourceAccount, $network);
                if(isset($result['data'])) {
                    $response = "END Airtime purchase successful ";
                }
                else {
                    $response = "END ".$result['message'];
                }
                break;
            case '3*2':
                $response = "END Service Unavailable";
                break;
            default:
                // Invalid input
                $response = "END Invalid input. Please try again.";
                break;
        }

        return $response;
    }

    private function getAccountBalance($customer, $account_id)
    {
        // Implement logic to get account balance
        $account = $this->safehaven->getAccountById($account_id);

        if(isset($account["data"]["accountBalance"])) {
            return number_format($account["data"]["accountBalance"], 2);
            // return number_format(($account["data"]["balance"] / 100), 2);
        }
        return 'Service unavailable';
    }

    private function handleBankTransfer($customer, $amount, $accountNumber, $bank) {
        
    }

    private function handleAccountValidation($accountNumber, $type) {
        if(strtolower($type) === 'internal') {
            $customer = Customer::where('tag', strtolower($accountNumber))->first();
            return $customer ? $customer->name : 'not_found';
        }
        elseif(strtolower($type) === 'bank_transfer') {
            $result = $this->safehaven->resolveAccount($accountNumber, env('HASHIT_SAFE_HAVEN_BANK_CODE'));
            if(isset($result['data'])) {
                return $result['data']['owner'];
            }
        }
    }

    private function handleInternalTransfer($amount, $username, $sessionId, $account) {
        $data = [
            'amount' => $amount,
            'from_account_id' => $account->reference,
            'username' => $username,
            'sub_type' => 'inter_account',
            'narration' => 'Transfer',
            'reference' => 'ussd_'.$sessionId,
        ];
        return $this->payment->internalTransfer($data);
    }

    private function validateTransactionPin($value, $customer) {
        if(\Hash::check($value, $customer->transaction_pin)) {
            return true;
        }
        return false;
    }

    private function handleBillsPayment($amount, $accountNumber, $type, $sourceAccount, $network) {
        if(strtolower($type) === 'airtime') {
            $data = [
                'service' => 'airtime',
                'phone_number' => $accountNumber,
                'amount' => $amount,
                'network' => $network,
                'reference' => "airtime_ussd_".\Str::random(10),
                'customer_id' => $sourceAccount->customer_id,
            ];
            // return ['message' => 'Service unavailable'];
            return $this->bills->billsPayment($data);
        }
    }

    public function getBanks() {
      return $this->safehaven->getUSSDBankList()["data"] ?? [];
    }

    public function testUssd(Request $request)
    {
        // return Session::flush();
        // Session::all();
        // return $reg_step = Session::get('reg_step') ?? null;
        Log::info("************ USSD *****************");
        Log::info($request->all());
        Log::info("************ USSD *****************");

        // Get user input from the USSD request
        $sessionSet = null;
        $userInput = $request->input('text');
        $sessionId = $request->input('sessionId');
        $phoneNumber = substr($request->input('phoneNumber'), 4);
        $phoneNumber = '0'.$phoneNumber;
        $serviceCode = $request->input('serviceCode');
        
        // Check if Session set, otherwise Set
        if(!Session::has('session_id')) {
            Session::put('session_id', $sessionId);
        }

        // Process the user input
        $response = $this->processActions($userInput, $sessionId, $phoneNumber, $serviceCode);

        // Return the USSD response
        return response($response)->header('Content-Type', 'text/plain');
    }

    private function processActions($userInput, $sessionId, $phoneNumber, $serviceCode)
    {
        $last = substr($userInput, strrpos($userInput, '*') + 1);
        $lastInput = explode('*', $userInput);
        $customer = DB::table('users')->where('phone', '=', $phoneNumber)->first();
        $customerName = $customer->name ?? null;
        $sessionSet = null;
        
        if(!$customer) {
          return "END Signup with VETKONECT mobile app to use this service.";
        }

        /* if(Hash::check($sessionId, Session::get('session_id'))){
            $sessionSet = true;
        } */

        switch($userInput){

            /**
             * Main Menu
             */
            case '':
                $response = "CON Welcome to Vet Konect '{$customerName}' \n";
                $response .= "1. Register \n";
                $response .= "2. Veterinary Services \n";
                $response .= "3. Training & Resources \n";
                $response .= "4. Vaccination Schedule \n";
                $response .= "5. Call Support/Helpline \n";
                $response .= "0. Exit \n";
                break;
            
            /**
             * 1. Register
             */
            case '1':
                $response = "CON Register \n";
                $response .= "1. Veterinarian/VPP \n";
                $response .= "2. Livestock Farmer \n";
                $response .= "3. Pet Owner \n";
                $response .= "4. Vendor \n";
                $response .= "0. Back \n";
                break;
            
            /**
             * 1*0 => Back to Main Menu
             */
            case '1*0':
                $response = "CON Welcome to Vet Konect '{$customerName}' \n";
                $response .= "1. Register \n";
                $response .= "2. Veterinary Services \n";
                $response .= "3. Training & Resources \n";
                $response .= "4. Vaccination Schedule \n";
                $response .= "5. Call Support/Helpline \n";
                $response .= "0. Exit \n";
                break;
            
            // *1 => Register Veterinarian/VPP
            case '1*1':
                $response = "CON Veterinarian/VPP \n";
                $response .= "Enter Name \n";
                Session::put(['reg_step1' => 1]);
                break;
            
            // *1* Input => Accepts Name
            case "1*1*".$last:
                /* $regStep1 = Session::get('reg_step1');
                $regStep2 = Session::get('reg_step2');
                $regStep3 = Session::get('reg_step3');
                $regStep4 = Session::get('reg_step4');
                if ($sessionSet != false){
                    switch()
                } else {
                    $response = "END Error in Processing \n";
                    $response .= "Operation Cancelled \n";

                } */
                if(Session::get('reg_step1') == 1 && Session::get('session_id') == $sessionId){
                    $response = "CON Veterinarian/VPP \n";
                    $response .= "Enter Phone Number \n";
                    Session::put(['reg_step2' => true]);
                    // Session::forget('reg_step1');

                } else if(Session::get('reg_step2') != false && $sessionSet != false){
                    $response = "CON Veterinarian/VPP \n";
                    $response .= "Enter License number \n";
                    Session::put(['reg_step3' => true]);
                    Session::forget('reg_step2');

                } else if(Session::get('reg_step3') != false && $sessionSet != false){
                    $response = "CON Veterinarian/VPP \n";
                    $response .= "Enter Location \n";
                    Session::put(['reg_step4' => true]);
                    Session::forget('reg_step3');

                } else if(Session::get('reg_step4') != false && $sessionSet != false){
                    $response = "END Veterinarian/VPP \n";
                    $response .= "Registration Successful! \n";
                    $response .= "0. Exit \n";
                    Session::flush();

                } else{
                    $response = "END Error in Processing \n";
                    $response .= "Operation Cancelled \n";
                }
                break;
            
            // *0 => Exit
            case '1*1*0':
                $response = "END Thank you '{$customerName}' for using Vet Konect. Goodbye!";
                break;
            
            // *2 => Livestock Farmer
            case '1*2':
                $response = "CON Livestock Farmer \n";
                $response .= "Enter Name \n";
                break;
            
            // *3 => Pet Owner
            case '1*3':
                $response = "CON Pet Owner \n";
                $response .= "Enter Name \n";
                break;
            
            // *4 => Vendor
            case '1*4':
                $response = "CON Vendor \n";
                $response .= "Enter Name \n";
                break;
                
            /**
             * 2. Veterinary Services
             */
            case '2':
                $response = "CON Veterinary Services \n";
                $response .= "1. Request Vet Visit \n";
                $response .= "2. Emergency Services \n";
                $response .= "3. Health Tips \n";
                $response .= "0. Exit \n";
                break; 
            
            // *1 => Request Vet Visit
            case '2*1':
                $response = "CON Request Vet Visit \n";
                $response .= "Enter Location \n";
                break;
            
            // *2 => Health Tips
            case '2*2':
                $response = "CON Emergency Services \n";
                $response .= "Call our emergency hotline: 08066459317 \n";
                break;
            
            // *3 => Health Tips
            case '2*3':
                $response = "CON Select Animal Type \n";
                $response .= "1. Poultry \n";
                $response .= "2. Sheep/Goats \n";
                $response .= "3. Cattle \n";
                $response .= "4. Dogs/Cats \n";
                $response .= "5. Fish \n";
                $response .= "6. Others (type) \n";
                $response .= "0. Back \n";
                break;
            
            // *2*1 => Poultry
            case '2*3*1':
                $response = "CON Poultry \n";
                $response .= "1. Disease Prevention Tips \n";
                $response .= "2. Feeding Guidelines \n";
                $response .= "0. Back \n";
                break;
            
            // *2*2 => Sheep/Goats
            case '2*3*2':
                $response = "CON Sheep/Goats \n";
                $response .= "1. Disease Prevention Tips \n";
                $response .= "2. Feeding Guidelines \n";
                $response .= "0. Back \n";
                break;
            
            // *2*3 => Cattle
            case '2*3*3':
                $response = "CON Cattle \n";
                $response .= "1. Disease Prevention Tips \n";
                $response .= "2. Feeding Guidelines \n";
                $response .= "0. Back \n";
                break;
            
            // *2*1 => Dogs/Cats
            case '2*3*4':
                $response = "CON Dogs/Cats \n";
                $response .= "1. Disease Prevention Tips \n";
                $response .= "2. Feeding Guidelines \n";
                $response .= "0. Back \n";
                break;
            
            // *2*1 => Fish
            case '2*3*5':
                $response = "CON Fish \n";
                $response .= "1. Disease Prevention Tips \n";
                $response .= "2. Feeding Guidelines \n";
                $response .= "0. Back \n";
                break;
            
            // *2*1 => Others (type)
            case '2*3*6':
                $response = "CON Others (type) \n";
                $response .= "1. Disease Prevention Tips \n";
                $response .= "2. Feeding Guidelines \n";
                $response .= "0. Back \n";
                break;
                
            /**
             * 3. Training & Resources
             */
            case '3':
                $response = "CON Training & Resources \n";
                $response .= "1. Upcoming Training \n";
                $response .= "2. Access Resources \n";
                $response .= "0. Back \n";
                break;
            
            // *1 => Upcoming Training
            case '3*1':
                $response = "CON Training Schedule \n";
                $response .= "1. Poultry Farming Basics - 20th July \n";
                $response .= "2. Advanced Cattle Care - 25th July \n";
                $response .= "0. Back \n";
                break;
            
            // *2 => Access Resources
            case '3*2':
                $response = "CON Access Resources \n";
                $response .= "1. Download eBooks \n";
                $response .= "2. Watch Videos \n";
                $response .= "0. Back \n";
                break; 
                
            /**
             * 4. Vaccination Schedule
             */
            case '4':
                $response = "CON Vaccination Schedule \n";
                $response .= "1. Check Schedule \n";
                $response .= "2. Book Vaccination \n";
                $response .= "0. Exit \n";
                break;
            
            // *2 => Vaccination Schedule
            case '4*1':
                $response = "CON (Check Schedule) Enter Type of Animal \n";
                $response .= "1. Poultry \n";
                $response .= "2. Sheep/Goats \n";
                $response .= "3. Cattle \n";
                $response .= "4. Dogs/Cats \n";
                $response .= "5. Fish \n";
                $response .= "0. Back \n";
                break; 
            
            // *2 => Book Vaccination
            case '4*2':
                $response = "CON (Book Vaccination) Enter Type of Animal \n";
                $response .= "1. Poultry \n";
                $response .= "2. Sheep/Goats \n";
                $response .= "3. Cattle \n";
                $response .= "4. Dogs/Cats \n";
                $response .= "5. Fish \n";
                $response .= "0. Back \n";
                break;
                
            /**
             * 5. Call Support/Helpline
             */
            case '5':
                $response = "CON Support \n";
                $response .= "1. Contact Us \n";
                $response .= "2. FAQ \n";
                $response .= "0. Exit \n";
                break;
            
            // *1 =>  Contact Us
            case '5*1':
                $response = "CON Call our support line: 08066459317 \n";
                break;
            
            // *2 =>  FAQ
            case '5*2':
                $response = "CON FAQ \n";
                $response .= "1. How to Register \n";
                $response .= "2. How to Book a Vet Visit \n";
                $response .= "3. How to Access Resources \n";
                $response .= "0. Back \n";
                $response .= "0. Exit \n";
                break;
            
            /**
             * 0. End Session
             */
            case '0':
                $response = "END Thank you '{$customerName}' for using Vet Konect. Goodbye!";
                break;
            
            /**
             * 'Invalid Input' Default
             */
            default:
                // Invalid input
                $response = "END Invalid input. Please try again.";
                break;
        }
        
        return $response;


    }


}
