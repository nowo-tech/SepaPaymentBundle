# SEPA Payment Bundle

[![CI](https://github.com/nowo-tech/SepaPaymentBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/SepaPaymentBundle/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/sepa-payment-bundle.svg?style=flat)](https://packagist.org/packages/nowo-tech/sepa-payment-bundle) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/sepa-payment-bundle.svg)](https://packagist.org/packages/nowo-tech/sepa-payment-bundle) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net) [![Symfony](https://img.shields.io/badge/Symfony-6.0%2B%20%7C%207.4%2B%20%7C%208.0%20%7C%208.1%2B-000000?logo=symfony)](https://symfony.com)

> ⭐ **Found this useful?** Give it a star on GitHub! It helps us maintain and improve the project.

**Symfony bundle for SEPA (Single Euro Payments Area) payment management** - Generate SEPA Credit Transfer (pain.001.001.03) and Direct Debit (pain.008.001.02) XML files compliant with ISO 20022 standards. Includes comprehensive IBAN/BIC validation, mandate management, XSD schema validation, and banking utilities for European payments.

![FrankenPHP Friendly Worker Mode](docs/images/frankenphp-friendly.png)

This bundle is **FrankenPHP worker mode friendly**.

## Features

- ✅ **IBAN Validation**: Complete IBAN validation according to ISO 13616 standard
- ✅ **IBAN Utilities**: Format, normalize, extract country code, check digits, and BBAN
- ✅ **CCC to IBAN Conversion**: Convert Spanish CCC (Código Cuenta Cliente) to IBAN format
- ✅ **BIC Validation**: Validate BIC (Business Identifier Code) format
- ✅ **Automatic BIC Lookup**: Automatically look up BIC codes from IBANs (supports 8 countries)
- ✅ **Credit Card Validation**: Validate credit card numbers using Luhn algorithm and detect card types (Visa, Mastercard, Amex, Discover, etc.)
- ✅ **Identifier Generation**: Generate unique identifiers for messages, payments, and transactions
- ✅ **SEPA XML Parser**: Parse and validate SEPA XML files (Credit Transfer and Direct Debit)
- ✅ **XSD Schema Validation**: Validate XML files against official SEPA XSD schemas (ISO 20022)
- ✅ **SEPA String Sanitization**: Validate and sanitize strings according to SEPA character rules
- ✅ **SEPA Country Validation**: Validate SEPA member countries
- ✅ **SEPA Business Rules Validation**: Validate SEPA limits and business rules (amounts, dates, currencies, sequence types)
- ✅ **Export to Other Formats**: Export SEPA payment data to JSON and CSV formats, import from JSON
- ✅ **Symfony Events**: Event system for extensibility (before/after generation events)
- ✅ **Structured Logging**: Comprehensive logging for SEPA operations with PSR-3 integration
- ✅ **SEPA Mandates**: Manage SEPA Direct Debit mandates with full support
- ✅ **Credit Transfer**: Generate SEPA Credit Transfer XML files (pain.001.001.03 format) using Digitick\Sepa library
- ✅ **Direct Debit**: Generate SEPA Direct Debit XML files (pain.008.001.02 format) using Digitick\Sepa library
- ✅ **Array-based API**: Generate both types of payment files from simple array format
- ✅ **Object-based API**: Generate payment files using typed objects for better type safety
- ✅ **Multiple Transactions**: Support for batch payments in a single file
- ✅ **Full Validation**: Automatic validation of IBANs before XML generation
- ✅ **Type Safety**: Full type hints and strict types throughout
- ✅ **Console Commands**: `nowo:sepa:validate-iban`, `nowo:sepa:ccc-to-iban`, `sepa:validate-credit-card`, `nowo:sepa:parse-direct-debit`

## Installation

```bash
composer require nowo-tech/sepa-payment-bundle
```

Then, register the bundle in your `config/bundles.php`:

```php
<?php

return [
  // ...
  Nowo\SepaPaymentBundle\NowoSepaPaymentBundle::class => ['all' => true],
];
```

## Requirements

- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0
- digitick/sepa-xml ^3.0 (automatically installed as a dependency)

## Configuration

See [docs/CONFIGURATION.md](docs/CONFIGURATION.md) for configuration options.

The bundle works out of the box with default settings. **No configuration file is required** - the bundle uses sensible defaults.

## Usage

For detailed usage examples and API documentation, see [docs/USAGE.md](docs/USAGE.md).

### Quick Examples

**Generate SEPA Credit Transfer from array:**
```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\Translation\IdentityTranslator;

$generator = new CreditTransferGenerator(new IbanValidator(), new IdentityTranslator());
$xml = $generator->generateFromArray([
  'reference' => 'MSG-001',
  'initiatingPartyName' => 'My Company',
  'paymentInfoId' => 'PMT-001',
  'creditorIban' => 'ES9121000418450200051332',
  'creditorName' => 'My Company Name',
  'requestedExecutionDate' => '2024-01-20',
  'transactions' => [
    [
      'amount' => 100.50,
      'debtorIban' => 'GB82WEST12345698765432',
      'debtorName' => 'John Doe',
      'endToEndId' => 'E2E-001',
    ],
  ],
]);
```

**Generate SEPA Direct Debit from array:**
```php
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$generator = new DirectDebitGenerator(new IbanValidator());
$xml = $generator->generateFromArray([
  'reference' => 'MSG-001',
  'bankAccountOwner' => 'My Company',
  'paymentInfoId' => 'PMTINF-1',
  'dueDate' => '2024-01-20',
  'creditorName' => 'My Company Name',
  'creditorIban' => 'ES9121000418450200051332',
  'seqType' => 'RCUR',
  'creditorId' => 'ES98ZZZ09999999999',
  'localInstrumentCode' => 'CORE',
  'transactions' => [
    [
      'amount' => 100.50,
      'debtorIban' => 'GB82WEST12345698765432',
      'debtorName' => 'John Doe',
      'debtorMandate' => 'MANDATE-001',
      'debtorMandateSignDate' => '2024-01-15',
      'endToEndId' => 'E2E-001',
    ],
  ],
]);
```

For configuration options and translation overrides (domain `NowoSepaPaymentBundle`), see [docs/CONFIGURATION.md](docs/CONFIGURATION.md).

See [docs/USAGE.md](docs/USAGE.md) for complete examples including:
- IBAN, BIC, and Credit Card validation
- CCC to IBAN conversion
- Identifier generation
- SEPA XML parsing
- Object-based generation
- Address support
- Dependency injection examples

## Console Commands

See [docs/COMMANDS.md](docs/COMMANDS.md) for detailed documentation of all console commands.

## Demo Projects

Demos for Symfony 8 run with **FrankenPHP** (development uses `Caddyfile.dev` without workers; production-style uses worker mode — see [docs/DEMO-FRANKENPHP.md](docs/DEMO-FRANKENPHP.md)). See [docs/DEMOS.md](docs/DEMOS.md) for endpoints and quick start.

## Development

See [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) for development setup, testing, code quality, and CI/CD information.

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)
- [GitHub Actions CI requirements](docs/GITHUB_CI.md)

### Additional documentation

- [Commands](docs/COMMANDS.md)
- [Demo projects](docs/DEMOS.md)
- [Demo with FrankenPHP (development and production)](docs/DEMO-FRANKENPHP.md)
- [Development](docs/DEVELOPMENT.md)
- [Deprecated fields](docs/DEPRECATED_FIELDS.md)
- [Future improvements](docs/FUTURE.md)
- [Branching](docs/BRANCHING.md)
## Tests and coverage

- Tests: PHPUnit (PHP)
- PHP: 100%

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Author

Created by [Héctor Franco Aceituno](https://github.com/HecFranco) at [Nowo.tech](https://nowo.tech)

