# SEPA Payment Bundle – Demo (Symfony 7.0)

Demo app for **nowo-tech/sepa-payment-bundle** on Symfony 7.0. Uses the bundle from the repo (path repo + Docker mount).

## Quick start

From the bundle root or `demo/`:

```bash
make up-symfony7
make install-symfony7
```

App: http://localhost:8002 (or PORT in .env).

From this directory:

```bash
cp .env.example .env
make up
make install
```

See the [parent README](../README.md) for full documentation.

## Makefile

Run `make help` for targets: up, down, install, shell, test, test-coverage.
