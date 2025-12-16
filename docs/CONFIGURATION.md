# Configuration Guide

This document describes the configuration options available for the SEPA Payment Bundle.

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
- **Description**: Default currency code for remesas (ISO 4217 format)
- **Example**: `EUR`, `USD`, `GBP`

```yaml
nowo_sepa_payment:
    default_currency: EUR
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

- `default_currency` must be a valid ISO 4217 currency code (3 letters)
- Invalid values will cause a configuration exception during container compilation

## Examples

### Basic Configuration

```yaml
# config/packages/nowo_sepa_payment.yaml
nowo_sepa_payment:
    default_currency: EUR
```

### Multi-Currency Setup

If you need to support multiple currencies, you can still use the default currency for convenience, but you can always specify the currency per transaction:

```php
$transaction = new Transaction(
    'E2E-001',
    100.50,
    'USD',  // Currency specified per transaction
    'ES9121000418450200051332',
    'John Doe'
);
```

