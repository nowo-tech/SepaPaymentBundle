# Development Guide

This document provides information about developing and contributing to the SEPA Payment Bundle.

## Development Setup

### Using Docker (Recommended)

```bash
# Start the container
make up

# Install dependencies
make install

# Run tests
make test

# Run tests with coverage
make test-coverage

# Run all QA checks
make qa
```

### Without Docker

```bash
composer install
composer test
composer test-coverage
composer qa
```

## Testing

The bundle has comprehensive test coverage. All tests are located in the `tests/` directory and cover:

- **Validators**: `IbanValidator`, `BicValidator`, `CreditCardValidator`, `SepaStringSanitizer`, `SepaCountryValidator`, `SepaBusinessRulesValidator`, `XsdValidator`
  - `SepaStringSanitizer`: 100% line coverage (10/10 methods)
  - `SepaCountryValidator`: 100% line coverage (4/4 methods)
  - `SepaBusinessRulesValidator`: 100% line coverage (9/9 methods)
- **Converters**: `CccConverter`
- **Generators**: `CreditTransferGenerator`, `DirectDebitGenerator`, `IdentifierGenerator`
  - `CreditTransferGenerator` includes extensive test coverage (30 tests, 82.29% line coverage):
    - Array-based generation with camelCase and snake_case formats
    - DateTimeInterface support for dates
    - Amount conversion from cents
    - Optional fields (BIC, remittance information, currency, batch booking)
    - Address handling (creditor and debtor)
    - Multiple transactions with addresses
    - Validation and edge cases
  - `DirectDebitGenerator` includes extensive test coverage for all code paths:
    - Array-based generation with various data types
    - Validation of required fields
    - Optional fields handling
    - Edge cases (empty transactions, amount conversion, etc.)
- **Models**: `CreditTransferData`, `Transaction`, `DirectDebitData`, `DirectDebitTransaction`, `Mandate`
  - All models have 100% test coverage
- **Parsers**: `CreditTransferParser`, `DirectDebitParser`
- **Commands**: All console commands
- **DependencyInjection**: `Configuration`, `NowoSepaPaymentExtension`
- **Generator (deprecated)**: `RemesaGenerator`
- **Models (deprecated)**: `RemesaData`, `Remesa\Transaction`
- **Repository**: `MandateRepository`

**Current Test Statistics:**
- Run `composer test-coverage` and open `coverage/index.html` for up-to-date coverage. The project targets at least 80% line coverage (enforced in CI).

**New Validators (100% Coverage)**:
- `SepaStringSanitizer`: 100% methods (10/10), 100% lines (21/21)
- `SepaCountryValidator`: 100% methods (4/4), 100% lines (45/45)
- `SepaBusinessRulesValidator`: 100% methods (9/9), 100% lines (39/39)

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# View coverage report
open coverage/index.html
```

## Code Quality

The bundle uses PHP-CS-Fixer to enforce code style (PSR-12).

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## CI/CD

The bundle uses GitHub Actions for continuous integration:

- **Tests**: Runs on PHP 8.1, 8.2, 8.3, 8.4, and 8.5 with Symfony 6.4, 7.0, and 8.0
- **Code Style**: Automatically fixes code style on push
- **Coverage**: Validates minimum 80% code coverage requirement
- **Dependabot**: Automatically updates dependencies

See `.github/workflows/ci.yml` for details.

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## Branching Strategy

See [BRANCHING.md](BRANCHING.md) for information about our branching strategy and workflow.
