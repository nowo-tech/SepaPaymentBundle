# Security policy

## Table of contents

- [Supported versions](#supported-versions)
- [Reporting a vulnerability](#reporting-a-vulnerability)
- [Attack surface](#attack-surface)
- [Threat model](#threat-model)
- [Exposure](#exposure)
- [Security considerations for this bundle](#security-considerations-for-this-bundle)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)
- [Logging of payment identifiers](#logging-of-payment-identifiers)

## Supported versions

Security fixes are applied to the current major version line. Please upgrade to the latest patch release to receive security updates.

## Reporting a vulnerability

If you discover a security issue, please report it responsibly:

1. **Do not** open a public GitHub issue for security-sensitive bugs.
2. Send details to the maintainers (e.g. via the contact information on the [repository](https://github.com/nowo-tech/SepaPaymentBundle) or the Nowo.tech website).
3. Include a clear description, steps to reproduce, and the impact of the issue.
4. Allow time for a fix and coordinated disclosure before any public disclosure.

We will acknowledge your report and work on a fix. We appreciate responsible disclosure and will credit reporters when the issue is fixed (unless you prefer to remain anonymous).

## Attack surface

| Input | Where | Notes |
| --- | --- | --- |
| IBAN / BIC / CCC / creditor id / card numbers | Validators, `CccConverter`, Symfony constraints, CLI commands | Untrusted strings; validated and sanitized |
| Payment data (credit transfers, direct debits, remittances) | Generators (`pain.001` / `pain.008` XML), `ExportService` | Built from application data |
| SEPA XML files | `CreditTransferParser`, `DirectDebitParser`, `XsdValidator` | Untrusted XML; parsed with `LIBXML_NONET` |
| Mandates | `MandateService`, `MandateRepository` (optional Doctrine) | Application data |

## Threat model

| Threat | Mitigation |
| --- | --- |
| XML external entities / network fetches while parsing uploaded files | `DOMDocument::loadXML(…, LIBXML_NONET)` (no network access) with libxml's default of not substituting entities; XSD validation available |
| Malformed or out-of-spec payment files rejected by the bank | Validators (IBAN checksum, BIC, creditor id, SEPA countries/currency), business rules (execution / expiry dates from an injected clock), XSD validation of generated files |
| Injection into generated XML or CSV | Values are written through DOM (`digitick/sepa-xml`) and CSV writers, which escape them; `SepaStringSanitizer` restricts free text to the SEPA character set |
| Leaking account data in logs | See [Logging of payment identifiers](#logging-of-payment-identifiers) |
| Vulnerable dependencies | `composer audit` in CI; Dependabot updates; fixes released as patch versions |

## Exposure

The bundle exposes **no HTTP routes or admin UI**. Its CLI commands (`ValidateIban`, `ValidateCreditCard`, `ConvertCcc`, `ParseDirectDebit`) only read the arguments or files you pass them.

## Security considerations for this bundle

- **No secrets in config:** The bundle does not store passwords or API keys. Configuration is for behaviour (validation, defaults); sensitive data (e.g. BIC lookup endpoints, if any) should be injected via environment variables or parameters.
- **Input validation:** IBAN, BIC, and payment data are validated and sanitized according to SEPA rules. Do not bypass validation when building payment files.
- **Dependencies:** Run `composer audit` in projects that use this bundle to check for known vulnerabilities in dependencies (including Digitick\Sepa and Symfony components).

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document is current and linked from the README where applicable. |
| **`.gitignore` and `.env`** | `.env` and local env files are ignored; no committed secrets. |
| **No secrets in repo** | No API keys, passwords, or tokens in tracked files. |
| **Recipe / Flex** | Default recipe or installer templates do not ship production secrets. |
| **Input / output** | Inputs validated; outputs escaped in Twig/templates where user-controlled. |
| **Dependencies** | `composer audit` run; issues triaged. |
| **Logging** | Logs do not print secrets, tokens, or session identifiers unnecessarily. |
| **Cryptography** | If used: keys from secure config; never hardcoded. |
| **Permissions / exposure** | Routes and admin features documented; roles configured for production. |
| **Limits / DoS** | Timeouts, size limits, rate limits where applicable. |
| **AI security audit (REQ-SEC-004)** | Latest grade still current; re-audit after changes to parsers, generators or validators. |

Record confirmation in the release PR or tag notes.

## Logging of payment identifiers

`SepaPaymentLogger` masks IBAN/BIC in structured logs (only the last few characters remain visible). Do not add custom loggers that write full account identifiers.
