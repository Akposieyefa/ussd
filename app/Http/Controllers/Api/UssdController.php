<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UssdController extends Controller
{
    public function __construct() {}

    public function handleUssd(Request $request)
    {
        \Log::info("************ USSD *****************");
        \Log::info($request->all());
        \Log::info("************ USSD *****************");

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
                    \Log::info($arr[5]);
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


}
