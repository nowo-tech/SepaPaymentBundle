# Configuration Guide

This document describes the configuration options available for the SEPA Payment Bundle.


## Table of contents

- [Overview](#overview)
- [Configuration File](#configuration-file)
  - [Location](#location)
  - [Structure](#structure)
- [Configuration Options](#configuration-options)
  - [`default_currency`](#default-currency)
  - [`validation_cache`](#validation-cache)
- [How Configuration Works](#how-configuration-works)
- [Accessing Configuration in Code](#accessing-configuration-in-code)
- [Environment-Specific Configuration](#environment-specific-configuration)
- [Validation](#validation)
- [Translations](#translations)
- [Examples](#examples)
  - [Basic Configuration](#basic-configuration)
  - [Multi-Currency Setup](#multi-currency-setup)

## Overview

The bundle works out of the box with default settings. **No configuration file is required** - the bundle uses sensible defaults defined in `Configuration.php`.

**Important**: The configuration file (`nowo_sepa_payment.yaml`) is **optional**. You only need to create it if you want to customize the default behavior.

## Configuration File

### Location

Create the configuration file at:

```
config/packages/nowo_sepa_payment.yaml
```

### Structure

```yaml
nowo_sepa_payment:
    default_currency: EUR  # Default currency code (ISO 4217)
```

## Configuration Options

### `default_currency`

- **Type**: `string`
- **Default**: `EUR`
- **Description**: Currency (ISO 4217, 3 uppercase letters) used by `CreditTransferGenerator` and `DirectDebitGenerator` for transactions that do not set their own `currency`.
- **SEPA constraint**: the SEPA credit transfer and direct debit schemes only support euro. The configuration accepts any well-formed code, but the generators throw an `InvalidArgumentException` when the effective currency (per transaction or this default) is not `EUR`. Keep the default unless you have a specific reason.

```yaml
nowo_sepa_payment:
    default_currency: EUR
```

### `validation_cache`

- **Type**: `array` with `service` (`string|null`, default `null`) and `ttl` (`int`, seconds, default `3600`)
- **Description**: Caches IBAN/BIC validation results used by `CachedIbanValidator` and `CachedBicValidator` in a PSR-16 cache (`Psr\SimpleCache\CacheInterface`). With `service: null` (default) caching is disabled and the cached validators simply delegate to the plain validators.
- **Note**: `ValidationCache::clear()` clears the whole PSR-16 pool, so use a dedicated pool. In FrankenPHP worker mode prefer a shared pool (APCu/Redis/filesystem) over an in-memory `ArrayAdapter`.

```yaml
services:
    app.sepa_validation_cache:
        class: Symfony\Component\Cache\Psr16Cache
        arguments: ['@cache.app']   # better: a dedicated pool

nowo_sepa_payment:
    validation_cache:
        service: app.sepa_validation_cache
        ttl: 86400
```

## How Configuration Works

1. **Default Values**: The bundle uses default values from `Configuration.php` if no config file exists
2. **YAML Merging**: If a YAML file exists, Symfony automatically merges it with default values
3. **No Auto-Deletion**: When uninstalling the bundle, the YAML file is **not** automatically deleted (you may want to keep your custom configuration)

## Accessing Configuration in Code

Configuration values are available as container parameters:

```php
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MyService
{
    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function getDefaultCurrency(): string
    {
        return $this->parameterBag->get('nowo_sepa_payment.default_currency');
    }
}
```

## Environment-Specific Configuration

You can override configuration per environment:

```yaml
# config/packages/dev/nowo_sepa_payment.yaml
nowo_sepa_payment:
    default_currency: EUR

# config/packages/prod/nowo_sepa_payment.yaml
nowo_sepa_payment:
    default_currency: EUR
```

## Validation

The bundle validates configuration values:

- `default_currency` must be 3 uppercase letters (ISO 4217 format, e.g. `EUR`)
- Invalid values (e.g. `eur`, `EURO`) cause a configuration exception during container compilation
- Non-EUR values pass configuration but are rejected by the generators at generation time (SEPA is EUR-only)

## Translations

The bundle uses the translation domain **`NowoSepaPaymentBundle`** (CamelCase with `Bundle` suffix). All validation messages and constraint messages (IBAN, BIC, SEPA Creditor Identifier, Credit Card, SEPA Country) are loaded from this domain.

### Are bundle translations overridable?

**Yes.** You can override any message by placing a file with the same domain and locale in your application. Symfony merges catalogues so that your keys take precedence.

### Load order (priority, highest first)

Symfony loads translation files in this order:

1. **`translations/`** at the root of your project (configurable via `framework.translator.default_path`, usually `%kernel.project_dir%/translations`).
2. **`src/Resources/<BundleName>/translations/`** in your application (if you mirror the bundle structure to override only that bundle).
3. **`Resources/translations/`** inside each bundle (this bundle’s messages live here).

So anything you define in your project’s `translations/` for the domain `NowoSepaPaymentBundle` overrides the same keys from the bundle. You only need to define the keys you want to change; the rest fall back to the bundle defaults.

### How to override in your application

Create YAML (or XLIFF) files in your project’s `translations/` directory with the same domain name and locale, for example:

- `translations/NowoSepaPaymentBundle.es.yaml` — overrides Spanish messages
- `translations/NowoSepaPaymentBundle.en_US.yaml` — overrides US English messages

Keys match the bundle’s structure: `validation.*` for validation errors (e.g. `validation.invalid_iban`, `validation.missing_required_field`) and `iban.invalid`, `bic.invalid`, `sepa_creditor_identifier.invalid`, `credit_card.invalid`, `sepa_country.invalid` for constraint messages. Only define the keys you want to override; Symfony will fall back to the bundle’s defaults for the rest.

## Examples

### Basic Configuration

```yaml
# config/packages/nowo_sepa_payment.yaml
nowo_sepa_payment:
    default_currency: EUR
```

### Multi-Currency Setup

SEPA schemes are EUR-only, so the generators reject any non-EUR currency. A per-transaction currency can still be set explicitly (it must be `EUR`):

```php
$transaction = new Transaction(
    'E2E-001',
    100.50,
    'EUR',  // Currency specified per transaction (SEPA: EUR only; other codes throw when generating)
    'ES9121000418450200051332',
    'John Doe'
);
```

