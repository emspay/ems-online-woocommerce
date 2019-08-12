<?php

namespace GingerPayments\Payment\Client;

use Dotenv\Dotenv;

class EndpointResolver {

    /**
     * API endpoint EPAY
     */
    const ENDPOINT_EMS = 'https://api.online.emspay.eu/{version}/';


    public function __construct() {
        try {
            $dotenv = new Dotenv(__DIR__.'/../..');
            $dotenv->load();
        } catch (\Exception $e) {
            
        }
    }

    /**
     * @return string
     */
    public function getEndpointEms() {
        return false !== getenv('ENDPOINT_EMS') ? getenv('ENDPOINT_EMS') : self::ENDPOINT_EMS;
    }

}