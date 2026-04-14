## AI contribution guidelines (Nowo Symfony bundle)

Use this when suggesting code, tests, documentation, or CI changes for this repository.

### Scope

- This is a **Symfony bundle** for **SEPA payment** flows (`nowo-tech/*` on Packagist).
- Respect **PHP** and **Symfony** ranges in `composer.json` (including demo apps under `demo/`).
- Prefer **PHP 8 attributes**; do not add `doctrine/annotations` for new metadata.

### Code

- Follow **PSR-12** and project tooling (`composer cs-check`, `composer phpstan`, `composer test`).
- Payment and mandate handling must remain **explicit and auditable**; avoid silent failure paths.
- Coordinate changes across **root bundle** and **demo** `composer.json` files when dependencies shift.

### Documentation

- User-facing documentation in **English** under `docs/`.
- Never embed real IBANs, credentials, or production endpoints in examples; use test data only.

### Tests

- Add or update tests for payment edge cases; maintain coverage targets from CI and README.
