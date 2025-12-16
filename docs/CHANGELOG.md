# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

## [Unreleased]

### Added
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

## [1.0.0] - 2024-01-15

### Added
- **IBAN Validation**: Complete IBAN validation according to ISO 13616 standard
  - `IbanValidator::isValid()` - Validate IBAN format and check digits
  - `IbanValidator::normalize()` - Normalize IBAN (remove spaces, uppercase)
  - `IbanValidator::format()` - Format IBAN with spaces for readability
  - `IbanValidator::getCountryCode()` - Extract country code
  - `IbanValidator::getCheckDigits()` - Extract check digits
  - `IbanValidator::getBban()` - Extract BBAN (Basic Bank Account Number)
  - `IbanValidator::calculateCheckDigits()` - Calculate check digits for an IBAN

- **CCC to IBAN Conversion**: Convert Spanish CCC (Código Cuenta Cliente) to IBAN format
  - `CccConverter::convert()` - Convert CCC to IBAN
  - `CccConverter::isValidCcc()` - Validate CCC format
  - `CccConverter::extractBankCode()` - Extract bank code from CCC
  - `CccConverter::extractBranchCode()` - Extract branch code from CCC
  - `CccConverter::extractAccountNumber()` - Extract account number from CCC
  - `nowo:sepa:ccc-to-iban` console command for CLI conversion

- **BIC Validation**: Validate BIC (Business Identifier Code) format according to ISO 13616 standard
  - `BicValidator::isValid()` - Validate BIC format
  - `BicValidator::normalize()` - Normalize BIC (remove spaces, uppercase)
  - `BicValidator::getBankCode()` - Extract bank code (first 4 letters)
  - `BicValidator::getCountryCode()` - Extract country code (2 letters)
  - `BicValidator::getLocationCode()` - Extract location code (2 alphanumeric)
  - `BicValidator::getBranchCode()` - Extract branch code (3 alphanumeric, optional)

- **Identifier Generation**: Generate unique identifiers for SEPA messages, payments, and transactions
  - `IdentifierGenerator::generateMessageId()` - Generate unique message identifier
  - `IdentifierGenerator::generatePaymentInfoId()` - Generate unique payment information identifier
  - `IdentifierGenerator::generateEndToEndId()` - Generate unique end-to-end identifier
  - `IdentifierGenerator::generateTransactionId()` - Generate unique transaction identifier
  - Support for custom prefixes and lengths

- **SEPA XML Parser**: Parse and validate SEPA XML files
  - `RemesaParser` class for parsing SEPA XML files
  - Support for pain.002 (Payment Status Report) and other SEPA message types
  - Extract transaction status and information from XML files

- **SEPA Mandates**: Manage SEPA Direct Debit mandates
  - `Mandate` class with full support for mandate data
  - Support for mandate types (CORE, B2B)
  - Support for sequence types (FRST, RCUR, OOFF, FNAL)
  - Active/inactive status management

- **SEPA Credit Transfer Generation (Remesas de Pago)**: Generate SEPA XML files using Digitick\Sepa library
  - `RemesaGenerator` class for generating SEPA Credit Transfer XML
  - Uses `digitick/sepa-xml` library for reliable SEPA XML generation
  - Support for pain.001.001.03 format (ISO 20022)
  - `RemesaData` class for remesa data container
  - `Transaction` class for individual transactions
  - Support for multiple transactions in a single remesa
  - Automatic IBAN validation before XML generation

- **SEPA Direct Debit Generation (Remesas de Cobro)**: Generate SEPA Direct Debit XML files using Digitick\Sepa library
  - `DirectDebitGenerator` class for generating SEPA Direct Debit XML
  - Uses `digitick/sepa-xml` library for reliable SEPA XML generation
  - Support for pain.008.001.02 format (ISO 20022)
  - `DirectDebitData` class for direct debit data container
  - `DirectDebitTransaction` class for individual direct debit transactions
  - Support for both array-based and object-based APIs
  - Support for multiple transactions in a single remesa
  - Automatic IBAN validation before XML generation

- **Console Commands**: CLI tools for common operations
  - `nowo:sepa:validate-iban` - Validate IBAN and display detailed information
  - `nowo:sepa:ccc-to-iban` - Convert Spanish CCC to IBAN format

- **Dependency Injection**: Full Symfony service integration
  - Automatic service registration
  - Configuration support via `nowo_sepa_payment.yaml`
  - Default currency configuration

- **Tests**: Comprehensive test suite with 100% code coverage
  - Tests for all IBAN validation methods
  - Tests for CCC conversion
  - Tests for BIC validation
  - Tests for identifier generation
  - Tests for mandate management
  - Tests for remesa generation (Credit Transfer and Direct Debit)
  - Tests for edge cases and error handling

- **Documentation**: Complete documentation
  - README with usage examples
  - CONTRIBUTING guide
  - BRANCHING strategy
  - CHANGELOG
  - CONFIGURATION guide

