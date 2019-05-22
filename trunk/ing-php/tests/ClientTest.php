<?php

namespace GingerPayments\Payment\Tests;

use GingerPayments\Payment\Client;
use GingerPayments\Payment\Common\ArrayFunctions;
use GingerPayments\Payment\Order;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use Mockery as m;

final class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Mockery\MockInterface
     */
    private $httpResponse;

    /**
     * @var \Mockery\MockInterface
     */
    private $httpClient;

    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        $this->httpResponse = m::mock('GuzzleHttp\Message\ResponseInterface');
        $this->httpClient = m::mock('GuzzleHttp\Client');
        $this->client = new Client($this->httpClient);
    }

    /**
     * @test
     */
    public function itShouldVerifySSLUsingBundledCA()
    {
        $this->httpClient->shouldReceive('setDefaultOption')
            ->once()
            ->with('verify', realpath(dirname(__FILE__).'/../assets/cacert.pem'))
            ->andReturn(null);

        $this->client->useBundledCA();
    }

    /**
     * @test
     */
    public function itShouldGetIdealIssuers()
    {
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('ideal/issuers/')
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(
                [
                    [
                        'id' => 'ABNANL2A',
                        'name' => 'ABN AMRO Bank',
                        'list_type' => 'Nederland'
                    ]
                ]
            );

        $this->assertInstanceOf(
            'GingerPayments\Payment\Ideal\Issuers',
            $this->client->getIdealIssuers()
        );
    }

    /**
     * @test
     */
    public function itShouldCatchHttpClientExceptionsWhenGettingIdealIssuers()
    {
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('ideal/issuers/')
            ->andThrow(new HttpClientException('Something happened', m::mock('GuzzleHttp\Message\Request')));

        $this->setExpectedException('GingerPayments\Payment\Client\ClientException');
        $this->client->getIdealIssuers();
    }

    /**
     * @test
     */
    public function itShouldCreateAnOrder()
    {
        $order = Order::create(
            1234,
            'EUR',
            'credit-card',
            [],
            'A nice description',
            'my-order-id',
            'http://www.example.com',
            'PT10M'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'orders/',
                m::on(
                    function (array $options) use ($order) {
                        $this->assertEquals(30, $options['timeout']);
                        $this->assertEquals('application/json', $options['headers']['Content-Type']);
                        $this->assertEquals(
                            ArrayFunctions::withoutNullValues($order->toArray()),
                            json_decode($options['body'], true)
                        );
                        return true;
                    }
                )
            )
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(ArrayFunctions::withoutNullValues($order->toArray()));

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->createOrder(
                1234,
                'EUR',
                'credit-card',
                [],
                'A nice description',
                'my-order-id',
                'http://www.example.com',
                'PT10M'
            )
        );
    }

    /**
     * @test
     */
    public function itShouldCreateACreditCardOrder()
    {
        $order = Order::create(
            1234,
            'EUR',
            'credit-card',
            [],
            'A nice description',
            'my-order-id',
            'http://www.example.com',
            'PT10M'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'orders/',
                m::on(
                    function (array $options) use ($order) {
                        $this->assertEquals(30, $options['timeout']);
                        $this->assertEquals('application/json', $options['headers']['Content-Type']);
                        $this->assertEquals(
                            ArrayFunctions::withoutNullValues($order->toArray()),
                            json_decode($options['body'], true)
                        );
                        return true;
                    }
                )
            )
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(ArrayFunctions::withoutNullValues($order->toArray()));

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->createCreditCardOrder(
                1234,
                'EUR',
                'A nice description',
                'my-order-id',
                'http://www.example.com',
                'PT10M'
            )
        );
    }

    /**
     * @test
     */
    public function itShouldCreateAnIdealOrder()
    {
        $order = Order::create(
            1234,
            'EUR',
            'ideal',
            ['issuer_id' => 'ABNANL2A'],
            'A nice description',
            'my-order-id',
            'http://www.example.com',
            'PT10M'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'orders/',
                m::on(
                    function (array $options) use ($order) {
                        $this->assertEquals('application/json', $options['headers']['Content-Type']);
                        $this->assertEquals(
                            ArrayFunctions::withoutNullValues($order->toArray()),
                            json_decode($options['body'], true)
                        );
                        return true;
                    }
                )
            )
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(ArrayFunctions::withoutNullValues($order->toArray()));

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->createIdealOrder(
                1234,
                'EUR',
                'ABNANL2A',
                'A nice description',
                'my-order-id',
                'http://www.example.com',
                'PT10M'
            )
        );
    }

    /**
     * @test
     */
    public function itShouldCreateABankTransferOrder()
    {
        $order = Order::create(
            1234,
            'EUR',
            'bank-transfer',
            [],
            'Bank Transfer order description',
            'my-order-id',
            'http://www.example.com',
            'PT10M'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'orders/',
                m::on(
                    function (array $options) use ($order) {
                        $this->assertEquals('application/json', $options['headers']['Content-Type']);
                        $this->assertEquals(
                            ArrayFunctions::withoutNullValues($order->toArray()),
                            json_decode($options['body'], true)
                        );
                        return true;
                    }
                )
            )
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(ArrayFunctions::withoutNullValues($order->toArray()));

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->createSepaOrder(
                1234,
                'EUR',
                [],
                'Bank Transfer order description',
                'my-order-id',
                'http://www.example.com',
                'PT10M'
            )
        );
    }

    /**
     * @test
     */
    public function itShouldCreateBancontactOrder()
    {
        $order = Order::create(
            1234,
            'EUR',
            'bancontact',
            [],
            'Bancontact order description',
            'my-order-id',
            'http://www.example.com',
            'PT10M'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'orders/',
                m::on(
                    function (array $options) use ($order) {
                        $this->assertEquals('application/json', $options['headers']['Content-Type']);
                        $this->assertEquals(
                            ArrayFunctions::withoutNullValues($order->toArray()),
                            json_decode($options['body'], true)
                        );
                        return true;
                    }
                )
            )
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(ArrayFunctions::withoutNullValues($order->toArray()));

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->createBancontactOrder(
                1234,
                'EUR',
                'Bancontact order description',
                'my-order-id',
                'http://www.example.com',
                'PT10M'
            )
        );
    }

    /**
     * @test
     */
    public function itShouldUpdateOrder()
    {
        $orderData = [
            'transactions' => [['payment_method' => 'credit-card']],
            'amount' => 9999,
            'currency' => 'EUR',
            'id' => 'c384b47e-7a5e-4c91-ab65-c4eed7f26e85',
            'expiration_period' => 'PT10M',
            'merchant_order_id' => '123',
            'description' => "Test",
            'return_url' => "http://example.com",
            'webhook_url' => "http://example.com/webhook"
        ];

        $order = Order::fromArray($orderData);

        $this->httpClient->shouldReceive('put')
            ->once()
            ->with(
                'orders/c384b47e-7a5e-4c91-ab65-c4eed7f26e85/',
                m::on(
                    function (array $options) use ($order) {
                        $this->assertEquals(
                            ArrayFunctions::withoutNullValues($order->toArray()),
                            $options['json']
                        );
                        return true;
                    }
                )
            )
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(ArrayFunctions::withoutNullValues($order->toArray()));

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->updateOrder($order)
        );
    }

    /**
     * @test
     */
    public function itShouldThrowAnOrderNotFoundExceptionWhenUpdatingOrder()
    {
        $orderData = [
            'transactions' => [['payment_method' => 'credit-card']],
            'amount' => 9999,
            'currency' => 'EUR',
            'id' => 'c384b47e-7a5e-4c91-ab65-c4eed7f26e85',
            'expiration_period' => 'PT10M',
            'merchant_order_id' => '123',
            'description' => "Test",
            'return_url' => "http://example.com",
        ];

        $request = m::mock('GuzzleHttp\Message\Request');
        $response = m::mock('GuzzleHttp\Message\Response');
        $response->shouldReceive('getStatusCode')->andReturn(404);

        $this->httpClient->shouldReceive('put')
            ->once()
            ->andThrow(new HttpClientException('Something happened', $request, $response));

        $this->setExpectedException('GingerPayments\Payment\Client\OrderNotFoundException');
        $this->client->updateOrder(Order::fromArray($orderData));
    }

    /**
     * @test
     */
    public function itShouldThrowAClientExceptionWhenUpdatingOrder()
    {
        $orderData = [
            'transactions' => [['payment_method' => 'credit-card']],
            'amount' => 9999,
            'currency' => 'EUR',
            'id' => 'c384b47e-7a5e-4c91-ab65-c4eed7f26e85',
            'expiration_period' => 'PT10M',
            'merchant_order_id' => '123',
            'description' => "Test",
            'return_url' => "http://example.com",
        ];

        $request = m::mock('GuzzleHttp\Message\Request');

        $this->httpClient->shouldReceive('put')
            ->once()
            ->andThrow(new HttpClientException('Something happened', $request));

        $this->setExpectedException('GingerPayments\Payment\Client\ClientException');
        $this->client->updateOrder(Order::fromArray($orderData));
    }

    /**
     * @test
     */
    public function itShouldCreateASofortOrder()
    {
        $order = Order::create(
            1234,
            'EUR',
            'sofort',
            [],
            'Sofort Transfer order description',
            'my-order-id',
            'http://www.example.com',
            'PT10M'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'orders/',
                m::on(
                    function (array $options) use ($order) {
                        $this->assertEquals('application/json', $options['headers']['Content-Type']);
                        $this->assertEquals(
                            ArrayFunctions::withoutNullValues($order->toArray()),
                            json_decode($options['body'], true)
                        );
                        return true;
                    }
                )
            )
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(ArrayFunctions::withoutNullValues($order->toArray()));

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->createSofortOrder(
                1234,
                'EUR',
                [],
                'Sofort Transfer order description',
                'my-order-id',
                'http://www.example.com',
                'PT10M'
            )
        );
    }

    /**
     * @test
     */
    public function itShouldCatchHttpClientExceptionsWhenCreatingAnOrder()
    {
        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Something happened', m::mock('GuzzleHttp\Message\Request')));

        $this->setExpectedException('GingerPayments\Payment\Client\ClientException');
        $this->client->createOrder(1234, 'EUR', 'credit-card');
    }

    /**
     * @test
     */
    public function itShouldGetAnOrder()
    {
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('orders/123456')
            ->andReturn($this->httpResponse);

        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(
                [
                    'amount' => 1234,
                    'currency' => 'EUR',
                    'transactions' => [
                        ['payment_method' => 'credit-card']
                    ]
                ]
            );

        $this->assertInstanceOf(
            'GingerPayments\Payment\Order',
            $this->client->getOrder('123456')
        );
    }

    /**
     * @test
     */
    public function itShouldThrowAnOrderNotFoundExceptionWhenGettingAnOrder()
    {
        $request = m::mock('GuzzleHttp\Message\Request');
        $response = m::mock('GuzzleHttp\Message\Response');
        $response->shouldReceive('getStatusCode')->andReturn(404);

        $this->httpClient->shouldReceive('get')
            ->once()
            ->andThrow(new HttpClientException('Something happened', $request, $response));

        $this->setExpectedException('GingerPayments\Payment\Client\OrderNotFoundException');
        $this->client->getOrder('123456');
    }

    /**
     * @test
     */
    public function itShouldThrowAClientExceptionWhenGettingAnOrder()
    {
        $request = m::mock('GuzzleHttp\Message\Request');

        $this->httpClient->shouldReceive('get')
            ->once()
            ->andThrow(new HttpClientException('Something happened', $request));

        $this->setExpectedException('GingerPayments\Payment\Client\ClientException');
        $this->client->getOrder('123456');
    }
    
    /**
     * @test
     */
    public function itShouldSetOrderStatusToCaptured() 
    {
        $this->httpClient->shouldReceive('post')
            ->once()
            ->andReturn($this->httpResponse);
        
        $this->httpResponse->shouldReceive('json')
            ->once()
            ->andReturn(
                [
                    'payment_method' => 'credit-card',
                    'payment_method_details' => [],
                    'id' => '5ac3eb32-384d-4d61-a797-9f44b1cd70e5',
                    'created' => '2015-03-07T20:58:35+0100',
                    'modified' => '2015-03-07T21:58:35+0100',
                    'completed' => '2015-03-07T22:58:35+0100',
                    'status' => 'new',
                    'reason' => 'A great reason',
                    'currency' => 'EUR',
                    'amount' => 100,
                    'expiration_period' => 'P0Y0M0DT1H0M0S',
                    'description' => 'A transaction',
                    'balance' => 'internal',
                    'payment_url' => 'http://www.example.com'
                ]
        );
        
        $this->assertInstanceOf(
            'GingerPayments\Payment\Order\Transaction',
            $this->client->setOrderCapturedStatus($this->createOrderWithTransactionsFormArray())
        );
         
    }
    
    /**
     * @test
     */
    public function itShouldThrowAnOrderNotFoundExceptionWhenSettingCapturedOrderStatus()
    {
        $request = m::mock('GuzzleHttp\Message\Request');
        $response = m::mock('GuzzleHttp\Message\Response');
        $response->shouldReceive('getStatusCode')->andReturn(404);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Something happened', $request, $response));

        $this->setExpectedException('GingerPayments\Payment\Client\OrderNotFoundException');
        $this->client->setOrderCapturedStatus($this->createOrderWithTransactionsFormArray());
    }
    
    /**
     * @test
     */
    public function itShouldThrowAClientExceptionWhenSettingCapturedOrderStatus()
    {
        $request = m::mock('GuzzleHttp\Message\Request');

        $this->httpClient->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Something happened', $request));

        $this->setExpectedException('GingerPayments\Payment\Client\ClientException');
        $this->client->setOrderCapturedStatus($this->createOrderWithTransactionsFormArray());
    }
    
    /**
     * Helper method for creating an order with transactions
     * 
     * @return Order
     */
    protected function createOrderWithTransactionsFormArray() {
        $orderData = [
            'transactions' => [
                 [
                    'payment_method' => 'credit-card',
                    'payment_method_details' => [],
                    'id' => '5ac3eb32-384d-4d61-a797-9f44b1cd70e5',
                    'created' => '2015-03-07T20:58:35+0100',
                    'modified' => '2015-03-07T21:58:35+0100',
                    'completed' => '2015-03-07T22:58:35+0100',
                    'status' => 'new',
                    'reason' => 'A great reason',
                    'currency' => 'EUR',
                    'amount' => 100,
                    'expiration_period' => 'P0Y0M0DT1H0M0S',
                    'description' => 'A transaction',
                    'balance' => 'internal',
                    'payment_url' => 'http://www.example.com'
                ]
            ],
            'amount' => 100,
            'currency' => 'EUR',
            'id' => 'c384b47e-7a5e-4c91-ab65-c4eed7f26e85',
            'expiration_period' => 'PT10M',
            'merchant_order_id' => '123',
            'description' => "Test",
            'return_url' => "http://example.com",
        ];
        return Order::fromArray($orderData);
    }
    
}
