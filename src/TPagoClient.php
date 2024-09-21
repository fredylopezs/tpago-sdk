<?php 
namespace FMLS\TPago;

use GuzzleHttp\Client as GuzzleClient;
use FMLS\TPago\Exceptions\TPagoException;
use FMLS\TPago\Utils\Auth;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Request;


class TPagoClient {
    private ClientInterface $httpClient;
    private string $publicKey;
    private string $privateKey;
    private string $commerceCode;
    private string $branchCode;
    private bool $isProduction = false;

    private const URL_Staging = 'https://vpos.infonet.com.py:8888';
    private const URL_PRODUCTION = 'https://vpos.infonet.com.py:8888';

    public function __construct(
        string $publicKey, 
        string $privateKey, 
        string $commerceCode,
        string $branchCode, 
        bool $isProduction = true,
        ClientInterface $httpClient = null
    ) {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->commerceCode = $commerceCode;
        $this->branchCode = $branchCode;
        $this->isProduction = $isProduction;
        // Si no se provee un cliente, utiliza Guzzle por defecto
        $this->httpClient = $httpClient ?? new GuzzleClient(['base_uri' => $this->getBaseURL()]);
    }


    // Método para manejar confirmación de pagos
    public function handlePaymentConfirmation(array $requestData): array {
        $this->validatePaymentData($requestData);
        
        $payment = $requestData['payment'];
        $paymentStatus = $payment['status'];
        $responseCode = $payment['response_code'];
        
        return match ($paymentStatus) {
            'confirmed' => $this->handleConfirmedPayment($payment),
            'failed' => $this->handleFailedPayment($payment),
            default => $this->handleUnknownStatus($responseCode),
        };
    }

    private function validatePaymentData(array $requestData): void {
        if (!isset($requestData['payment']['status'])) {
            throw new TPagoException("Datos de confirmación incompletos o incorrectos.");
        }
    }

    private function handleConfirmedPayment(array $payment): array {
        if ($payment['response_code'] === '00') {
            return [
                'status' => 'success',
                'messages' => [ 
                    'key' => 'Confirmed',
                    'level' => 'success',
                    'description' => sprintf(
                        "Pago confirmado exitosamente para el link: %s, monto: %s.",
                    $payment['link_alias'],
                        $payment['amount']
                    )
                ]
            ];
        }
        return $this->handleUnknownStatus($payment['response_code']);
    }

    private function handleFailedPayment(array $payment): array {
        return [
            'status' => 'error',
            'messages' => [ 
                'key' => 'ConfirmedError',
                'level' => 'error',
                'description' => 'No se pudo procesar la confirmacion'
            ]
        ];
    }

    private function handleUnknownStatus(string $responseCode): array {
        return [
            'status' => 'error',
            'messages' => [ 
                'key' => 'ConfirmedError',
                'level' => 'error',
                'description' => 'Error desconocido con el código: $responseCode.'
            ]
        ];
    }

    public function request(string $method, string $endpoint, array $data = []): array {
        $authHeader = Auth::getBasicAuthHeader($this->publicKey, $this->privateKey);
        
        $url = $this->getBaseURL() . "/{$endpoint}";
        $request = new Request(
            $method,
            $url,
            [
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
            ],
            json_encode($data)
        );

        try {
            $response = $this->httpClient->sendRequest($request);
            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true);
        } catch (\Exception $e) {
            throw new TPagoException($e->getMessage());
        }
    }

    private function getBaseURL() {
        $url_path = "/external-commerce/api/0.1/commerces/$this->commerceCode/branches/$this->branchCode";
        return ($this->isProduction) ? self::URL_PRODUCTION . $url_path : self::URL_Staging . $url_path;
    }

}
