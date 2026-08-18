# Installation

## Table of contents

- [Requirements](#requirements)
- [Install via Composer](#install-via-composer)
- [Enable the bundle](#enable-the-bundle)
- [Optional configuration](#optional-configuration)
- [Next steps](#next-steps)

## Requirements

- PHP >= 8.1, < 8.6
- Symfony ^6.0 || ^7.0 || ^8.0
- Composer 2

The bundle pulls in `digitick/sepa-xml` and `brick/math` as dependencies.

## Install via Composer

```bash
composer require nowo-tech/sepa-payment-bundle
```

The Flex recipe lives under `.symfony/recipe/` (copy those files if Flex does not apply them).

## Enable the bundle

Register the bundle in `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\SepaPaymentBundle\NowoSepaPaymentBundle::class => ['all' => true],
];
```

If you use **Symfony Flex**, the bundle is registered automatically when you require it.

## Optional configuration

The bundle works with sensible defaults. To customize behaviour, add or edit `config/packages/nowo_sepa_payment.yaml`. See [Configuration](CONFIGURATION.md) for all options.

## Next steps

- [Configuration](CONFIGURATION.md) — configuration options and defaults
- [Usage](USAGE.md) — generating SEPA XML, validation, and examples
- [Commands](COMMANDS.md) — console commands (IBAN validation, CCC conversion, etc.)
