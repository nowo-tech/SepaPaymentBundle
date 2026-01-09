# Console Commands

The bundle provides console commands for common operations.

## Validate IBAN

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

## Convert CCC to IBAN

```bash
php bin/console nowo:sepa:ccc-to-iban 21000418450200051332
```

This command converts a Spanish CCC to IBAN format and displays:
- Original CCC
- Generated IBAN
- Bank code
- Branch code
- Account number

## Validate Credit Card

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
