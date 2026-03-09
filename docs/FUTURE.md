# Future Improvements and Features

This document serves as a checklist for future improvements and new features to be added to the SEPA Payment Bundle.


## Table of contents

- [Priority: High 🔴](#priority-high)
  - [1. Direct Debit Parser ✅](#1-direct-debit-parser)
  - [2. XML Schema Validation (XSD) ✅](#2-xml-schema-validation-xsd)
  - [3. Automatic BIC Lookup by IBAN ✅](#3-automatic-bic-lookup-by-iban)
  - [4. SEPA Limits and Business Rules Validation ✅](#4-sepa-limits-and-business-rules-validation)
- [Priority: Medium 🟡](#priority-medium)
  - [5. Symfony Events ✅](#5-symfony-events)
  - [6. Structured Logging ✅](#6-structured-logging)
  - [7. SEPA Character Validation and Sanitization ✅](#7-sepa-character-validation-and-sanitization)
  - [8. Export to Other Formats ✅](#8-export-to-other-formats)
  - [9. SEPA Country Validation ✅](#9-sepa-country-validation)
- [Priority: Low 🟢](#priority-low)
  - [10. REST API Controllers](#10-rest-api-controllers)
  - [11. Validation Caching ✅](#11-validation-caching)
  - [12. Metrics and Monitoring](#12-metrics-and-monitoring)
  - [13. Support for Multiple PAIN Versions](#13-support-for-multiple-pain-versions)
  - [14. Mandate Management and Validation ✅](#14-mandate-management-and-validation)
  - [15. XML Compression and Encryption](#15-xml-compression-and-encryption)
- [Additional Improvements](#additional-improvements)
  - [Code Quality](#code-quality)
  - [Documentation](#documentation)
  - [Developer Experience](#developer-experience)
  - [Infrastructure](#infrastructure)
- [Notes](#notes)

## Priority: High 🔴

### 1. Direct Debit Parser ✅
- [x] Create `DirectDebitParser` class to parse SEPA Direct Debit XML files (pain.008.001.02 format)
- [x] Parse group header information (message ID, creation date, initiating party)
- [x] Parse payment information (payment info ID, sequence type, creditor information)
- [x] Parse transaction details (amount, debtor information, mandate details, end-to-end ID)
- [x] Extract addresses (creditor and debtor) from XML
- [x] Support for multiple transactions
- [x] Add comprehensive tests
- [x] Update documentation (USAGE.md, README.md)
- [x] Add console command for parsing Direct Debit files (`nowo:sepa:parse-direct-debit`)

**Rationale**: Currently only `CreditTransferParser` exists for Credit Transfer. Adding Direct Debit parser provides feature parity.

---

### 2. XML Schema Validation (XSD) ✅
- [x] Add XSD schema validation for Credit Transfer (pain.001.001.03)
- [x] Add XSD schema validation for Direct Debit (pain.008.001.02)
- [x] Create directory structure for SEPA XSD schemas
- [x] Create `XsdValidator` service
- [x] Integrate XSD validation into generators (optional, configurable)
- [x] Provide clear error messages for schema violations
- [x] Add comprehensive tests
- [x] Update documentation
- [ ] Download/embed official SEPA XSD schemas (optional - users can download and place in schemas directory)
- [ ] Add configuration option to enable/disable XSD validation globally (via services.yaml)

**Rationale**: Ensures generated XML files are fully compliant with SEPA standards and ISO 20022 specifications.

---

### 3. Automatic BIC Lookup by IBAN ✅
- [x] Research BIC lookup services/APIs (e.g., SWIFT, IBAN.com)
- [x] Create `BicLookupService` interface and implementation
- [x] Implement fallback mechanism (cache, database, API)
- [ ] Add configuration for BIC lookup providers
- [x] Integrate into generators to auto-fill missing BIC
- [x] Add caching mechanism for BIC lookups
- [x] Add comprehensive tests
- [x] Update documentation

**Rationale**: Improves user experience by automatically filling BIC when only IBAN is provided. Reduces errors and manual work.

---

### 4. SEPA Limits and Business Rules Validation ✅
- [x] Validate maximum transaction amount limits
- [x] Validate maximum number of transactions per file
- [x] Validate execution date rules (must be future date, business days only, etc.)
- [x] Validate mandate expiration dates
- [x] Validate sequence type transitions (FRST → RCUR, etc.)
- [x] Validate currency restrictions (EUR only for SEPA)
- [x] Create `SepaBusinessRulesValidator` service
- [ ] Add configuration for custom limits
- [x] Add comprehensive tests
- [x] Update documentation

**Rationale**: Prevents common SEPA compliance errors before XML generation, reducing rejections by banks.

---

## Priority: Medium 🟡

### 5. Symfony Events ✅
- [x] Create event classes:
  - [x] `BeforeCreditTransferGenerationEvent`
  - [x] `AfterCreditTransferGenerationEvent`
  - [x] `BeforeDirectDebitGenerationEvent`
  - [x] `AfterDirectDebitGenerationEvent`
  - [x] `BeforeValidationEvent`
  - [x] `AfterValidationEvent`
- [x] Dispatch events in generators
- [x] Allow event listeners to modify data before generation
- [x] Allow event listeners to modify XML after generation
- [ ] Add event documentation with examples
- [x] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Provides extensibility without modifying bundle code. Allows integration with logging, monitoring, and custom business logic.

---

### 6. Structured Logging ✅
- [x] Integrate with Symfony's LoggerInterface
- [x] Log generation events (start, success, failure)
- [x] Log validation events (IBAN, BIC, business rules)
- [x] Log parsing events
- [x] Add log levels (info, warning, error)
- [x] Include context data (message ID, transaction count, etc.)
- [ ] Add configuration for log levels
- [x] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Improves debugging and monitoring capabilities. Essential for production environments.

---

### 7. SEPA Character Validation and Sanitization ✅
- [x] Validate allowed characters in names according to SEPA rules
- [x] Sanitize invalid characters automatically
- [x] Validate maximum field lengths
- [x] Handle special characters (accents, umlauts, etc.)
- [x] Create `SepaStringSanitizer` service
- [ ] Add configuration for sanitization behavior
- [x] Add comprehensive tests
- [x] Update documentation

**Rationale**: Prevents XML generation failures due to invalid characters. Ensures SEPA compliance.

---

### 8. Export to Other Formats ✅
- [x] Add JSON export for Credit Transfer data
- [x] Add JSON export for Direct Debit data
- [x] Add CSV export for reporting
- [ ] Add Excel export (optional, requires additional dependency)
- [x] Create `ExportService` interface and implementations
- [x] Support import from JSON/CSV back to array format
- [x] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Provides flexibility for reporting, data analysis, and integration with other systems.

---

### 9. SEPA Country Validation ✅
- [x] Create list of SEPA member countries
- [x] Validate country codes in IBANs
- [x] Validate country codes in addresses
- [x] Warn/error for non-SEPA countries
- [x] Create `SepaCountryValidator` service
- [ ] Keep country list up-to-date
- [x] Add comprehensive tests
- [x] Update documentation

**Rationale**: Ensures only valid SEPA countries are used in transactions.

---

## Priority: Low 🟢

### 10. REST API Controllers
- [ ] Create REST API endpoints for validation (IBAN, BIC, Credit Card)
- [ ] Create REST API endpoints for generation (Credit Transfer, Direct Debit)
- [ ] Create REST API endpoints for parsing
- [ ] Add API authentication/authorization
- [ ] Add API rate limiting
- [ ] Add OpenAPI/Swagger documentation
- [ ] Add request/response DTOs
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Enables integration with external systems and microservices architecture.

---

### 11. Validation Caching ✅
- [x] Add cache for IBAN validations
- [x] Add cache for BIC validations
- [x] Add cache for BIC lookups (already implemented in BicLookupService)
- [x] Use Symfony Cache component (PSR-16 SimpleCache compatible)
- [x] Add configuration for cache TTL
- [x] Add cache invalidation strategies (clear, delete methods)
- [x] Add comprehensive tests
- [x] Update documentation

**Rationale**: Improves performance for repeated validations, especially in high-traffic scenarios.

---

### 12. Metrics and Monitoring
- [ ] Track number of XML files generated
- [ ] Track validation success/failure rates
- [ ] Track average transaction count per file
- [ ] Track error types and frequencies
- [ ] Integrate with monitoring systems (Prometheus, StatsD, etc.)
- [ ] Add configuration for metrics collection
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Provides insights into bundle usage and helps identify issues in production.

---

### 13. Support for Multiple PAIN Versions
- [ ] Support pain.001.001.04 (newer Credit Transfer version)
- [ ] Support pain.008.001.03 (newer Direct Debit version)
- [ ] Auto-detect PAIN version from XML
- [ ] Add configuration for default PAIN version
- [ ] Maintain backward compatibility
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Ensures compatibility with banks that require newer PAIN versions.

---

### 14. Mandate Management and Validation ✅
- [x] Create mandate repository/service
- [x] Track mandate status (active, expired, revoked)
- [x] Validate mandate expiration dates
- [x] Validate mandate sequence type transitions
- [x] Store mandate history
- [ ] Add database schema for mandates (optional - in-memory implementation provided)
- [x] Add comprehensive tests
- [x] Update documentation

**Rationale**: Provides complete mandate lifecycle management, essential for Direct Debit operations.

---

### 15. XML Compression and Encryption
- [ ] Add support for compressing XML files (gzip, zip)
- [ ] Add support for encrypting XML files
- [ ] Add configuration for compression/encryption
- [ ] Support for password-protected files
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Useful for secure file transfer and storage, especially for sensitive payment data.

---

## Additional Improvements

### Code Quality
- [ ] Increase test coverage to 90%+ (currently 88.57%)
- [ ] Add mutation testing (Infection PHP)
- [ ] Add static analysis (PHPStan level 9)
- [ ] Add performance benchmarks
- [ ] Optimize XML generation performance

### Documentation
- [ ] Add API reference documentation
- [ ] Add more code examples
- [ ] Add troubleshooting guide
- [ ] Add migration guides for major versions
- [ ] Add video tutorials (optional)

### Developer Experience
- [ ] Add IDE autocomplete helpers
- [ ] Add Symfony Maker commands for generating examples
- [ ] Add debugging tools/commands
- [ ] Improve error messages with actionable suggestions

### Infrastructure
- [ ] Add Docker image for testing
- [ ] Add GitHub Actions for automated releases
- [ ] Add automated dependency updates (Dependabot)
- [ ] Add security scanning

---

## Notes

- Items are organized by priority (High, Medium, Low)
- Each item includes a checklist of sub-tasks
- Rationale is provided for each major feature
- This document should be updated as features are completed or new ideas emerge
- Consider user feedback and issues when prioritizing features

---

**Last Updated**: 2026-01-09
**Maintainer**: Héctor Franco Aceituno (@HecFranco)
