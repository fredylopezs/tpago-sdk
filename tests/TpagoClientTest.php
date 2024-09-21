<?php 
use PHPUnit\Framework\TestCase;
use FMLS\TPago\TPagoClient;
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

        $client = new TPagoClient('publicKey', 'privateKey', 'commerceCode', 'branchCode', true, $customClient);
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

        $client = new TPagoClient('publicKey', 'privateKey', 'commerceCode', 'branchCode', true, $customClient);
        $payment = new Payment($client);        

        $this->expectException(TPagoException::class);
        $this->expectExceptionMessage('invalid_amount');

        $payment->generateLink( -5000, "Test Payment");
    }

    public function testGenerateSubscriptionLink() {
        $mock = new MockHandler([new Response(200, [], json_encode(['status' => 'success']))]);
        $handlerStack = HandlerStack::create($mock);
        $customClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new TPagoClient('publicKey', 'privateKey', 'commerceCode', 'branchCode', true, $customClient);
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

        $client = new TPagoClient('publicKey', 'privateKey', 'commerceCode', 'branchCode', true, $customClient);
        $subscription = new Subscription($client);

        $this->expectException(TPagoException::class);
        $this->expectExceptionMessage('invalid_amount');

        $subscription->generateLink(['amount' => -5000, 'description' => 'Test Subscription', 'periodicity' => 'monthly', 'debit_day' => 1, 'unlimited' => false]);
    }
}