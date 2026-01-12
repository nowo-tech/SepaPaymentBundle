# Release v1.2.7

## 🐛 Bug Fixes

### Catalan Translations YAML Syntax Fix
- **Fixed YAML syntax error** in `nowo_sepa_payment.ca.yaml` translation file
  - Fixed invalid YAML syntax caused by unescaped double quotes within single-quoted strings
  - Changed single quotes to double quotes and properly escaped internal double quotes
  - This fix resolves YAML parsing errors when loading Catalan translations

### CreditTransferGenerator Compatibility Fix
- **Fixed incorrect usage** of deprecated `debtor*` methods in `Transaction` objects
  - Changed `getDebtorAddress()` to `getCreditorAddress()` in transaction address handling
  - Changed `setDebtorBic()` to `setCreditorBic()` when setting transaction BIC
  - Changed `setDebtorAddressFromArray()` to `setCreditorAddressFromArray()` when setting transaction address from array
  - Changed `setDebtorAddress()` to `setCreditorAddress()` when setting transaction address
  - Updated comments and variable names for consistency
  - This fix ensures compatibility with the refactored `Transaction` class that now uses `creditor*` field names (changed in 1.2.4)

## 📦 Installation

```bash
composer require nowo-tech/sepa-payment-bundle:^1.2.7
```

## 🔗 Links

- [Full Changelog](https://github.com/nowo-tech/sepa-payment-bundle/blob/v1.2.7/docs/CHANGELOG.md)
- [Upgrade Guide](https://github.com/nowo-tech/sepa-payment-bundle/blob/v1.2.7/docs/UPGRADE.md)
