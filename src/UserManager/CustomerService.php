<?php

/*
 * This file is part of the DATICASH PROJECT
 *
 * (c) ewoniewonimerveil@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @TODO: hash token before storage
 */

namespace App\UserManager;

use App\Exception\DatiException;
use GuzzleHttp\Client;

class CustomerService
{
    protected $client;
    public function __construct(Client $intranetClient){
        $this->client = $intranetClient;
    }
    
     public function createUser($data)
    { 
        $response = $this->client->request("POST", "/thirdparties/register_account", json_encode($data));
        echo $response;
    }

}
