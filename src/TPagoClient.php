<?php 
namespace FMLS\TPago;

use FMLS\TPago\Exceptions\TPagoException;
use FMLS\TPago\Utils\Auth;
use FMLS\TPago\TPagoConfig;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

class TPagoClient {
    private ClientInterface $httpClient;
    private TPagoConfig $config;

    public function __construct(
        TPagoConfig $config,
        ClientInterface $httpClient = null
    ) {
        $this->config = $config;
        // Si no se provee un cliente, utiliza Guzzle por defecto
        $this->httpClient = $httpClient ?? new GuzzleClient(['base_uri' => $this->config->getBaseURL()]);
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
        $authHeader = Auth::getBasicAuthHeader($this->config->getPublicKey(), $this->config->getPrivateKey());
        
        $url = $this->config->getBaseURL() . "/{$endpoint}";
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
}
