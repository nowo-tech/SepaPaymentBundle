# Deprecated Fields in SEPA Direct Debit

This document describes fields that were previously required or allowed in SEPA Direct Debit transactions (pain.008.001.02) but can **no longer be included** according to current SEPA standards.

## Important Information

SEPA standards have evolved and some fields that were previously required or common have been **removed or deprecated** in the most recent versions of the pain.008.001.02 format. It is important not to attempt to include these fields, as they may cause validation errors or rejection by banks.

**Note:** As of version 0.0.8, postal addresses for both creditor and debtor **are now supported** and will be included in the generated XML. The addresses are added using structured format (PstlAdr) with elements like StrtNm, TwnNm, PstCd, and Ctry.

## Fields that MUST NOT be included

### 1. Contact Information (Phone, Email)

**Before:** Contact information such as phone number or email of the debtor could be included.

**Example of what you should NOT do:**

```php
// ❌ INCORRECT - Do not attempt to include contact info in the transaction
$transaction = new DirectDebitTransaction(
    100.50,
    'GB82WEST12345698765432',
    'John Doe',
    'MANDATE-001',
    new \DateTime('2024-01-15'),
    'E2E-001'
);

// ❌ These methods do not exist and should not be used
// $transaction->setDebtorPhone('+44 20 1234 5678');
// $transaction->setDebtorEmail('john@example.com');
```

**Correct example:**

```php
// ✅ CORRECT - Only include allowed fields
$transaction = new DirectDebitTransaction(
    100.50,
    'GB82WEST12345698765432',
    'John Doe',
    'MANDATE-001',
    new \DateTime('2024-01-15'),
    'E2E-001'
);

$transaction->setRemittanceInformation('Invoice 12345');
$transaction->setDebtorBic('WESTGB22'); // BIC is optional but allowed

// ✅ Addresses are now supported (as of v0.0.8)
$transaction->setDebtorAddress([
    'street' => '123 Main St',
    'city' => 'London',
    'postalCode' => 'SW1A 1AA',
    'country' => 'GB',
]);
```

### 2. Tax Identification Information

**Now:** These fields **are not allowed** in direct debit transactions.

```php
// ❌ INCORRECT - Do not include contact information
// $transaction->setDebtorPhone('+34 123 456 789');
// $transaction->setDebtorEmail('debtor@example.com');
```

### 3. Tax Identification Information

**Before:** Some older implementations allowed including tax identification numbers or tax IDs.

**Now:** These fields **are not allowed** in the standard SEPA Direct Debit format.

```php
// ❌ INCORRECT - Do not include tax information
// $transaction->setDebtorTaxId('ES12345678A');
// $transaction->setDebtorVatNumber('ES12345678A');
```

## Fields that ARE allowed

For reference, these are the fields that **you can use** in Direct Debit transactions:

### Required Fields

- `amount` - Amount to debit
- `debtorIban` - Debtor IBAN
- `debtorName` - Debtor name
- `debtorMandate` - Mandate identifier
- `debtorMandateSignDate` - Mandate sign date
- `endToEndId` - Unique end-to-end identifier

### Optional Fields

- `remittanceInformation` - Remittance information (payment description)
- `debtorBic` - Debtor BIC (optional, but recommended for international transactions)

## Complete Correct Example

```php
use Nowo\SepaPaymentBundle\Generator\DirectDebitGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$generator = new DirectDebitGenerator(new IbanValidator());

$data = [
    'reference' => 'MSG-001',
    'bankAccountOwner' => 'My Company',
    'paymentInfoId' => 'PMTINF-1',
    'dueDate' => new \DateTime('2024-01-20'),
    'creditorName' => 'My Company Name',
    'creditorIban' => 'ES9121000418450200051332',
    'creditorBic' => 'CAIXESBBXXX',
    'seqType' => 'RCUR',
    'creditorId' => 'ES98ZZZ09999999999',
    'localInstrumentCode' => 'CORE',
    'transactions' => [
        [
            // ✅ Required fields
            'amount' => 100.50,
            'debtorIban' => 'GB82WEST12345698765432',
            'debtorName' => 'John Doe',
            'debtorMandate' => 'MANDATE-001',
            'debtorMandateSignDate' => new \DateTime('2024-01-15'),
            'endToEndId' => 'E2E-001',
            
            // ✅ Optional allowed fields
            'remittanceInformation' => 'Invoice 12345',
            'debtorBic' => 'WESTGB22',
            
            // ✅ Addresses are now supported (as of v0.0.8)
            'debtorAddress' => [
                'street' => '123 Main St',
                'city' => 'London',
                'postalCode' => 'SW1A 1AA',
                'country' => 'GB',
            ],
            
            // ❌ DO NOT include these fields (they will cause errors):
            // 'debtorPhone' => '+44 20 1234 5678',     // ❌ Not allowed
            // 'debtorEmail' => 'john@example.com',      // ❌ Not allowed
            // 'debtorTaxId' => 'GB123456789',          // ❌ Not allowed
        ],
    ],
];

$xml = $generator->generateFromArray($data);
```

## Address Support (as of v0.0.8)

**Postal addresses are now supported** in SEPA Direct Debit transactions. Both creditor and debtor addresses can be included in the XML using structured format:

- **Street** (`StrtNm`): Street name
- **City** (`TwnNm`): City name
- **Postal Code** (`PstCd`): Postal/ZIP code
- **Country** (`Ctry`): ISO 3166-1 alpha-2 country code

The addresses are automatically added to the XML using DOM manipulation, ensuring compatibility with the SEPA pain.008.001.02 format.

**Note:** The address is also required in the **SEPA mandate** (the document that authorizes the direct debit). The mandate is managed separately and contains:

- Full name of the debtor
- Complete postal address
- Debtor IBAN
- Unique mandate identifier
- Sign date
- Mandate type (CORE, B2B)
- Sequence type (FRST, RCUR, OOFF, FNAL)

The XML transaction (pain.008.001.02) only references the mandate by its identifier; it does not include all the mandate information.

## References

- [SEPA Direct Debit Scheme Rulebook](https://www.europeanpaymentscouncil.eu/document-library/rulebooks)
- [ISO 20022 pain.008.001.02 Specification](https://www.iso20022.org/)
- [Digitick\Sepa Library Documentation](https://github.com/digitick/sepa-xml)

## Compatibility Notes

This bundle uses the `digitick/sepa-xml ^3.0` library, which implements the pain.008.001.02 standard. If you attempt to include deprecated fields, the library may:

1. Silently ignore them
2. Throw an exception
3. Generate invalid XML that will be rejected by the bank

To avoid problems, **only use the fields documented** in this bundle and do not attempt to add additional fields that are not explicitly supported.

## Support

If you need to include additional information that is not in the SEPA standard, consider:

1. Including it in the `remittanceInformation` field (limited to 140 characters)
2. Storing it in your mandate management system
3. Using the bundle's `additionalData` field for internal storage (it will not be included in the XML)

```php
// Store additional information internally (not included in XML)
$transaction->setAdditionalData([
    'internalReference' => 'INT-12345',
    'customerId' => 'CUST-789',
    // This information will NOT be included in the SEPA XML
    // but can be useful for your internal system
]);
```
