# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
  - Added `/demo-remesa-cobro-snake-case` endpoint to all demo applications (Symfony 6, 7, 8)
  - Demonstrates usage of snake_case format with real-world example

### Fixed
- Fixed constant type declarations for PHP 8.2 compatibility
  - Removed `const string` type declarations that caused syntax errors in PHP 8.2
  - Changed to untyped constants with literal string values
  - Fixed in `Configuration::ALIAS`, `DirectDebitGenerator::SERVICE_NAME`, `RemesaGenerator::SERVICE_NAME`, `IdentifierGenerator::SERVICE_NAME`

- **Credit Card Validation**: Complete credit card number validation using Luhn algorithm
  - `CreditCardValidator::isValid()` - Validate credit card number using Luhn algorithm
  - `CreditCardValidator::normalize()` - Normalize card number (remove spaces and dashes)
  - `CreditCardValidator::format()` - Format card number with spaces every 4 digits
  - `CreditCardValidator::getCardType()` - Detect card type (Visa, Mastercard, Amex, Discover, Diners Club, JCB)
  - `CreditCardValidator::getBin()` - Extract BIN (Bank Identification Number - first 6 digits)
  - `CreditCardValidator::getLastFour()` - Extract last 4 digits
  - `CreditCardValidator::mask()` - Mask card number showing only last 4 digits
  - `CreditCardValidator::isValidForType()` - Validate card number for specific card type
  - `sepa:validate-credit-card` console command for CLI validation

- **Comprehensive Test Suite**: Complete test coverage for all bundle features
  - Tests for `CccConverter` with CCC validation and conversion
  - Tests for `BicValidator` with BIC format validation
  - Tests for `CreditCardValidator` with Luhn algorithm and card type detection
  - Tests for `DirectDebitGenerator` with XML generation and validation
  - Tests for `IdentifierGenerator` with identifier generation
  - Tests for `DirectDebitData` and `DirectDebitTransaction` models
  - Tests for `RemesaParser` with XML parsing and validation
  - All tests follow PHPUnit best practices with proper assertions

- **Enhanced Demo Applications**: Updated demo applications for Symfony 6, 7, and 8
  - Added endpoints for BIC validation (`/validate-bic`)
  - Added endpoints for credit card validation (`/validate-credit-card`)
  - Added endpoints for CCC to IBAN conversion (`/convert-ccc`)
  - Added endpoints for identifier generation (`/generate-identifier`)
  - Improved demo homepage with organized endpoint categories
  - All demos showcase complete bundle functionality

### Changed
- **Code Organization**: Reorganized bundle structure for better separation of concerns
  - Moved validators (`IbanValidator`, `BicValidator`, `CreditCardValidator`) from `Services/` to `Validator/`
  - Moved converters (`CccConverter`) from `Services/` to `Converter/`
  - Moved generators (`RemesaGenerator`, `DirectDebitGenerator`, `IdentifierGenerator`) from `Services/` to `Generator/`
  - Moved parsers (`RemesaParser`) from `Services/` to `Parser/`
  - Moved models/DTOs (`RemesaData`, `Transaction`, `DirectDebitData`, `DirectDebitTransaction`, `Mandate`) from `Services/` to `Model/`
  - Updated all namespaces and imports throughout the codebase
  - Updated service definitions in `services.yaml`
  - Updated documentation and examples

- **Modern PHP Syntax**: Updated all classes to use PHP 8.0+ constructor property promotion
  - Replaced traditional property declaration and constructor assignment with constructor property promotion
  - Improved code readability and reduced boilerplate
  - All classes now use modern PHP syntax while maintaining backward compatibility

- Updated `digitick/sepa-xml` dependency from `^2.0` to `^3.0`
  - Improved support for multiple ISO 20022 pain.001 and pain.008 versions
  - Better compatibility with latest SEPA standards
  - **Note**: Version 3.0 requires breaking changes in GroupHeader and TransferFile constructors (addressed in v0.0.2, v0.0.3, v0.0.4, and v0.0.5)

