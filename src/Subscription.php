<?php

namespace FMLS\TPago;

use FMLS\TPago\TPagoClient;
use FMLS\TPago\Exceptions\TPagoException;

class Subscription {
    private $client;

    public function __construct(TPagoClient $client) {
        $this->client = $client;
    }

     // Método para generar links de pago
     public function generateLink($params) {
        $endpoint = "/links/generate-subscription-link";
        $data = [
            'amount' => $params['amount'],
            'description' => $params['description'],
            'periodicity' => $params['periodicity'],
            'debit_day' => $params['debit_day'],
            'unlimited' => $params['unlimited']
        ];

        if (isset($params['reference_id'])) {
            $data['reference_id'] = $params['reference_id'];
        }

        if (isset($params['start_date'])) {
            $data['start_date'] = $params['start_date'];
        }

        if (isset($params['end_date'])) {
            $data['end_date'] = $params['end_date'];
        }

        if (isset($params['created_at'])) {
            $data['created_at'] = $params['created_at'];
        }

        if (isset($params['first_installment_amount'])) {
            $data['first_installment_amount'] = $params['first_installment_amount'];
        }

        $response = $this->client->request('POST', $endpoint, $data);
        return $this->handleResponse($response);
    }

    public function update($subscription_link_alias, $params) {
        $endpoint = "/links/update-subscription-link";
        $data= [ 
            'subscription_link_alias' => $subscription_link_alias,
            'attrs' => [
                'amount' => $params['amount'],
                'first_installment_amount' => $params['first_installment_amount']
            ]
        ];

        $response = $this->client->request('POST', $endpoint, $data);
        return $this->handleResponse($response);
    }

    public function cancel($subscription_link_alias) {
        $endpoint = "/links/delete-subscription-link";
        $data = [
            'subscription_link_alias' => $subscription_link_alias
        ];

        $response = $this->client->request('POST', $endpoint, $data);
        return $this->handleResponse($response);
    }

    public function handleResponse($response) {
        if ($response['status'] === 'success') {
            return $response;
        } else {
            throw new TPagoException($response['messages'][0]['key']);
        }
    }
}
