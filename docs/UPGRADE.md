# Upgrade Guide

This guide helps you upgrade between versions of the SEPA Payment Bundle.

## Upgrading to 0.0.11

### Service Auto-Registration with `#[AsAlias]` Attributes

All services now use Symfony's `#[AsAlias]` attribute for automatic service registration. This is a **non-breaking change** that improves code organization and follows Symfony best practices.

#### What Changed

- **All services now use `#[AsAlias]` attributes**: Every service class includes the `#[AsAlias]` attribute with its service alias and `public: true`
- **Simplified `services.yaml`**: Service definitions are now handled automatically via resource discovery and `#[AsAlias]` attributes
- **Resource-based service discovery**: Services are automatically discovered using `resource` directives in `services.yaml`
- **Consistent pattern**: All services follow the same pattern with a `SERVICE_NAME` constant

#### Impact Assessment

**✅ No action required - this is a non-breaking change:**

1. **Service behavior unchanged**: All services work exactly the same way
2. **Service aliases unchanged**: All service aliases remain the same
3. **Autowiring unchanged**: Services can still be injected via constructor type-hinting
4. **Explicit service retrieval unchanged**: Services can still be retrieved by their aliases

#### Benefits

- **Better code organization**: Service registration is now declarative in the classes themselves
- **Easier maintenance**: No need to maintain service definitions in `services.yaml` for most services
- **Symfony best practices**: Aligns with Symfony's recommended approach for service registration
- **Consistency**: All services follow the same registration pattern

#### Technical Details

Each service class now includes:

```php
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: self::SERVICE_NAME, public: true)]
class MyService
{
    public const SERVICE_NAME = 'nowo_sepa_payment.category.service_name';
    // ...
}
```

The `services.yaml` file now uses resource-based discovery:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: true

    Nowo\SepaPaymentBundle\Validator\:
        resource: '../../Validator/*'
    # ... similar for other namespaces
```

**No action required**: This change is completely transparent to users of the bundle.

## Upgrading to 0.0.10

### Service Configuration Changes

The service definitions in `services.yaml` have been updated to use service aliases directly instead of fully qualified class names. This is an **internal change** that improves consistency and aligns with Symfony best practices.

#### What Changed

- **Service IDs**: Service definitions now use aliases (e.g., `nowo_sepa_payment.validator.iban_validator`) as the service ID instead of class names
- **Service Dependencies**: All service arguments now reference other services by their aliases instead of class names
- **Consistency**: All services now follow a consistent naming pattern: `nowo_sepa_payment.{category}.{service_name}`

#### Impact Assessment

**✅ No action required for most users:**

1. **Autowiring (Type-hinting)**: If you inject services via constructor type-hinting, no changes needed:
   ```php
   class MyService
   {
       public function __construct(
           private IbanValidator $ibanValidator,
           private DirectDebitGenerator $generator
       ) {
       }
   }
   ```

2. **Using `#[Autowire]` with aliases**: If you're already using service aliases, no changes needed:
   ```php
   class MyService
   {
       public function __construct(
           #[Autowire('nowo_sepa_payment.generator.direct_debit_generator')]
           private DirectDebitGenerator $generator
       ) {
       }
   }
   ```

**⚠️ Action required only if:**

You're manually retrieving services by their fully qualified class name using `$container->get()` or similar methods.

**Before (needs update):**
```php
// ❌ This will no longer work
$ibanValidator = $container->get('Nowo\\SepaPaymentBundle\\Validator\\IbanValidator');
$generator = $container->get('Nowo\\SepaPaymentBundle\\Generator\\DirectDebitGenerator');
```

**After (updated code):**
```php
// ✅ Use service aliases instead
$ibanValidator = $container->get('nowo_sepa_payment.validator.iban_validator');
$generator = $container->get('nowo_sepa_payment.generator.direct_debit_generator');
```

#### How to Check if You Need to Update

Search your codebase for patterns like:
- `$container->get('Nowo\\SepaPaymentBundle\\`
- `$container->get(Nowo\SepaPaymentBundle\`
- `$this->get('Nowo\\SepaPaymentBundle\\`
- Any service locator patterns using class names

If you find any matches, update them to use the service aliases listed below.

#### Complete Service Alias Reference

All services are available via these aliases:

**Validators:**
- `nowo_sepa_payment.validator.iban_validator` - IBAN validator
- `nowo_sepa_payment.validator.bic_validator` - BIC validator
- `nowo_sepa_payment.validator.credit_card_validator` - Credit card validator

**Converters:**
- `nowo_sepa_payment.converter.ccc_converter` - CCC to IBAN converter

**Generators:**
- `nowo_sepa_payment.generator.remesa_generator` - Remesa (credit transfer) generator
- `nowo_sepa_payment.generator.direct_debit_generator` - Direct debit generator
- `nowo_sepa_payment.generator.identifier_generator` - Identifier generator

**Parsers:**
- `nowo_sepa_payment.parser.remesa_parser` - Remesa parser

#### Migration Example

If you have code like this:

```php
// Old way (needs update)
class PaymentService
{
    public function __construct(private ContainerInterface $container)
    {
    }
    
    public function validateIban(string $iban): bool
    {
        $validator = $this->container->get('Nowo\\SepaPaymentBundle\\Validator\\IbanValidator');
        return $validator->isValid($iban);
    }
}
```

Update it to:

```php
// New way (recommended - use autowiring)
class PaymentService
{
    public function __construct(private IbanValidator $ibanValidator)
    {
    }
    
    public function validateIban(string $iban): bool
    {
        return $this->ibanValidator->isValid($iban);
    }
}
```

Or if you must use the container:

```php
// Alternative (using alias)
class PaymentService
{
    public function __construct(private ContainerInterface $container)
    {
    }
    
    public function validateIban(string $iban): bool
    {
        $validator = $this->container->get('nowo_sepa_payment.validator.iban_validator');
        return $validator->isValid($iban);
    }
}
```

**Note**: Using autowiring (first example) is the recommended Symfony approach and doesn't require any changes.

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

