# Usage Guide

This document provides detailed usage examples for all features of the SEPA Payment Bundle.

## Table of contents

- [IBAN Validation](#iban-validation)
- [CCC to IBAN Conversion](#ccc-to-iban-conversion)
- [BIC Validation](#bic-validation)
- [Automatic BIC Lookup by IBAN](#automatic-bic-lookup-by-iban)
  - [Basic Usage](#basic-usage)
  - [Supported Countries](#supported-countries)
  - [Adding Custom Mappings](#adding-custom-mappings)
  - [Cache Support (Optional)](#cache-support-optional)
  - [Automatic Integration in Generators](#automatic-integration-in-generators)
  - [Dependency Injection](#dependency-injection)
- [Credit Card Validation](#credit-card-validation)
- [Identifier Generation](#identifier-generation)
- [SEPA XML Parser](#sepa-xml-parser)
  - [Parsing SEPA Credit Transfer](#parsing-sepa-credit-transfer)
  - [Parsing SEPA Direct Debit](#parsing-sepa-direct-debit)
- [Amounts in the transaction DTOs](#amounts-in-the-transaction-dtos)
- [Amounts and Currency in Array Input](#amounts-and-currency-in-array-input)
- [XSD Schema Validation](#xsd-schema-validation)
  - [Basic Usage](#basic-usage-1)
  - [Using Custom XSD Schema Files](#using-custom-xsd-schema-files)
  - [Validating Against Schema String](#validating-against-schema-string)
  - [Enabling XSD Validation in Generators](#enabling-xsd-validation-in-generators)
  - [XSD Schema Files](#xsd-schema-files)
- [SEPA String Sanitization](#sepa-string-sanitization)
  - [Field Length Limits](#field-length-limits)
  - [Allowed Characters](#allowed-characters)
- [SEPA Country Validation](#sepa-country-validation)
  - [SEPA Member Countries](#sepa-member-countries)
- [SEPA Business Rules Validation](#sepa-business-rules-validation)
  - [Business Rules](#business-rules)
- [Export to Other Formats](#export-to-other-formats)
  - [JSON Export](#json-export)
  - [CSV Export](#csv-export)
  - [JSON Import](#json-import)
- [Symfony Events](#symfony-events)
  - [Available Events](#available-events)
  - [Event Listeners](#event-listeners)
  - [Registering Event Listeners](#registering-event-listeners)
  - [Using Event Dispatcher in Generators](#using-event-dispatcher-in-generators)
- [Structured Logging](#structured-logging)
  - [Basic Usage](#basic-usage-2)
  - [Automatic Integration in Generators](#automatic-integration-in-generators-1)
  - [Logging Methods](#logging-methods)
  - [Context Data](#context-data)
  - [Dependency Injection](#dependency-injection-1)
  - [Custom Logger Integration](#custom-logger-integration)
  - [Log Levels](#log-levels)
- [SEPA Mandates](#sepa-mandates)
- [Generating SEPA Credit Transfer (Remesa de Pago)](#generating-sepa-credit-transfer-remesa-de-pago)
  - [`CreditTransferGenerator` and `TranslatorInterface`](#credittransfergenerator-and-translatorinterface)
  - [Using Array Format (Recommended)](#using-array-format-recommended)
  - [Using Object Format](#using-object-format)
- [Generating SEPA Direct Debit (Remesa de Cobro)](#generating-sepa-direct-debit-remesa-de-cobro)
  - [Using Array Format (Recommended)](#using-array-format-recommended-1)
  - [Using Object Format](#using-object-format-1)
  - [Versions, several blocks, sequence types (2.2)](#versions-several-blocks-sequence-types-22)
- [Using with Dependency Injection](#using-with-dependency-injection)
  - [Service Aliases](#service-aliases)
- [Bank reports: pain.002 and camt.054 (2.3)](#bank-reports-pain002-and-camt054-23)
- [Masking IBANs (2.3)](#masking-ibans-23)
- [Using it from a DDD / hexagonal application](#using-it-from-a-ddd--hexagonal-application)
  - [Standard Symfony path](#standard-symfony-path)
  - [DDD / hexagonal path](#ddd--hexagonal-path)
  - [Mandates](#mandates)
  - [Mandate snapshots, evidence and amendments (2.3)](#mandate-snapshots-evidence-and-amendments-23)
  - [Known limitations](#known-limitations)
- [Overriding bundle translations](#overriding-bundle-translations)
- [Forms in host apps and demos (REQ-TWIG-005)](#forms-in-host-apps-and-demos-req-twig-005)

## IBAN Validation

```php
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$validator = new IbanValidator();

// Validate IBAN (format, ISO 13616 country length and mod-97 check digits)
if ($validator->isValid('ES9121000418450200051332')) {
    echo "Valid IBAN";
}
// Unknown country codes and lengths that do not match the country (e.g. a 23-character ES IBAN) are invalid

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
// Throws InvalidArgumentException if the CCC is not 20 digits or its control digits (DD) are wrong

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

Mappings added with `addMapping()` are **request-scoped**: they are dropped at the next unit-of-work boundary (main request start, `kernel.terminate`, each Messenger message, `console.terminate`, and `kernel.reset`), so a tenant-specific mapping never leaks to other requests in FrankenPHP worker mode. For mappings that must always apply, use the `$customMappings` constructor argument:

```yaml
# config/services.yaml
services:
    Nowo\SepaPaymentBundle\Lookup\BicLookupService:
        arguments:
            $customMappings:
                ES: { '9999': 'CUSTOMBIC' }
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
use Symfony\Component\Translation\IdentityTranslator;

$ibanValidator = new IbanValidator();
$translator    = new IdentityTranslator();
$bicLookup     = new BicLookupService($ibanValidator);

// Inject BIC lookup service into generator
$generator = new CreditTransferGenerator(
    $ibanValidator,
    $translator,
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
$transaction = Transaction::fromCents(
    'E2E-001',
    10050,                         // EUR 100.50 in cents (or new Transaction('E2E-001', '100.50', …))
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

// Generate custom identifier with prefix (at most 11 characters, so the ID fits the SEPA 35-character limit)
$customId = $generator->generateCustomId('CUSTOM');
// Returns: CUSTOM-20240115143022-a1b2c3d4

// Generate with custom prefix
$messageId = $generator->generateMessageId('MY-MSG');
// Returns: MY-MSG-20240115143022-a1b2c3d4
```

## SEPA XML Parser

The bundle provides parsers for both SEPA Credit Transfer and SEPA Direct Debit XML files. The parsers read the ISO 20022 namespace from the document and support `pain.001.001.03` / `pain.001.001.09` (credit transfer) and `pain.008.001.02` / `pain.008.001.09` (direct debit), so files produced by the bundle's generators (2019 `.09` versions) can be parsed back. Documents in other namespaces are not recognised (`isValid*()` returns `false`).

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

## Bank reports: pain.002 and camt.054 (2.3)

`PaymentStatusReportParser` reads payment status reports (pain.002.001.03 and .10: rejections and acceptances of a
submitted file) and `DebitCreditNotificationParser` reads debit / credit notifications (camt.054.001.02 and .08:
booked collections, returns, refunds). Both validate the document against the official XSD shipped with the bundle,
refuse DTDs and network access, and cap the size (`reports.max_size`). They return value objects; matching to your
collections stays in your code:

A pain.002 rejection can come at three levels, and banks often leave out the lower ones: a "total reject" (`GrpSts`
RJCT) may list no block or transaction at all, and a rejected block may list no transaction. Handle the group and the
blocks before the transaction lines, and never treat "no rejected line" as "collected":

```php
$report = $statusParser->parse($xml); // PaymentStatusReportParserInterface
if ($report->isFullyRejected()) {
    // GrpSts RJCT: every collection of your file (OrgnlMsgId = your pain.008 MsgId), listed or not
    $this->collections->markFileRejected($report->getOriginalMessageId(), $report->getGroupReasons()[0] ?? null);
}
foreach ($report->getPaymentInformationStatuses() as $block) {
    if ($block->isRejected()) {
        // PmtInfSts RJCT (or no PmtInfSts in a rejected group): every collection of that block
        $this->collections->markBlockRejected($block->getOriginalPaymentInformationId(), $block->getReasons()[0] ?? null);
    }
}
foreach ($report->getRejectedTransactionStatuses() as $status) {
    // TxSts RJCT, or no TxSts in a rejected block or group (getEffectiveStatus(); PART is never inherited)
    $reason = $status->getReason();
    $this->collections->markRejected(
        $status->getOriginalEndToEndId(),
        $reason?->getCode() ?? $reason?->getProprietaryCode(),
        $reason?->getReasonCode()?->getTranslationKey(), // e.g. reason_code.AM04
    );
}
```

`getRejectedEndToEndIds()` and `getRejectedPaymentInformationIds()` give the ids directly. A pain.002 acceptance
(ACCP, ACSC) is not the money: book collections from camt.054.

A camt.054 entry can be booked (`BOOK`), pending (`PDNG`) or for information only (`INFO`), and can reverse an earlier
entry. Every transaction carries its entry's context, so book only from booked entries and treat reversals as the
opposite movement:

```php
$notification = $camtParser->parse($xml); // DebitCreditNotificationParserInterface
foreach ($notification->getBookedTransactions() as $transaction) { // Ntry/Sts BOOK only
    if ($transaction->isReversal()) {
        $this->collections->markReversed($transaction->getEndToEndId(), $transaction->getAmountCents());
    } elseif ($transaction->isReturn()) {
        $this->collections->markReturned($transaction->getEndToEndId(), $transaction->getAmountCents(), $transaction->getReturnReason()?->getReasonCode());
    } elseif ($transaction->getCreditDebitIndicator() === 'CRDT') { // the transaction's, else the entry's (.02)
        $this->collections->markCollected($transaction->getEndToEndId(), $transaction->getAmountCents());
    }
}
foreach ($notification->getEntriesWithoutTransactions() as $entry) {
    // e.g. a batch booked as one amount without details: match it by $entry->getAccountServicerReference()
}
```

Amounts are integer cents; unknown reason codes keep their raw `getCode()` and `getReasonCode()` returns null. The
parsers keep the libxml error mode and any pending libxml errors of the host.

## Masking IBANs (2.3)

```php
$masker = new IbanMasker(); // or inject IbanMaskerInterface
$masker->mask('ES9121000418450200051332');          // ES91****************1332
$masker->maskFormatted('ES9121000418450200051332', 2); // ES91 **** **** **** **** **32
```

Separators (spaces, dashes, non-breaking spaces) are dropped first. Values shorter than 15 characters (the shortest
IBAN, Norway) are fully masked, so a malformed value never leaks.

## Amounts in the transaction DTOs

`DirectDebitTransaction` and `CreditTransfer\Transaction` keep the amount as **integer cents**, the value written to the
XML (`InstdAmt`) and summed into the control sum (`CtrlSum`, `getTotalAmountCents()`). Since 2.1:

| Input | Example | Exact? |
| --- | --- | --- |
| `DirectDebitTransaction::fromCents(int $amountCents, …)` / `Transaction::fromCents($endToEndId, int $amountCents, …)` | `fromCents(10050, …)` | yes (preferred) |
| decimal string in euros to the constructor | `new Transaction('E2E-1', '100.50', …)` | yes, read digit by digit |
| int in euros to the constructor | `new DirectDebitTransaction(7, …)` (EUR 7.00) | yes |
| float in euros to the constructor | `new Transaction('E2E-1', 100.50, …)` | **deprecated** (`E_USER_DEPRECATED`), still works |
| other numeric string to the constructor | `'1e3'`, `' 1.5'`, `'.5'` | **deprecated** (`E_USER_DEPRECATED`), read like a float |

Rounding: a float is first written with 6 decimals (`sprintf('%.6F')`, which removes binary noise such as
`0.1 + 0.2 = 0.30000000000000004`) and read as a decimal string; digits beyond the cent are rounded **half up** (half
away from zero, the euro convention and PHP's `round($x, 2)`): `"0.125"` → EUR 0.13, `"-0.125"` → EUR -0.13,
`1.005` → EUR 1.01, `2.675` → EUR 2.68. Because the float noise is removed first, the result is the same on every PHP
version. SEPA amounts have at most two decimals, so this only matters for computed values. A non-finite float (`NAN`,
`INF`) or a string that is not numeric (`"1,50"`, `"abc"`) throws an `InvalidArgumentException`; so does an amount above
EUR 999 999 999 999 999.99 (15 integer digits), including `fromCents(PHP_INT_MAX)`. The EPC limit (EUR 999 999 999.99)
is not enforced by the DTOs.

Read the amount back with `getAmountCents(): int` (exact), `getAmountDecimal(): string` (`"100.50"`) or
`getAmount(): float` (kept for compatibility). `DirectDebitData::getTotalAmountCents()` and
`CreditTransferData::getTotalAmountCents()` sum in cents; `getTotalAmount(): float` is that sum divided by 100.

## Amounts and Currency in Array Input

`CreditTransferGenerator::generateFromArray()` and `DirectDebitGenerator::generateFromArray()` read each transaction amount from exactly one of:

- `amount` — decimal amount **in euros** (`15000`, `15000.00` or `"15000.00"` all mean EUR 15,000.00). Large values are never reinterpreted as cents. A plain decimal string is read exactly; a float or another numeric string (`"1e3"`) is rounded half up to the cent as described in [Amounts in the transaction DTOs](#amounts-in-the-transaction-dtos) (no deprecation on the array path).
- `amountCents` (or `amount_cents`) — integer amount **in cents** (`1500000` means EUR 15,000.00).

Passing both, neither, a non-numeric `amount` or a non-integer `amountCents` throws an `InvalidArgumentException`.

`currency` is optional per transaction. When omitted, the bundle's `default_currency` (default `EUR`) is used. Currency codes must be 3 uppercase letters, and since the SEPA credit transfer and direct debit schemes only support euro, any currency other than `EUR` (per transaction or as `default_currency`) is rejected with an `InvalidArgumentException` when generating.

## XSD Schema Validation

The bundle includes an `XsdValidator` service that can validate generated XML files against official SEPA XSD schemas (ISO 20022). This ensures that your XML files are fully compliant with SEPA standards.

### Basic Usage

```php
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Component\Translation\IdentityTranslator;

$validator = new XsdValidator(new IdentityTranslator());

// Validate Credit Transfer XML
try {
    $isValid = $validator->validateCreditTransfer($xml);
    if ($isValid) {
        echo "XML is valid against the pain.001 schema matching its namespace";
    }
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: " . $e->getMessage();
}

// Validate Direct Debit XML
try {
    $isValid = $validator->validateDirectDebit($xml);
    if ($isValid) {
        echo "XML is valid against the pain.008 schema matching its namespace";
    }
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: " . $e->getMessage();
}
```

### Using Custom XSD Schema Files

If you have your own XSD schema files, you can specify the path:

```php
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Component\Translation\IdentityTranslator;

$validator = new XsdValidator(new IdentityTranslator());

// Validate against a specific XSD file
$isValid = $validator->validate($xml, '/path/to/schema.xsd', 'credit_transfer');
```

### Validating Against Schema String

You can also validate against an XSD schema provided as a string:

```php
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Component\Translation\IdentityTranslator;

$validator = new XsdValidator(new IdentityTranslator());
$xsdContent = file_get_contents('/path/to/schema.xsd');
$isValid = $validator->validateAgainstSchemaString($xml, $xsdContent);
```

### Enabling XSD Validation in Generators

You can enable automatic XSD validation when generating XML files:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Component\Translation\IdentityTranslator;

$ibanValidator = new IbanValidator();
$translator    = new IdentityTranslator();
$xsdValidator  = new XsdValidator($translator);

// Enable XSD validation (fourth parameter: validateXsd)
$generator = new CreditTransferGenerator($ibanValidator, $translator, $xsdValidator, true);

// Now all generated XML will be validated against XSD schema
$xml = $generator->generate($creditTransferData);
// If validation fails, an InvalidArgumentException will be thrown
```

### XSD Schema Files

The bundle ships the official ISO 20022 schemas in `src/Resources/schemas/`:

| File | Message |
|------|---------|
| `pain.001.001.03.xsd` | Credit Transfer (2009) — default for `validateCreditTransfer()` |
| `pain.008.001.02.xsd` | Direct Debit (2009) — default for `validateDirectDebit()` |
| `pain.001.001.09.xsd` | Credit Transfer (2019) — namespace written by the bundled generator (`digitick/sepa-xml` 3.x) |
| `pain.008.001.09.xsd` | Direct Debit (2019 series) — namespace written by the bundled generator (`digitick/sepa-xml` 3.x) |

When no `$xsdPath` is given, the validator picks the bundled schema that matches the document's root namespace (same message family only), and falls back to the defaults above otherwise.

**A missing schema is an error.** If the schema file cannot be found (custom `$xsdPath` that does not exist, unknown schema type, or a pain version that is not bundled), `validate()` throws an `InvalidArgumentException` (`validation.xsd_schema_not_found`). It no longer returns `true` without validating. Generators built with `validateXsd: true` therefore always validate.

The schemas are published by the ISO 20022 Registration Authority and may be used and reproduced freely under the ISO 20022 Intellectual Property Right Policy. The bundled copies are not the official source; the up-to-date versions are at https://www.iso20022.org/.

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

The list follows the EPC list of countries in the SEPA schemes' geographical scope (EPC409-09 v8.0, 24 December 2025):
- All EU member states and the EEA countries (Iceland, Liechtenstein, Norway)
- Non-EEA countries: Albania, Andorra, Moldova, Monaco, Montenegro, North Macedonia, San Marino, Serbia, Switzerland, United Kingdom, Vatican City State
- Territories with their own ISO 3166 code: Åland Islands (AX), Gibraltar (GI), Guernsey (GG), Jersey (JE), Isle of Man (IM), French Guiana (GF), Guadeloupe (GP), Martinique (MQ), Réunion (RE), Mayotte (YT), Saint Pierre and Miquelon (PM), Saint Barthélemy (BL), Saint Martin (MF)

Note that several territories use another country's IBAN prefix (French territories use `FR`, Guernsey/Jersey/Isle of Man use `GB`, Åland uses `FI`).

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
use Symfony\Component\Translation\IdentityTranslator;

$generator = new CreditTransferGenerator(new IbanValidator(), new IdentityTranslator());
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

Since 2.4.0 every generation event has a framework-free twin in `Nowo\SepaPaymentBundle\Event\Generation`: a plain
class with the same getters and setters and no Symfony base class (REQ-ARCH-001 rule 3). The generator dispatches the
legacy event first, exactly as before, then the twin with the values the legacy listeners left, and uses the twin's
final values. Prefer the twins; the legacy classes are deprecated and removed in 3.0.

| Moment | Twin (2.4.0, preferred) | Legacy (deprecated, extends Symfony `Event`) |
|--------|-------------------------|----------------------------------------------|
| Before credit transfer generation | `Generation\CreditTransferGeneratingEvent` | `BeforeCreditTransferGenerationEvent` |
| After credit transfer generation | `Generation\CreditTransferGeneratedEvent` | `AfterCreditTransferGenerationEvent` |
| Before direct debit generation | `Generation\DirectDebitGeneratingEvent` | `BeforeDirectDebitGenerationEvent` |
| After direct debit generation | `Generation\DirectDebitGeneratedEvent` | `AfterDirectDebitGenerationEvent` |

`BeforeValidationEvent` / `AfterValidationEvent` are never dispatched by the bundle (they never were); they are
deprecated and removed in 3.0.

### Event Listeners

```php
use Nowo\SepaPaymentBundle\Event\Generation\CreditTransferGeneratedEvent;
use Nowo\SepaPaymentBundle\Event\Generation\CreditTransferGeneratingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SepaPaymentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CreditTransferGeneratingEvent::class => 'onBeforeGeneration',
            CreditTransferGeneratedEvent::class => 'onAfterGeneration',
        ];
    }

    public function onBeforeGeneration(CreditTransferGeneratingEvent $event): void
    {
        $data = $event->getCreditTransferData();
        
        // Modify data before generation
        // For example, add a custom transaction or modify creditor name
        $data->setCreditorName('Modified Company Name');
        
        $event->setCreditTransferData($data);
    }

    public function onAfterGeneration(CreditTransferGeneratedEvent $event): void
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
use Symfony\Component\Translation\IdentityTranslator;

// The event dispatcher is the 5th constructor argument (after translator, XSD, validateXsd flag)
$generator = new CreditTransferGenerator(
    new IbanValidator(),
    new IdentityTranslator(),
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
use Symfony\Component\Translation\IdentityTranslator;

$ibanValidator = new IbanValidator();
$translator    = new IdentityTranslator();
$logger        = new SepaPaymentLogger($psrLogger); // Your PSR-3 logger

$generator = new CreditTransferGenerator(
    $ibanValidator,
    $translator,
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

// Expiry (EPC SDD rulebook): a mandate lapses when no collection has been presented for 36 months.
$mandate->recordCollection(new \DateTime('2025-03-01')); // call after each collection presented
$mandate->getExpirationDate(); // 2028-03-01 (36 months after the last collection)
// Without collections the expiry is 36 months after the signature date;
// setExpirationDate() sets an explicit expiry that takes precedence.
```

`MandateService` stores mandates through `MandateRepositoryInterface`. The default implementation (`MandateRepository`) is an **in-memory, request-scoped** store: it is emptied at every unit-of-work boundary: the start of every main request, `kernel.terminate`, the start and end of every Messenger message and `console.terminate` (also in FrankenPHP worker mode or a Messenger consumer without `services_resetter`). Bind `MandateRepositoryInterface` to a persistent implementation (Doctrine or similar) to keep mandates between requests.

One console command run is **one** unit of work: the store is emptied only at `console.terminate`. A long command that walks several tenants (a cron importer, for example) with the in-memory repository still sees tenant A's mandates and debtor IBANs while it processes tenant B. Inside such a loop call `MandateRepository::reset()` (or `clear()`) per tenant, or bind `MandateRepositoryInterface` to a persistent repository.

## Generating SEPA Credit Transfer (Remesa de Pago)

**Credit transfers (remesas de pago)** are used to send money from the debtor (payer) to the creditor (beneficiary).

### `CreditTransferGenerator` and `TranslatorInterface`

`CreditTransferGenerator` requires `Symfony\Contracts\Translation\TranslatorInterface` as the **second** constructor argument (for translated messages). In Symfony, inject `CreditTransferGenerator` (or pass the `translator` service). In the **standalone examples** below, `Symfony\Component\Translation\IdentityTranslator` is used so the snippets are copy-pasteable outside the container.

`DirectDebitGenerator` does **not** take a translator (only `IbanValidator` and optional dependencies).

### Using Array Format (Recommended)

The `generateFromArray()` method supports both **camelCase** and **snake_case** field names for maximum flexibility.

**Using camelCase (default):**

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\Translation\IdentityTranslator;

$generator = new CreditTransferGenerator(new IbanValidator(), new IdentityTranslator());

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
            'amount' => 100.50,                   // Amount in euros (decimal); or 'amountCents' => 10050 (integer cents)
            'currency' => 'EUR',                  // Currency (optional, defaults to default_currency; SEPA: EUR only)
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

Postal addresses for both creditor and debtor are **optional** and will be included in the XML **only if provided** in the array. Addresses are added using structured format (PstlAdr) with elements StrtNm, PstCd, TwnNm and Ctry. The addresses are automatically added to the XML using DOM manipulation, in the ISO 20022 element order (StrtNm, PstCd, TwnNm, Ctry) of the generated pain.001.001.09 format.

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
use Symfony\Component\Translation\IdentityTranslator;

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
$transaction1 = Transaction::fromCents(
    'E2E-001',                    // End-to-end ID (unique per transaction)
    10050,                        // Amount in cents (EUR 100.50)
    'EUR',                        // Currency (ISO 4217)
    'GB82WEST12345698765432',     // Debtor IBAN
    'John Doe'                    // Debtor name
);

$transaction1->setCreditorBic('WESTGB22');

// Set debtor address (will be included in XML)
$transaction1->setCreditorAddress([
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
    '200.75',                     // decimal string: exact too
    'EUR',
    'FR1420041010050500013M02606',
    'Jane Smith'
);
$creditTransferData->addTransaction($transaction2);

// Generate XML
$ibanValidator = new IbanValidator();
$generator     = new CreditTransferGenerator($ibanValidator, new IdentityTranslator());
$xml = $generator->generate($creditTransferData);

// Save to file
file_put_contents('credit-transfer.xml', $xml);

// Or return as HTTP Response (for Symfony controllers; createResponse() on the generator is deprecated since 2.2)
use Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory;
return (new SepaXmlResponseFactory())->createResponse($xml, 'credit-transfer.xml');
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
            'amount' => 100.50,                           // Amount in euros (decimal); or 'amountCents' => 10050 (integer cents)
            'debtorIban' => 'GB82WEST12345698765432',    // Debtor IBAN
            'debtorName' => 'John Doe',                   // Debtor name
            'debtorMandate' => 'MANDATE-001',            // Mandate identifier
            'debtorMandateSignDate' => new \DateTime('2024-01-15'), // Mandate sign date (required)
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

As of version 0.0.8, postal addresses for both creditor and debtor are **optional** and will be included in the XML **only if provided** in the array. Addresses are added using structured format (PstlAdr) with elements StrtNm, PstCd, TwnNm and Ctry. The addresses are automatically added to the XML using DOM manipulation, in the ISO 20022 element order (StrtNm, PstCd, TwnNm, Ctry) of the generated pain.008.001.09 format.

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

$transaction = DirectDebitTransaction::fromCents(
    10050,                                       // Amount in cents (EUR 100.50)
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

// Or return as HTTP Response (for Symfony controllers; createResponse() on the generator is deprecated since 2.2)
use Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory;
return (new SepaXmlResponseFactory())->createResponse($xml, 'direct-debit.xml');
```

### Versions, several blocks, sequence types (2.2)

**Version (P1).** `pain.008.001.09` is the default. `pain.008.001.08` (EPC 2023 implementation guidelines) and
`pain.008.001.02` are available through `direct_debit.pain_version` ([CONFIGURATION.md](CONFIGURATION.md#direct_debit))
or per call; each one validates against its own XSD shipped in `src/Resources/schemas`:

```php
use Nowo\SepaPaymentBundle\Model\DirectDebit\DirectDebitGenerationOptions;
use Nowo\SepaPaymentBundle\Model\DirectDebit\Pain008Version;

$xml = $generator->generate($data, new DirectDebitGenerationOptions(
    painVersion: Pain008Version::V08,   // or 'pain.008.001.08'
    validateXsd: true,
    creationDateTime: $now,             // GrpHdr/CreDtTm; default: now
));
```

The options are per call only: the generator keeps no state between calls (safe in FrankenPHP worker mode).

**Several payment-information blocks (P2).** One file, one `PmtInf` per block (for example per sequence type, due date
or local instrument). The creditor stays on the file:

```php
use Nowo\SepaPaymentBundle\Model\DirectDebit\PaymentInformationData;

$data = DirectDebitData::fromPaymentInformation(
    'REM-2026-11', 'My Company SL', 'My Company SL', 'ES9121000418450200051332', 'ES59ZZZX1234567',
    new PaymentInformationData('REM-2026-11-1', $dueDate, 'FRST', 'CORE', $firstCollections),
    new PaymentInformationData('REM-2026-11-2', $dueDate, 'RCUR', 'CORE', $recurringCollections),
);
$data->setCreditorBic('CAIXESBBXXX');
```

`addPaymentInformation()` adds a block to a `DirectDebitData` built with the single-block constructor; the constructor
block is written first when it has transactions.

**Sequence types (P3).** `direct_debit.sequence_type_mode` or `DirectDebitGenerationOptions(sequenceTypeMode: …)`:

| Mode | `SeqTp` written |
| --- | --- |
| `per_transaction` (default) | The block's sequence type, or each transaction's own (`$transaction->setSequenceType('FRST')`). A block whose transactions carry different types is written as one `PmtInf` per type, ids `<PmtInfId>-FRST`, `<PmtInfId>-RCUR`. |
| `always_rcur` | RCUR for FRST, FNAL and missing types. One-off (OOFF) collections stay OOFF; a block with both is split into `<PmtInfId>-RCUR` and `<PmtInfId>-OOFF`. |
| `omit` | No `SeqTp` element. Valid against the ISO schemas but **not conformant to the EPC rulebook**, which makes the sequence type (AT-21) mandatory: use it only if your bank's own guide requires it. |

The generator also rejects files that banks refuse: an empty block or file, CORE and B2B blocks in one file (one file
per local instrument), a duplicate `EndToEndId`, and address or identification text that is not valid UTF-8.

So a host can put all collections of a remittance in one block with their own sequence type and let a setting decide
between one block per type, RCUR only, or no `SeqTp`.

**Initiating party id and agents (P4).** Spanish banks usually want `GrpHdr/InitgPty/Id`:

```php
use Nowo\SepaPaymentBundle\Model\PartyIdentification;

$data->setInitiatingPartyIdentification(PartyIdentification::organisation('ES59000B12345678'));
// PartyIdentification::organisation($id, schemeProprietary: 'SEPA'), ::privatePerson($id, schemeCode: 'NIDN'), issuer …
```

The creditor scheme id is written with `SchmeNm/Prtry SEPA`. Without a BIC, the creditor and debtor agents are written
as `<FinInstnId><Othr><Id>NOTPROVIDED</Id></Othr></FinInstnId>`.

**Amount range (P5).** Each transaction must be between 0.01 and 999 999 999.99 EUR (1 … 99 999 999 999 cents, the EPC
limit); the generators throw `InvalidArgumentException` otherwise.

**Reason codes (P7).** `Nowo\SepaPaymentBundle\Model\ReasonCode` lists the ISO 20022 reason codes of SEPA
R-transactions (42 codes: AC01, AM04, AM06, MD01, MD06, MS02, SL11–SL14, …) with their ISO name, an English description and a translation key:

```php
$reason = ReasonCode::fromCode($codeFromTheBankFile); // null when unknown
$label  = $translator->trans($reason->getTranslationKey(), [], ReasonCode::TRANSLATION_DOMAIN); // "Fondos insuficientes"
```

Parsing pain.002 / camt.054 bank files is planned for 2.3.

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

    public function generateDirectDebitResponse(array $data, \Nowo\SepaPaymentBundle\Http\SepaXmlResponseFactory $responseFactory): \Symfony\Component\HttpFoundation\Response
    {
        $xml = $this->directDebitGenerator->generateFromArray($data);
        return $responseFactory->createResponse($xml, 'direct-debit.xml');
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
- `nowo_sepa_payment.http.sepa_xml_response_factory` - Download response for generated XML (since 2.2)
- `nowo_sepa_payment.service.mandate_service` - Mandate service

Ports (interfaces, since 2.2) autowire to the same services: `DirectDebitGeneratorInterface`,
`CreditTransferGeneratorInterface`, `CreditTransferParserInterface`, `DirectDebitParserInterface`,
`MandateServiceInterface`, `IbanValidatorInterface` (and the other validator interfaces).

All services are public and available for dependency injection via autowiring (type-hinting) or explicit alias retrieval.

## Using it from a DDD / hexagonal application

SepaPaymentBundle is an **infrastructure library**: it builds and parses SEPA XML files (pain.001 credit transfers,
pain.008 direct debits) and validates IBAN, BIC, creditor identifiers and SEPA countries. It has no Doctrine entities,
never touches your domain objects, never opens a transaction and never flushes an EntityManager. Both paths below are
supported; the standard one needs no configuration.

### Standard Symfony path

Inject the generator and the response factory in a controller, build the DTOs from your entities and return the file:

```php
#[Route('/remittances/{id}/pain008')]
public function download(Remittance $remittance, DirectDebitGenerator $generator, SepaXmlResponseFactory $responses): Response
{
    $xml = $generator->generate($this->mapper->toDirectDebitData($remittance));

    return $responses->createResponse($xml, sprintf('remittance-%d.xml', $remittance->getId()));
}
```

Listen to `Event\Generation\DirectDebitGeneratedEvent` (or the deprecated `AfterDirectDebitGenerationEvent`) to log the file (it carries the message id, PmtInf ids, version, count and
control sum). Mandates can use `MandateService` with your own `MandateRepositoryInterface` adapter (see below).

### DDD / hexagonal path

Model payment batches, debtors and mandates in your own domain. Define an outbound port (for example
`DirectDebitFileExporter`) and implement it in infrastructure against the bundle **ports** — interfaces that return
strings and take plain value objects (no HTTP, Doctrine or Symfony types): `DirectDebitGeneratorInterface`,
`CreditTransferGeneratorInterface`, `CreditTransferParserInterface`, `DirectDebitParserInterface`,
`MandateServiceInterface`, `IbanValidatorInterface` and the other validator interfaces. Pass your integer-cents money
straight through with `fromCents()`; no float is involved:

```php
final readonly class SepaDirectDebitFileExporter implements DirectDebitFileExporter // your port
{
    public function __construct(private DirectDebitGeneratorInterface $generator) {}

    public function export(CollectionBatch $batch, BankSettings $bank): string
    {
        $blocks = [];
        foreach ($batch->groups() as $group) { // e.g. per due date
            $block = new PaymentInformationData($group->id(), $group->dueDate(), null, 'CORE');
            foreach ($group->collections() as $c) {
                $block->addTransaction(DirectDebitTransaction::fromCents(
                    $c->amount()->cents(),
                    $c->debtorIban(), $c->debtorName(), $c->mandateId(), $c->mandateSignedOn(), $c->endToEndId(),
                )->setSequenceType($c->isFirst() ? 'FRST' : 'RCUR'));
            }
            $blocks[] = $block;
        }
        $data = DirectDebitData::fromPaymentInformation(
            $batch->id(), $batch->creditor()->name(), $batch->creditor()->name(),
            $batch->creditor()->iban(), $batch->creditor()->creditorId(), ...$blocks,
        );
        $data->setCreditorBic($batch->creditor()->bic());
        $data->setInitiatingPartyIdentification(PartyIdentification::organisation($batch->creditor()->initiatingPartyId()));

        return $this->generator->generate($data, new DirectDebitGenerationOptions(
            painVersion: $bank->painVersion(),
            sequenceTypeMode: $bank->sequenceTypeMode(),
            validateXsd: true,
            creationDateTime: $batch->createdAt(),
        ));
    }
}
```

`CreditTransfer\Transaction::fromCents($endToEndId, $cents, 'EUR', $iban, $name)` does the same for credit transfers;
`generateFromArray()` accepts `amountCents` as well.

- Validate input in your application layer with the constraints (`#[Iban]`, `#[Bic]`, `#[SepaCreditorIdentifier]`,
  `#[SepaCountry]`) on command DTOs, or call the validator interfaces from your own domain service adapters. A decorator
  of `IbanValidatorInterface` (blocklist, cache) is used by the generators too.
- Events carry ids, never entities, and since 2.4.0 have framework-free twins (`Event\Generation\*`, plain classes,
  no Symfony base class): listen to those from a DDD host. `DirectDebitGeneratingEvent` /
  `CreditTransferGeneratingEvent` expose the mutable DTO and `getMessageId()`; `DirectDebitGeneratedEvent` the XML,
  message id, PmtInf ids, version, transaction count and control sum. The mandate events are plain classes too.
- **Events fire before you persist** (P9): `DirectDebitGeneratedEvent` (and the legacy `AfterDirectDebitGenerationEvent`) is dispatched inside `generate()`, before
  your code stores or sends the file, and the mandate events right after `MandateRepositoryInterface::save()` — inside
  your transaction if your adapter writes in one. Do not treat them as "committed"; dispatch your own domain event after
  your commit (or through Messenger) when a listener needs the stored row. An exception thrown by a mandate-event
  listener reaches your caller after `save()` and the history entry: catch it, or keep listeners side-effect free and
  move the work to Messenger.

### Mandates

`MandateService` (port `MandateServiceInterface`) stores mandates through `MandateRepositoryInterface`. The default
implementation is in-memory and emptied on every request: bind the interface to your own persistent adapter (your own
table, Doctrine XML mapping or anything else; the bundle has no entity) in `services.yaml`:

```yaml
services:
    Nowo\SepaPaymentBundle\Repository\MandateRepositoryInterface: '@App\Infrastructure\Sepa\MandateRepositoryAdapter'
```

It dispatches PSR-14 events with the mandate id, the new status and the time (`Event\Mandate\MandateCreatedEvent`,
`MandateSequenceTypeChangedEvent`, `MandateRevokedEvent` with the reason, `MandateSuspendedEvent`,
`MandateReactivatedEvent`; previous status / sequence type included). If your domain already has a mandate aggregate,
you can skip `MandateService` and only pass the mandate id and signature date to the transaction DTOs.

### Mandate snapshots, evidence and amendments (2.3)

`Mandate::toSnapshot()` returns an immutable `MandateSnapshot` with every field; `MandateSnapshot::toArray()` is
JSON-safe and `Mandate::reconstitute(MandateSnapshot::fromArray($row))` rebuilds the mandate without running any
lifecycle rule. A host repository can therefore store mandates in its own table (columns or a JSON document, Doctrine
XML mapping on its own entity) with no bundle entity:

```php
final class DbalMandateRepository implements MandateRepositoryInterface
{
    public function save(Mandate $mandate): void
    {
        $row = $mandate->toSnapshot()->toArray(); // mandateId, signatureDate, debtorIban, …, evidence, amendment
        $this->connection->executeStatement('REPLACE INTO sepa_mandate (id, data) VALUES (?, ?)', [$row['mandateId'], json_encode($row)]);
    }

    public function findById(string $mandateId): ?Mandate
    {
        $json = $this->connection->fetchOne('SELECT data FROM sepa_mandate WHERE id = ?', [$mandateId]);

        return $json === false ? null : Mandate::reconstitute(MandateSnapshot::fromArray(json_decode($json, true)));
    }
    // …
}
```

`fromArray()` is strict, so a damaged row never becomes a valid mandate: `mandateId`, `signatureDate`, `debtorIban` and
`debtorName` are required, and dates must be absolute (ATOM with optional microseconds, `Y-m-d H:i:s` or `Y-m-d`).
A missing, empty or relative date (`now`, `tomorrow`, `@0`) throws `InvalidArgumentException` instead of becoming the
current time; the same applies to the evidence `acceptedAt`. The JSON form keeps dates to the second with a numeric
offset (`Europe/Madrid` becomes `+01:00`); this moves the "36 months without collection" expiry by DST hours at most.
`reconstitute()` restores what was stored without the setters' checks (an owner reference over 255 characters, or a
revoked status with `active: true`, are kept as they are; `MandateService` still refuses a revoked mandate), so store
only snapshots produced by `toSnapshot()`.

Optional data on a mandate: `setEvidence(new MandateEvidence(hash('sha256', $acceptedText), $acceptedAt, $ip, 'web',
$signupId))`, `setDebtorAddress()`, `setOwnerReference('customer:42')` and `setAmendment(new MandateAmendment(…))`.
To report an amendment with the next collection, pass it to the transaction:
`$transaction->setAmendment($mandate->getAmendment())` (written in `MndtRltdInf/AmdmntInfDtls`; a new debtor bank,
SMNDA, must be collected as FRST). The FRST rule is stricter than the EPC rulebook, which no longer requires FRST
after a debtor-bank change since the 2016 rulebooks (v9.0); it is kept because some banks' guides still require it.
With `sequence_type_mode: always_rcur` every collection is RCUR, so an SMNDA amendment always throws: generate that
collection in a file with `per_transaction`. Check your bank's guide. Text the generator adds (the original creditor
name, addresses) is reduced to the EPC character set; text that becomes empty (e.g. only CJK characters) throws.

Identifiers (`EndToEndId`, mandate ids) are passed to the library, which transliterates them to the EPC set
(`E2E-ÄÖ&x/1` becomes `E2E-AeOe x/1`). The bank then reports the transliterated id in pain.002 / camt.054, so use ids
in the EPC set (Latin letters, digits, `/-?:().,'+` and space; no leading `/`, no `//`) or store the transliterated one.

### Known limitations

- The pain.001 / pain.008 parsers return arrays (the pain.002 / camt.054 report parsers return value objects).
- `getAmount()` / `getTotalAmount()` still return floats (a view of the cents); use the `*Cents()` getters for
  arithmetic.

## Overriding bundle translations

The bundle uses the translation domain **`NowoSepaPaymentBundle`** for validation messages and constraint messages (IBAN, BIC, SEPA Creditor Identifier, Credit Card, SEPA Country). You can override any of these messages in your application without modifying the bundle.

**Steps:**

1. Create a translation file in your project’s `translations/` directory (or the path configured in `framework.translator.default_path`).
2. Use the same domain and locale as the bundle, e.g. `NowoSepaPaymentBundle.es.yaml`, `NowoSepaPaymentBundle.de.yaml`.
3. Define only the keys you want to change; Symfony will use the bundle’s messages for the rest.

**Example — override Spanish messages:**

Create `translations/NowoSepaPaymentBundle.es.yaml` in your project:

```yaml
# Override only the keys you need
validation.invalid_iban: 'El IBAN %iban% no es válido.'
validation.missing_required_field: 'Falta el campo obligatorio: %field%'

# Constraint messages (Symfony validator)
iban:
    invalid: 'Este valor no es un IBAN válido.'
bic:
    invalid: 'Este valor no es un BIC válido.'
```

**Example — override German constraint messages only:**

Create `translations/NowoSepaPaymentBundle.de.yaml`:

```yaml
sepa_creditor_identifier:
    invalid: 'Ungültige SEPA-Gläubiger-Identifikationsnummer.'
credit_card:
    invalid: 'Ungültige Kreditkartennummer.'
```

For the full list of keys, load order (priority), and configuration options, see [Configuration → Translations](CONFIGURATION.md#translations).

## Forms in host apps and demos (REQ-TWIG-005)

The bundle ships no page templates of its own; its validators and services are used from your own Symfony forms. Every form in host apps and in the demo is a PHP Form Type created in the controller and rendered only with the Twig form helpers and the rendered-aware child loop (REQ-TWIG-003):

```twig
{{ form_start(form) }}
    {% for child in form %}
        {% if not child.rendered %}
            {{ form_row(child) }}
        {% endif %}
    {% endfor %}
    <button type="submit">Submit</button>
{{ form_end(form) }}
```

Hosts and demos must not reintroduce raw `<form>` / `<input>` / `<textarea>` / `<select>` markup; `make check-no-raw-html-form` (part of `make release-check`) fails when bundle or demo Twig contains them.
