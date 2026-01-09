# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.2] - 2026-01-09

### Fixed
- **ValidationCache Tests**: Fixed cache implementation for tests
  - Created `ArrayCache` class for testing that properly implements PSR-16 SimpleCache interface methods
  - Fixed `ValidationCache::get()` logic to check `has()` before calling `get()` for better compatibility
  - All ValidationCache tests now pass correctly
- **ParseDirectDebitCommand Tests**: Fixed Symfony 6+ compatibility
  - Removed deprecated `Application::add()` calls from tests (not needed in Symfony 6+)
  - Commands with `#[AsCommand]` attribute are automatically registered
  - All ParseDirectDebitCommand tests now pass correctly
- **ParseDirectDebitCommand Output**: Fixed amount formatting
  - Amount values are now formatted with 2 decimal places using `number_format()`
  - Ensures consistent display format (e.g., "100.50" instead of "100.5")

## [1.2.1] - 2026-01-09

### Fixed
- **Deprecated Attribute**: Fixed incorrect usage of `#[Deprecated]` attribute with invalid `replacement` parameter
  - Removed invalid `replacement` parameter from `#[Deprecated]` attribute in `RemesaGenerator` methods
  - Removed invalid `replacement` parameter from `#[Deprecated]` attribute in `RemesaParser` methods
  - Corrected attribute syntax to use only valid parameters: `reason` and `since`
  - The `replacement` parameter only exists in PHPDoc `@deprecated` annotations, not in PHP `#[Deprecated]` attribute

## [1.2.0] - 2026-01-09

### Added
- **Mandate Management**: Complete mandate lifecycle management system for SEPA Direct Debit
  - `MandateService` - Service for managing mandate lifecycle (create, suspend, reactivate, revoke)
  - `MandateRepositoryInterface` and `MandateRepository` - Repository pattern for mandate storage
  - `MandateStatus` enum - Status enumeration (ACTIVE, EXPIRED, REVOKED, SUSPENDED)
  - `MandateHistory` - History tracking for mandate changes
  - Enhanced `Mandate` model with status, expiration date, and revocation support
  - Mandate expiration date validation (defaults to 36 months after signature date)
  - Mandate sequence type transition validation (FRST → RCUR/FNAL, RCUR → RCUR/FNAL, etc.)
  - Mandate history tracking for all status and sequence type changes
  - `createMandate()` - Create new mandates
  - `updateSequenceType()` - Update mandate sequence type with validation
  - `revokeMandate()` - Revoke mandates with optional reason
  - `suspendMandate()` - Suspend mandates temporarily
  - `reactivateMandate()` - Reactivate suspended mandates
  - `validateMandateForTransaction()` - Validate mandates before use in transactions
  - `isValidSequenceTransition()` - Check if sequence type transitions are valid
  - `findMandatesByDebtorIban()` - Find all mandates for a debtor
  - `findActiveMandates()` - Find all active mandates
  - `findExpiredMandates()` - Find expired mandates
  - `getMandateHistory()` - Get complete history for a mandate
  - In-memory repository implementation (can be extended with database-backed implementation)
  - Comprehensive test coverage with 12 test cases
  - Demo endpoints: `/demo-mandate-management`, `/demo-mandate-lifecycle`, `/demo-mandate-sequence-transitions`

## [1.1.0] - 2026-01-09

### Added
- **Validation Caching**: New caching system for validation results to improve performance
  - `ValidationCacheInterface` and `ValidationCache` - Service for caching validation results (PSR-16 SimpleCache compatible)
  - `CachedIbanValidator` - Cached wrapper for IBAN validation
  - `CachedBicValidator` - Cached wrapper for BIC validation
  - Optional cache adapter support (works without cache, backward compatible)
  - Configurable TTL for cached results
  - Cache key normalization and management
- **Console Command for Direct Debit Parsing**: New command to parse SEPA Direct Debit XML files
  - `nowo:sepa:parse-direct-debit` - Parse and display Direct Debit XML information
  - JSON output option (`--json`)
  - Displays group header, payment information, and transaction details
  - Validates XML structure before parsing
- **Validation Events**: Event system for validation operations
  - `BeforeValidationEvent` - Dispatched before validation (allows custom validation logic)
  - `AfterValidationEvent` - Dispatched after validation (for logging and monitoring)
  - Full integration with Symfony EventDispatcher
- **Automatic BIC Lookup by IBAN**: New `BicLookupService` for automatically looking up BIC codes from IBANs
  - `BicLookupServiceInterface` - Interface for BIC lookup services
  - `BicLookupService` - Implementation with local database of IBAN to BIC mappings
  - Support for 8 countries: ES (Spain), DE (Germany), FR (France), IT (Italy), GB (UK), NL (Netherlands), BE (Belgium), PT (Portugal)
  - Automatic BIC lookup in `CreditTransferGenerator` and `DirectDebitGenerator` when BIC is missing
  - Optional cache support (PSR-16 SimpleCache compatible)
  - `lookupBic()` - Look up BIC code for a given IBAN
  - `isAvailable()` - Check if BIC lookup is available for a given IBAN
  - `addMapping()` - Add custom IBAN to BIC mappings for bank-specific codes
  - Country-specific bank code extraction patterns for accurate BIC lookup
  - Comprehensive local database with common bank mappings
- **Export to Other Formats**: New `ExportService` for exporting SEPA payment data to various formats
  - `exportCreditTransferToJson()` - Export Credit Transfer data to JSON format (with pretty print option)
  - `exportDirectDebitToJson()` - Export Direct Debit data to JSON format (with pretty print option)
  - `exportCreditTransferToCsv()` - Export Credit Transfer data to CSV format (customizable delimiter and enclosure)
  - `exportDirectDebitToCsv()` - Export Direct Debit data to CSV format (customizable delimiter and enclosure)
  - `importCreditTransferFromJson()` - Import Credit Transfer data from JSON format
  - `importDirectDebitFromJson()` - Import Direct Debit data from JSON format
  - Full support for multiple transactions in CSV exports
  - Comprehensive CSV headers for both Credit Transfer and Direct Debit formats
- **Symfony Events**: Event system for extensibility without modifying bundle code
  - `BeforeCreditTransferGenerationEvent` - Dispatched before Credit Transfer XML generation (allows data modification)
  - `AfterCreditTransferGenerationEvent` - Dispatched after Credit Transfer XML generation (allows XML modification)
  - `BeforeDirectDebitGenerationEvent` - Dispatched before Direct Debit XML generation (allows data modification)
  - `AfterDirectDebitGenerationEvent` - Dispatched after Direct Debit XML generation (allows XML modification)
  - Event dispatcher integration in `CreditTransferGenerator` and `DirectDebitGenerator` (optional dependency injection)
  - Full support for event listeners and subscribers to modify data and XML
- **Structured Logging**: New `SepaPaymentLogger` service for structured logging of SEPA operations
  - Integration with `Psr\Log\LoggerInterface` for flexible logging backends
  - Structured logging methods for Credit Transfer and Direct Debit generation (start, success, failure)
  - Structured logging methods for validation events (IBAN, BIC, XSD validation)
  - Structured logging methods for parsing events (Credit Transfer and Direct Debit parsing)
  - Context data included in all log entries (messageId, transactionCount, etc.)
  - Optional logger injection in generators (backward compatible)
  - Automatic logging of generation lifecycle events
- **SEPA String Sanitization**: New `SepaStringSanitizer` service for validating and sanitizing strings according to SEPA character rules
  - Validates allowed characters in SEPA names and addresses
  - Sanitizes invalid characters and replaces accented characters with ASCII equivalents
  - Validates and truncates field lengths (names, addresses, remittance information)
  - Supports maximum length validation for all SEPA fields
- **SEPA Country Validation**: New `SepaCountryValidator` service for validating SEPA member countries
  - Validates if a country code is a SEPA member (34 countries supported)
  - Validates country from IBAN
  - Provides country names for all SEPA member countries
  - Includes all current SEPA members (EU, EEA, Switzerland, UK, Monaco, San Marino)
- **SEPA Business Rules Validation**: New `SepaBusinessRulesValidator` service for validating SEPA business rules and limits
  - Validates transaction amount limits (max: 999,999,999.99 EUR)
  - Validates transaction count limits (max: 99,999 per file)
  - Validates execution date rules (must be today or future)
  - Validates business day rules (Monday to Friday)
  - Validates currency restrictions (EUR only for SEPA)
  - Validates mandate expiration dates
  - Validates sequence type transitions for Direct Debit (FRST → RCUR, etc.)
  - Comprehensive validation methods for Credit Transfer and Direct Debit

### Improved
- **Generators**: 
  - `CreditTransferGenerator` now auto-fills creditor and debtor BIC when missing (if `BicLookupService` is injected)
  - `DirectDebitGenerator` now auto-fills creditor and debtor BIC when missing (if `BicLookupService` is injected)
  - BIC lookup is optional and backward compatible (only works if service is injected)
- **Demo Applications**: 
  - Added new demo endpoints for export/import functionality, BIC lookup, string sanitization, country validation, business rules validation, validation caching, and mandate management
  - Added `/demo-validation-cache-iban` - Demonstrates IBAN validation caching with performance comparison
  - Added `/demo-validation-cache-bic` - Demonstrates BIC validation caching with performance comparison
  - Added `/demo-sepa-string-sanitizer` - Demonstrates SEPA string sanitization
  - Added `/demo-sepa-country-validator` - Demonstrates SEPA country validation
  - Added `/demo-sepa-business-rules` - Demonstrates SEPA business rules validation
  - Added `/demo-mandate-management` - Demonstrates mandate creation and management
  - Added `/demo-mandate-lifecycle` - Demonstrates mandate lifecycle (create, suspend, reactivate, revoke)
  - Added `/demo-mandate-sequence-transitions` - Demonstrates sequence type transition validation
  - Fixed JsonResponse usage in all demo endpoints (proper encoding options with `setEncodingOptions()`)
  - All demo endpoints now properly format JSON output with pretty print and unicode support
- **Documentation**: 
  - Updated `USAGE.md` with comprehensive examples for all new services
  - Updated `FUTURE.md` to mark Validation Caching and Mandate Management as completed
  - Updated `UPGRADE.md` with Mandate Management documentation
- **Test Coverage**: 
  - Added 5 new tests for `ValidationCache` (`ValidationCacheTest.php`)
  - Added 4 new tests for `CachedIbanValidator` (`CachedIbanValidatorTest.php`)
  - Added 4 new tests for `CachedBicValidator` (`CachedBicValidatorTest.php`)
  - Added 4 new tests for `ParseDirectDebitCommand` (`ParseDirectDebitCommandTest.php`)
  - Added 2 new tests for validation events (`ValidationEventTest.php`)
  - Added 12 new tests for `MandateService` (`MandateServiceTest.php`)
  - Total: 335+ tests, 1042+ assertions

## [1.0.0] - 2026-01-09

### Added
- **DirectDebitParser**: Complete SEPA Direct Debit XML parser for feature parity with Credit Transfer parser
  - `parseDirectDebit()` method to extract all information from SEPA Direct Debit XML files
  - `isValidDirectDebit()` method to validate XML structure
  - Full support for parsing:
    - Group header (message ID, creation date, initiating party)
    - Payment information (payment info ID, sequence type, due date, local instrument code)
    - Creditor information (name, IBAN, BIC, creditor ID)
    - Transaction details (end-to-end ID, amount, currency, debtor information, mandate details)
    - Creditor and debtor postal addresses (full and partial)
  - Supports pain.008.001.02 format
  - Comprehensive test coverage with 7 test cases

### Improved
- **Test Coverage**: Added comprehensive test cases for both parsers:
  - RemesaParser: Added 4 new test cases (multiple transactions, addresses, optional fields, different currencies)
  - DirectDebitParser: Added 5 new test cases (sequence types, missing BIC, missing remittance info, B2B instrument, optional fields)
  - Total test suite: 182+ tests with comprehensive coverage
- **Documentation**: Enhanced documentation with parser examples in all demo applications
- **Badge Fix**: Replaced PUGX PHP version badge with shields.io for better reliability

### Fixed
- Fixed PHP version badge not displaying correctly by switching from PUGX to shields.io

## [0.0.12] - 2026-01-09

### Added
- **RemesaGenerator: Complete Feature Parity with DirectDebitGenerator**:
  - Added `generateFromArray()` method to `RemesaGenerator` for generating SEPA Credit Transfer XML from array data
  - Supports both camelCase and snake_case field names (same as DirectDebitGenerator)
  - Added postal address support for creditor in `RemesaData`:
    - `setCreditorAddress()` - Set creditor address (array or individual fields)
    - `setCreditorAddressFromArray()` - Set creditor address from array
    - `getCreditorAddress()` - Get creditor address
  - Added postal address support for debtor in `Transaction`:
    - `setDebtorAddress()` - Set debtor address (array or individual fields)
    - `setDebtorAddressFromArray()` - Set debtor address from array
    - `getDebtorAddress()` - Get debtor address
  - Addresses are automatically included in the generated XML (pain.001.001.03 format)
  - Addresses are added using DOM manipulation to ensure compatibility with SEPA standards
  - Support for address fields in `generateFromArray()` method:
    - `creditorAddress` or individual fields: `creditorStreet`, `creditorCity`, `creditorPostalCode`, `creditorCountry`
    - `debtorAddress` or individual fields: `debtorStreet`, `debtorCity`, `debtorPostalCode`, `debtorCountry`
    - Both camelCase and snake_case formats supported

### Improved
- **Feature Parity**: `RemesaGenerator` now has the same functionality as `DirectDebitGenerator`:
  - Array-based generation with `generateFromArray()`
  - Postal address support (creditor and debtor)
  - Snake_case and camelCase field name support
  - Automatic address inclusion in XML
- **Demo Applications**: Updated all demo applications (Symfony 6, 7, 8) with new endpoints:
  - `/demo-credit-transfer-array` - Generate from array (camelCase)
  - `/demo-credit-transfer-with-addresses` - Generate from array with addresses
  - `/demo-credit-transfer-snake-case` - Generate from array (snake_case)
  - Updated existing `/demo-credit-transfer` to include address examples
- **Test Coverage**: Added comprehensive test coverage for new `RemesaGenerator` features:
  - Tests for `generateFromArray()` method with camelCase and snake_case formats
  - Tests for address handling (creditor and debtor) in both array and object formats
  - Tests for `RemesaData` and `Transaction` address methods
  - Edge case tests (empty arrays, missing fields, validation)
  - Additional tests for DateTimeInterface support (requestedExecutionDate, creationDate)
  - Tests for amount conversion from cents (> 10000)
  - Tests for optional fields (creditorBic, debtorBic, remittanceInformation, currency, batchBooking)
  - Tests for date type validation (invalid types)
  - Tests for multiple transactions with addresses
  - Total of 30 tests for `RemesaGenerator` (12 new tests added)
  - Test coverage improved to 83.08% lines for `RemesaGenerator`
  - Overall test suite: 173 tests, 608 assertions
- **Documentation**: Improved documentation structure:
  - Moved detailed usage examples to `docs/USAGE.md`
  - Moved console commands documentation to `docs/COMMANDS.md`
  - Moved demo information to `docs/DEMOS.md`
  - Moved development guidelines to `docs/DEVELOPMENT.md`
  - Main `README.md` now provides concise overview with references
- **Internationalization**: All demo routes and templates now use English:
  - **Routes translated to English**:
    - `/demo-remesa-pago` → `/demo-credit-transfer`
    - `/demo-remesa-pago-array` → `/demo-credit-transfer-array`
    - `/demo-remesa-pago-with-addresses` → `/demo-credit-transfer-with-addresses`
    - `/demo-remesa-pago-snake-case` → `/demo-credit-transfer-snake-case`
    - `/demo-remesa-cobro` → `/demo-direct-debit`
    - `/demo-remesa-cobro-snake-case` → `/demo-direct-debit-snake-case`
    - `/demo-remesa-cobro-with-addresses` → `/demo-direct-debit-with-addresses`
  - **Route names translated**: All route names now use English (e.g., `demo_credit_transfer` instead of `demo_remesa_pago`)
  - **File names translated**: Generated XML files now use English names (e.g., `credit-transfer.xml` instead of `remesa-pago.xml`)
  - **Templates translated**: All demo templates now use English text:
    - Replaced "Remesa de Pago" with "Credit Transfer"
    - Replaced "Remesa de Cobro" with "Direct Debit"
    - All user-facing text in demo applications is now in English
- **Code Documentation**: All PHPDoc comments translated to English:
  - Replaced "remesa data" with "credit transfer data" in all PHPDoc comments
  - Updated example filenames in PHPDoc: `remesa-pago.xml` → `credit-transfer.xml`
  - Updated example filenames in PHPDoc: `remesa-cobro.xml` → `direct-debit.xml`
  - Updated class descriptions to use English terminology
  - All PHPDoc comments now consistently use English
  - Improved PHPDoc comments for address handling methods to clarify DOM manipulation fallback mechanism
  - Added PHPStan ignore annotations for dynamic method calls verified with `method_exists()`

## [0.0.11] - 2025-12-19

### Added
- **Complete Service Auto-Registration with `#[AsAlias]` Attributes**: All services now use Symfony's `#[AsAlias]` attribute for automatic service registration
  - Added `#[AsAlias]` attribute to all service classes:
    - `IbanValidator` - `nowo_sepa_payment.validator.iban_validator`
    - `BicValidator` - `nowo_sepa_payment.validator.bic_validator`
    - `CreditCardValidator` - `nowo_sepa_payment.validator.credit_card_validator`
    - `CccConverter` - `nowo_sepa_payment.converter.ccc_converter`
    - `RemesaParser` - `nowo_sepa_payment.parser.remesa_parser`
    - `RemesaGenerator` - `nowo_sepa_payment.generator.remesa_generator` (already had it)
    - `DirectDebitGenerator` - `nowo_sepa_payment.generator.direct_debit_generator` (already had it)
    - `IdentifierGenerator` - `nowo_sepa_payment.generator.identifier_generator` (already had it)
  - Each service class now includes a `SERVICE_NAME` constant for consistent alias naming
  - Services are automatically registered as public services via the `#[AsAlias]` attribute

### Changed
- **Simplified Service Configuration**: Updated `services.yaml` to use resource-based service discovery
  - Services are now automatically discovered via `resource` directives for each namespace
  - Removed manual service definitions (now handled by `#[AsAlias]` attributes)
  - All services are configured as public by default
  - Only console commands require explicit configuration (for tags and arguments)
  - This follows Symfony best practices for modern service registration

### Improved
- **Code Organization**: All services follow a consistent pattern with `#[AsAlias]` attributes
- **Maintainability**: Service registration is now declarative in the classes themselves rather than in configuration files
- **Symfony Best Practices**: Aligns with Symfony's recommended approach for service registration using attributes

## [0.0.10] - 2025-12-17

### Changed
- **Service Configuration**: Updated service definitions in `services.yaml` to use service aliases directly instead of fully qualified class names
  - All services now use consistent alias naming: `nowo_sepa_payment.{category}.{service_name}`
  - Service IDs changed from class names to aliases:
    - `Nowo\SepaPaymentBundle\Validator\IbanValidator` → `nowo_sepa_payment.validator.iban_validator`
    - `Nowo\SepaPaymentBundle\Validator\BicValidator` → `nowo_sepa_payment.validator.bic_validator`
    - `Nowo\SepaPaymentBundle\Validator\CreditCardValidator` → `nowo_sepa_payment.validator.credit_card_validator`
    - `Nowo\SepaPaymentBundle\Converter\CccConverter` → `nowo_sepa_payment.converter.ccc_converter`
    - `Nowo\SepaPaymentBundle\Generator\RemesaGenerator` → `nowo_sepa_payment.generator.remesa_generator`
    - `Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator` → `nowo_sepa_payment.generator.direct_debit_generator`
    - `Nowo\SepaPaymentBundle\Generator\IdentifierGenerator` → `nowo_sepa_payment.generator.identifier_generator`
    - `Nowo\SepaPaymentBundle\Parser\RemesaParser` → `nowo_sepa_payment.parser.remesa_parser`
  - All service arguments now reference services by their aliases instead of class names
  - This change improves consistency with Symfony best practices and aligns with the `#[AsAlias]` attributes used in generator classes
  - Services can still be injected by type-hinting (autowiring) or by their aliases using `#[Autowire]` attribute

### Improved
- **Service Dependency Resolution**: Service dependencies now use alias references, making the service container configuration more maintainable and consistent

## [0.0.9] - 2025-12-16

### Added
- **HTTP Response Helper Method**: Added `createResponse()` method to both `DirectDebitGenerator` and `RemesaGenerator`
  - Method accepts XML content (string) and filename (string) as parameters
  - Returns a Symfony `Response` object with proper headers for XML file download
  - Automatically sets `Content-Type: application/xml` and `Content-Disposition: attachment; filename="..."`
  - Simplifies controller code by eliminating manual Response creation
  - Example usage: `$generator->createResponse($xml, 'direct-debit.xml')`

### Added
- **Additional Fields Support for DirectDebit Transactions**:
  - Added `debtorBic` field support in `DirectDebitTransaction` (optional BIC for debtor)
  - Added `additionalData` array for storing custom fields internally
  - Added methods: `setDebtorBic()`, `getDebtorBic()`, `setAdditionalData()`, `getAdditionalData()`, `setAdditionalField()`, `getAdditionalField()`
  - `debtorBic` is included in generated XML when provided
  - `additionalData` is stored internally but not included in XML (for internal use only)
  - Support for `debtorBic` and additional fields in `generateFromArray()` method

- **Documentation**:
  - Added `DEPRECATED_FIELDS.md` documenting fields that are no longer allowed in SEPA Direct Debit transactions
  - Documented that postal addresses and contact information cannot be included in transactions (only in mandates)
  - Added examples of correct and incorrect usage

- **Test Coverage**:
  - Added 5 new tests for `DirectDebitTransaction` covering `debtorBic` and `additionalData` functionality
  - Added 5 new tests for `DirectDebitGenerator` verifying BIC inclusion in XML and additional data handling
  - Tests verify that additional data is stored but not included in generated XML

### Fixed
- Fixed constant type declarations for PHP 8.2 compatibility
  - Removed `const string` type declarations that caused syntax errors in PHP 8.2
  - Changed to untyped constants with literal string values
  - Fixed in `Configuration::ALIAS`, `DirectDebitGenerator::SERVICE_NAME`, `RemesaGenerator::SERVICE_NAME`, `IdentifierGenerator::SERVICE_NAME`

## [0.0.8] - 2025-12-17

### Added
- **Postal Address Support (Optional)**: Added support for exporting creditor and debtor postal addresses in the generated XML
  - Addresses are **completely optional** - only included if provided in the array
  - Addresses are included using structured format (PstlAdr) with elements: StrtNm, TwnNm, PstCd, and Ctry
  - Addresses are automatically added to XML using DOM manipulation after generation
  - Both `DirectDebitData` and `DirectDebitTransaction` support address fields
  - Addresses can be set via object methods (`setCreditorAddress()`, `setDebtorAddress()`) or array input (both camelCase and snake_case)
  - Support for `creditorAddress` and `debtorAddress` in `generateFromArray()` method
  - Support for `creditor_address` and `debtor_address` in snake_case format
  - Support for individual address fields: `creditor_street`, `creditor_city`, `creditor_postal_code`, `creditor_country`, `debtor_street`, `debtor_city`, `debtor_postal_code`, `debtor_country`
  - Updated `setCreditorAddress()` and `setDebtorAddress()` methods to accept arrays directly
  - Empty address arrays are ignored and will not create address elements
  - At least one address field must be provided for the address to be included in XML
  - Updated documentation to reflect address support and optional nature

### Changed
- Updated `DEPRECATED_FIELDS.md` to clarify that addresses are now supported (as of v0.0.8)
- Updated README examples to show address usage and clarify that addresses are optional
- Address methods now accept arrays directly as first parameter for better usability

### Test Coverage
- Added 8 new tests for address functionality:
  - `testGenerateXmlWithCreditorAddress()` - Tests creditor address with object methods
  - `testGenerateXmlWithDebtorAddress()` - Tests debtor address with object methods
  - `testGenerateXmlWithBothAddresses()` - Tests both addresses together
  - `testGenerateFromArrayWithCreditorAddressSnakeCase()` - Tests creditor address in snake_case
  - `testGenerateXmlWithoutAddresses()` - Tests that addresses are not included when not provided
  - `testGenerateXmlWithEmptyAddressArray()` - Tests that empty address arrays are ignored
  - Updated `testGenerateXml()` - Verifies no address elements when addresses are not provided
  - Updated `testGenerateFromArrayWithAddresses()` - Enhanced assertions for address elements
  - Updated `testGenerateFromArrayWithAddressesSnakeCase()` - Enhanced assertions for address elements

## [0.0.7] - 2025-12-17

### Added
- **Additional Fields Support for DirectDebit Transactions**:
  - Added `debtorBic` field support in `DirectDebitTransaction` (optional BIC for debtor)
  - Added `additionalData` array for storing custom fields internally
  - Added methods: `setDebtorBic()`, `getDebtorBic()`, `setAdditionalData()`, `getAdditionalData()`, `setAdditionalField()`, `getAdditionalField()`
  - `debtorBic` is included in generated XML when provided
  - `additionalData` is stored internally but not included in XML (for internal use only)
  - Support for `debtorBic` and additional fields in `generateFromArray()` method

- **Snake_case Field Name Support**:
  - `DirectDebitGenerator::generateFromArray()` now supports both camelCase and snake_case field names
  - Automatic field name normalization for maximum flexibility
  - Supports common snake_case formats: `message_id`, `initiating_party_name`, `payment_name`, `due_date`, `creditor_name`, `creditor_iban`, `creditor_bic`, `sequence_type`, `creditor_id`, `instrument_code`, `items`, `instruction_id`, `debtor_iban`, `debtor_name`, `debtor_mandate`, `debtor_mandate_signature_date`, `information`
  - Backward compatible: existing camelCase code continues to work

- **Documentation**:
  - Added `DEPRECATED_FIELDS.md` documenting fields that are no longer allowed in SEPA Direct Debit transactions
  - Documented that postal addresses and contact information cannot be included in transactions (only in mandates)
  - Added examples of correct and incorrect usage
  - Updated README with snake_case examples

- **Test Coverage**:
  - Added 5 new tests for `DirectDebitTransaction` covering `debtorBic` and `additionalData` functionality
  - Added 5 new tests for `DirectDebitGenerator` verifying BIC inclusion in XML and additional data handling
  - Added 2 new tests for snake_case format support
  - Tests verify that additional data is stored but not included in generated XML

- **Demo Applications**:
  - Added `/demo-direct-debit-snake-case` endpoint to all demo applications (Symfony 6, 7, 8)
  - Demonstrates usage of snake_case format with real-world example

### Fixed
- Fixed constant type declarations for PHP 8.2 compatibility
  - Removed `const string` type declarations that caused syntax errors in PHP 8.2
  - Changed to untyped constants with literal string values
  - Fixed in `Configuration::ALIAS`, `DirectDebitGenerator::SERVICE_NAME`, `RemesaGenerator::SERVICE_NAME`, `IdentifierGenerator::SERVICE_NAME`

## [0.0.6] - 2025-12-16

### Added
- **Service Registration with Attributes**: `DirectDebitGenerator` now uses Symfony `#[AsAlias]` attribute for automatic service registration
  - Service is registered with alias `nowo_sepa_payment.generator.direct_debit_generator`
  - Service is marked as public for explicit service retrieval
  - Added `SERVICE_NAME` constant using `Configuration::ALIAS` for consistent naming

- **Enhanced Test Coverage for DirectDebitGenerator**: Added comprehensive test cases to improve code coverage
  - Tests for `generateFromArray()` with `DateTimeInterface` objects
  - Tests for amount conversion from cents (> 10000)
  - Tests for optional fields (`creditorBic`, `remittanceInformation`, `debtorMandateSignDate`)
  - Tests for missing required fields validation
  - Tests for invalid data types validation
  - Tests for edge cases (empty transactions, missing transactions)
  - Total of 14 new test methods covering all code paths

## [0.0.5] - 2025-12-16

### Fixed
- Removed `setPaymentMethod()` calls from `RemesaGenerator` and `DirectDebitGenerator`
  - Payment method is now automatically set by Digitick\Sepa v3.0 based on transfer file type
- Fixed `testGenerateFromArray` test to use correct field names (`reference`, `bankAccountOwner`, `seqType`)
- Updated CHANGELOG documentation

## [0.0.4] - 2025-12-16

### Fixed
- **Complete Digitick\Sepa v3.0 API compatibility**:
  - Updated `PaymentInformation` constructor usage
  - Updated transaction creation with `CustomerCreditTransferInformation` and `CustomerDirectDebitTransferInformation`
  - Fixed transaction amounts to use integers (cents)
  - Updated method names to match v3.0 API
  - Removed deprecated methods (`setCreationDateTime`, `setNumberOfTransactions`, `setControlSum`)
  - Updated documentation and CHANGELOG
  - Updated README with dependency information

## [0.0.3] - 2025-01-23

### Fixed
- **Full compatibility with Digitick\Sepa v3.0 API changes**:
  - Fixed `GroupHeader` constructor to pass required parameters (`messageId` and `initiatingPartyName`)
  - Removed `setCreationDateTime()` calls (creation date is now set automatically by GroupHeader)
  - Updated `PaymentInformation` constructor to use required parameters (`id`, `originAccountIBAN`, `originAgentBIC`, `originName`, `originAccountCurrency`)
  - Updated transaction creation to use `CustomerCreditTransferInformation` and `CustomerDirectDebitTransferInformation` constructors
  - Changed transaction amounts to be passed as integers (cents) instead of floats
  - Updated method names to match v3.0 API:
    - `setRequestedExecutionDate()` → `setDueDate()`
    - `setRequestedCollectionDate()` → `setDueDate()`
    - `setCreditorSchemeIdentification()` → `setCreditorId()`
    - `setMandateIdentification()` → `setMandateId()`
    - `setDateOfSignature()` → `setMandateSignDate()`
    - `addCreditTransferTransaction()` → `addTransfer()` with `CustomerCreditTransferInformation`
    - `addTransferInformation()` → `addTransfer()` with `CustomerDirectDebitTransferInformation`
  - Removed automatic calculation methods (`setNumberOfTransactions`, `setControlSum`) as they are now calculated automatically

## [0.0.2] - 2025-01-23

### Fixed
- Updated `RemesaGenerator` and `DirectDebitGenerator` to use `GroupHeader` in constructor instead of format string
- Fixed footer display in demo templates using flexbox layout for proper positioning

### Changed
- Updated demo `composer.json` files to use `@dev` version for local development with path repositories

## [0.0.1] - 2025-01-23

### Added
- Initial release of SEPA Payment Bundle
- Full SEPA payment management functionality
- IBAN validation and utilities
- CCC to IBAN conversion
- BIC validation
- Credit Card validation
- Identifier generation
- SEPA Credit Transfer XML generation (pain.001.001.03)
- SEPA Direct Debit XML generation (pain.008.001.02)
- SEPA XML parsing (Credit Transfer)
- Basic demo applications
- IBAN validation and utilities
- CCC to IBAN conversion
- BIC validation
- Credit Card validation
- Identifier generation
- SEPA Credit Transfer XML generation (pain.001.001.03)
- SEPA Direct Debit XML generation (pain.008.001.02)
- SEPA XML parsing (Credit Transfer)
- Basic demo applications

