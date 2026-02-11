# Demo Projects

The bundle includes demo projects for different Symfony versions. Each demo has its own `docker-compose.yml` and can be run independently.

**Note**: The demos include examples of both deprecated classes (marked with ⚠️) and new classes to demonstrate backward compatibility. The deprecated classes still work but show deprecation warnings. They will be removed in version 2.0.0.

## Available Demos

- **Symfony 6.4 Demo**: `demo/symfony6/` (Port 8001 by default)
- **Symfony 7.0 Demo**: `demo/symfony7/` (Port 8001 by default)
- **Symfony 8.0 Demo**: `demo/symfony8/` (Port 8001 by default)

## Demo Endpoints

Each demo application includes the following endpoints to showcase bundle functionality:

### Validators
- `/validate-iban?iban=ES9121000418450200051332` - Validate IBAN and display detailed information
- `/validate-bic?bic=ESPBESMM` - Validate BIC and extract components
- `/validate-credit-card?card=4532015112830366` - Validate credit card number using Luhn algorithm
- `/demo-sepa-string-sanitizer?input=José García & Company` - SEPA string sanitization and validation
- `/demo-sepa-country-validator?country=ES&iban=ES9121000418450200051332` - SEPA country validation
- `/demo-sepa-business-rules?amount=100.50&count=1&currency=EUR&date=tomorrow` - SEPA business rules validation

### Converters
- `/convert-ccc?ccc=21000418450200051332` - Convert Spanish CCC to IBAN format

### Generators
- `/generate-identifier` - Generate various types of identifiers (message, payment, end-to-end, mandate)
- `/demo-mandate` - Demo SEPA mandate creation

### Credit Transfer (New - Recommended)
- `/demo-credit-transfer` - Generate and download SEPA Credit Transfer XML (with addresses)
- `/demo-credit-transfer-array` - Generate from array (camelCase format)
- `/demo-credit-transfer-with-addresses` - Generate from array with addresses
- `/demo-credit-transfer-snake-case` - Generate from array (snake_case format)

### Credit Transfer (Deprecated - Still Works)
- `/demo-remesa-generator-deprecated` - Demo using deprecated `RemesaGenerator` and `RemesaData` classes (shows backward compatibility)
- `/demo-remesa-generator-array-deprecated` - Demo using deprecated `RemesaGenerator::generateFromArray()` method
- `/demo-comparison-deprecated-vs-new` - Side-by-side comparison showing both deprecated and new classes work identically

### Direct Debit
- `/demo-direct-debit` - Generate and download SEPA Direct Debit XML (from array - camelCase)
- `/demo-direct-debit-snake-case` - Generate from array (snake_case format)
- `/demo-direct-debit-with-addresses` - Generate from array with addresses

### Parsers
- **Credit Transfer Parser (New - Recommended):**
  - `/demo-parse-credit-transfer` - Generate and parse a Credit Transfer XML using `CreditTransferParser`
- **Credit Transfer Parser (Deprecated - Still Works):**
  - `/demo-remesa-parser-deprecated` - Generate and parse using deprecated `RemesaParser` (shows backward compatibility)
- **Direct Debit Parser:**
  - `/demo-parse-direct-debit` - Generate and parse a Direct Debit XML

### Export/Import
- **Credit Transfer Export:**
  - `/demo-export-credit-transfer-json` - Export Credit Transfer to JSON format
  - `/demo-export-credit-transfer-csv` - Export Credit Transfer to CSV format (download)
- **Direct Debit Export:**
  - `/demo-export-direct-debit-json` - Export Direct Debit to JSON format
  - `/demo-export-direct-debit-csv` - Export Direct Debit to CSV format (download)
- **Import:**
  - `/demo-import-from-json` - Import Credit Transfer data from JSON format

## Quick Start with Docker

```bash
cd demo
make up-symfony6
make install-symfony6
# Access at: http://localhost:8001
```

For more details, see `demo/README.md`.
