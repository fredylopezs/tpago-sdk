<?php

namespace FMLS\TPago;

use FMLS\TPago\TPagoClient;
use FMLS\TPago\Exceptions\TPagoException;

class Payment {
    private $client;

    public function __construct(TPagoClient $client) {
        $this->client = $client;
    }

     // Método para generar links de pago
     public function generateLink($amount, $description = "", $referenceId = null) {
        $endpoint = "/links/generate-payment-link";
        $data = [
            'amount' => $amount,
            'description' => $description
        ];
        if ($referenceId) {
            $data['reference_id'] = $referenceId;
        }
        $response = $this->client->request('POST', $endpoint, $data);
        if ($response['status'] === 'success') {
            return $response;
        } else {
            throw new TPagoException($response['messages'][0]['key']);
        }
    }

    public function revert($hook_alias) {
        $endpoint = "/payments/revert/$hook_alias";
        $response = $this->client->request('PUT', $endpoint);

        if ($response['status'] === 'success' && $response['reverse']['status'] === 'success') {
            return $response['reverse'];
        } else {
            throw new TPagoException($response['payment']);
        }
    }
}