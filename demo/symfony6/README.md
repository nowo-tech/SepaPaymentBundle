# SEPA Payment Bundle – Demo (Symfony 6.4)

Demo app for **nowo-tech/sepa-payment-bundle** on Symfony 6.4. Uses the bundle from the repo (path repo + Docker mount).

## Quick start

From the bundle root or `demo/`:

```bash
make up-symfony6
make install-symfony6
```

App: http://localhost:8001 (or PORT in .env).

From this directory:

```bash
cp .env.example .env
make up
make install
```

See the [parent README](../README.md) for full documentation.

## Makefile

Run `make help` for targets: up, down, install, shell, test, test-coverage.
