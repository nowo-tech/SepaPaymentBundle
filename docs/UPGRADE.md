# Upgrade Guide

This guide helps you upgrade between versions of the SEPA Payment Bundle.

## Upgrading from 1.2.1 to 1.2.2

### 🐛 Bug Fixes (1.2.2)

- **Fixed ValidationCache Tests**: Improved cache implementation for better test compatibility
  - Created `ArrayCache` class for testing that properly implements PSR-16 SimpleCache interface
  - Fixed `ValidationCache::get()` logic for better cache adapter compatibility
- **Fixed ParseDirectDebitCommand Tests**: Removed deprecated Symfony API usage
  - Removed `Application::add()` calls (not needed in Symfony 6+)
  - Commands with `#[AsCommand]` are automatically registered
- **Fixed ParseDirectDebitCommand Output**: Improved amount formatting
  - Amount values now display with 2 decimal places consistently

### Backward Compatibility

- No breaking changes
- All functionality remains the same
- Only test and internal improvements

---

## Upgrading from 1.2.0 to 1.2.1

### 🐛 Bug Fixes (1.2.1)

- **Fixed Deprecated Attribute Syntax**: Corrected invalid usage of `#[Deprecated]` attribute
  - The `#[Deprecated]` attribute in PHP only accepts `reason` and `since` parameters
  - The `replacement` parameter was incorrectly used and has been removed
  - This fix resolves PHP errors when using deprecated classes/methods

### Backward Compatibility

- No breaking changes
- All functionality remains the same

---

## Upgrading from 1.1.0 to 1.2.0

### ✨ New Features (1.2.0)

The following features were added in version 1.2.0:

1. **Mandate Management**: Complete mandate lifecycle management system for SEPA Direct Debit

### Mandate Management

The new `MandateService` provides complete lifecycle management for SEPA Direct Debit mandates:

```php
use Nowo\SepaPaymentBundle\Service\MandateService;
use Nowo\SepaPaymentBundle\Repository\MandateRepository;

$repository = new MandateRepository();
$mandateService = new MandateService($repository);

// Create a new mandate
$mandate = $mandateService->createMandate(
    'MANDATE-001',
    new \DateTime('2024-01-15'),
    'ES9121000418450200051332',
    'John Doe',
    'CORE',
    'FRST'
);

// Update sequence type (validates transition)
$mandateService->updateSequenceType('MANDATE-001', 'RCUR');

// Suspend mandate
$mandateService->suspendMandate('MANDATE-001');

// Reactivate mandate
$mandateService->reactivateMandate('MANDATE-001');

// Revoke mandate
$mandateService->revokeMandate('MANDATE-001', 'Customer request');

// Validate mandate for transaction
$isValid = $mandateService->validateMandateForTransaction('MANDATE-001', 'RCUR');

// Get mandate history
$history = $mandateService->getMandateHistory('MANDATE-001');
```

**Key Features:**
- Status tracking (ACTIVE, EXPIRED, REVOKED, SUSPENDED)
- Expiration date validation (defaults to 36 months after signature)
- Sequence type transition validation (FRST → RCUR/FNAL, RCUR → RCUR/FNAL, etc.)
- Complete history tracking for all changes
- Find mandates by debtor IBAN, status, or expiration date
- In-memory repository (can be extended with database implementation)

**Sequence Type Rules:**
- FRST (First) → RCUR (Recurring) or FNAL (Final)
- RCUR (Recurring) → RCUR or FNAL
- OOFF (One-off) → FNAL
- FNAL (Final) is terminal (no further transitions allowed)

### Backward Compatibility

- All existing functionality remains unchanged
- Mandate management is optional and doesn't affect existing code
- No breaking changes

---

## Upgrading from 1.0.0 to 1.1.0

### ✨ New Features (1.1.0)

1. **BIC Lookup Service**: Automatically look up BIC codes from IBANs

### BIC Lookup Service

The new `BicLookupService` automatically finds BIC codes when only IBANs are provided. This improves user experience by reducing manual work and errors.

**Basic Usage:**

```php
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$ibanValidator = new IbanValidator();
$bicLookup = new BicLookupService($ibanValidator);

// Look up BIC from IBAN
$iban = 'ES9121000418450200051332';
$bic = $bicLookup->lookupBic($iban);
// Returns: 'CAIXESBB' (if found)

// Check if lookup is available for an IBAN
if ($bicLookup->isAvailable($iban)) {
    $bic = $bicLookup->lookupBic($iban);
}
```

**Automatic Integration in Generators:**

When you inject `BicLookupService` into generators, BIC codes are automatically filled when missing:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$ibanValidator = new IbanValidator();
$bicLookup = new BicLookupService($ibanValidator);
$generator = new CreditTransferGenerator($ibanValidator, null, false, null, null, $bicLookup);

// Create data without BIC
$creditTransferData = new CreditTransferData(
    'MSG-001',
    new \DateTime(),
    'My Company',
    'PMT-001',
    'ES9121000418450200051332', // IBAN only, no BIC
    'My Company Name',
    new \DateTime('tomorrow')
);

// BIC will be automatically looked up and included in XML
$xml = $generator->generate($creditTransferData);
```

**Supported Countries:**

The service includes mappings for major banks in:
- 🇪🇸 Spain (ES)
- 🇩🇪 Germany (DE)
- 🇫🇷 France (FR)
- 🇮🇹 Italy (IT)
- 🇬🇧 United Kingdom (GB)
- 🇳🇱 Netherlands (NL)
- 🇧🇪 Belgium (BE)
- 🇵🇹 Portugal (PT)

**Adding Custom Mappings:**

You can add custom bank mappings for banks not in the default database:

```php
$bicLookup->addMapping('ES', '9999', 'CUSTOMBIC');
// Now IBANs with bank code 9999 will return 'CUSTOMBIC'
```

**Cache Support (Optional):**

You can use a PSR-16 compatible cache to cache lookup results:

```php
use Psr\SimpleCache\CacheInterface;

$cache = /* your cache implementation */;
$bicLookup = new BicLookupService($ibanValidator, $cache, 86400); // 24 hour TTL
```

### Backward Compatibility

- BIC lookup is **completely optional** - existing code works without changes
- Generators accept optional `BicLookupServiceInterface` parameter (backward compatible)
- If BIC lookup service is not provided, generators work exactly as before
- No breaking changes

### Additional New Features (Unreleased)

1. **Mandate Management**: Complete mandate lifecycle management system
2. **Validation Caching**: Cache validation results for improved performance
3. **Console Command for Direct Debit Parsing**: Parse Direct Debit XML files from command line
4. **Validation Events**: Event system for validation operations
5. **Export Service**: Export SEPA payment data to JSON and CSV formats
6. **Symfony Events**: Event system for extensibility
7. **Structured Logging**: Comprehensive logging for SEPA operations
8. **SEPA String Sanitization**: Validate and sanitize strings according to SEPA character rules
9. **SEPA Country Validation**: Validate SEPA member countries
10. **SEPA Business Rules Validation**: Validate SEPA limits and business rules

### Export Service

The new `ExportService` allows you to export parsed SEPA data to various formats:

```php
use Nowo\SepaPaymentBundle\Exporter\ExportService;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;

$parser = new CreditTransferParser();
$exporter = new ExportService();

// Parse XML
$data = $parser->parseCreditTransfer($xml);

// Export to JSON
$json = $exporter->exportCreditTransferToJson($data, true);

// Export to CSV
$csv = $exporter->exportCreditTransferToCsv($data);
```

The service is automatically registered and can be injected via dependency injection.

### Symfony Events

The bundle now dispatches events before and after XML generation. You can listen to these events to modify data or XML:

```php
use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SepaPaymentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCreditTransferGenerationEvent::class => 'onBeforeGeneration',
        ];
    }

    public function onBeforeGeneration(BeforeCreditTransferGenerationEvent $event): void
    {
        $data = $event->getCreditTransferData();
        // Modify data before generation
        $event->setCreditTransferData($data);
    }
}
```

Register your event subscriber in `config/services.yaml`:

```yaml
services:
    App\EventListener\SepaPaymentSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

### Structured Logging

The new `SepaPaymentLogger` service provides structured logging for all SEPA operations. It integrates with PSR-3 logging interfaces for maximum compatibility.

**Basic Usage:**

```php
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Psr\Log\LoggerInterface;

// Logger is automatically registered and can be injected
$logger = new SepaPaymentLogger($psrLogger); // Optional: defaults to NullLogger

// Or inject via dependency injection
class PaymentService
{
    public function __construct(
        private SepaPaymentLogger $logger
    ) {
    }
}
```

**Automatic Integration in Generators:**

When you inject `SepaPaymentLogger` into generators, operations are automatically logged:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$ibanValidator = new IbanValidator();
$logger = new SepaPaymentLogger($psrLogger); // Your PSR-3 logger

$generator = new CreditTransferGenerator(
    $ibanValidator,
    null, // XSD validator (optional)
    false, // validate XSD (optional)
    null, // event dispatcher (optional)
    $logger // logger (optional)
);

// Generation events are automatically logged
$xml = $generator->generate($creditTransferData);
```

**Logging Methods:**

The logger provides structured methods for:
- Credit Transfer generation (start, success, failure)
- Direct Debit generation (start, success, failure)
- Validation events (IBAN, BIC, XSD)
- Parsing events (Credit Transfer and Direct Debit)

All log entries include contextual data like messageId, transactionCount, and error messages.

### Backward Compatibility

- All existing functionality remains unchanged
- Mandate management is optional and doesn't affect existing code
- No breaking changes

---

## Upgrading from 1.0.0 to 1.1.0

### ✨ New Features (1.1.0)

The following features were added in version 1.1.0:

1. **BIC Lookup Service**: Automatically look up BIC codes from IBANs
2. **Validation Caching**: Cache validation results for improved performance
3. **Console Command for Direct Debit Parsing**: Parse Direct Debit XML files from command line
4. **Validation Events**: Event system for validation operations
5. **Export Service**: Export SEPA payment data to JSON and CSV formats
6. **Symfony Events**: Event system for extensibility
7. **Structured Logging**: Comprehensive logging for SEPA operations
8. **SEPA String Sanitization**: Validate and sanitize strings according to SEPA character rules
9. **SEPA Country Validation**: Validate SEPA member countries
10. **SEPA Business Rules Validation**: Validate SEPA limits and business rules

### Backward Compatibility

- All existing functionality remains unchanged
- Generators accept optional `EventDispatcherInterface` parameter (backward compatible)
- Generators accept optional `SepaPaymentLogger` parameter (backward compatible)
- Export service is optional and doesn't affect existing code
- Logger service is optional and doesn't affect existing code
- No breaking changes

### Planned Breaking Changes (Future Versions)

**Note**: The following changes are planned for future versions but have not been released yet:

- **Version 2.0.0 (Planned)**: All "Remesa" classes will be removed (deprecated since 1.1.0)
- **Version 1.1.0**: All "Remesa" classes were deprecated in favor of "CreditTransfer" classes

These changes are documented here for reference.

#### Class Renames (Planned)

**Generators and Parsers:**
- `RemesaGenerator` → `CreditTransferGenerator`
- `RemesaParser` → `CreditTransferParser`

**Models:**
- `RemesaData` → `CreditTransferData`
- Namespace `Model\Remesa` → `Model\CreditTransfer`
- `Transaction` class moved to `Model\CreditTransfer` namespace

**Service Aliases:**
- `nowo_sepa_payment.generator.remesa_generator` → `nowo_sepa_payment.generator.credit_transfer_generator`
- `nowo_sepa_payment.parser.remesa_parser` → `nowo_sepa_payment.parser.credit_transfer_parser`

#### Migration Steps

1. **Update imports:**
```php
// Before
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Parser\RemesaParser;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;

// After
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
```

2. **Update class instantiations:**
```php
// Before
$generator = new RemesaGenerator($ibanValidator);
$parser = new RemesaParser();
$data = new RemesaData(/* ... */);

// After
$generator = new CreditTransferGenerator($ibanValidator);
$parser = new CreditTransferParser();
$data = new CreditTransferData(/* ... */);
```

3. **Update variable names (optional but recommended):**
```php
// Before
$remesaData = new RemesaData(/* ... */);
$xml = $generator->generate($remesaData);

// After
$creditTransferData = new CreditTransferData(/* ... */);
$xml = $generator->generate($creditTransferData);
```

4. **Update service aliases in configuration (if used):**
```yaml
# Before
services:
    my_service:
        arguments:
            $generator: '@nowo_sepa_payment.generator.remesa_generator'
            $parser: '@nowo_sepa_payment.parser.remesa_parser'

# After
services:
    my_service:
        arguments:
            $generator: '@nowo_sepa_payment.generator.credit_transfer_generator'
            $parser: '@nowo_sepa_payment.parser.credit_transfer_parser'
```

5. **Update dependency injection (if using type hints):**
```php
// Before
public function __construct(
    private RemesaGenerator $remesaGenerator,
    private RemesaParser $remesaParser
) {}

// After
public function __construct(
    private CreditTransferGenerator $creditTransferGenerator,
    private CreditTransferParser $creditTransferParser
) {}
```

#### Why This Change?

This change improves code clarity and consistency by using standard English terminology ("Credit Transfer") instead of the Spanish term "Remesa". This makes the bundle more accessible to international developers and aligns with SEPA documentation standards.

#### Timeline

- **Version 1.1.0** (Current): Old classes are deprecated but still functional
- **Version 2.0.0** (Future): Old classes will be completely removed

**Recommendation**: Start migrating to the new classes now to avoid issues when upgrading to version 2.0.0.

#### Impact Assessment

- **Low Immediate Impact**: Old classes still work but will show deprecation warnings
- **Medium Long-term Impact**: Old classes will be removed in version 2.0.0, so migration is recommended
- **Service Aliases**: Old service aliases still work but are deprecated. Update to new aliases when possible
- **Type Hints**: Update type hints in constructors and method signatures to avoid deprecation warnings
- **Variable Names**: While variable names are optional to update, it's recommended for consistency

#### Deprecation Warnings

When using deprecated classes, you will see PHP deprecation warnings like:
```
Deprecated: RemesaGenerator is deprecated since 1.1.0. Use CreditTransferGenerator instead.
```

To suppress these warnings temporarily (not recommended for production), you can:
- Set `error_reporting` to exclude `E_USER_DEPRECATED`
- Use `@` operator to suppress warnings (not recommended)
- **Best practice**: Migrate to new classes as soon as possible

#### Automated Migration

You can use find-and-replace in your IDE to quickly update:
- Find: `RemesaGenerator` → Replace: `CreditTransferGenerator`
- Find: `RemesaParser` → Replace: `CreditTransferParser`
- Find: `RemesaData` → Replace: `CreditTransferData`
- Find: `Model\Remesa` → Replace: `Model\CreditTransfer`
- Find: `remesa_generator` → Replace: `credit_transfer_generator`
- Find: `remesa_parser` → Replace: `credit_transfer_parser`

## Upgrading to 1.0.0

### 🎉 First Stable Release

This is the first stable release (1.0.0) of the SEPA Payment Bundle. The bundle is now considered production-ready with comprehensive features, extensive test coverage, and complete documentation.

### What's New

1. **DirectDebitParser**: Complete SEPA Direct Debit XML parser
   - Parse SEPA Direct Debit XML files (pain.008.001.02 format)
   - Extract all payment and transaction information
   - Validate XML structure
   - Full address support (creditor and debtor)

2. **Enhanced Test Coverage**: Additional test cases for both parsers
   - Better edge case coverage
   - Improved validation testing
   - More comprehensive scenarios

3. **Documentation Improvements**: Enhanced examples and documentation
   - Parser examples in all demo applications
   - Improved code documentation

### Breaking Changes

**None** - This release is fully backward compatible with 0.0.12.

### Migration Steps

No migration required. Simply update your `composer.json`:

```bash
composer require nowo-tech/sepa-payment-bundle:^1.0
```

### New Features You Can Use

**DirectDebitParser** - Parse SEPA Direct Debit XML files:

```php
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;

$parser = new DirectDebitParser();

// Parse a Direct Debit XML file
$data = $parser->parseDirectDebit($xml);

// Access parsed data
echo $data['messageId'];
echo $data['creditorName'];
echo $data['transactions'][0]['amount'];

// Validate XML
if ($parser->isValidDirectDebit($xml)) {
    // Process the file
}
```

See [docs/USAGE.md](docs/USAGE.md) for complete examples.

## Upgrading to 0.0.12

### CreditTransferGenerator: New Features and Feature Parity

`CreditTransferGenerator` (formerly `RemesaGenerator` in versions < 2.0.0) now has complete feature parity with `DirectDebitGenerator`, including array-based generation and postal address support.

#### What's New

1. **`generateFromArray()` Method**: You can now generate SEPA Credit Transfer XML directly from arrays
2. **Postal Address Support**: Both creditor and debtor addresses can be included in the XML
3. **Snake_case Support**: Field names can be in either camelCase or snake_case format

#### New Features

**Array-Based Generation:**

```php
// Before (only object-based)
$remesaData = new RemesaData(/* ... */);
$xml = $generator->generate($remesaData);

// Now (array-based, also available)
$data = [
    'reference' => 'MSG-001',
    'initiatingPartyName' => 'My Company',
    // ...
];
$xml = $generator->generateFromArray($data);
```

**Postal Address Support:**

```php
// Creditor address
$creditTransferData->setCreditorAddress([
    'street' => '123 Business Street',
    'city' => 'Madrid',
    'postalCode' => '28001',
    'country' => 'ES',
]);

// Debtor address in transaction
$transaction->setDebtorAddress([
    'street' => '456 Customer Avenue',
    'city' => 'London',
    'postalCode' => 'SW1A 1AA',
    'country' => 'GB',
]);
```

**Using with Arrays:**

```php
$data = [
    'reference' => 'MSG-001',
    // ...
    'creditorAddress' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postalCode' => '28001',
        'country' => 'ES',
    ],
    'transactions' => [
        [
            'amount' => 100.50,
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
$xml = $generator->generateFromArray($data);
```

#### Impact Assessment

**✅ No breaking changes - this is a non-breaking addition:**

1. **Existing code continues to work**: The `generate()` method with objects still works exactly as before
2. **New methods are optional**: You can continue using the object-based approach or switch to arrays
3. **Addresses are optional**: If you don't provide addresses, the XML is generated without them (same as before)

#### Benefits

- **Consistency**: Both generators (`CreditTransferGenerator` and `DirectDebitGenerator`) now have the same API
- **Flexibility**: Choose between object-based or array-based generation
- **Address Support**: Include postal addresses in Credit Transfer XML files
- **Snake_case Support**: Use either naming convention for field names

**No action required**: Existing code will continue to work without any changes. The new features are optional additions.

### Demo Routes Translation

All demo application routes have been translated from Spanish to English:

**Before (Spanish routes):**
- `/demo-remesa-pago` → **Now**: `/demo-credit-transfer`
- `/demo-remesa-pago-array` → **Now**: `/demo-credit-transfer-array`
- `/demo-remesa-pago-with-addresses` → **Now**: `/demo-credit-transfer-with-addresses`
- `/demo-remesa-pago-snake-case` → **Now**: `/demo-credit-transfer-snake-case`
- `/demo-remesa-cobro` → **Now**: `/demo-direct-debit`
- `/demo-remesa-cobro-snake-case` → **Now**: `/demo-direct-debit-snake-case`
- `/demo-remesa-cobro-with-addresses` → **Now**: `/demo-direct-debit-with-addresses`

**Impact**: If you have bookmarks or links to demo endpoints, update them to use the new English routes. Route names have also changed (e.g., `demo_remesa_pago` → `demo_credit_transfer`).

### Code Documentation

All PHPDoc comments have been translated to English for consistency:
- Class descriptions now use English terminology
- Parameter descriptions use English
- Example filenames in comments use English names
- Improved documentation for address handling methods to clarify how DOM manipulation is used as a fallback when library methods are not available

**No code changes required**: This is a documentation-only change that doesn't affect functionality. The improved PHPDoc comments provide better clarity on how address handling works internally.

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
- `nowo_sepa_payment.generator.credit_transfer_generator` - Credit transfer generator
- `nowo_sepa_payment.generator.direct_debit_generator` - Direct debit generator
- `nowo_sepa_payment.generator.identifier_generator` - Identifier generator

**Parsers:**
- `nowo_sepa_payment.parser.credit_transfer_parser` - Credit transfer parser

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

Both `DirectDebitGenerator` and `CreditTransferGenerator` now include a `createResponse()` method that simplifies returning XML files as HTTP responses in Symfony controllers.

**Before:**
```php
$xml = $generator->generateFromArray($data);
return new Response($xml, 200, [
    'Content-Type' => 'application/xml',
    'Content-Disposition' => 'attachment; filename="direct-debit.xml"',
]);
```

**After:**
```php
$xml = $generator->generateFromArray($data);
return $generator->createResponse($xml, 'direct-debit.xml');
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
- `nowo_sepa_payment.generator.credit_transfer_generator`
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

