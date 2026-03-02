# SEPA Payment Bundle – Demo (Symfony 8.0)

Demo app for **nowo-tech/sepa-payment-bundle** on Symfony 8.0. Uses the bundle from the repo (path repo + Docker mount).

## Quick start

From the bundle root or `demo/`:

```bash
make up-symfony8
make install-symfony8
```

App: http://localhost:8003 (or PORT in .env).

From this directory:

```bash
cp .env.example .env
make up
make install
```

See the [parent README](../README.md) for full documentation.

## Makefile

Run `make help` for targets: up, down, install, shell, test, test-coverage.
