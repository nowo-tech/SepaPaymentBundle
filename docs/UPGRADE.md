# Upgrade Guide

This guide helps you upgrade between versions of the SEPA Payment Bundle.

## Upgrading to 0.0.10

### Service Configuration Changes

The service definitions in `services.yaml` have been updated to use service aliases directly instead of fully qualified class names. This is an **internal change** that improves consistency and aligns with Symfony best practices.

**What changed:**
- Service IDs now use aliases (e.g., `nowo_sepa_payment.validator.iban_validator`) instead of class names
- Service dependencies now reference services by their aliases

**Impact:**
- **No breaking changes for most users**: Services can still be injected via constructor type-hinting (autowiring)
- **No breaking changes for explicit service retrieval**: If you were using service aliases with `#[Autowire]` or `$container->get()`, the aliases remain the same
- **Potential impact**: If you were manually retrieving services by their fully qualified class name (e.g., `$container->get('Nowo\\SepaPaymentBundle\\Validator\\IbanValidator')`), you should update to use the alias instead (e.g., `$container->get('nowo_sepa_payment.validator.iban_validator')`)

**Action required:**
- Only if you're manually retrieving services by class name in your code, update to use service aliases
- If you're using autowiring or `#[Autowire]` with aliases, no changes needed

**Service alias reference:**
- `nowo_sepa_payment.validator.iban_validator` - IBAN validator
- `nowo_sepa_payment.validator.bic_validator` - BIC validator
- `nowo_sepa_payment.validator.credit_card_validator` - Credit card validator
- `nowo_sepa_payment.converter.ccc_converter` - CCC to IBAN converter
- `nowo_sepa_payment.generator.remesa_generator` - Remesa (credit transfer) generator
- `nowo_sepa_payment.generator.direct_debit_generator` - Direct debit generator
- `nowo_sepa_payment.generator.identifier_generator` - Identifier generator
- `nowo_sepa_payment.parser.remesa_parser` - Remesa parser

## Upgrading to 0.0.9

### New Features

#### HTTP Response Helper Method

Both `DirectDebitGenerator` and `RemesaGenerator` now include a `createResponse()` method that simplifies returning XML files as HTTP responses in Symfony controllers.

**Before:**
```php
$xml = $generator->generateFromArray($data);
return new Response($xml, 200, [
    'Content-Type' => 'application/xml',
    'Content-Disposition' => 'attachment; filename="remesa-cobro.xml"',
]);
```

**After:**
```php
$xml = $generator->generateFromArray($data);
return $generator->createResponse($xml, 'remesa-cobro.xml');
```

This is a **non-breaking change** - existing code will continue to work. The new method is optional and provides a more convenient way to create HTTP responses.

## Upgrading to 0.0.8

### New Features

#### Postal Address Support (Optional)

Postal addresses for both creditor and debtor are now **optional** and will be **included in the generated XML only if provided** in the array. Addresses are added using structured format (PstlAdr) with elements: StrtNm, TwnNm, PstCd, and Ctry.

**Important Notes:**
- Addresses are **completely optional** - if you don't provide them, no address elements will be added to the XML
- Empty address arrays are ignored and will not create address elements
- At least one address field (street, city, postalCode, or country) must be provided for the address to be included
- This is a **non-breaking change** - existing code without addresses will continue to work exactly as before

**Using object methods:**
```php
// Creditor address
$directDebitData->setCreditorAddress([
    'street' => '123 Business Street',
    'city' => 'Madrid',
    'postalCode' => '28001',
    'country' => 'ES',
]);

// Debtor address
$transaction->setDebtorAddress([
    'street' => '456 Customer Avenue',
    'city' => 'London',
    'postalCode' => 'SW1A 1AA',
    'country' => 'GB',
]);
```

**Using array input (camelCase):**
```php
$data = [
    // ...
    'creditorAddress' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postalCode' => '28001',
        'country' => 'ES',
    ],
    'transactions' => [
        [
            // ...
            'debtorAddress' => [
                'street' => '456 Customer Avenue',
                'city' => 'London',
                'postalCode' => 'SW1A 1AA',
                'country' => 'GB',
            ],
        ],
    ],
];
```

**Using array input (snake_case):**
```php
$data = [
    // ...
    'creditor_address' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postal_code' => '28001',
        'country' => 'ES',
    ],
    'items' => [
        [
            // ...
            'debtor_address' => [
                'street' => '456 Customer Avenue',
                'city' => 'London',
                'postal_code' => 'SW1A 1AA',
                'country' => 'GB',
            ],
        ],
    ],
];
```

**Using individual fields (snake_case):**
```php
$data = [
    // ...
    'creditor_street' => '123 Business Street',
    'creditor_city' => 'Madrid',
    'creditor_postal_code' => '28001',
    'creditor_country' => 'ES',
    'items' => [
        [
            // ...
            'debtor_street' => '456 Customer Avenue',
            'debtor_city' => 'London',
            'debtor_postal_code' => 'SW1A 1AA',
            'debtor_country' => 'GB',
        ],
    ],
];
```

**No breaking changes**: If you were previously storing addresses in `additionalData`, they will now be automatically included in the XML. The old methods continue to work, but addresses are now exported to XML.

### Important Notes

- Addresses are **completely optional** - if not provided, no address elements will be added to the XML
- Addresses are **included in the generated XML only if provided** in the array (previously they were only stored internally)
- Empty address arrays are ignored and will not create address elements
- At least one address field (street, city, postalCode, or country) must be provided for the address to be included
- Address format follows SEPA structured address format (PstlAdr)
- Addresses are added to XML using DOM manipulation to ensure compatibility with the SEPA pain.008.001.02 format
- See [DEPRECATED_FIELDS.md](DEPRECATED_FIELDS.md) for information about which fields are still not allowed

## Upgrading to 0.0.7

### New Features

#### Snake_case Field Name Support

The `DirectDebitGenerator::generateFromArray()` method now supports both camelCase and snake_case field names. This means you can use either format:

**Before (camelCase only):**
```php
$data = [
    'reference' => 'MSG-001',
    'bankAccountOwner' => 'My Company',
    'paymentInfoId' => 'PMTINF-1',
    // ...
];
```

**Now (both formats work):**
```php
// camelCase (still works)
$data = [
    'reference' => 'MSG-001',
    'bankAccountOwner' => 'My Company',
    // ...
];

// snake_case (new support)
$data = [
    'message_id' => 'MSG-001',
    'initiating_party_name' => 'My Company',
    // ...
];
```

**No breaking changes**: Existing code using camelCase continues to work without modification.

#### Additional Fields Support

You can now add custom fields to DirectDebit transactions:

```php
$transaction = new DirectDebitTransaction(/* ... */);
$transaction->setDebtorBic('WESTGB22'); // Optional BIC
$transaction->setAdditionalField('customField', 'value'); // Custom data
```

### PHP 8.2 Compatibility Fix

Fixed constant type declarations that caused syntax errors in PHP 8.2. If you were experiencing parse errors with constants like `SERVICE_NAME`, this is now resolved.

**No action required**: The fix is backward compatible.

## Upgrading to 0.0.6

### Service Registration

Services now use Symfony attributes for automatic registration. If you were manually retrieving services by alias, the aliases remain the same:

- `nowo_sepa_payment.generator.direct_debit_generator`
- `nowo_sepa_payment.generator.remesa_generator`
- `nowo_sepa_payment.generator.identifier_generator`

**No action required**: Services are automatically registered and can be injected via constructor.

## Upgrading to 0.0.5

### Payment Method

The `setPaymentMethod()` calls have been removed from generators. Payment method is now automatically set by Digitick\Sepa v3.0 based on transfer file type.

**No action required**: This is handled internally.

## Upgrading to 0.0.4

### Digitick\Sepa v3.0 Compatibility

This version includes complete compatibility with Digitick\Sepa v3.0. The API changes are handled internally, so your code should continue to work.

**No action required**: The bundle handles all API changes internally.

## Upgrading to 0.0.3

### Breaking Changes from Digitick\Sepa v3.0

If you're upgrading from a version that used Digitick\Sepa v2.0, be aware that v3.0 introduced breaking changes. However, the bundle handles these changes internally, so your code should continue to work.

**No action required**: The bundle abstracts these changes.

## General Upgrade Notes

1. **Always test in a development environment first**
2. **Review the CHANGELOG** for detailed changes
3. **Check for deprecated methods** - they will be removed in future versions
4. **Update your tests** to match new behavior if needed

## Getting Help

If you encounter issues during upgrade:

1. Check the [CHANGELOG.md](CHANGELOG.md) for detailed changes
2. Review the [README.md](../README.md) for usage examples
3. Open an issue on GitHub with details about your upgrade path

