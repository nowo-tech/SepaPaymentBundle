# Usage Guide

This document provides detailed usage examples for all features of the SEPA Payment Bundle.

## Table of Contents

- [IBAN Validation](#iban-validation)
- [CCC to IBAN Conversion](#ccc-to-iban-conversion)
- [BIC Validation](#bic-validation)
- [Credit Card Validation](#credit-card-validation)
- [Identifier Generation](#identifier-generation)
- [SEPA XML Parser](#sepa-xml-parser)
- [SEPA Mandates](#sepa-mandates)
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
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$generator = new RemesaGenerator(new IbanValidator());

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
file_put_contents('remesa.xml', $xml);
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

// Set creditor address (will be included in XML)
$remesaData->setCreditorAddress([
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

    public function generateRemesaPagoFromArray(array $data): string
    {
        return $this->remesaGenerator->generateFromArray($data);
    }

    public function generateRemesaCobro(array $data): string
    {
        return $this->directDebitGenerator->generateFromArray($data);
    }

    public function generateRemesaCobroResponse(array $data): \Symfony\Component\HttpFoundation\Response
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
- `nowo_sepa_payment.generator.remesa_generator` - Remesa (credit transfer) generator
- `nowo_sepa_payment.generator.direct_debit_generator` - Direct debit generator
- `nowo_sepa_payment.generator.identifier_generator` - Identifier generator
- `nowo_sepa_payment.parser.remesa_parser` - Remesa parser

All services are public and available for dependency injection via autowiring (type-hinting) or explicit alias retrieval.
