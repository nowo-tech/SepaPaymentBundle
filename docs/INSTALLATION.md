# Installation

## Requirements

- PHP 8.1 or higher
- Symfony 6.1+ / 7.x / 8.x
- Composer

## Install via Composer

```bash
composer require nowo-tech/sepa-payment-bundle
```

## Register the bundle

Register the bundle in your `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\SepaPaymentBundle\NowoSepaPaymentBundle::class => ['all' => true],
];
```

## Optional configuration

The bundle works with defaults. To customize, create `config/packages/nowo_sepa_payment.yaml`. See [CONFIGURATION.md](CONFIGURATION.md) for options.

## Next steps

- [Configuration](CONFIGURATION.md)
- [Usage](USAGE.md)
- [Upgrade guide](UPGRADING.md)
