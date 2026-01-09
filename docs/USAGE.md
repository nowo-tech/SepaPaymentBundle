# Usage Guide

This document provides detailed usage examples for all features of the SEPA Payment Bundle.

## Table of Contents

- [IBAN Validation](#iban-validation)
- [CCC to IBAN Conversion](#ccc-to-iban-conversion)
- [BIC Validation](#bic-validation)
- [Automatic BIC Lookup by IBAN](#automatic-bic-lookup-by-iban)
- [Credit Card Validation](#credit-card-validation)
- [Identifier Generation](#identifier-generation)
- [SEPA XML Parser](#sepa-xml-parser)
- [XSD Schema Validation](#xsd-schema-validation)
- [SEPA String Sanitization](#sepa-string-sanitization)
- [SEPA Country Validation](#sepa-country-validation)
- [SEPA Business Rules Validation](#sepa-business-rules-validation)
- [SEPA Mandates](#sepa-mandates)
- [Export to Other Formats](#export-to-other-formats)
- [Symfony Events](#symfony-events)
- [Structured Logging](#structured-logging)
- [Generating SEPA Credit Transfer (Remesa de Pago)](#generating-sepa-credit-transfer-remesa-de-pago)
- [Generating SEPA Direct Debit (Remesa de Cobro)](#generating-sepa-direct-debit-remesa-de-cobro)
- [Using with Dependency Injection](#using-with-dependency-injection)

## IBAN Validation

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

## CCC to IBAN Conversion

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

## BIC Validation

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

## Automatic BIC Lookup by IBAN

The `BicLookupService` automatically finds BIC codes from IBANs, reducing manual work and errors.

### Basic Usage

```php
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$ibanValidator = new IbanValidator();
$bicLookup = new BicLookupService($ibanValidator);

// Look up BIC from IBAN
$iban = 'ES9121000418450200051332';
$bic = $bicLookup->lookupBic($iban);
// Returns: 'CAIXESBB' (if found in database)

// Check if lookup is available for a country
if ($bicLookup->isAvailable($iban)) {
    $bic = $bicLookup->lookupBic($iban);
    if (null !== $bic) {
        echo "BIC found: {$bic}";
    } else {
        echo "BIC not found in database";
    }
}
```

### Supported Countries

The service includes mappings for major banks in:
- 🇪🇸 **Spain (ES)**: Bank codes (first 4 digits of BBAN)
- 🇩🇪 **Germany (DE)**: Bank codes (first 8 digits of BBAN)
- 🇫🇷 **France (FR)**: Bank codes (first 5 digits of BBAN)
- 🇮🇹 **Italy (IT)**: Bank codes (first 5 digits of BBAN)
- 🇬🇧 **United Kingdom (GB)**: Sort codes (first 4 digits of BBAN)
- 🇳🇱 **Netherlands (NL)**: Bank codes (first 4 characters of BBAN)
- 🇧🇪 **Belgium (BE)**: Bank codes (first 3 digits of BBAN)
- 🇵🇹 **Portugal (PT)**: Bank codes (first 4 digits of BBAN)

### Adding Custom Mappings

You can add custom bank mappings for banks not in the default database:

```php
// Add mapping for a Spanish bank with code 9999
$bicLookup->addMapping('ES', '9999', 'CUSTOMBIC');

// Now IBANs with bank code 9999 will return 'CUSTOMBIC'
$iban = 'ES9121009999000000000000';
$bic = $bicLookup->lookupBic($iban);
// Returns: 'CUSTOMBIC'
```

### Cache Support (Optional)

You can use a PSR-16 compatible cache to cache lookup results and improve performance:

```php
use Psr\SimpleCache\CacheInterface;

// Your cache implementation (e.g., Symfony Cache)
$cache = /* your cache implementation */;

// Create lookup service with cache (24 hour TTL)
$bicLookup = new BicLookupService($ibanValidator, $cache, 86400);

// First lookup: queries database
$bic1 = $bicLookup->lookupBic($iban);

// Second lookup: uses cache
$bic2 = $bicLookup->lookupBic($iban);
```

### Automatic Integration in Generators

When you inject `BicLookupService` into generators, BIC codes are automatically filled when missing:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$ibanValidator = new IbanValidator();
$bicLookup = new BicLookupService($ibanValidator);

// Inject BIC lookup service into generator
$generator = new CreditTransferGenerator(
    $ibanValidator,
    null, // XSD validator (optional)
    false, // validate XSD (optional)
    null, // event dispatcher (optional)
    null, // logger (optional)
    $bicLookup // BIC lookup service (optional)
);

// Create data without BIC
$creditTransferData = new CreditTransferData(
    'MSG-001',
    new \DateTime(),
    'My Company',
    'PMT-001',
    'ES9121000418450200051332', // IBAN only, no BIC provided
    'My Company Name',
    new \DateTime('tomorrow')
);

// Add transaction without BIC
$transaction = new Transaction(
    'E2E-001',
    100.50,
    'EUR',
    'GB82WEST12345698765432', // IBAN only, no BIC
    'John Doe'
);
$creditTransferData->addTransaction($transaction);

// BIC codes will be automatically looked up and included in XML
$xml = $generator->generate($creditTransferData);
```

The same applies to `DirectDebitGenerator`:

```php
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;

$generator = new DirectDebitGenerator(
    $ibanValidator,
    null, // XSD validator (optional)
    false, // validate XSD (optional)
    null, // event dispatcher (optional)
    null, // logger (optional)
    $bicLookup // BIC lookup service (optional)
);

// BIC codes will be automatically filled when missing
$xml = $generator->generate($directDebitData);
```

### Dependency Injection

In Symfony, you can inject the service via dependency injection:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Lookup\BicLookupServiceInterface;

class PaymentService
{
    public function __construct(
        private CreditTransferGenerator $generator,
        private BicLookupServiceInterface $bicLookup
    ) {
    }

    public function generatePayment(array $data): string
    {
        // Generator will automatically use BIC lookup if BIC is missing
        return $this->generator->generateFromArray($data);
    }
}
```

The service is automatically registered and can be injected:

```yaml
# config/services.yaml
services:
    App\Service\PaymentService:
        arguments:
            $generator: '@nowo_sepa_payment.generator.credit_transfer_generator'
            $bicLookup: '@nowo_sepa_payment.lookup.bic_lookup_service'
```

Or use autowiring (recommended):

```php
class PaymentService
{
    public function __construct(
        private CreditTransferGenerator $generator,
        private BicLookupServiceInterface $bicLookup
    ) {
    }
}
```

## Credit Card Validation

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

## Identifier Generation

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

## SEPA XML Parser

The bundle provides parsers for both SEPA Credit Transfer and SEPA Direct Debit XML files.

### Parsing SEPA Credit Transfer

```php
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;

$parser = new CreditTransferParser();

// Parse SEPA Credit Transfer XML
$xml = file_get_contents('credit-transfer.xml');
$data = $parser->parseCreditTransfer($xml);

// Access parsed data
$messageId = $data['messageId'];
$creationDate = $data['creationDate'];
$initiatingPartyName = $data['initiatingPartyName'];
$paymentInfoId = $data['paymentInfoId'];
$numberOfTransactions = $data['numberOfTransactions'];
$controlSum = $data['controlSum'];
$transactions = $data['transactions'];

// Each transaction contains:
foreach ($data['transactions'] as $transaction) {
    $endToEndId = $transaction['endToEndId'];
    $amount = $transaction['amount'];
    $currency = $transaction['currency'];
    $iban = $transaction['iban'];
    $name = $transaction['name'];
    $remittanceInformation = $transaction['remittanceInformation'] ?? null;
}

// Validate XML format
if ($parser->isValidCreditTransfer($xml)) {
    echo "Valid SEPA Credit Transfer XML";
}
```

### Parsing SEPA Direct Debit

```php
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;

$parser = new DirectDebitParser();

// Parse SEPA Direct Debit XML
$xml = file_get_contents('direct-debit.xml');
$data = $parser->parseDirectDebit($xml);

// Access parsed data
$messageId = $data['messageId'];
$creationDate = $data['creationDate'];
$initiatingPartyName = $data['initiatingPartyName'];
$paymentInfoId = $data['paymentInfoId'];
$paymentMethod = $data['paymentMethod']; // Usually "DD"
$numberOfTransactions = $data['numberOfTransactions'];
$controlSum = $data['controlSum'];
$sequenceType = $data['sequenceType']; // FRST, RCUR, OOFF, FNAL
$dueDate = $data['dueDate'];
$creditorName = $data['creditorName'];
$creditorIban = $data['creditorIban'];
$creditorId = $data['creditorId'];
$localInstrumentCode = $data['localInstrumentCode']; // CORE, B2B

// Creditor address (if present)
$creditorAddress = $data['creditorAddress'] ?? null;
if ($creditorAddress) {
    $street = $creditorAddress['street'] ?? null;
    $city = $creditorAddress['city'] ?? null;
    $postalCode = $creditorAddress['postalCode'] ?? null;
    $country = $creditorAddress['country'] ?? null;
}

// Each transaction contains:
foreach ($data['transactions'] as $transaction) {
    $endToEndId = $transaction['endToEndId'];
    $amount = $transaction['amount'];
    $currency = $transaction['currency'];
    $mandateId = $transaction['mandateId'];
    $mandateSignDate = $transaction['mandateSignDate'];
    $debtorName = $transaction['debtorName'];
    $debtorIban = $transaction['debtorIban'];
    $debtorBic = $transaction['debtorBic'] ?? null;
    $remittanceInformation = $transaction['remittanceInformation'] ?? null;
    
    // Debtor address (if present)
    $debtorAddress = $transaction['debtorAddress'] ?? null;
    if ($debtorAddress) {
        $street = $debtorAddress['street'] ?? null;
        $city = $debtorAddress['city'] ?? null;
        $postalCode = $debtorAddress['postalCode'] ?? null;
        $country = $debtorAddress['country'] ?? null;
    }
}

// Validate XML format
if ($parser->isValidDirectDebit($xml)) {
    echo "Valid SEPA Direct Debit XML";
}
```
<｜tool▁calls▁begin｜><｜tool▁call▁begin｜>
read_file

## XSD Schema Validation

The bundle includes an `XsdValidator` service that can validate generated XML files against official SEPA XSD schemas (ISO 20022). This ensures that your XML files are fully compliant with SEPA standards.

### Basic Usage

```php
use Nowo\SepaPaymentBundle\Validator\XsdValidator;

$validator = new XsdValidator();

// Validate Credit Transfer XML
try {
    $isValid = $validator->validateCreditTransfer($xml);
    if ($isValid) {
        echo "XML is valid against pain.001.001.03 schema";
    }
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: " . $e->getMessage();
}

// Validate Direct Debit XML
try {
    $isValid = $validator->validateDirectDebit($xml);
    if ($isValid) {
        echo "XML is valid against pain.008.001.02 schema";
    }
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: " . $e->getMessage();
}
```

### Using Custom XSD Schema Files

If you have your own XSD schema files, you can specify the path:

```php
$validator = new XsdValidator();

// Validate against a specific XSD file
$isValid = $validator->validate($xml, '/path/to/schema.xsd', 'credit_transfer');
```

### Validating Against Schema String

You can also validate against an XSD schema provided as a string:

```php
$xsdContent = file_get_contents('/path/to/schema.xsd');
$isValid = $validator->validateAgainstSchemaString($xml, $xsdContent);
```

### Enabling XSD Validation in Generators

You can enable automatic XSD validation when generating XML files:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;

$ibanValidator = new IbanValidator();
$xsdValidator = new XsdValidator();

// Enable XSD validation (third parameter)
$generator = new CreditTransferGenerator($ibanValidator, $xsdValidator, true);

// Now all generated XML will be validated against XSD schema
$xml = $generator->generate($creditTransferData);
// If validation fails, an InvalidArgumentException will be thrown
```

### XSD Schema Files

The validator looks for XSD schema files in `src/Resources/schemas/`:
- `pain.001.001.03.xsd` - Credit Transfer schema
- `pain.008.001.02.xsd` - Direct Debit schema

You can download these schemas from:
- ISO 20022 official repository: https://www.iso20022.org/
- SEPA official documentation

**Note**: If XSD schema files are not found, validation will be skipped (returns `true`). This allows the validator to work even without schema files, but for full validation, you should download and place the XSD files in the schemas directory.

## SEPA String Sanitization

The `SepaStringSanitizer` validates and sanitizes strings according to SEPA character rules and field length limits.

```php
use Nowo\SepaPaymentBundle\Validator\SepaStringSanitizer;

$sanitizer = new SepaStringSanitizer();

// Validate if a string contains only allowed SEPA characters
if ($sanitizer->isValid('John Doe')) {
    echo "Valid SEPA string";
}

// Sanitize a string (removes invalid characters, replaces accented characters)
$sanitized = $sanitizer->sanitize('José García & Company');
// Returns: "Jose Garcia Company"

// Validate field lengths
$name = 'Very Long Company Name...';
if ($sanitizer->isValidNameLength($name)) {
    echo "Name length is valid (max 70 characters)";
}

// Truncate strings to maximum allowed length
$longName = str_repeat('A', 100);
$truncated = $sanitizer->truncateName($longName);
// Returns: string of 70 characters
```

### Field Length Limits

- **Name fields**: Maximum 70 characters
- **Street address**: Maximum 70 characters
- **City**: Maximum 35 characters
- **Postal code**: Maximum 16 characters
- **Remittance information**: Maximum 140 characters

### Allowed Characters

SEPA allows the following characters in names and addresses:
- Letters: a-z, A-Z
- Digits: 0-9
- Special characters: `/ - ? ( ) . , ' + Space`

Accented characters (á, é, í, ó, ú, ñ, etc.) are automatically converted to their ASCII equivalents.

## SEPA Country Validation

The `SepaCountryValidator` validates if a country is a SEPA member country.

```php
use Nowo\SepaPaymentBundle\Validator\SepaCountryValidator;

$validator = new SepaCountryValidator();

// Validate if a country is a SEPA member
if ($validator->isSepaCountry('ES')) {
    echo "Spain is a SEPA member";
}

// Validate country from IBAN
if ($validator->isSepaCountryFromIban('ES9121000418450200051332')) {
    echo "IBAN country is a SEPA member";
}

// Get all SEPA member countries
$sepaCountries = $validator->getSepaCountries();
// Returns: ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', ...]

// Get country name
$countryName = $validator->getCountryName('ES');
// Returns: "Spain"
```

### SEPA Member Countries

The validator includes all current SEPA member countries (34 countries as of 2025), including:
- All EU member states
- EEA countries (Iceland, Liechtenstein, Norway)
- Switzerland
- United Kingdom (still a SEPA member despite Brexit)
- Monaco and San Marino

## SEPA Business Rules Validation

The `SepaBusinessRulesValidator` validates SEPA-specific business rules and limits.

```php
use Nowo\SepaPaymentBundle\Validator\SepaBusinessRulesValidator;

$validator = new SepaBusinessRulesValidator();

// Validate transaction amount (max: 999,999,999.99 EUR)
if ($validator->isValidTransactionAmount(100.50)) {
    echo "Transaction amount is valid";
}

// Validate transaction count (max: 99,999 per file)
if ($validator->isValidTransactionCount(100)) {
    echo "Transaction count is valid";
}

// Validate execution date (must be today or future)
$executionDate = new \DateTime('tomorrow');
if ($validator->isValidExecutionDate($executionDate)) {
    echo "Execution date is valid";
}

// Check if date is a business day (Monday to Friday)
if ($validator->isBusinessDay($executionDate)) {
    echo "Execution date is a business day";
}

// Validate currency (EUR only for SEPA)
if ($validator->isValidSepaCurrency('EUR')) {
    echo "Currency is valid for SEPA";
}

// Validate sequence type transitions for Direct Debit
$previousType = 'FRST';
$newType = 'RCUR';
if ($validator->isValidSequenceTypeTransition($previousType, $newType)) {
    echo "Sequence type transition is valid";
}

// Validate all business rules for a credit transfer
$errors = $validator->validateCreditTransfer(
    100.50,                    // Amount
    1,                         // Transaction count
    new \DateTime('tomorrow'), // Execution date
    'EUR'                      // Currency
);

if (empty($errors)) {
    echo "All business rules are valid";
} else {
    foreach ($errors as $error) {
        echo $error . "\n";
    }
}

// Validate all business rules for a direct debit
$errors = $validator->validateDirectDebit(
    100.50,                    // Amount
    1,                         // Transaction count
    new \DateTime('tomorrow'), // Due date
    'EUR',                     // Currency
    'FRST',                    // Sequence type
    new \DateTime('2025-12-31') // Mandate expiration date (optional)
);
```

### Business Rules

- **Maximum transaction amount**: 999,999,999.99 EUR
- **Maximum transactions per file**: 99,999
- **Execution date**: Must be today or in the future
- **Currency**: Only EUR is allowed for SEPA
- **Sequence type transitions**: 
  - First transaction: FRST or OOFF
  - FRST → RCUR or FNAL
  - RCUR → RCUR or FNAL
  - OOFF → OOFF only
  - FNAL → FNAL only

## Export to Other Formats

The `ExportService` allows you to export SEPA payment data to JSON and CSV formats, and import from JSON.

### JSON Export

```php
use Nowo\SepaPaymentBundle\Exporter\ExportService;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$generator = new CreditTransferGenerator(new IbanValidator());
$parser = new CreditTransferParser();
$exporter = new ExportService();

// Generate XML
$xml = $generator->generateFromArray([
    'reference' => 'MSG-001',
    'initiatingPartyName' => 'My Company',
    'paymentInfoId' => 'PMT-001',
    'creditorIban' => 'ES9121000418450200051332',
    'creditorName' => 'My Company Name',
    'requestedExecutionDate' => '2024-01-20',
    'transactions' => [
        [
            'amount' => 100.50,
            'currency' => 'EUR',
            'debtorIban' => 'GB82WEST12345698765432',
            'debtorName' => 'John Doe',
            'endToEndId' => 'E2E-001',
        ],
    ],
]);

// Parse XML
$parsedData = $parser->parseCreditTransfer($xml);

// Export to JSON
$json = $exporter->exportCreditTransferToJson($parsedData, true); // true = pretty print
echo $json;

// Export Direct Debit to JSON
$json = $exporter->exportDirectDebitToJson($parsedData, true);
```

### CSV Export

```php
// Export Credit Transfer to CSV
$csv = $exporter->exportCreditTransferToCsv($parsedData);
file_put_contents('credit-transfer.csv', $csv);

// Export Direct Debit to CSV
$csv = $exporter->exportDirectDebitToCsv($parsedData);
file_put_contents('direct-debit.csv', $csv);

// Custom delimiter and enclosure
$csv = $exporter->exportCreditTransferToCsv($parsedData, ';', '"');
```

### JSON Import

```php
// Import from JSON
$json = '{
    "messageId": "MSG-001",
    "creationDate": "2024-01-15T10:00:00",
    "initiatingPartyName": "My Company",
    "paymentInfoId": "PMT-001",
    "creditorIban": "ES9121000418450200051332",
    "creditorName": "My Company Name",
    "requestedExecutionDate": "2024-01-20",
    "transactions": [
        {
            "endToEndId": "E2E-001",
            "amount": 100.50,
            "currency": "EUR",
            "debtorIban": "GB82WEST12345698765432",
            "debtorName": "John Doe"
        }
    ]
}';

$data = $exporter->importCreditTransferFromJson($json);

// Use the imported data with generator
$xml = $generator->generateFromArray($data);
```

## Symfony Events

The bundle dispatches Symfony events that allow you to extend functionality without modifying the bundle code.

### Available Events

- `BeforeCreditTransferGenerationEvent`: Dispatched before Credit Transfer XML generation
- `AfterCreditTransferGenerationEvent`: Dispatched after Credit Transfer XML generation
- `BeforeDirectDebitGenerationEvent`: Dispatched before Direct Debit XML generation
- `AfterDirectDebitGenerationEvent`: Dispatched after Direct Debit XML generation

### Event Listeners

```php
use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Nowo\SepaPaymentBundle\Event\AfterCreditTransferGenerationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SepaPaymentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCreditTransferGenerationEvent::class => 'onBeforeGeneration',
            AfterCreditTransferGenerationEvent::class => 'onAfterGeneration',
        ];
    }

    public function onBeforeGeneration(BeforeCreditTransferGenerationEvent $event): void
    {
        $data = $event->getCreditTransferData();
        
        // Modify data before generation
        // For example, add a custom transaction or modify creditor name
        $data->setCreditorName('Modified Company Name');
        
        $event->setCreditTransferData($data);
    }

    public function onAfterGeneration(AfterCreditTransferGenerationEvent $event): void
    {
        $xml = $event->getXml();
        
        // Modify XML after generation
        // For example, add custom elements or modify existing ones
        $xml = str_replace('Original', 'Modified', $xml);
        
        $event->setXml($xml);
    }
}
```

### Registering Event Listeners

In `config/services.yaml`:

```yaml
services:
    App\EventListener\SepaPaymentSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

### Using Event Dispatcher in Generators

The generators automatically use the event dispatcher if it's available via dependency injection:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// The event dispatcher is automatically injected if available
$generator = new CreditTransferGenerator(
    new IbanValidator(),
    null, // XsdValidator (optional)
    false, // validateXsd
    $eventDispatcher // EventDispatcherInterface (optional)
);
```

## Structured Logging

The `SepaPaymentLogger` service provides structured logging for all SEPA operations, integrating with PSR-3 logging interfaces for maximum compatibility.

### Basic Usage

```php
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

// Create logger with PSR-3 logger (or use NullLogger if not provided)
$psrLogger = /* your PSR-3 logger implementation */;
$logger = new SepaPaymentLogger($psrLogger);

// Or use without logger (defaults to NullLogger)
$logger = new SepaPaymentLogger();
```

### Automatic Integration in Generators

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

### Logging Methods

The logger provides structured methods for different operations:

**Credit Transfer Generation:**

```php
// Log generation start
$logger->logCreditTransferGenerationStart('MSG-001', 5);

// Log generation success
$logger->logCreditTransferGenerationSuccess('MSG-001', 5, 1024); // messageId, count, xmlSize

// Log generation failure
$logger->logCreditTransferGenerationFailure('MSG-001', 5, 'Error message');
```

**Direct Debit Generation:**

```php
// Log generation start
$logger->logDirectDebitGenerationStart('MSG-001', 3);

// Log generation success
$logger->logDirectDebitGenerationSuccess('MSG-001', 3, 2048);

// Log generation failure
$logger->logDirectDebitGenerationFailure('MSG-001', 3, 'Error message');
```

**Validation Events:**

```php
// Log IBAN validation
$logger->logIbanValidation('ES9121000418450200051332', true);

// Log BIC validation
$logger->logBicValidation('CAIXESBBXXX', true);

// Log XSD validation
$logger->logXsdValidation('credit_transfer', true, 'Validation successful');
```

**Parsing Events:**

```php
// Log Credit Transfer parsing
$logger->logCreditTransferParsing('MSG-001', 5, true);

// Log Direct Debit parsing
$logger->logDirectDebitParsing('MSG-001', 3, true);
```

### Context Data

All log entries include contextual data:

```php
// Log entries automatically include:
// - messageId: The SEPA message identifier
// - transactionCount: Number of transactions
// - event: Event type (e.g., 'credit_transfer.generation.start')
// - Additional context based on the operation
```

### Dependency Injection

In Symfony, you can inject the logger service:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;

class PaymentService
{
    public function __construct(
        private CreditTransferGenerator $generator,
        private SepaPaymentLogger $logger
    ) {
    }

    public function generatePayment(array $data): string
    {
        // Generator will automatically use logger if injected
        return $this->generator->generateFromArray($data);
    }
}
```

The service is automatically registered and can be injected:

```yaml
# config/services.yaml
services:
    App\Service\PaymentService:
        arguments:
            $generator: '@nowo_sepa_payment.generator.credit_transfer_generator'
            $logger: '@nowo_sepa_payment.logger.sepa_payment_logger'
```

Or use autowiring (recommended):

```php
class PaymentService
{
    public function __construct(
        private CreditTransferGenerator $generator,
        private SepaPaymentLogger $logger
    ) {
    }
}
```

### Custom Logger Integration

You can use any PSR-3 compatible logger:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;

// Create Monolog logger
$monolog = new Logger('sepa_payment');
$monolog->pushHandler(new StreamHandler('path/to/your.log', Logger::DEBUG));

// Use with SepaPaymentLogger
$logger = new SepaPaymentLogger($monolog);
```

### Log Levels

The logger uses appropriate PSR-3 log levels:
- **Info**: Generation start, success, validation success
- **Warning**: Validation warnings
- **Error**: Generation failures, validation failures

## SEPA Mandates

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

## Generating SEPA Credit Transfer (Remesa de Pago)

**Credit transfers (remesas de pago)** are used to send money from the debtor (payer) to the creditor (beneficiary).

### Using Array Format (Recommended)

The `generateFromArray()` method supports both **camelCase** and **snake_case** field names for maximum flexibility.

**Using camelCase (default):**

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$generator = new CreditTransferGenerator(new IbanValidator());

$data = [
    'reference' => 'MSG-001',                    // Message ID (unique)
    'initiatingPartyName' => 'My Company',        // Initiating party name
    'paymentInfoId' => 'PMT-001',                 // Payment info ID
    'creditorIban' => 'ES9121000418450200051332', // Creditor IBAN
    'creditorName' => 'My Company Name',          // Creditor name
    'requestedExecutionDate' => '2024-01-20',     // Requested execution date (string or DateTime)
    'creditorBic' => 'CAIXESBBXXX',               // Creditor BIC (optional)
    'batchBooking' => true,                       // Batch booking (optional, default: false)
    'creationDate' => '2024-01-15 10:00:00',      // Creation date (optional, defaults to now)
    'transactions' => [
        [
            'amount' => 100.50,                   // Amount
            'currency' => 'EUR',                  // Currency (optional, defaults to EUR)
            'debtorIban' => 'GB82WEST12345698765432', // Debtor IBAN
            'debtorName' => 'John Doe',           // Debtor name
            'endToEndId' => 'E2E-001',            // End-to-end ID (unique per transaction)
            'debtorBic' => 'WESTGB22',            // Debtor BIC (optional)
            'remittanceInformation' => 'Invoice 12345', // Remittance information (optional)
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
file_put_contents('credit-transfer.xml', $xml);
```

**Note about Addresses:**

Postal addresses for both creditor and debtor are **optional** and will be included in the XML **only if provided** in the array. Addresses are added using structured format (PstlAdr) with elements like StrtNm, TwnNm, PstCd, and Ctry. The addresses are automatically added to the XML using DOM manipulation, ensuring compatibility with the SEPA pain.001.001.03 format.

**Important:**
- Addresses are **completely optional** - if not provided, no address elements will be added to the XML
- Empty address arrays are ignored and will not create address elements
- At least one address field (street, city, postalCode, or country) must be provided for the address to be included

**Using snake_case (also supported):**

```php
$data = [
    'message_id' => 'MSG-001',
    'initiating_party_name' => 'My Company',
    'payment_info_id' => 'PMT-001',
    'creditor_iban' => 'ES9121000418450200051332',
    'creditor_name' => 'My Company Name',
    'requested_execution_date' => '2024-01-20',
    'creditor_bic' => 'CAIXESBBXXX',
    'batch_booking' => true,
    'items' => [  // 'items' is normalized to 'transactions'
        [
            'instruction_id' => 'E2E-001',  // 'instruction_id' is normalized to 'endToEndId'
            'amount' => 100.50,
            'currency' => 'EUR',
            'debtor_iban' => 'GB82WEST12345698765432',
            'debtor_name' => 'John Doe',
            'debtor_bic' => 'WESTGB22',
            'remittance_information' => 'Invoice 12345',
            'debtor_address' => [
                'street' => '456 Customer Avenue',
                'city' => 'London',
                'postal_code' => 'SW1A 1AA',
                'country' => 'GB',
            ],
        ],
    ],
    'creditor_address' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postal_code' => '28001',
        'country' => 'ES',
    ],
];

$xml = $generator->generateFromArray($data);
```

### Using Object Format

You can also use the object-based approach for more control:

```php
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;

// Create credit transfer data
$creditTransferData = new CreditTransferData(
    'MSG-001',                                    // Message ID (unique)
    new \DateTime('2024-01-15 10:00:00'),        // Creation date
    'My Company',                                 // Initiating party name
    'PMT-001',                                    // Payment info ID
    'ES9121000418450200051332',                   // Creditor IBAN
    'My Company Name',                            // Creditor name
    new \DateTime('2024-01-20')                   // Requested execution date
);

$creditTransferData->setCreditorBic('CAIXESBBXXX');
$creditTransferData->setBatchBooking(true);

// Set creditor address (will be included in XML)
$creditTransferData->setCreditorAddress([
    'street' => '123 Business Street',
    'city' => 'Madrid',
    'postalCode' => '28001',
    'country' => 'ES',
]);

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

$creditTransferData->addTransaction($transaction1);

// Add more transactions if needed
$transaction2 = new Transaction(
    'E2E-002',
    200.75,
    'EUR',
    'FR1420041010050500013M02606',
    'Jane Smith'
);
$creditTransferData->addTransaction($transaction2);

// Generate XML
$ibanValidator = new IbanValidator();
$generator = new CreditTransferGenerator($ibanValidator);
$xml = $generator->generate($creditTransferData);

// Save to file
file_put_contents('credit-transfer.xml', $xml);

// Or return as HTTP Response (for Symfony controllers)
use Symfony\Component\HttpFoundation\Response;
$response = $generator->createResponse($xml, 'credit-transfer.xml');
return $response;
```

## Generating SEPA Direct Debit (Remesa de Cobro)

**Direct debits (remesas de cobro)** are used to collect money from the debtor by the creditor based on a SEPA mandate.

### Using Array Format (Recommended)

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

See [DEPRECATED_FIELDS.md](DEPRECATED_FIELDS.md) for more information about deprecated fields.

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

### Using Object Format

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
$response = $generator->createResponse($xml, 'direct-debit.xml');
return $response;
```

## Using with Dependency Injection

The bundle registers services automatically using Symfony service attributes. All services are autowired and can be injected via constructor:

```php
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Validator\CreditCardValidator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;

class MyService
{
    public function __construct(
        private IbanValidator $ibanValidator,
        private CreditTransferGenerator $creditTransferGenerator,
        private DirectDebitGenerator $directDebitGenerator,
        private CreditCardValidator $creditCardValidator
    ) {
    }

    public function generateCreditTransfer(): string
    {
        $creditTransferData = new CreditTransferData(/* ... */);
        return $this->creditTransferGenerator->generate($creditTransferData);
    }

    public function generateCreditTransferFromArray(array $data): string
    {
        return $this->creditTransferGenerator->generateFromArray($data);
    }

    public function generateDirectDebit(array $data): string
    {
        return $this->directDebitGenerator->generateFromArray($data);
    }

    public function generateDirectDebitResponse(array $data): \Symfony\Component\HttpFoundation\Response
    {
        $xml = $this->directDebitGenerator->generateFromArray($data);
        return $this->directDebitGenerator->createResponse($xml, 'direct-debit.xml');
    }
}
```

### Service Aliases

All services are registered with consistent aliases and can be retrieved explicitly using their service IDs:

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

class MyService
{
    public function __construct(
        #[Autowire('nowo_sepa_payment.generator.direct_debit_generator')]
        private DirectDebitGenerator $directDebitGenerator,
        #[Autowire('nowo_sepa_payment.validator.iban_validator')]
        private IbanValidator $ibanValidator
    ) {
    }
}
```

**Available service aliases:**
- `nowo_sepa_payment.validator.iban_validator` - IBAN validator
- `nowo_sepa_payment.validator.bic_validator` - BIC validator
- `nowo_sepa_payment.validator.credit_card_validator` - Credit card validator
- `nowo_sepa_payment.converter.ccc_converter` - CCC to IBAN converter
- `nowo_sepa_payment.generator.credit_transfer_generator` - Credit transfer generator
- `nowo_sepa_payment.generator.direct_debit_generator` - Direct debit generator
- `nowo_sepa_payment.generator.identifier_generator` - Identifier generator
- `nowo_sepa_payment.parser.credit_transfer_parser` - Credit transfer parser
- `nowo_sepa_payment.validator.sepa_string_sanitizer` - SEPA string sanitizer
- `nowo_sepa_payment.validator.sepa_country_validator` - SEPA country validator
- `nowo_sepa_payment.validator.sepa_business_rules_validator` - SEPA business rules validator

All services are public and available for dependency injection via autowiring (type-hinting) or explicit alias retrieval.
