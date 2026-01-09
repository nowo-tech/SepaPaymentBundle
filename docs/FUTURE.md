# Future Improvements and Features

This document serves as a checklist for future improvements and new features to be added to the SEPA Payment Bundle.

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
- [ ] Add console command for parsing Direct Debit files (optional)

**Rationale**: Currently only `RemesaParser` exists for Credit Transfer. Adding Direct Debit parser provides feature parity.

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

### 3. Automatic BIC Lookup by IBAN
- [ ] Research BIC lookup services/APIs (e.g., SWIFT, IBAN.com)
- [ ] Create `BicLookupService` interface and implementation
- [ ] Implement fallback mechanism (cache, database, API)
- [ ] Add configuration for BIC lookup providers
- [ ] Integrate into generators to auto-fill missing BIC
- [ ] Add caching mechanism for BIC lookups
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Improves user experience by automatically filling BIC when only IBAN is provided. Reduces errors and manual work.

---

### 4. SEPA Limits and Business Rules Validation
- [ ] Validate maximum transaction amount limits
- [ ] Validate maximum number of transactions per file
- [ ] Validate execution date rules (must be future date, business days only, etc.)
- [ ] Validate mandate expiration dates
- [ ] Validate sequence type transitions (FRST → RCUR, etc.)
- [ ] Validate currency restrictions (EUR only for SEPA)
- [ ] Create `SepaBusinessRulesValidator` service
- [ ] Add configuration for custom limits
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Prevents common SEPA compliance errors before XML generation, reducing rejections by banks.

---

## Priority: Medium 🟡

### 5. Symfony Events
- [ ] Create event classes:
  - [ ] `BeforeCreditTransferGenerationEvent`
  - [ ] `AfterCreditTransferGenerationEvent`
  - [ ] `BeforeDirectDebitGenerationEvent`
  - [ ] `AfterDirectDebitGenerationEvent`
  - [ ] `BeforeValidationEvent`
  - [ ] `AfterValidationEvent`
- [ ] Dispatch events in generators
- [ ] Allow event listeners to modify data before generation
- [ ] Allow event listeners to modify XML after generation
- [ ] Add event documentation with examples
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Provides extensibility without modifying bundle code. Allows integration with logging, monitoring, and custom business logic.

---

### 6. Structured Logging
- [ ] Integrate with Symfony's LoggerInterface
- [ ] Log generation events (start, success, failure)
- [ ] Log validation events (IBAN, BIC, business rules)
- [ ] Log parsing events
- [ ] Add log levels (info, warning, error)
- [ ] Include context data (message ID, transaction count, etc.)
- [ ] Add configuration for log levels
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Improves debugging and monitoring capabilities. Essential for production environments.

---

### 7. SEPA Character Validation and Sanitization
- [ ] Validate allowed characters in names according to SEPA rules
- [ ] Sanitize invalid characters automatically
- [ ] Validate maximum field lengths
- [ ] Handle special characters (accents, umlauts, etc.)
- [ ] Create `SepaStringSanitizer` service
- [ ] Add configuration for sanitization behavior
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Prevents XML generation failures due to invalid characters. Ensures SEPA compliance.

---

### 8. Export to Other Formats
- [ ] Add JSON export for Credit Transfer data
- [ ] Add JSON export for Direct Debit data
- [ ] Add CSV export for reporting
- [ ] Add Excel export (optional, requires additional dependency)
- [ ] Create `ExportService` interface and implementations
- [ ] Support import from JSON/CSV back to array format
- [ ] Add comprehensive tests
- [ ] Update documentation

**Rationale**: Provides flexibility for reporting, data analysis, and integration with other systems.

---

### 9. SEPA Country Validation
- [ ] Create list of SEPA member countries
- [ ] Validate country codes in IBANs
- [ ] Validate country codes in addresses
- [ ] Warn/error for non-SEPA countries
- [ ] Create `SepaCountryValidator` service
- [ ] Keep country list up-to-date
- [ ] Add comprehensive tests
- [ ] Update documentation

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

### 11. Validation Caching
- [ ] Add cache for IBAN validations
- [ ] Add cache for BIC validations
- [ ] Add cache for BIC lookups
- [ ] Use Symfony Cache component
- [ ] Add configuration for cache TTL
- [ ] Add cache invalidation strategies
- [ ] Add comprehensive tests
- [ ] Update documentation

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

### 14. Mandate Management and Validation
- [ ] Create mandate repository/service
- [ ] Track mandate status (active, expired, revoked)
- [ ] Validate mandate expiration dates
- [ ] Validate mandate sequence type transitions
- [ ] Store mandate history
- [ ] Add database schema for mandates (optional)
- [ ] Add comprehensive tests
- [ ] Update documentation

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
