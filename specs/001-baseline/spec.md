# Feature Specification: SepaPaymentBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Created**: 2026-07-07  
**Status**: Active  
**Input**: Backfill GitHub Spec Kit baseline documenting 100% of production code in `src/`.

**Related docs**: [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md), [`docs/USAGE.md`](../../docs/USAGE.md)  
**Code inventory (traceability)**: [`code-inventory.md`](code-inventory.md)

---

## Summary

**Package**: `nowo-tech/sepa-payment-bundle`  
**Configuration root**: `nowo_sepa_payment`

Symfony bundle for SEPA payment operations: ISO 20022 XML generation (pain.001 credit transfer, pain.008 direct debit), parsing, IBAN/BIC/credit-card validation, mandate management, export, Spanish CCC conversion, and console utilities.

---

## User Scenarios & Testing

See user stories US-01…US-05 in [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md).

### User Story 1 — Generate bank-ready XML (Priority: P1)

**Given** populated `CreditTransferData` or `DirectDebitData`, **When** the integrator calls the generator service, **Then** pain.001 or pain.008 XML is returned, optionally XSD-validated, with before/after events dispatched.

### User Story 2 — Validate IBAN/BIC before export (Priority: P1)

**Given** an invalid IBAN, **When** `IbanValidator` or `#[Iban]` runs, **Then** validation fails with translatable message; cached validators reuse results when enabled.

### User Story 3 — Manage direct debit mandates (Priority: P2)

**Given** a new mandate, **When** `MandateService::create()` runs, **Then** status/sequence transitions are tracked; production apps MUST replace in-memory `MandateRepository` with persistent storage.

### User Story 4 — CLI utilities (Priority: P3)

**Given** operators without custom UI, **When** console commands run, **Then** IBAN, credit card, CCC, and DD XML parsing produce human-readable or JSON output.

---

## Requirements

### Bundle & configuration

- **FR-BUNDLE-001**: Bundle MUST expose alias `nowo_sepa_payment`.
- **FR-CFG-001**: Config MUST define `default_currency` (default `EUR`).
- **FR-CFG-002**: Extension MUST load `services.yaml` and publish `%nowo_sepa_payment.default_currency%`.
- **FR-DI-001**: All public services documented in [`docs/USAGE.md`](../../docs/USAGE.md) MUST be wired with autowire defaults.

### Generation & parsing

- **FR-GEN-001 / FR-GEN-002**: Generators MUST produce Digitick\Sepa-compatible XML, support optional XSD validation, BIC lookup, HTTP response helper, and generation events.
- **FR-GEN-003**: `IdentifierGenerator` MUST produce unique MsgId, PmtInfId, EndToEndId, MandateId values.
- **FR-GEN-004 / FR-PARSE-003 / FR-MODEL-004**: Deprecated Remesa* classes MUST delegate to CreditTransfer equivalents until removal in 2.0.0.
- **FR-PARSE-001 / FR-PARSE-002**: Parsers MUST extract header, payment info, and transactions from valid XML.

### Validation

- **FR-VAL-001…010**: Service validators and cached decorators as mapped in inventory.
- **FR-CONSTRAINT-001…005**: Symfony constraint attributes MUST delegate to service validators with translation keys under `NowoSepaPaymentBundle` domain.
- **FR-VAL-008**: XSD validation MUST support pain.001 and pain.008 when schema files are available.

### Mandates

- **FR-MANDATE-001**: Service MUST support create, revoke, suspend, reactivate, sequence transitions, and transaction eligibility checks.
- **FR-MANDATE-002**: Default in-memory repository is for dev/demo; interface documents production contract.

### Events, export, utilities

- **FR-EVENT-001…003**: Before/after hooks for validation and generation MUST allow mutation or short-circuit where documented.
- **FR-EXPORT-001 / FR-EXPORT-002**: Export/import JSON and CSV via injectable stream handler.
- **FR-LOOKUP-001**: BIC lookup from IBAN using bundled reference data where country supported.
- **FR-CACHE-001**: Optional PSR-16 cache for boolean validation results (TTL default 3600s).
- **FR-CONV-001**: CCC↔IBAN for Spanish accounts.
- **FR-LOG-001**: Structured logging for generation, validation, parse, and error paths.
- **FR-CLI-001**: Four console commands as listed in inventory.
- **FR-I18N-001**: Eleven locale files for validator messages.

---

## Success Criteria

- **SC-001**: **74/74** files mapped in [`code-inventory.md`](code-inventory.md).
- **SC-002**: Generated XML samples in tests pass XSD when schemas enabled.
- **SC-003**: PHPUnit + PHPStan pass in CI (`composer qa`).
- **SC-004**: Deprecated APIs documented in CHANGELOG/UPGRADING with 2.0.0 removal target.

---

## Out of scope

- Bank SFTP upload, payment status webhooks, PSD2 open banking APIs.
- Persistent mandate storage implementation (integrator responsibility).
