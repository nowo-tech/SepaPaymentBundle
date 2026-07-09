# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/sepa-payment-bundle`  
**Last audited**: 2026-07-07

This file proves that **every production source artifact** under `src/` is referenced by the baseline specification. PHPUnit under `tests/` and XSD files under `Resources/schemas/` (when added) are referenced by requirement IDs but counted here when present under `src/`.

## Bundle & DI (`src/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `NowoSepaPaymentBundle.php` | Bundle entry | FR-BUNDLE-001 |
| `DependencyInjection/Configuration.php` | Config tree | FR-CFG-001 |
| `DependencyInjection/NowoSepaPaymentExtension.php` | DI extension | FR-CFG-002 |
| `Resources/config/services.yaml` | Service wiring | FR-DI-001 |

## Commands (`src/Command/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Command/ValidateIbanCommand.php` | `nowo:sepa:validate-iban` | FR-CLI-001 |
| `Command/ValidateCreditCardCommand.php` | `sepa:validate-credit-card` | FR-CLI-001 |
| `Command/ConvertCccCommand.php` | `nowo:sepa:ccc-to-iban` | FR-CLI-001 |
| `Command/ParseDirectDebitCommand.php` | `nowo:sepa:parse-direct-debit` | FR-CLI-001 |

## Generators (`src/Generator/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Generator/CreditTransferGenerator.php` | pain.001 XML generation | FR-GEN-001 |
| `Generator/DirectDebitGenerator.php` | pain.008 XML generation | FR-GEN-002 |
| `Generator/IdentifierGenerator.php` | MsgId / E2E / Mandate IDs | FR-GEN-003 |
| `Generator/RemesaGenerator.php` | Deprecated CT wrapper | FR-GEN-004 |

## Parsers (`src/Parser/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Parser/CreditTransferParser.php` | pain.001 DOM parse | FR-PARSE-001 |
| `Parser/DirectDebitParser.php` | pain.008 DOM parse | FR-PARSE-002 |
| `Parser/RemesaParser.php` | Deprecated CT parser | FR-PARSE-003 |

## Validators — services (`src/Validator/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Validator/IbanValidator.php` | IBAN ISO 13616 | FR-VAL-001 |
| `Validator/BicValidator.php` | BIC ISO 13616 | FR-VAL-002 |
| `Validator/CreditCardValidator.php` | Luhn + card type | FR-VAL-003 |
| `Validator/SepaCountryValidator.php` | SEPA country list | FR-VAL-004 |
| `Validator/SepaCreditorIdentifierValidator.php` | Creditor ID MOD97-10 | FR-VAL-005 |
| `Validator/SepaBusinessRulesValidator.php` | Amount/currency/date rules | FR-VAL-006 |
| `Validator/SepaStringSanitizer.php` | SEPA charset sanitize | FR-VAL-007 |
| `Validator/XsdValidator.php` | pain.001 / pain.008 XSD | FR-VAL-008 |
| `Validator/CachedIbanValidator.php` | Cached IBAN decorator | FR-VAL-009 |
| `Validator/CachedBicValidator.php` | Cached BIC decorator | FR-VAL-010 |

## Validators — Symfony constraints (`src/Validator/Constraint/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Validator/Constraint/Iban.php` | `#[Iban]` attribute | FR-CONSTRAINT-001 |
| `Validator/Constraint/IbanValidator.php` | IBAN constraint validator | FR-CONSTRAINT-001 |
| `Validator/Constraint/Bic.php` | `#[Bic]` attribute | FR-CONSTRAINT-002 |
| `Validator/Constraint/BicValidator.php` | BIC constraint validator | FR-CONSTRAINT-002 |
| `Validator/Constraint/CreditCard.php` | `#[CreditCard]` attribute | FR-CONSTRAINT-003 |
| `Validator/Constraint/CreditCardValidator.php` | Credit card validator | FR-CONSTRAINT-003 |
| `Validator/Constraint/SepaCountry.php` | `#[SepaCountry]` attribute | FR-CONSTRAINT-004 |
| `Validator/Constraint/SepaCountryValidator.php` | Country constraint validator | FR-CONSTRAINT-004 |
| `Validator/Constraint/SepaCreditorIdentifier.php` | `#[SepaCreditorIdentifier]` | FR-CONSTRAINT-005 |
| `Validator/Constraint/SepaCreditorIdentifierValidator.php` | Creditor ID validator | FR-CONSTRAINT-005 |

## Models (`src/Model/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Model/CreditTransfer/CreditTransferData.php` | CT batch DTO | FR-MODEL-001 |
| `Model/CreditTransfer/Transaction.php` | CT transaction DTO | FR-MODEL-001 |
| `Model/DirectDebit/DirectDebitData.php` | DD batch DTO | FR-MODEL-002 |
| `Model/DirectDebit/DirectDebitTransaction.php` | DD transaction DTO | FR-MODEL-002 |
| `Model/Mandate/Mandate.php` | Mandate entity | FR-MODEL-003 |
| `Model/Mandate/MandateHistory.php` | Mandate audit entry | FR-MODEL-003 |
| `Model/Mandate/MandateStatus.php` | Mandate status enum | FR-MODEL-003 |
| `Model/Remesa/RemesaData.php` | Deprecated CT DTO | FR-MODEL-004 |
| `Model/Remesa/Transaction.php` | Deprecated transaction | FR-MODEL-004 |

## Events (`src/Event/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Event/BeforeValidationEvent.php` | Pre-validation hook | FR-EVENT-001 |
| `Event/AfterValidationEvent.php` | Post-validation hook | FR-EVENT-001 |
| `Event/BeforeCreditTransferGenerationEvent.php` | Pre CT generation | FR-EVENT-002 |
| `Event/AfterCreditTransferGenerationEvent.php` | Post CT generation | FR-EVENT-002 |
| `Event/BeforeDirectDebitGenerationEvent.php` | Pre DD generation | FR-EVENT-003 |
| `Event/AfterDirectDebitGenerationEvent.php` | Post DD generation | FR-EVENT-003 |

## Export (`src/Exporter/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Exporter/ExportService.php` | JSON/CSV export-import | FR-EXPORT-001 |
| `Exporter/CsvStreamHandlerInterface.php` | CSV stream contract | FR-EXPORT-002 |
| `Exporter/PhpTempCsvStreamHandler.php` | Default CSV handler | FR-EXPORT-002 |

## Lookup & cache (`src/Lookup/`, `src/Cache/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Lookup/BicLookupServiceInterface.php` | BIC lookup contract | FR-LOOKUP-001 |
| `Lookup/BicLookupService.php` | IBAN→BIC local lookup | FR-LOOKUP-001 |
| `Cache/ValidationCacheInterface.php` | Validation cache contract | FR-CACHE-001 |
| `Cache/ValidationCache.php` | PSR-16 validation cache | FR-CACHE-001 |

## Mandate service & repository (`src/Service/`, `src/Repository/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Service/MandateService.php` | Mandate lifecycle | FR-MANDATE-001 |
| `Repository/MandateRepositoryInterface.php` | Persistence contract | FR-MANDATE-002 |
| `Repository/MandateRepository.php` | In-memory repository | FR-MANDATE-002 |

## Converter & logger (`src/Converter/`, `src/Logger/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Converter/CccConverter.php` | Spanish CCC ↔ IBAN | FR-CONV-001 |
| `Logger/SepaPaymentLogger.php` | Structured PSR-3 logging | FR-LOG-001 |

## Resources under `src/Resources/`

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/schemas/.gitkeep` | XSD placeholder directory | FR-VAL-008 |
| `Resources/translations/NowoSepaPaymentBundle.de.yaml` | Constraint messages DE | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.en_GB.yaml` | Constraint messages en_GB | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.en_US.yaml` | Constraint messages en_US | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.es.yaml` | Constraint messages ES | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.fr.yaml` | Constraint messages FR | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.it.yaml` | Constraint messages IT | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.nb.yaml` | Constraint messages NB | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.nl.yaml` | Constraint messages NL | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.pt.yaml` | Constraint messages PT | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.pt_PT.yaml` | Constraint messages pt_PT | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.ru.yaml` | Constraint messages RU | FR-I18N-001 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Bundle & DI | 4 | 4 |
| Commands | 4 | 4 |
| Generators | 4 | 4 |
| Parsers | 3 | 3 |
| Validators (services) | 10 | 10 |
| Validators (constraints) | 10 | 10 |
| Models | 9 | 9 |
| Events | 6 | 6 |
| Export | 3 | 3 |
| Lookup & cache | 4 | 4 |
| Mandate | 3 | 3 |
| Converter & logger | 2 | 2 |
| Schemas placeholder | 1 | 1 |
| Translations | 11 | 11 |
| **Total `src/` artifacts** | **74** | **74** |
