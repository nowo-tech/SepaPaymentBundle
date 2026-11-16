# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/sepa-payment-bundle`  
**Last audited**: 2026-11-16  

This file proves that **every production source artifact** under `src/` is referenced by the baseline specification. PHPUnit under `tests/` is out of scope; the ISO 20022 XSD files under `Resources/schemas/` ship with the package and are counted.

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
| `Generator/CreditTransferGeneratorInterface.php` | Port: pain.001 XML string, no HTTP types | FR-GEN-001, FR-API-001 |
| `Generator/DirectDebitGeneratorInterface.php` | Port: pain.008 XML string, no HTTP types | FR-GEN-002, FR-API-001 |

## Parsers (`src/Parser/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Parser/CreditTransferParser.php` | pain.001 DOM parse | FR-PARSE-001 |
| `Parser/DirectDebitParser.php` | pain.008 DOM parse | FR-PARSE-002 |
| `Parser/DirectDebitParserInterface.php` | pain.008 parser contract (2.0) | FR-PARSE-002, FR-API-001 |
| `Parser/RemesaParser.php` | Deprecated CT parser | FR-PARSE-003 |
| `Parser/CreditTransferParserInterface.php` | pain.001 parser contract | FR-PARSE-001, FR-API-001 |
| `Parser/PaymentStatusReportParser.php` | pain.002.001.03 / .10 status reports | FR-REPORT-001 |
| `Parser/PaymentStatusReportParserInterface.php` | pain.002 parser contract | FR-REPORT-001, FR-API-001 |
| `Parser/DebitCreditNotificationParser.php` | camt.054.001.02 / .08 notifications | FR-REPORT-002 |
| `Parser/DebitCreditNotificationParserInterface.php` | camt.054 parser contract | FR-REPORT-002, FR-API-001 |
| `Parser/Report/IsoReportLoader.php` | Safe report loading: size cap, no network/DTD, namespace + XSD check | FR-REPORT-001, FR-REPORT-002, FR-WORKER-001 |

## Validators — services (`src/Validator/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Validator/IbanValidator.php` | IBAN ISO 13616 | FR-VAL-001 |
| `Validator/IbanValidatorInterface.php` | Iban validation contract (2.0) | FR-VAL-001, FR-API-001 |
| `Validator/BicValidator.php` | BIC ISO 13616 | FR-VAL-002 |
| `Validator/BicValidatorInterface.php` | Bic validation contract (2.0) | FR-VAL-002, FR-API-001 |
| `Validator/CreditCardValidator.php` | Luhn + card type | FR-VAL-003 |
| `Validator/CreditCardValidatorInterface.php` | CreditCard validation contract (2.0) | FR-VAL-003, FR-API-001 |
| `Validator/SepaCountryValidator.php` | SEPA country list | FR-VAL-004 |
| `Validator/SepaCountryValidatorInterface.php` | SepaCountry validation contract (2.0) | FR-VAL-004, FR-API-001 |
| `Validator/SepaCreditorIdentifierValidator.php` | Creditor ID MOD97-10 | FR-VAL-005 |
| `Validator/SepaCreditorIdentifierValidatorInterface.php` | SepaCreditorIdentifier validation contract (2.0) | FR-VAL-005, FR-API-001 |
| `Validator/SepaBusinessRulesValidator.php` | Amount/currency/date rules | FR-VAL-006 |
| `Validator/SepaStringSanitizer.php` | SEPA charset sanitize | FR-VAL-007 |
| `Validator/XsdValidator.php` | pain.001 / pain.008 XSD | FR-VAL-008 |
| `Validator/XsdValidatorInterface.php` | XSD validation contract (2.0) | FR-VAL-008, FR-API-001 |
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
| `Model/Amount.php` | Exact integer-cent amounts (no float) | FR-MODEL-005 |
| `Model/PartyIdentification.php` | ISO 20022 generic party id (e.g. initiating party) | FR-GEN-005 |
| `Model/ReasonCode.php` | ISO 20022 R-transaction reason codes | FR-REPORT-003 |
| `Model/DirectDebit/DirectDebitGenerationOptions.php` | Per-call DD generator options | FR-GEN-005 |
| `Model/DirectDebit/Pain008Version.php` | pain.008 versions written | FR-GEN-005 |
| `Model/DirectDebit/PaymentInformationData.php` | One `PmtInf` block | FR-GEN-005 |
| `Model/DirectDebit/SequenceType.php` | SequenceType3Code values | FR-GEN-005, FR-MANDATE-001 |
| `Model/DirectDebit/SequenceTypeMode.php` | How `SeqTp` is written | FR-GEN-005 |
| `Model/Mandate/MandateAmendment.php` | EPC amendment details | FR-MANDATE-003 |
| `Model/Mandate/MandateEvidence.php` | Optional e-mandate acceptance evidence | FR-MANDATE-003 |
| `Model/Mandate/MandateSnapshot.php` | Immutable mandate snapshot for host repositories (DDD path) | FR-MANDATE-003 |
| `Model/Mandate/SnapshotDate.php` | Strict date parsing of stored rows | FR-MANDATE-003 |
| `Model/Report/PaymentStatusReport.php` | pain.002 report VO | FR-REPORT-001 |
| `Model/Report/PaymentInformationStatus.php` | pain.002 block status | FR-REPORT-001 |
| `Model/Report/TransactionStatus.php` | pain.002 transaction status | FR-REPORT-001 |
| `Model/Report/StatusReason.php` | Status / return reason | FR-REPORT-001, FR-REPORT-002, FR-REPORT-003 |
| `Model/Report/DebitCreditNotification.php` | camt.054 message VO | FR-REPORT-002 |
| `Model/Report/AccountNotification.php` | camt.054 `Ntfctn` | FR-REPORT-002 |
| `Model/Report/NotificationEntry.php` | camt.054 `Ntry` | FR-REPORT-002 |
| `Model/Report/NotificationTransaction.php` | camt.054 `TxDtls` | FR-REPORT-002 |

## Events (`src/Event/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Event/BeforeValidationEvent.php` | Pre-validation hook | FR-EVENT-001 |
| `Event/AfterValidationEvent.php` | Post-validation hook | FR-EVENT-001 |
| `Event/BeforeCreditTransferGenerationEvent.php` | Pre CT generation | FR-EVENT-002 |
| `Event/AfterCreditTransferGenerationEvent.php` | Post CT generation | FR-EVENT-002 |
| `Event/BeforeDirectDebitGenerationEvent.php` | Pre DD generation | FR-EVENT-003 |
| `Event/AfterDirectDebitGenerationEvent.php` | Post DD generation | FR-EVENT-003 |
| `Event/Generation/CreditTransferGeneratingEvent.php` | Framework-free pre CT generation | FR-EVENT-004 |
| `Event/Generation/CreditTransferGeneratedEvent.php` | Framework-free post CT generation | FR-EVENT-004 |
| `Event/Generation/DirectDebitGeneratingEvent.php` | Framework-free pre DD generation | FR-EVENT-004 |
| `Event/Generation/DirectDebitGeneratedEvent.php` | Framework-free post DD generation | FR-EVENT-004 |
| `Event/Mandate/MandateEvent.php` | Base mandate event (ids + plain values) | FR-EVENT-005 |
| `Event/Mandate/MandateCreatedEvent.php` | Mandate created | FR-EVENT-005 |
| `Event/Mandate/MandateRevokedEvent.php` | Mandate revoked | FR-EVENT-005 |
| `Event/Mandate/MandateSuspendedEvent.php` | Mandate suspended | FR-EVENT-005 |
| `Event/Mandate/MandateReactivatedEvent.php` | Mandate reactivated | FR-EVENT-005 |
| `Event/Mandate/MandateSequenceTypeChangedEvent.php` | Sequence type changed | FR-EVENT-005 |

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
| `Lookup/BicLookupService.php` | IBAN→BIC local lookup (request-scoped runtime mappings) | FR-LOOKUP-001 |
| `Cache/ValidationCacheInterface.php` | Validation cache contract | FR-CACHE-001 |
| `Cache/ValidationCache.php` | PSR-16 validation cache | FR-CACHE-001 |

## Mandate service & repository (`src/Service/`, `src/Repository/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Service/MandateService.php` | Mandate lifecycle | FR-MANDATE-001 |
| `Service/MandateServiceInterface.php` | Mandate lifecycle port | FR-MANDATE-001, FR-API-001 |
| `Repository/MandateRepositoryInterface.php` | Persistence contract | FR-MANDATE-002 |
| `Repository/MandateRepository.php` | In-memory request-scoped repository | FR-MANDATE-002 |

## Worker / request lifecycle (`src/EventSubscriber/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `EventSubscriber/WorkerStateResetSubscriber.php` | Reset request-scoped services on main request | FR-WORKER-001 |

## Converter & logger (`src/Converter/`, `src/Logger/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Converter/CccConverter.php` | Spanish CCC ↔ IBAN | FR-CONV-001 |
| `Logger/SepaPaymentLogger.php` | Structured PSR-3 logging | FR-LOG-001 |

## Formatter & HTTP edge (`src/Formatter/`, `src/Http/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Formatter/IbanMasker.php` | IBAN masking for display, logs, CSV | FR-MASK-001 |
| `Formatter/IbanMaskerInterface.php` | IBAN masking port | FR-MASK-001, FR-API-001 |
| `Http/SepaXmlResponseFactory.php` | XML download response (only HTTP-aware piece) | FR-HTTP-001 |

## Resources under `src/Resources/`

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/schemas/pain.001.001.03.xsd` | ISO 20022 CustomerCreditTransferInitiationV03 schema | FR-VAL-008, FR-GEN-001 |
| `Resources/schemas/pain.001.001.09.xsd` | ISO 20022 CustomerCreditTransferInitiationV09 schema | FR-VAL-008, FR-GEN-001 |
| `Resources/schemas/pain.008.001.02.xsd` | ISO 20022 CustomerDirectDebitInitiationV02 schema | FR-VAL-008, FR-GEN-002 |
| `Resources/schemas/pain.008.001.09.xsd` | ISO 20022 CustomerDirectDebitInitiationV09 schema | FR-VAL-008, FR-GEN-002 |
| `Resources/schemas/pain.008.001.08.xsd` | ISO 20022 CustomerDirectDebitInitiationV08 schema | FR-VAL-008, FR-GEN-005 |
| `Resources/schemas/pain.002.001.03.xsd` | ISO 20022 CustomerPaymentStatusReportV03 schema | FR-REPORT-001 |
| `Resources/schemas/pain.002.001.10.xsd` | ISO 20022 CustomerPaymentStatusReportV10 schema | FR-REPORT-001 |
| `Resources/schemas/camt.054.001.02.xsd` | ISO 20022 BankToCustomerDebitCreditNotificationV02 schema | FR-REPORT-002 |
| `Resources/schemas/camt.054.001.08.xsd` | ISO 20022 BankToCustomerDebitCreditNotificationV08 schema | FR-REPORT-002 |
| `Resources/schemas/NOTICE` | Provenance / licence notice for the bundled ISO 20022 schemas | FR-VAL-008 |
| `Resources/translations/NowoSepaPaymentBundle.de.yaml` | Constraint messages DE | FR-I18N-001 |
| `Resources/translations/NowoSepaPaymentBundle.en.yaml` | Constraint messages en | FR-I18N-001 |
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
| Bundle & DI (`src/`) | 4 | 4 |
| Commands (`src/Command/`) | 4 | 4 |
| Generators (`src/Generator/`) | 6 | 6 |
| Parsers (`src/Parser/`) | 10 | 10 |
| Validators — services (`src/Validator/`) | 16 | 16 |
| Validators — Symfony constraints (`src/Validator/Constraint/`) | 10 | 10 |
| Models (`src/Model/`) | 29 | 29 |
| Events (`src/Event/`) | 16 | 16 |
| Export (`src/Exporter/`) | 3 | 3 |
| Lookup & cache (`src/Lookup/`, `src/Cache/`) | 4 | 4 |
| Mandate service & repository (`src/Service/`, `src/Repository/`) | 4 | 4 |
| Worker / request lifecycle (`src/EventSubscriber/`) | 1 | 1 |
| Converter & logger (`src/Converter/`, `src/Logger/`) | 2 | 2 |
| Formatter & HTTP edge (`src/Formatter/`, `src/Http/`) | 3 | 3 |
| Resources under `src/Resources/` | 22 | 22 |
| **Total `src/` artifacts** | **134** | **134** |

Audit: `git ls-files src | wc -l` → 134 (2026-11-16).
