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
