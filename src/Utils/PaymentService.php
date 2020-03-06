<?php

/*
 * This file is part of the DATICASH PROJECT
 *
 * (c) ewoniewonimerveil@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
  namespace App\Utils;
  use Braintree\Gateway;
 
class PaymentService{
    protected $brainAgent;
    
    public function __construct(){
        $this->brainAgent = new Gateway([
                                    'environment' => 'sandbox',
                                    'merchantId' => 'jnsyvy7gffhbhh86',
                                    'publicKey' => 'g23wbhvn8554fhg3',
                                    'privateKey' => '8cb4094182a175308234196bfa5a64a7'
                                ]);
                                echo "new brainservice created successfully... <br>";
    }
    
    public function charge($amount){
        $result = $this->brainAgent->transaction()->sale([
                    'amount' => $amount,
                    'paymentMethodNonce' => 'nonceFromTheClient',
                    'options' => [ 'submitForSettlement' => true ]
                ]);
                
                if ($result->success) {
                    print_r("success!: " . $result->transaction->id);
                } else if ($result->transaction) {
                    print_r("Error processing transaction:");
                    print_r("\n  code: " . $result->transaction->processorResponseCode);
                    print_r("\n  text: " . $result->transaction->processorResponseText);
                } else {
                    print_r("Validation errors: \n");
                    print_r($result->errors->deepAll());
                }
    }
}