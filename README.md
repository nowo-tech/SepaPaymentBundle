# SEPA Payment Bundle

[![CI](https://github.com/nowo-tech/sepa-payment-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/sepa-payment-bundle/actions/workflows/ci.yml) [![Latest Stable Version](https://poser.pugx.org/nowo-tech/sepa-payment-bundle/v)](https://packagist.org/packages/nowo-tech/sepa-payment-bundle) [![License](https://poser.pugx.org/nowo-tech/sepa-payment-bundle/license)](https://packagist.org/packages/nowo-tech/sepa-payment-bundle) [![PHP Version Require](https://poser.pugx.org/nowo-tech/sepa-payment-bundle/require/php)](https://packagist.org/packages/nowo-tech/sepa-payment-bundle) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/sepa-payment-bundle.svg?style=social&label=Star)](https://github.com/nowo-tech/sepa-payment-bundle)

> ⭐ **Found this project useful?** Give it a star on GitHub! It helps us maintain and improve the project.

Symfony bundle for SEPA payment management: IBAN validation, mandate management, SEPA Credit Transfer and SEPA Direct Debit generation.

## Features

- ✅ **IBAN Validation**: Complete IBAN validation according to ISO 13616 standard
- ✅ **IBAN Utilities**: Format, normalize, extract country code, check digits, and BBAN
- ✅ **CCC to IBAN Conversion**: Convert Spanish CCC (Código Cuenta Cliente) to IBAN format
- ✅ **BIC Validation**: Validate BIC (Business Identifier Code) format
- ✅ **Credit Card Validation**: Validate credit card numbers using Luhn algorithm and detect card types (Visa, Mastercard, Amex, Discover, etc.)
- ✅ **Identifier Generation**: Generate unique identifiers for messages, payments, and transactions
- ✅ **SEPA XML Parser**: Parse and validate SEPA XML files
- ✅ **SEPA Mandates**: Manage SEPA Direct Debit mandates with full support
- ✅ **Credit Transfer (Remesas de Pago)**: Generate SEPA Credit Transfer XML files (pain.001.001.03 format) using Digitick\Sepa library
- ✅ **Direct Debit (Remesas de Cobro)**: Generate SEPA Direct Debit XML files (pain.008.001.02 format) using Digitick\Sepa library
- ✅ **Array-based API**: Generate both types of remesas from simple array format
- ✅ **Object-based API**: Generate remesas using typed objects for better type safety
- ✅ **Multiple Transactions**: Support for batch payments in a single remesa
- ✅ **Full Validation**: Automatic validation of IBANs before XML generation
- ✅ **Type Safety**: Full type hints and strict types throughout
- ✅ **Console Commands**: CLI tools for IBAN validation, CCC conversion, and credit card validation

## Installation

```bash
composer require nowo-tech/sepa-payment-bundle
```

Then, register the bundle in your `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\SepaPaymentBundle\NowoSepaPaymentBundle::class => ['all' => true],
];
```

## Usage

### IBAN Validation

```php
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$validator = new IbanValidator();

// Validate IBAN
if ($validator->isValid('ES9121000418450200051332')) {
    echo "Valid IBAN";
}

// Normalize IBAN (remove spaces, uppercase)
$normalized = $validator->normalize('es91 2100 0418 4502 0005 1332');
// Returns: ES9121000418450200051332

// Format IBAN (add spaces every 4 characters)
$formatted = $validator->format('ES9121000418450200051332');
// Returns: ES91 2100 0418 4502 0005 1332

// Extract components
$countryCode = $validator->getCountryCode('ES9121000418450200051332'); // ES
$checkDigits = $validator->getCheckDigits('ES9121000418450200051332'); // 91
$bban = $validator->getBban('ES9121000418450200051332'); // 21000418450200051332

// Calculate check digits
$checkDigits = $validator->calculateCheckDigits('ES0021000418450200051332');
// Returns: 91
```

### CCC to IBAN Conversion

```php
use Nowo\SepaPaymentBundle\Converter\CccConverter;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$converter = new CccConverter(new IbanValidator());

// Convert CCC to IBAN
$iban = $converter->cccToIban('21000418450200051332');
// Returns: ES9121000418450200051332

// Validate CCC format
if ($converter->isValidCcc('21000418450200051332')) {
    echo "Valid CCC";
}

// Extract components
$bankCode = $converter->getBankCode('21000418450200051332'); // 2100
$branchCode = $converter->getBranchCode('21000418450200051332'); // 0418
$accountNumber = $converter->getAccountNumber('21000418450200051332'); // 450200051332
```

### BIC Validation

```php
use Nowo\SepaPaymentBundle\Validator\BicValidator;

$validator = new BicValidator();

// Validate BIC
if ($validator->isValid('CAIXESBBXXX')) {
    echo "Valid BIC";
}

// Normalize BIC (remove spaces, uppercase)
$normalized = $validator->normalize('caixesbb xxx');
// Returns: CAIXESBBXXX

// Extract components
$bankCode = $validator->getBankCode('CAIXESBBXXX'); // CAIX
$countryCode = $validator->getCountryCode('CAIXESBBXXX'); // ES
$locationCode = $validator->getLocationCode('CAIXESBBXXX'); // BB
$branchCode = $validator->getBranchCode('CAIXESBBXXX'); // XXX (or null if not present)
```

### Identifier Generation

```php
use Nowo\SepaPaymentBundle\Generator\IdentifierGenerator;

$generator = new IdentifierGenerator();

// Generate message identifier
$messageId = $generator->generateMessageId();
// Returns: MSG-20240115143022-a1b2c3d4

// Generate payment information identifier
$paymentInfoId = $generator->generatePaymentInfoId();
// Returns: PMT-20240115143022-a1b2c3d4

// Generate end-to-end identifier
$endToEndId = $generator->generateEndToEndId();
// Returns: E2E-20240115143022-a1b2c3d4

// Generate mandate identifier
$mandateId = $generator->generateMandateId();
// Returns: MANDATE-20240115143022-a1b2c3d4

// Generate custom identifier with prefix
$customId = $generator->generateCustomId('CUSTOM');
// Returns: CUSTOM-20240115143022-a1b2c3d4

// Generate with custom prefix
$messageId = $generator->generateMessageId('MY-MSG');
// Returns: MY-MSG-20240115143022-a1b2c3d4
```

### SEPA XML Parser

```php
use Nowo\SepaPaymentBundle\Parser\RemesaParser;

$parser = new RemesaParser();

// Parse SEPA Credit Transfer XML
$xml = file_get_contents('remesa.xml');
$data = $parser->parseCreditTransfer($xml);

// Access parsed data
$messageId = $data['messageId'];
$creationDate = $data['creationDate'];
$initiatingPartyName = $data['initiatingPartyName'];
$paymentInfoId = $data['paymentInfoId'];
$numberOfTransactions = $data['numberOfTransactions'];
$controlSum = $data['controlSum'];
$transactions = $data['transactions'];

// Validate XML format
if ($parser->isValidCreditTransfer($xml)) {
    echo "Valid SEPA Credit Transfer XML";
}
```

### Credit Card Validation

```php
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;

$validator = new CreditCardValidator();

// Validate credit card number (using Luhn algorithm)
if ($validator->isValid('4532015112830366')) {
    echo "Valid credit card";
}

// Normalize card number (remove spaces and dashes)
$normalized = $validator->normalize('4532 0151 1283 0366');
// Returns: 4532015112830366

// Format card number (add spaces every 4 digits)
$formatted = $validator->format('4532015112830366');
// Returns: 4532 0151 1283 0366

// Detect card type
$cardType = $validator->getCardType('4532015112830366');
// Returns: 'visa' (or 'mastercard', 'amex', 'discover', 'diners_club', 'jcb', 'unknown')

// Get BIN (Bank Identification Number - first 6 digits)
$bin = $validator->getBin('4532015112830366');
// Returns: 453201

// Get last 4 digits
$lastFour = $validator->getLastFour('4532015112830366');
// Returns: 0366

// Mask card number (show only last 4 digits)
$masked = $validator->mask('4532015112830366');
// Returns: ************0366

// Validate for specific card type
if ($validator->isValidForType('4532015112830366', CreditCardValidator::TYPE_VISA)) {
    echo "Valid Visa card";
}
```

### SEPA Mandates

```php
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;

$mandate = new Mandate(
    'MANDATE-001',                    // Mandate identifier
    new \DateTime('2024-01-15'),       // Signature date
    'ES9121000418450200051332',       // Debtor IBAN
    'John Doe',                       // Debtor name
    'CORE',                           // Mandate type (CORE, B2B)
    'FRST'                            // Sequence type (FRST, RCUR, OOFF, FNAL)
);

$mandate->setDebtorBic('CAIXESBBXXX');
$mandate->setSequenceType('RCUR'); // For recurring payments
$mandate->setActive(true);
```

### Generating SEPA Credit Transfer (Remesa de Pago)

**Credit transfers (remesas de pago)** are used to send money from the debtor (payer) to the creditor (beneficiary).

```php
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;

// Create remesa data
$remesaData = new RemesaData(
    'MSG-001',                                    // Message ID (unique)
    new \DateTime('2024-01-15 10:00:00'),        // Creation date
    'My Company',                                 // Initiating party name
    'PMT-001',                                    // Payment info ID
    'ES9121000418450200051332',                   // Creditor IBAN
    'My Company Name',                            // Creditor name
    new \DateTime('2024-01-20')                   // Requested execution date
);

$remesaData->setCreditorBic('CAIXESBBXXX');
$remesaData->setBatchBooking(true);

// Add transactions
$transaction1 = new Transaction(
    'E2E-001',                    // End-to-end ID (unique per transaction)
    100.50,                       // Amount
    'EUR',                        // Currency (ISO 4217)
    'GB82WEST12345698765432',     // Debtor IBAN
    'John Doe'                    // Debtor name
);

$transaction1->setDebtorBic('WESTGB22');

// Set debtor address (will be included in XML)
$transaction1->setDebtorAddress([
    'street' => '123 Main Street',
    'city' => 'London',
    'postalCode' => 'SW1A 1AA',
    'country' => 'GB',
]);
$transaction1->setRemittanceInformation('Invoice 12345');

$remesaData->addTransaction($transaction1);

// Add more transactions if needed
$transaction2 = new Transaction(
    'E2E-002',
    200.75,
    'EUR',
    'FR1420041010050500013M02606',
    'Jane Smith'
);
$remesaData->addTransaction($transaction2);

// Generate XML
$ibanValidator = new IbanValidator();
$generator = new RemesaGenerator($ibanValidator);
$xml = $generator->generate($remesaData);

// Save to file
file_put_contents('remesa.xml', $xml);

// Or return as HTTP Response (for Symfony controllers)
use Symfony\Component\HttpFoundation\Response;
$response = $generator->createResponse($xml, 'remesa-pago.xml');
return $response;
```

### Generating SEPA Direct Debit (Remesa de Cobro)

**Direct debits (remesas de cobro)** are used to collect money from the debtor by the creditor based on a SEPA mandate.

#### Using Array Format (Recommended)

The `generateFromArray()` method supports both **camelCase** and **snake_case** field names for maximum flexibility.

**Using camelCase (default):**

```php
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$generator = new DirectDebitGenerator(new IbanValidator());

$data = [
    'reference' => 'MSG-001',                    // Message ID (unique)
    'bankAccountOwner' => 'My Company',          // Initiating party name
    'paymentInfoId' => 'PMTINF-1',               // Payment info ID
    'dueDate' => new \DateTime('2024-01-20'),    // Due date
    'creditorName' => 'My Company Name',          // Creditor name
    'creditorIban' => 'ES9121000418450200051332', // Creditor IBAN
    'creditorBic' => 'CAIXESBBXXX',              // Creditor BIC (optional)
    'seqType' => 'RCUR',                         // Sequence type: FRST, RCUR, OOFF, FNAL
    'creditorId' => 'ES98ZZZ09999999999',        // SEPA identifier
    'localInstrumentCode' => 'CORE',             // CORE or B2B
    'transactions' => [
        [
            'amount' => 100.50,                           // Amount (in currency units)
            'debtorIban' => 'GB82WEST12345698765432',    // Debtor IBAN
            'debtorName' => 'John Doe',                   // Debtor name
            'debtorMandate' => 'MANDATE-001',            // Mandate identifier
            'debtorMandateSignDate' => new \DateTime('2024-01-15'), // Mandate sign date
            'endToEndId' => 'E2E-001',                    // End-to-end ID
            'remittanceInformation' => 'Invoice 12345',  // Remittance info (optional)
            'debtorBic' => 'WESTGB22',                    // Debtor BIC (optional)
            // Debtor address (optional, included in XML)
            'debtorAddress' => [
                'street' => '456 Customer Avenue',
                'city' => 'London',
                'postalCode' => 'SW1A 1AA',
                'country' => 'GB',
            ],
            // Or use individual fields:
            // 'debtorStreet' => '456 Customer Avenue',
            // 'debtorCity' => 'London',
            // 'debtorPostalCode' => 'SW1A 1AA',
            // 'debtorCountry' => 'GB',
            // You can add any additional custom fields here
            // They will be stored in additionalData and can be used in applyAdditionalData()
        ],
        // More transactions...
    ],
    // Creditor address (optional, included in XML)
    'creditorAddress' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postalCode' => '28001',
        'country' => 'ES',
    ],
    // Or use individual fields:
    // 'creditorStreet' => '123 Business Street',
    // 'creditorCity' => 'Madrid',
    // 'creditorPostalCode' => '28001',
    // 'creditorCountry' => 'ES',
];

$xml = $generator->generateFromArray($data);
file_put_contents('direct_debit.xml', $xml);
```

**Note about Addresses:**

As of version 0.0.8, postal addresses for both creditor and debtor are **optional** and will be included in the XML **only if provided** in the array. Addresses are added using structured format (PstlAdr) with elements like StrtNm, TwnNm, PstCd, and Ctry. The addresses are automatically added to the XML using DOM manipulation, ensuring compatibility with the SEPA pain.008.001.02 format.

**Important:**
- Addresses are **completely optional** - if not provided, no address elements will be added to the XML
- Empty address arrays are ignored and will not create address elements
- At least one address field (street, city, postalCode, or country) must be provided for the address to be included

See [docs/DEPRECATED_FIELDS.md](docs/DEPRECATED_FIELDS.md) for more information about deprecated fields.

**Using snake_case (also supported):**

```php
$data = [
    'message_id' => 'PRE2025121614020000001REM000001',
    'initiating_party_name' => 'My Company',
    'payment_name' => 'PMTINF-1',
    'due_date' => '2025-12-18',
    'creditor_name' => 'My Company Name',
    'creditor_iban' => 'ES2931183364320522274646',
    'creditor_bic' => 'BBVAESMM',
    'sequence_type' => 'RCUR',
    'creditor_id' => 'ES654646464646',
    'instrument_code' => 'CORE',
    'items' => [  // Note: 'items' is also accepted (maps to 'transactions')
        [
            'instruction_id' => 'E2E-001',  // Maps to 'endToEndId'
            'amount' => 2500.0,
            'debtor_iban' => 'ES3330605615396412039906',
            'debtor_name' => 'John Doe',
            'debtor_mandate' => 'MANDATE-001',
            'debtor_mandate_signature_date' => new \DateTime('2025-09-26'),
            'information' => 'Invoice details',  // Maps to 'remittanceInformation'
            'id' => 'custom-id',  // Additional field (stored in additionalData)
            'debtor_address' => [                        // Debtor address (snake_case, included in XML)
                'street' => '789 Customer Road',
                'city' => 'Barcelona',
                'postal_code' => '08001',
                'country' => 'ES',
            ],
        ],
    ],
];

$xml = $generator->generateFromArray($data);
```

**Field name mapping (snake_case → camelCase):**
- `message_id` → `reference`
- `initiating_party_name` → `bankAccountOwner`
- `payment_name` → `paymentInfoId`
- `due_date` → `dueDate`
- `creditor_name` → `creditorName`
- `creditor_iban` → `creditorIban`
- `creditor_bic` → `creditorBic`
- `sequence_type` → `seqType`
- `creditor_id` → `creditorId`
- `instrument_code` → `localInstrumentCode`
- `items` → `transactions`
- `instruction_id` → `endToEndId`
- `debtor_iban` → `debtorIban`
- `debtor_name` → `debtorName`
- `debtor_mandate` → `debtorMandate`
- `debtor_mandate_signature_date` → `debtorMandateSignDate`
- `information` → `remittanceInformation`

#### Using Object Format

```php
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitData;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitTransaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$directDebitData = new DirectDebitData(
    'MSG-001',                                    // Message ID
    'My Company',                                 // Initiating party name
    'PMTINF-1',                                   // Payment info ID
    new \DateTime('2024-01-20'),                  // Due date
    'My Company Name',                            // Creditor name
    'ES9121000418450200051332',                   // Creditor IBAN
    'RCUR',                                       // Sequence type
    'ES98ZZZ09999999999',                         // Creditor ID
    'CORE'                                        // Local instrument code
);

$directDebitData->setCreditorBic('CAIXESBBXXX');

$transaction = new DirectDebitTransaction(
    100.50,                                      // Amount
    'GB82WEST12345698765432',                    // Debtor IBAN
    'John Doe',                                  // Debtor name
    'MANDATE-001',                               // Mandate identifier
    new \DateTime('2024-01-15'),                 // Mandate sign date
    'E2E-001'                                    // End-to-end ID
);

$transaction->setRemittanceInformation('Invoice 12345');
$transaction->setDebtorBic('WESTGB22'); // Optional: Set debtor BIC

// Set debtor address (included in XML)
$transaction->setDebtorAddress([
    'street' => '456 Customer Avenue',
    'city' => 'London',
    'postalCode' => 'SW1A 1AA',
    'country' => 'GB',
]);

// You can also add additional custom data
$transaction->setAdditionalField('customField', 'customValue');
// Or set multiple additional fields at once
$transaction->setAdditionalData(['field1' => 'value1', 'field2' => 'value2']);

// Set creditor address (included in XML)
$directDebitData->setCreditorAddress([
    'street' => '123 Business Street',
    'city' => 'Madrid',
    'postalCode' => '28001',
    'country' => 'ES',
]);

$directDebitData->addTransaction($transaction);

$generator = new DirectDebitGenerator(new IbanValidator());
$xml = $generator->generate($directDebitData);

// Or return as HTTP Response (for Symfony controllers)
use Symfony\Component\HttpFoundation\Response;
$response = $generator->createResponse($xml, 'remesa-cobro.xml');
return $response;
```

### Using with Dependency Injection

The bundle registers services automatically using Symfony service attributes. All services are autowired and can be injected via constructor:

```php
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;

class MyService
{
    public function __construct(
        private IbanValidator $ibanValidator,
        private RemesaGenerator $remesaGenerator,
        private DirectDebitGenerator $directDebitGenerator,
        private CreditCardValidator $creditCardValidator
    ) {
    }

    public function generateRemesaPago(): string
    {
        $remesaData = new RemesaData(/* ... */);
        return $this->remesaGenerator->generate($remesaData);
    }

    public function generateRemesaCobro(array $data): string
    {
        return $this->directDebitGenerator->generateFromArray($data);
    }

    public function generateRemesaCobroResponse(array $data): \Symfony\Component\HttpFoundation\Response
    {
        $xml = $this->directDebitGenerator->generateFromArray($data);
        return $this->directDebitGenerator->createResponse($xml, 'remesa-cobro.xml');
    }
}
```

#### Service Aliases

Services are also available via their service aliases for explicit service retrieval:

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MyService
{
    public function __construct(
        #[Autowire('nowo_sepa_payment.generator.direct_debit_generator')]
        private DirectDebitGenerator $directDebitGenerator
    ) {
    }
}
```

The `DirectDebitGenerator` service is registered with the alias `nowo_sepa_payment.generator.direct_debit_generator` and is available as a public service for dependency injection.

## Console Commands

The bundle provides console commands for common operations:

### Validate IBAN

```bash
php bin/console nowo:sepa:validate-iban ES9121000418450200051332
```

This command validates an IBAN and displays detailed information:
- Normalized IBAN
- Formatted IBAN
- Country code
- Check digits
- BBAN
- Validation result

### Convert CCC to IBAN

```bash
php bin/console nowo:sepa:ccc-to-iban 21000418450200051332
```

This command converts a Spanish CCC to IBAN format and displays:
- Original CCC
- Generated IBAN
- Bank code
- Branch code
- Account number

### Validate Credit Card

```bash
php bin/console sepa:validate-credit-card 4532015112830366
```

This command validates a credit card number and displays detailed information:
- Normalized card number
- Formatted card number
- Masked card number
- Validation result (Luhn algorithm)
- Card type (Visa, Mastercard, Amex, etc.)
- BIN (Bank Identification Number)
- Last 4 digits
- Card length

The command accepts card numbers with or without spaces and dashes:
```bash
php bin/console sepa:validate-credit-card "4532 0151 1283 0366"
php bin/console sepa:validate-credit-card 4532-0151-1283-0366
```

## Configuration

The bundle works out of the box with default settings. **No configuration file is required** - the bundle uses sensible defaults.

### Optional Configuration

If you want to customize the default currency, create `config/packages/nowo_sepa_payment.yaml`:

```yaml
nowo_sepa_payment:
    default_currency: EUR  # Default currency code (ISO 4217)
```

## Requirements

- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0
- digitick/sepa-xml ^3.0 (automatically installed as a dependency)

## Demo Projects

The bundle includes demo projects for different Symfony versions. Each demo has its own `docker-compose.yml` and can be run independently:

- **Symfony 6.4 Demo**: `demo/demo-symfony6/` (Port 8001 by default)
- **Symfony 7.0 Demo**: `demo/demo-symfony7/` (Port 8001 by default)
- **Symfony 8.0 Demo**: `demo/demo-symfony8/` (Port 8001 by default)

### Demo Endpoints

Each demo application includes the following endpoints to showcase bundle functionality:

**Validators:**
- `/validate-iban?iban=ES9121000418450200051332` - Validate IBAN and display detailed information
- `/validate-bic?bic=ESPBESMM` - Validate BIC and extract components
- `/validate-credit-card?card=4532015112830366` - Validate credit card number using Luhn algorithm

**Converters:**
- `/convert-ccc?ccc=21000418450200051332` - Convert Spanish CCC to IBAN format

**Generators:**
- `/generate-identifier` - Generate various types of identifiers (message, payment, end-to-end, mandate)
- `/demo-mandate` - Demo SEPA mandate creation
- `/demo-remesa-pago` - Generate and download SEPA Credit Transfer XML
- `/demo-remesa-cobro` - Generate and download SEPA Direct Debit XML

### Quick Start with Docker

```bash
cd demo
make up-symfony6
make install-symfony6
# Access at: http://localhost:8001
```

See `demo/README.md` for more details.

## Development

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

The bundle has comprehensive test coverage with **100% code coverage**. All tests are located in the `tests/` directory and cover:

- **Validators**: `IbanValidator`, `BicValidator`, `CreditCardValidator`
- **Converters**: `CccConverter`
- **Generators**: `RemesaGenerator`, `DirectDebitGenerator`, `IdentifierGenerator`
  - `DirectDebitGenerator` includes extensive test coverage for all code paths:
    - Array-based generation with various data types
    - Validation of required fields
    - Optional fields handling
    - Edge cases (empty transactions, amount conversion, etc.)
- **Models**: `RemesaData`, `Transaction`, `DirectDebitData`, `DirectDebitTransaction`, `Mandate`
- **Parsers**: `RemesaParser`
- **Commands**: All console commands

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
- **Coverage**: Validates 100% code coverage requirement
- **Dependabot**: Automatically updates dependencies

See `.github/workflows/ci.yml` for details.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Author

Created by [Héctor Franco Aceituno](https://github.com/HecFranco) at [Nowo.tech](https://nowo.tech)

## Contributing

Please read [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## Branching Strategy

See [docs/BRANCHING.md](docs/BRANCHING.md) for information about our branching strategy and workflow.

## Upgrade Guide

See [docs/UPGRADE.md](docs/UPGRADE.md) for instructions on upgrading between versions.

## Deprecated Fields

See [docs/DEPRECATED_FIELDS.md](docs/DEPRECATED_FIELDS.md) for information about fields that are no longer allowed in SEPA Direct Debit transactions (e.g., postal addresses).

## Changelog

See [docs/CHANGELOG.md](docs/CHANGELOG.md) for a list of changes and version history.
