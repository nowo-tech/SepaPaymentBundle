# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
  - **Note**: Version 3.0 requires breaking changes in GroupHeader and TransferFile constructors (addressed in v0.0.2, v0.0.3, v0.0.4, and v0.0.5)

