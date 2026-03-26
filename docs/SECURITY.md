# Security policy

## Supported versions

Security fixes are applied to the current major version line. Please upgrade to the latest patch release to receive security updates.

## Reporting a vulnerability

If you discover a security issue, please report it responsibly:

1. **Do not** open a public GitHub issue for security-sensitive bugs.
2. Send details to the maintainers (e.g. via the contact information on the [repository](https://github.com/nowo-tech/SepaPaymentBundle) or the Nowo.tech website).
3. Include a clear description, steps to reproduce, and the impact of the issue.
4. Allow time for a fix and coordinated disclosure before any public disclosure.

We will acknowledge your report and work on a fix. We appreciate responsible disclosure and will credit reporters when the issue is fixed (unless you prefer to remain anonymous).

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

Record confirmation in the release PR or tag notes.

