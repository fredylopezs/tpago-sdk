<?php 
use PHPUnit\Framework\TestCase;
use FMLS\TPago\TPagoClient;
use FMLS\TPago\TPagoConfig;
use FMLS\TPago\Payment;
use FMLS\TPago\Subscription;
use FMLS\TPago\Exceptions\TPagoException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\HandlerStack;


class TpagoClientTest extends TestCase {
    public function testGeneratePaymentLink() {
        $mock = new MockHandler([new Response(200, [], json_encode(['status' => 'success','payment_link' =>[] ]))]);
        $handlerStack = HandlerStack::create($mock);
        $customClient = new GuzzleClient(['handler' => $handlerStack]);

        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config, $customClient);
        $payment= new Payment($client);
        $response = $payment->generateLink( 5000, "Test Payment");
        
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('payment_link', $response);
    }

    public function testGeneratePaymenLinkError() {
        $mock = new MockHandler([
            new Response(200,
            [], 
            json_encode([
                'status' => 'error', 
                'messages' => [
                    [
                        'key' => 'invalid_amount',
                        'level' => 'error'
                    ]
                ]
            ]))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $customClient = new GuzzleClient(['handler' => $handlerStack]);

        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config, $customClient);
        $payment = new Payment($client);        

        $this->expectException(TPagoException::class);
        $this->expectExceptionMessage('invalid_amount');

        $payment->generateLink( -5000, "Test Payment");
    }

    public function testGenerateSubscriptionLink() {
        $mock = new MockHandler([new Response(200, [], json_encode(['status' => 'success']))]);
        $handlerStack = HandlerStack::create($mock);
        $customClient = new GuzzleClient(['handler' => $handlerStack]);

        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config, $customClient);
        $subscription = new Subscription($client);
        $response = $subscription->generateLink(['amount' => 5000, 'description' => 'Test Subscription', 'periodicity' => 'monthly', 'debit_day' => 1, 'unlimited' => false]);
        $this->assertEquals('success', $response['status']);
    }
    public function testGenerateSubscriptionLinkError() {
        $mock = new MockHandler([
            new Response(200,
            [], 
            json_encode([
                'status' => 'error', 
                'messages' => [
                    [
                        'key' => 'invalid_amount',
                        'level' => 'error'
                    ]
                ]
            ]))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $customClient = new GuzzleClient(['handler' => $handlerStack]);

        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config, $customClient);
        $subscription = new Subscription($client);

        $this->expectException(TPagoException::class);
        $this->expectExceptionMessage('invalid_amount');

        $subscription->generateLink(['amount' => -5000, 'description' => 'Test Subscription', 'periodicity' => 'monthly', 'debit_day' => 1, 'unlimited' => false]);
    }

    public function testSuccessfulPayment() {
        $payload = '{"payment":{"hook_alias":"PCJIZ61978","link_url":"https://comercios.bancard.com.py/tpago/payment_links/PCJIZ61978","status":"confirmed","response_code":"00","response_description":"Pago exitoso","amount":10000,"currency":"GS","installment_number":10,"description":"Coca Cola 1 Ltr.","date_time":"31/10/2019 13:59:39","ticket_number":78798374923423,"authorization_code":"134243","commerce_name":"Supermercado Maravilla","branch_name":"Sucursal San Vicente","account_type":"TD","card_last_numbers":1234,"bin":"433234","entity_id":"017","entity_name":"Banco Itau","brand_id":"MC","brand_name":"MasterCard","product_id":"CLA","product_name":"Clasica","afinity_id":null,"afinity_name":null,"type":"QrPayment","payer":{"name":"Juan","lastname":"Perez","cellphone":"098221155","email":"test@gmail.com","notes":"Notas de ejemplo"}}}';

        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config);

        $response = $client->handlePaymentConfirmation(json_decode($payload, true));
        $this->assertEquals('success', $response['status']);
    }

    public function testFailedPayment() {
        $payload = '{"payment":{"hook_alias":"PCJIZ61978","link_url":"https://comercios.bancard.com.py/tpago/payment_links/PCJIZ61978","status":"failed","response_code":"51","response_description":"Insuficiencia de fondos","amount":10000,"currency":"GS","installment_number":10,"description":"Coca Cola 1 Ltr.","date_time":"31/10/2019 13:59:39","ticket_number":78798374923423,"authorization_code":"","commerce_name":"Supermercado Maravilla","branch_name":"Sucursal San Vicente","account_type":"TC","card_last_numbers":1234,"bin":"433234","entity_id":"017","entity_name":"Banco Itau","brand_id":"VS","brand_name":"Visa","product_id":"CLA","product_name":"Clasica","afinity_id":"45","afinity_name":"SUPERMERCADO STOCK","type":"Authorization","payer":{"name":"Juan","lastname":"Perez","cellphone":"098221155","email":"test@gmail.com","notes":"Notas de ejemplo"}}}';

        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config);

        $response = $client->handlePaymentConfirmation(json_decode($payload, true));
        $this->assertEquals('error', $response['status']);
    }

    public function testSuccessfulPaymentSubscription() {
        $payload = '{"payment":{"link_alias":"PCJIZ61978","link_url":"https://www.tpago.com.py/links?alias=PCJIZ61978","status":"confirmed","response_code":"00","response_description":"Pago exitoso","amount":10000,"currency":"GS","installment_number":10,"description":"Coca Cola 1 Ltr.","date_time":"31/10/2019 13:59:39","ticket_number":78798374923423,"authorization_code":"134243","commerce_name":"Supermercado Maravilla","branch_name":"Sucursal San Vicente","account_type":"TD","card_last_numbers":1234,"bin":"433234","entity_id":"017","entity_name":"Banco Itau","brand_id":"MC","brand_name":"MasterCard","product_id":"CLA","product_name":"Clasica","afinity_id":null,"afinity_name":null,"type":"QrPayment","payer":{"name":"Juan","lastname":"Perez","cellphone":"098221155","email":"test@gmail.com","notes":"Notas de ejemplo"},"subscription":{"periodicity":1,"start_date":null,"end_date":null,"debit_day":10,"unlimited":true}}}';
        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config);

        $response = $client->handlePaymentConfirmation(json_decode($payload, true));
        $this->assertEquals('success', $response['status']);
    }

    public function testFailedPaymentSubscription() {
        $payload = '{"payment":{"link_alias":"PCJIZ61978","link_url":"https://www.tpago.com.py/links?alias=PCJIZ61978","status":"failed","response_code":"51","response_description":"Insuficiencia de fondos","amount":10000,"currency":"GS","installment_number":10,"description":"Coca Cola 1 Ltr.","date_time":"31/10/2019 13:59:39","ticket_number":78798374923423,"authorization_code":"","commerce_name":"Supermercado Maravilla","branch_name":"Sucursal San Vicente","account_type":"TC","card_last_numbers":1234,"bin":"433234","entity_id":"017","entity_name":"Banco Itau","brand_id":"VS","brand_name":"Visa","product_id":"CLA","product_name":"Clasica","afinity_id":"45","afinity_name":"SUPERMERCADO STOCK","type":"Authorization","payer":{"name":"Juan","lastname":"Perez","cellphone":"098221155","email":"test@gmail.com","notes":"Notas de ejemplo"},"subscription":{"periodicity":1,"start_date":null,"end_date":null,"debit_day":10,"unlimited":true}}}';
    
        $config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
        $client = new TPagoClient($config);  

        $response = $client->handlePaymentConfirmation(json_decode($payload, true));
        $this->assertEquals('error', $response['status']);
    }

}