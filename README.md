# TPago PHP SDK

Este SDK proporciona una interfaz fácil de usar para interactuar con la API de TPago en aplicaciones PHP.

## Instalación

Para instalar este SDK, utiliza Composer:

```bash
composer require fredylopezs/tpago-php
```

## Uso

### Configuración

Antes de usar el SDK, debes configurar las credenciales de TPago:

```php
use FMLS\TPago\TPagoConfig;

$config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode');
```

Alternativamente, puedes usar el entorno de pruebas con sus respectivas credenciales:

```php
$config = new TPagoConfig('publicKey', 'privateKey', 'commerceCode', 'branchCode', true);
```

### Generar un enlace de pago

Para generar un enlace de pago, utiliza la clase `Payment`:

```php
use FMLS\TPago\TPagoClient;
use FMLS\TPago\Payment; 

$client = new TPagoClient($config);
$payment = new Payment($client);

$response = $payment->generateLink(5000, "Test Payment");

print_r($response);
```

### Generar un enlace de suscripción

Para generar un enlace de suscripción, utiliza la clase `Subscription`:

```php
use FMLS\TPago\Subscription;

$client = new TPagoClient($config);
$subscription = new Subscription($client);

$response = $subscription->generateLink([
    'amount' => 5000,
    'description' => "Test Subscription",
    'periodicity' => "monthly",
    'debit_day' => 1,
    'unlimited' => false
]);



