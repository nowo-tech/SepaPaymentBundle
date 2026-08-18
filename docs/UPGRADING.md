# Upgrade Guide

This guide helps you upgrade between versions of the SEPA Payment Bundle.


## Table of contents

- [Upgrading from 1.2.23 to 1.2.24](#upgrading-from-1223-to-1224)
  - [📝 Changed (1.2.24)](#changed-1224)
  - [Backward Compatibility](#backward-compatibility-1224)
- [Upgrading from 1.2.22 to 1.2.23](#upgrading-from-1222-to-1223)
  - [✨ Added (1.2.23)](#added-1223)
  - [📝 Changed (1.2.23)](#changed-1223)
  - [📚 Documentation (1.2.23)](#documentation-1223)
  - [Backward Compatibility](#backward-compatibility-1223)
- [Upgrading from 1.2.21 to 1.2.22](#upgrading-from-1221-to-1222)
  - [✨ Added (1.2.22)](#added-1222)
  - [📝 Changed (1.2.22)](#changed-1222)
  - [📚 Documentation (1.2.22)](#documentation-1222)
  - [Backward Compatibility](#backward-compatibility-1222)
- [Upgrading from 1.2.20 to 1.2.21](#upgrading-from-1220-to-1221)
  - [✨ Added (1.2.21)](#added-1221)
  - [📝 Changed (1.2.21)](#changed-1221)
  - [📚 Documentation (1.2.21)](#documentation-1221)
  - [Backward Compatibility](#backward-compatibility-1221)
- [Upgrading from 1.2.19 to 1.2.20](#upgrading-from-1219-to-1220)
  - [✨ Added (1.2.20)](#added-1220)
  - [📝 Changed (1.2.20)](#changed-1220)
  - [📚 Documentation (1.2.20)](#documentation-1220)
  - [Backward Compatibility](#backward-compatibility-1220)
- [Upgrading from 1.2.18 to 1.2.19](#upgrading-from-1218-to-1219)
  - [📝 Changed (1.2.19)](#changed-1219)
  - [Backward Compatibility](#backward-compatibility-1219)
- [Upgrading from 1.2.17 to 1.2.18](#upgrading-from-1217-to-1218)
  - [✨ Added (1.2.18)](#added-1218)
  - [📝 Changed (1.2.18)](#changed-1218)
  - [📚 Documentation (1.2.18)](#documentation-1218)
  - [Backward Compatibility](#backward-compatibility-1218)
- [Upgrading from 1.2.16 to 1.2.17](#upgrading-from-1216-to-1217)
  - [✨ Added (1.2.17)](#added-1217)
  - [📝 Changed (1.2.17)](#changed-1217)
  - [📚 Documentation (1.2.17)](#documentation-1217)
  - [Backward Compatibility](#backward-compatibility-1217)
- [Upgrading from 1.2.15 to 1.2.16](#upgrading-from-1215-to-1216)
  - [🐛 Fixed (1.2.16)](#fixed-1216)
  - [📝 Changed (1.2.16)](#changed-1216)
  - [📚 Documentation (1.2.16)](#documentation-1216)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.12 to 1.2.15](#upgrading-from-1212-to-1215)
  - [✨ Added (1.2.15)](#added-1215)
  - [🔧 Improved (1.2.15)](#improved-1215)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.11 to 1.2.12](#upgrading-from-1211-to-1212)
  - [✨ Added (1.2.12)](#added-1212)
  - [🔧 Improved (1.2.12)](#improved-1212)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.10 to 1.2.11](#upgrading-from-1210-to-1211)
  - [🐛 Bug Fixes (1.2.11)](#bug-fixes-1211)
  - [🔧 CI / Development (1.2.11)](#ci-development-1211)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.9 to 1.2.10](#upgrading-from-129-to-1210)
  - [✨ Improvements (1.2.10)](#improvements-1210)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.8 to 1.2.9](#upgrading-from-128-to-129)
  - [🗑️ Removed (1.2.9)](#removed-129)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.7 to 1.2.8](#upgrading-from-127-to-128)
  - [🗑️ Removed (1.2.8)](#removed-128)
  - [🐛 Bug Fixes (1.2.8)](#bug-fixes-128)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.6 to 1.2.7](#upgrading-from-126-to-127)
  - [🗑️ Removed (1.2.7)](#removed-127)
  - [🐛 Bug Fixes (1.2.7)](#bug-fixes-127)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.5 to 1.2.6](#upgrading-from-125-to-126)
  - [⚠️ Breaking Changes (1.2.6)](#breaking-changes-126)
- [Upgrading from 1.2.4 to 1.2.5](#upgrading-from-124-to-125)
  - [✨ New Features (1.2.5)](#new-features-125)
  - [🔧 Improvements (1.2.5)](#improvements-125)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.3 to 1.2.4](#upgrading-from-123-to-124)
  - [⚠️ Breaking Changes (1.2.4)](#breaking-changes-124)
  - [🐛 Bug Fixes (1.2.4)](#bug-fixes-124)
  - [Backward Compatibility](#backward-compatibility)
  - [Impact](#impact)
- [Upgrading from 1.2.2 to 1.2.3](#upgrading-from-122-to-123)
  - [🐛 Bug Fixes (1.2.3)](#bug-fixes-123)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.1 to 1.2.2](#upgrading-from-121-to-122)
  - [🐛 Bug Fixes (1.2.2)](#bug-fixes-122)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.2.0 to 1.2.1](#upgrading-from-120-to-121)
  - [🐛 Bug Fixes (1.2.1)](#bug-fixes-121)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.1.0 to 1.2.0](#upgrading-from-110-to-120)
  - [✨ New Features (1.2.0)](#new-features-120)
  - [Mandate Management](#mandate-management)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.0.0 to 1.1.0](#upgrading-from-100-to-110)
  - [✨ New Features (1.1.0)](#new-features-110)
  - [BIC Lookup Service](#bic-lookup-service)
  - [Backward Compatibility](#backward-compatibility)
  - [Additional New Features (Unreleased)](#additional-new-features-unreleased)
  - [Export Service](#export-service)
  - [Symfony Events](#symfony-events)
  - [Structured Logging](#structured-logging)
  - [Backward Compatibility](#backward-compatibility)
- [Upgrading from 1.0.0 to 1.1.0](#upgrading-from-100-to-110)
  - [✨ New Features (1.1.0)](#new-features-110)
  - [Backward Compatibility](#backward-compatibility)
  - [Planned Breaking Changes (Future Versions)](#planned-breaking-changes-future-versions)
- [Upgrading to 1.0.0](#upgrading-to-100)
  - [🎉 First Stable Release](#first-stable-release)
  - [What's New](#whats-new)
  - [Breaking Changes](#breaking-changes)
  - [Migration Steps](#migration-steps)
  - [New Features You Can Use](#new-features-you-can-use)
- [Upgrading to 0.0.12](#upgrading-to-0012)
  - [CreditTransferGenerator: New Features and Feature Parity](#credittransfergenerator-new-features-and-feature-parity)
  - [Demo Routes Translation](#demo-routes-translation)
  - [Code Documentation](#code-documentation)
- [Upgrading to 0.0.11](#upgrading-to-0011)
  - [Service Auto-Registration with `#[AsAlias]` Attributes](#service-auto-registration-with-asalias-attributes)
- [Upgrading to 0.0.10](#upgrading-to-0010)
  - [Service Configuration Changes](#service-configuration-changes)
- [Upgrading to 0.0.9](#upgrading-to-009)
  - [New Features](#new-features)
- [Upgrading to 0.0.8](#upgrading-to-008)
  - [New Features](#new-features)
  - [Important Notes](#important-notes)
- [Upgrading to 0.0.7](#upgrading-to-007)
  - [New Features](#new-features)
  - [PHP 8.2 Compatibility Fix](#php-82-compatibility-fix)
- [Upgrading to 0.0.6](#upgrading-to-006)
  - [Service Registration](#service-registration)
- [Upgrading to 0.0.5](#upgrading-to-005)
  - [Payment Method](#payment-method)
- [Upgrading to 0.0.4](#upgrading-to-004)
  - [Digitick\Sepa v3.0 Compatibility](#digiticksepa-v30-compatibility)
- [Upgrading to 0.0.3](#upgrading-to-003)
  - [Breaking Changes from Digitick\Sepa v3.0](#breaking-changes-from-digiticksepa-v30)
- [General Upgrade Notes](#general-upgrade-notes)
  - [Demo Applications](#demo-applications)
- [Getting Help](#getting-help)

## Upgrading from 1.2.23 to 1.2.24

### 📝 Changed (1.2.24)

- **Demos only:** pin Hot Reload Bundle to `^1.4` (FrankenPHP Mercure/`hot_reload`, `dev`/`test`). Symfony 8 is the only shipped demo (Symfony 6/7 demo apps removed).

### Backward Compatibility (1.2.24)

- **No breaking API changes.** `composer update nowo-tech/sepa-payment-bundle` as usual.

---

## Upgrading from 1.2.22 to 1.2.23

### ✨ Added (1.2.23)

- FrankenPHP banner, `make down-dev` / `demo-smoke`, zero direct deprecations in PHPUnit/CI — **consumers**: no config change required.
- Contributors: `phpstan-frankenphp` rulesets after `composer install`.

### 📝 Changed (1.2.23)

- `NowoSepaPaymentBundle` and DI `Configuration` are **`final`** — do not subclass them.
- PHPStan analyses `src` only with empty `ignoreErrors`.

### 📚 Documentation (1.2.23)

- See [CHANGELOG.md](CHANGELOG.md) `[1.2.23]`.

### Backward Compatibility (1.2.23)

- **No breaking API changes** for typical integrators. `composer update nowo-tech/sepa-payment-bundle` as usual.

---

## Upgrading from 1.2.21 to 1.2.22

### ✨ Added (1.2.22)

- **English locale file**: **`NowoSepaPaymentBundle.en.yaml`** for apps that use locale **`en`** (same messages as **`en_US`**). No change required if you already use **`en_US`** / **`en_GB`**.
- **Contributor Covenant**: Root **`CODE_OF_CONDUCT.md`** — community standards only.
- **REQ-GIT-001 (repo only)**: Hooks, CI job, and scripts that reject Cursor co-author trailers — relevant when contributing to this repository. See [GITHUB_CI.md](GITHUB_CI.md).

### 📝 Changed (1.2.22)

- **Maintainers**: **`make release-check`** includes **`check-no-cursor-coauthor`**. Run **`make setup-hooks`** once after clone.
- **Locks**: Dev dependency **`friendsofphp/php-cs-fixer`** bumped via Dependabot — no impact on the published bundle API.

### 📚 Documentation (1.2.22)

- **README**, **CONTRIBUTING**, **RELEASE**, and **GITHUB_CI.md** document Code of Conduct and git hygiene. See [CHANGELOG.md](CHANGELOG.md).

### Backward Compatibility (1.2.22)

- **No breaking API changes**: patch release. `composer update nowo-tech/sepa-payment-bundle` as usual.

---

## Upgrading from 1.2.20 to 1.2.21

### ✨ Added (1.2.21)

- **French and Dutch translations**: The bundle ships **`NowoSepaPaymentBundle.fr.yaml`** and **`NowoSepaPaymentBundle.nl.yaml`**. Enable the locale in your Symfony app as usual; messages use the existing **`NowoSepaPaymentBundle`** domain. French returns after removal in 1.2.9 (files now use valid YAML).
- **Spec Kit (repo only)**: **`.specify/`**, **`specs/001-baseline/`**, and **`docs/SPEC-KIT.md`** — no impact when you install the package via Composer unless you contribute to this repository.

### 📝 Changed (1.2.21)

- **CI / locks (maintainers)**: GitHub Actions action bumps and refreshed **`composer.lock`** files — run `composer update` in your own project only when you choose to bump dependencies.
- **Demos**: Demo Docker images install **`intl`** — relevant only if you run demos from this repo.

### 📚 Documentation (1.2.21)

- **SPEC-DRIVEN-DEVELOPMENT.md** and **README** link **GitHub Spec Kit**; see [CHANGELOG.md](CHANGELOG.md) for the full list.

### Backward Compatibility (1.2.21)

- **No breaking API changes**: patch release. `composer update nowo-tech/sepa-payment-bundle` as usual. New locale files are optional for integrators.

---

## Upgrading from 1.2.19 to 1.2.20

### ✨ Added (1.2.20)

- **CodeRabbit (repo only)**: Optional automated PR reviews via `.coderabbit.yaml` — no impact on applications that `composer require` the bundle.
- **Spec-driven development**: New **`docs/SPEC-DRIVEN-DEVELOPMENT.md`** describes product scope, verification, and **`REQ-*`** anchors for maintainers.

### 📝 Changed (1.2.20)

- **CI**: Symfony **7.4** and **8.1** added to the GitHub Actions matrix (with PHP exclusions). Your app’s supported Symfony range in **`composer.json`** is unchanged.
- **Maintainers**: **`make update-deps`** (bundle + demos) requires **`COMPOSE`** / **`SERVICE_PHP`** in Makefiles — already fixed in this release. Clone/pull and run `make update-deps` as usual.
- **Composer locks**: Root and demo locks refreshed — run `composer update` in your project only when you intentionally bump dependencies.

### 📚 Documentation (1.2.20)

- **README** and **CONTRIBUTING** use canonical GitHub URLs (`nowo-tech/SepaPaymentBundle`).
- See [CHANGELOG.md](CHANGELOG.md) for the full list.

### Backward Compatibility (1.2.20)

- **No breaking API changes**: patch release. `composer update nowo-tech/sepa-payment-bundle` as usual.

---

## Upgrading from 1.2.18 to 1.2.19

### 📝 Changed (1.2.19)

- **Repository / demos**: Root **`composer.lock`** and **`demo/symfony8`** / **`demo/symfony8`** locks refreshed (Symfony and **digitick/sepa-xml** bumps in the resolved tree). Run `composer update` at the bundle root or inside each demo if you maintain a long-lived clone.
- **Reference fixtures**: **`demo/symfony8/config/reference.php`** and **`tests/Fixtures/app/config/reference.php`** — maintenance only; no action needed in consuming applications.
- **Rector (maintainers)**: **`rector.php`** skips **`CommandHelpToAttributeRector`** again so **`make release-check`** stays green while commands keep **`setHelp()`** for Console **6.0 / 6.1**.

### Backward Compatibility (1.2.19)

- **No breaking API changes**: patch release. `composer update nowo-tech/sepa-payment-bundle` as usual.

---

## Upgrading from 1.2.17 to 1.2.18

### ✨ Added (1.2.18)

- **Cursor / Copilot (repo only)**: `.cursor/rules/*.mdc`, `.cursorignore`, `.github/copilot-instructions.md` — no impact on your application when you install the package.
- **GitHub**: `pr-lint.yml`, `stale.yml`, and Dependabot groups for Symfony / PHPStan on the root Composer config — maintenance only.

### 📝 Changed (1.2.18)

- **Scrutinizer**: Stronger checks and Clover coverage from `composer test-coverage` (see `.scrutinizer.yml`).
- **Demos**: Docker Compose **`dns`** on Symfony 6/7/8 demos if Composer fails to resolve Packagist under Docker/WSL.
- **Locks**: Refreshed **`composer.lock`** (root and some demos) — run `composer update` in your own project as needed; the bundle’s **`composer.json`** constraints are unchanged.

### 📚 Documentation (1.2.18)

- **RELEASE.md** is the single maintainer guide; **RELEASE_CHECKLIST.md** was removed (see [CHANGELOG.md](CHANGELOG.md)).

### Backward Compatibility (1.2.18)

- **No breaking API changes**: patch release. `composer update nowo-tech/sepa-payment-bundle` as usual.

---

## Upgrading from 1.2.16 to 1.2.17

### ✨ Added (1.2.17)

- **Scrutinizer CI**: The repository includes `.scrutinizer.yml` (PHP 8.2 on `default-bionic`, `XDEBUG_MODE=coverage`, root PHPUnit). This only affects maintainers and Scrutinizer builds — **no change required** in your application when you `composer require` the bundle.

### 📝 Changed (1.2.17)

- **Demos** (in the bundle repo only): Default `PORT` / `DEFAULT_URI` differ per Symfony demo folder (8001, 8002, 8003) so you can run more than one demo locally without clashing.
- **Development**: Root Docker Compose and Makefile targets (`validate-translations`, coverage volume) are for contributors; they do not change runtime behaviour of the installed package.
- **Tooling**: Root `composer-sync` uses `composer install --dry-run` (it no longer rewrites `composer.lock` during `make release-check`). Rector skips moving console help into `#[AsCommand]` so Symfony Console **6.0 / 6.1** stay supported. **`demo/Makefile`**: `test-coverage-all` shell syntax fixed for `make -C demo release-check`.

### 📚 Documentation (1.2.17)

- **README / USAGE / UPGRADING**: Examples updated for `TranslatorInterface` and `CreditTransferGenerator` constructor order; **USAGE** XSD section and **DEMO-FRANKENPHP** paths/TOC; **INSTALLATION** table of contents.
- See [CHANGELOG.md](CHANGELOG.md) for the full list.

### Backward Compatibility (1.2.17)

- **No breaking API changes**: Patch release. Upgrade with `composer update nowo-tech/sepa-payment-bundle` as usual.

---

## Upgrading from 1.2.15 to 1.2.16

### 🐛 Fixed (1.2.16)

- **Console commands on Symfony 6.0 / 6.1**: The `#[AsCommand]` attribute does not support the `help` parameter before Symfony Console 6.2. The bundle’s console commands (`nowo:sepa:validate-iban`, `sepa:validate-credit-card`, `nowo:sepa:parse-direct-debit`, `nowo:sepa:ccc-to-iban`) now set their help text via `setHelp()` in `configure()`, so they work on Symfony 6.0 and 6.1 without the "Unknown named parameter $help" error (e.g. when running `cache:clear` after `composer install` in the demo).

### 📝 Changed (1.2.16)

- **Translation file names**: Bundle translation files are now named **`NowoSepaPaymentBundle.*.yaml`** (e.g. `NowoSepaPaymentBundle.es.yaml`, `NowoSepaPaymentBundle.de.yaml`) instead of `nowo_sepa_payment.*.yaml` and `validators.*.yaml`. The translation domain in code is already `NowoSepaPaymentBundle`. **If you override bundle messages** in your project’s `translations/` directory, rename your files to use the new domain (e.g. `translations/NowoSepaPaymentBundle.es.yaml`) and the same keys; no change to keys is required.

### 📚 Documentation (1.2.16)

- **CONFIGURATION.md**: New sections describe that bundle translations are overridable, the translation load order (priority: project `translations/`, then app bundle overrides, then bundle `Resources/translations/`), and how to override messages in your application.

### Backward Compatibility

- **No breaking API changes**: Command names, options, and validation behaviour are unchanged. If you do not override bundle translations, no migration is needed. If you have custom files for the old domains, rename them to the `NowoSepaPaymentBundle` domain.

---

## Upgrading from 1.2.12 to 1.2.15

### ✨ Added (1.2.15)

- **Tests**: MandateStatusTest, additional BicLookupService and CreditCardValidator tests, CreditTransferGenerator BIC lookup test for transactions.

### 🔧 Improved (1.2.15)

- **Bundle / CI**: Root `composer.json` now requires `brick/math` `^0.11 || ^0.12 || ^0.13` so that installs on PHP 8.1 (including GitHub Actions CI) resolve to a PHP 8.1‑compatible version (avoids `brick/math` 0.14.x requiring PHP 8.2+).
- **Demo Symfony 6.4**: Demo `composer.json` constrains `brick/math` to `^0.11 || ^0.12 || ^0.13` for the same PHP 8.1 compatibility when running demos locally.
- **Test coverage**: Coverage improvements and `@codeCoverageIgnore` for library-compatibility and defensive branches in generators (no behaviour change).

### Backward Compatibility

- **No breaking changes**: Patch release; no migration required.

---

## Upgrading from 1.2.11 to 1.2.12

### ✨ Added (1.2.12)

- **Rector**: Refactoring tool for PHP (config: `rector.php`). Use `composer rector-dry` or `make rector-dry` to check; `composer rector` or `make rector` to apply.
- **PHPStan**: Static analysis at level 8 (config: `phpstan.neon.dist`, `phpstan-baseline.neon`). Use `composer phpstan` or `make phpstan`.
- **Release check**: `composer release-check` and `make release-check` now include Rector (dry-run) and PHPStan in addition to cs-check and test.

### 🔧 Improved (1.2.12)

- **Type safety**: Internal improvements for PHPStan (parsers, generators, validators, models). No public API changes.
- **Code comments**: All inline and PHPDoc comments are in English.

### Backward Compatibility

- **No breaking changes**: All existing code and CLI usage continue to work.
- **No migration required**: Patch release with tooling and static analysis improvements only.

---

## Upgrading from 1.2.10 to 1.2.11

### 🐛 Bug Fixes (1.2.11)

- **ParseDirectDebitCommand**: Path validation before reading
  - The command now checks that the argument is a readable file (not a directory or unreadable path) before calling `file_get_contents()`
  - If you pass a directory or a non-readable path, you get a clear "Could not read file" error instead of a PHP Notice and a generic XML format error
  - No API or CLI changes; only more robust behaviour
  - See [Console Commands](COMMANDS.md#parse-direct-debit-xml) for full command documentation and all error messages
- **SepaBusinessRulesValidator**: PHP 8.4+ compatibility
  - Internal change: "first transaction" sequence type transition now uses an empty string instead of `null` as array key to avoid PHP 8.4+ deprecation ("Using null as array offset")
  - Public method `isValidSequenceTypeTransition(?string $previousSequenceType, string $newSequenceType)` is unchanged; passing `null` for the first transaction still works as before

### 🔧 CI / Development (1.2.11)

- **Coverage requirement**: CI now enforces a minimum of 80% code coverage (previously 85%). This only affects contributors and CI; no impact on bundle users.

### Backward Compatibility

- **No breaking changes**: All existing code and CLI usage continue to work
- **No migration required**: Patch release with bug fixes and CI/test improvements only

---

## Upgrading from 1.2.9 to 1.2.10

### ✨ Improvements (1.2.10)

- **Packagist search visibility**: Enhanced package metadata for better discoverability
  - No code changes - this is a documentation and metadata improvement
  - Improved package description and keywords in `composer.json`
  - Enhanced README.md with better search terms
  - Makes the package easier to find on Packagist when searching for SEPA, ISO 20022, pain.001, pain.008, IBAN validation, etc.

### Backward Compatibility

- **No breaking changes**: All existing code will continue to work
- **No migration required**: This is a patch release with documentation improvements only
- **No code changes**: Only metadata and documentation updates

---

## Upgrading from 1.2.8 to 1.2.9

### 🗑️ Removed (1.2.9)

- **French and Italian translations**: Removed translation files with YAML syntax errors
  - `nowo_sepa_payment.fr.yaml` has been removed
  - `nowo_sepa_payment.it.yaml` has been removed
  - `validators.fr.yaml` has been removed
  - These files had YAML syntax errors with incorrectly escaped apostrophes and are no longer maintained
  - If you were using French or Italian translations, they will fall back to the default language (usually English)
  - French and Italian language support can be added back in the future if needed

### Backward Compatibility

- **No breaking changes**: All existing code will continue to work
- **Translation fallback**: If you were using French or Italian translations, the system will fall back to the default language
- **No migration required**: This is a patch release with cleanup only

---

## Upgrading from 1.2.7 to 1.2.8

### 🗑️ Removed (1.2.8)

- **Catalan translations**: Removed Catalan translation files
  - `nowo_sepa_payment.ca.yaml` and `validators.ca.yaml` have been removed
  - These files had YAML syntax errors and are no longer maintained
  - If you were using Catalan translations, they will fall back to the default language
  - Catalan language support can be added back in the future if needed

### 🐛 Bug Fixes (1.2.8)

- **CreditTransferGenerator**: Fixed compatibility with refactored `Transaction` class
  - Fixed incorrect usage of deprecated `debtor*` methods
  - No action required - this is an internal fix

### Backward Compatibility

- **No breaking changes**: All existing code will continue to work
- **Translation fallback**: If you were using Catalan translations, the system will fall back to the default language (usually English)
- **No migration required**: This is a patch release with bug fixes and cleanup

---

## Upgrading from 1.2.6 to 1.2.7

### 🗑️ Removed (1.2.7)

- **Catalan translations**: Removed Catalan translation files
  - `nowo_sepa_payment.ca.yaml` and `validators.ca.yaml` have been removed
  - These files had YAML syntax errors and are no longer maintained
  - If you were using Catalan translations, they will fall back to the default language
  - Catalan language support can be added back in the future if needed

### 🐛 Bug Fixes (1.2.7)

- **CreditTransferGenerator**: Fixed compatibility with refactored `Transaction` class
  - Fixed incorrect usage of deprecated `debtor*` methods
  - No action required - this is an internal fix

### Backward Compatibility

- **No breaking changes**: All existing code will continue to work
- **Translation fallback**: If you were using Catalan translations, the system will fall back to the default language (usually English)
- **No migration required**: This is a patch release with bug fixes and cleanup

---

## Upgrading from 1.2.5 to 1.2.6

### ⚠️ Breaking Changes (1.2.6)

- **TranslatorInterface is now required**: `CreditTransferGenerator` and `XsdValidator` now require a `TranslatorInterface` instance
- **Dependencies moved to require**: `symfony/translation`, `symfony/validator`, and `symfony/yaml` are now production dependencies

#### TranslatorInterface Requirement

**Before (1.2.5 and earlier):**
```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$ibanValidator = new IbanValidator();
$generator = new CreditTransferGenerator($ibanValidator); // Translator was optional
```

**After (1.2.6):**
```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

$ibanValidator = new IbanValidator();
$translator = $container->get('translator'); // Or inject via DI
$generator = new CreditTransferGenerator($ibanValidator, $translator); // Translator is required
```

**Using Symfony Dependency Injection (Recommended):**
```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class MyService
{
    public function __construct(
        private CreditTransferGenerator $generator,
        private TranslatorInterface $translator
    ) {
        // Symfony automatically injects both services
    }
}
```

#### XsdValidator Migration

**Before (1.2.5 and earlier):**
```php
use Nowo\SepaPaymentBundle\Validator\XsdValidator;

$validator = new XsdValidator(); // Translator was optional
```

**After (1.2.6):**
```php
use Nowo\SepaPaymentBundle\Validator\XsdValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

$translator = $container->get('translator');
$validator = new XsdValidator($translator); // Translator is required
```

#### Dependencies Update

The following dependencies are now required in production (moved from `require-dev` to `require`):

- `symfony/translation`: ^6.0 || ^7.0 || ^8.0
- `symfony/validator`: ^6.0 || ^7.0 || ^8.0
- `symfony/yaml`: ^6.0 || ^7.0 || ^8.0

**Action required**: Run `composer update` to install these dependencies in production:

```bash
composer update symfony/translation symfony/validator symfony/yaml
```

Or simply:
```bash
composer update
```

**Note**: If you're using Symfony Framework, these packages are usually already installed. This change only affects standalone usage of the bundle.

#### Impact Assessment

**✅ No action required if:**
- You're using Symfony Framework with dependency injection (autowiring)
- Services are injected via constructor type-hinting
- You're not instantiating `CreditTransferGenerator` or `XsdValidator` manually

**⚠️ Action required if:**
- You're instantiating `CreditTransferGenerator` or `XsdValidator` manually (without DI)
- You're using the bundle in a non-Symfony application
- You have custom service configurations that manually instantiate these classes

#### Migration Steps

1. **Update composer dependencies:**
   ```bash
   composer update
   ```

2. **Update manual instantiation code:**
   - Find all places where `CreditTransferGenerator` or `XsdValidator` are instantiated manually
   - Add `TranslatorInterface` as a required parameter
   - Use dependency injection where possible (recommended)

3. **Update tests:**
   - Mock `TranslatorInterface` in test setup
   - Update test expectations if they rely on specific error messages

#### Benefits

- **Consistent error messages**: All error messages are now translatable and consistent
- **Better internationalization**: Error messages can be translated to multiple languages
- **Clearer dependencies**: Production dependencies are explicitly declared
- **Better error messages**: Translated error messages are more user-friendly

---

## Upgrading from 1.2.4 to 1.2.5

### ✨ New Features (1.2.5)

- **SEPA Creditor Identifier Validator**: New validator for validating SEPA Creditor Identifiers
  - Use `SepaCreditorIdentifierValidator` to validate creditor identifiers used in SEPA Direct Debit transactions
  - Validates format, check digits (MOD97-10 algorithm), and structure
  - Includes helper methods to extract country code and national identifier
  - Supports validation of Spanish NIF/CIF format for use in Spanish identifiers
  - Example usage:
    ```php
    use Nowo\SepaPaymentBundle\Validator\SepaCreditorIdentifierValidator;
    
    $validator = new SepaCreditorIdentifierValidator();
    $isValid = $validator->isValid('ES97ZZZM12345678');
    $countryCode = $validator->getCountryCode('ES97ZZZM12345678'); // 'ES'
    $nif = $validator->getNationalIdentifier('ES97ZZZM12345678'); // 'M12345678'
    ```
- **Credit Transfer Generator Validation**: Added validation to detect incorrect key usage in `generateFromArray()` method
  - If you use `creditor*` keys at the top level (e.g., `creditorIban`, `creditorName`), you will now get a clear error message suggesting to use `debtor*` keys instead
  - If you use `debtor*` keys within transactions (e.g., `debtorIban`, `debtorName`), you will now get a clear error message suggesting to use `creditor*` keys instead
  - Error messages include suggestions for the correct key names
  - This helps prevent confusion between debtor and creditor roles in SEPA Credit Transfers

### 🔧 Improvements (1.2.5)

- **Demo Applications**: Internal refactoring of demo controllers (no impact on bundle users)
  - Demo controllers were refactored for better code organization
  - Routes and functionality remain unchanged
  - Only affects the demo applications, not the bundle itself

### Backward Compatibility

- **No breaking changes**: All existing code will continue to work
- **Validation is additive**: The new validation only helps catch errors - it doesn't change the API
- **Correct usage**: Code that was already using the correct keys (`debtor*` at top level, `creditor*` in transactions) will work exactly as before
- **Error detection**: Code using incorrect keys will now get clearer error messages instead of potentially confusing errors later

---

## Upgrading from 1.2.3 to 1.2.4

### ⚠️ Breaking Changes (1.2.4)

- **Credit Transfer Transaction Model Refactoring**: `CreditTransfer\Transaction` field names and methods have changed
  - **Field names changed**: `debtorIban` → `creditorIban`, `debtorName` → `creditorName`, `debtorBic` → `creditorBic`, `debtorAddress` → `creditorAddress`
  - **Method names changed**: All `getDebtor*()`, `setDebtor*()` methods → `getCreditor*()`, `setCreditor*()`
  - **Why this change**: Makes the code self-documenting - Transaction fields now correctly reflect that they represent creditors (who receive money)
  - **Migration required**: Code using `CreditTransfer\Transaction` directly must be updated

#### Migration Steps

**Before (1.2.3 and earlier):**
```php
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;

$transaction = new Transaction(
    'E2E-001',
    100.50,
    'EUR',
    'GB82WEST12345698765432', // debtorIban
    'John Doe'                // debtorName
);

$transaction->setDebtorBic('WESTGB22');
$transaction->setDebtorAddress(['street' => '123 Main St', 'city' => 'London']);

$iban = $transaction->getDebtorIban();
$name = $transaction->getDebtorName();
$bic = $transaction->getDebtorBic();
$address = $transaction->getDebtorAddress();
```

**After (1.2.4):**
```php
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;

$transaction = new Transaction(
    'E2E-001',
    100.50,
    'EUR',
    'GB82WEST12345698765432', // creditorIban (who receives)
    'John Doe'                // creditorName (who receives)
);

$transaction->setCreditorBic('WESTGB22');
$transaction->setCreditorAddress(['street' => '123 Main St', 'city' => 'London']);

$iban = $transaction->getCreditorIban();
$name = $transaction->getCreditorName();
$bic = $transaction->getCreditorBic();
$address = $transaction->getCreditorAddress();
```

**Using `generateFromArray()` method:**
- The `generateFromArray()` method now accepts `debtor*` field names at the **top level** of the array (representing the company that pays)
- In **transactions**, use `creditor*` field names (representing each supplier/beneficiary that receives payment)
- This makes the code self-documenting: `debtor*` at top level = company that pays, `creditor*` in transactions = who receives

```php
// Correct format (required)
$data = [
    'reference' => 'MSG-001',
    'initiatingPartyName' => 'My Company',
    'paymentInfoId' => 'PMT-001',
    // Debtor data (company that PAYS) - using debtor* keys for clarity
    'debtorIban' => 'ES9121000418450200051332',  // ✅ Required (top level)
    'debtorName' => 'My Company Name',            // ✅ Required (top level)
    'debtorBic' => 'CAIXESBBXXX',                 // ✅ Optional (top level)
    'requestedExecutionDate' => '2024-01-20',
    'transactions' => [
        [
            'amount' => 100.50,
            // Creditor data (who RECEIVES the payment)
            'creditorIban' => 'GB82WEST12345698765432',  // ✅ Required (in transactions)
            'creditorName' => 'John Doe',                 // ✅ Required (in transactions)
            'creditorBic' => 'WESTGB22',                  // ✅ Optional (in transactions)
            'endToEndId' => 'E2E-001',
        ],
    ],
];

// ❌ Incorrect format (debtor* in transactions is no longer supported)
// $data = [
//     'transactions' => [
//         [
//             'amount' => 100.50,
//             'debtorIban' => 'GB82WEST12345698765432',  // ❌ No longer supported in transactions
//             'debtorName' => 'John Doe',                 // ❌ No longer supported in transactions
//         ],
//     ],
// ];
```

### 🐛 Bug Fixes (1.2.4)

- **Fixed Credit Transfer Generator Creditor/Debtor Mapping**: Corrected incorrect mapping of creditor and debtor roles
  - `PaymentInformation` now correctly uses debtor data (company that pays)
  - `CustomerCreditTransferInformation` now correctly uses creditor data (each supplier/beneficiary that receives)
  - This fix ensures compliance with SEPA pain.001 standard for credit transfers
  - The XML generated will now correctly reflect the payment structure: one debtor paying multiple creditors

### Backward Compatibility

- **Array format**: The `generateFromArray()` method now accepts `debtor*` field names at the top level (representing the company that pays) and `creditor*` field names in transactions (representing who receives payment)
- **Top level fields**: Use `debtorIban`, `debtorName`, `debtorBic`, `debtorAddress` at the top level of the array
- **Transaction fields**: Use `creditorIban`, `creditorName`, `creditorBic`, `creditorAddress` within each transaction
- **Remesa classes unaffected**: The deprecated `Remesa\Transaction` and `Remesa\RemesaData` classes continue to work (they maintain their `debtor*` API for backward compatibility)
- **XML output change**: The generated XML will now correctly map debtor/creditor roles according to SEPA standards

### Impact

- **Code using `CreditTransfer\Transaction` directly**: Requires migration to new `creditor*` method names
- **Code using `generateFromArray()`**: Must update arrays to use `debtor*` field names at the top level (`debtorIban`, `debtorName`, `debtorBic`, `debtorAddress`) and `creditor*` field names in transactions (`creditorIban`, `creditorName`, `creditorBic`, `creditorAddress`)
- **Code using deprecated `Remesa\*` classes**: No changes required (they maintain backward compatibility with `debtor*` API)
- **XML output**: Generated XML files will now correctly comply with SEPA pain.001 standard structure

---

## Upgrading from 1.2.2 to 1.2.3

### 🐛 Bug Fixes (1.2.3)

- **Fixed Deprecated Attribute Parameter**: Corrected parameter name in `#[Deprecated]` attribute
  - Changed `reason` to `message` parameter (correct PHP 8.1+ syntax)
  - Updated in `RemesaGenerator` and `RemesaParser` deprecated methods
  - The `#[Deprecated]` attribute in PHP uses `message` parameter, not `reason`

### Backward Compatibility

- No breaking changes
- All functionality remains the same
- Only internal attribute syntax correction

---

## Upgrading from 1.2.1 to 1.2.2

### 🐛 Bug Fixes (1.2.2)

- **Fixed ValidationCache Tests**: Improved cache implementation for better test compatibility
  - Created `ArrayCache` class for testing that properly implements PSR-16 SimpleCache interface
  - Fixed `ValidationCache::get()` logic for better cache adapter compatibility
- **Fixed ParseDirectDebitCommand Tests**: Removed deprecated Symfony API usage
  - Removed `Application::add()` calls (not needed in Symfony 6+)
  - Commands with `#[AsCommand]` are automatically registered
- **Fixed ParseDirectDebitCommand Output**: Improved amount formatting
  - Amount values now display with 2 decimal places consistently

### Backward Compatibility

- No breaking changes
- All functionality remains the same
- Only test and internal improvements

---

## Upgrading from 1.2.0 to 1.2.1

### 🐛 Bug Fixes (1.2.1)

- **Fixed Deprecated Attribute Syntax**: Corrected invalid usage of `#[Deprecated]` attribute
  - The `#[Deprecated]` attribute in PHP only accepts `reason` and `since` parameters
  - The `replacement` parameter was incorrectly used and has been removed
  - This fix resolves PHP errors when using deprecated classes/methods

### Backward Compatibility

- No breaking changes
- All functionality remains the same

---

## Upgrading from 1.1.0 to 1.2.0

### ✨ New Features (1.2.0)

The following features were added in version 1.2.0:

1. **Mandate Management**: Complete mandate lifecycle management system for SEPA Direct Debit

### Mandate Management

The new `MandateService` provides complete lifecycle management for SEPA Direct Debit mandates:

```php
use Nowo\SepaPaymentBundle\Service\MandateService;
use Nowo\SepaPaymentBundle\Repository\MandateRepository;

$repository = new MandateRepository();
$mandateService = new MandateService($repository);

// Create a new mandate
$mandate = $mandateService->createMandate(
    'MANDATE-001',
    new \DateTime('2024-01-15'),
    'ES9121000418450200051332',
    'John Doe',
    'CORE',
    'FRST'
);

// Update sequence type (validates transition)
$mandateService->updateSequenceType('MANDATE-001', 'RCUR');

// Suspend mandate
$mandateService->suspendMandate('MANDATE-001');

// Reactivate mandate
$mandateService->reactivateMandate('MANDATE-001');

// Revoke mandate
$mandateService->revokeMandate('MANDATE-001', 'Customer request');

// Validate mandate for transaction
$isValid = $mandateService->validateMandateForTransaction('MANDATE-001', 'RCUR');

// Get mandate history
$history = $mandateService->getMandateHistory('MANDATE-001');
```

**Key Features:**
- Status tracking (ACTIVE, EXPIRED, REVOKED, SUSPENDED)
- Expiration date validation (defaults to 36 months after signature)
- Sequence type transition validation (FRST → RCUR/FNAL, RCUR → RCUR/FNAL, etc.)
- Complete history tracking for all changes
- Find mandates by debtor IBAN, status, or expiration date
- In-memory repository (can be extended with database implementation)

**Sequence Type Rules:**
- FRST (First) → RCUR (Recurring) or FNAL (Final)
- RCUR (Recurring) → RCUR or FNAL
- OOFF (One-off) → FNAL
- FNAL (Final) is terminal (no further transitions allowed)

### Backward Compatibility

- All existing functionality remains unchanged
- Mandate management is optional and doesn't affect existing code
- No breaking changes

---

## Upgrading from 1.0.0 to 1.1.0

### ✨ New Features (1.1.0)

1. **BIC Lookup Service**: Automatically look up BIC codes from IBANs

### BIC Lookup Service

The new `BicLookupService` automatically finds BIC codes when only IBANs are provided. This improves user experience by reducing manual work and errors.

**Basic Usage:**

```php
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;

$ibanValidator = new IbanValidator();
$bicLookup = new BicLookupService($ibanValidator);

// Look up BIC from IBAN
$iban = 'ES9121000418450200051332';
$bic = $bicLookup->lookupBic($iban);
// Returns: 'CAIXESBB' (if found)

// Check if lookup is available for an IBAN
if ($bicLookup->isAvailable($iban)) {
    $bic = $bicLookup->lookupBic($iban);
}
```

**Automatic Integration in Generators:**

When you inject `BicLookupService` into generators, BIC codes are automatically filled when missing:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Lookup\BicLookupService;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\Translation\IdentityTranslator;

$ibanValidator = new IbanValidator();
$translator    = new IdentityTranslator();
$bicLookup     = new BicLookupService($ibanValidator);
$generator = new CreditTransferGenerator($ibanValidator, $translator, null, false, null, null, $bicLookup);

// Create data without BIC
$creditTransferData = new CreditTransferData(
    'MSG-001',
    new \DateTime(),
    'My Company',
    'PMT-001',
    'ES9121000418450200051332', // IBAN only, no BIC
    'My Company Name',
    new \DateTime('tomorrow')
);

// BIC will be automatically looked up and included in XML
$xml = $generator->generate($creditTransferData);
```

**Supported Countries:**

The service includes mappings for major banks in:
- 🇪🇸 Spain (ES)
- 🇩🇪 Germany (DE)
- 🇫🇷 France (FR)
- 🇮🇹 Italy (IT)
- 🇬🇧 United Kingdom (GB)
- 🇳🇱 Netherlands (NL)
- 🇧🇪 Belgium (BE)
- 🇵🇹 Portugal (PT)

**Adding Custom Mappings:**

You can add custom bank mappings for banks not in the default database:

```php
$bicLookup->addMapping('ES', '9999', 'CUSTOMBIC');
// Now IBANs with bank code 9999 will return 'CUSTOMBIC'
```

**Cache Support (Optional):**

You can use a PSR-16 compatible cache to cache lookup results:

```php
use Psr\SimpleCache\CacheInterface;

$cache = /* your cache implementation */;
$bicLookup = new BicLookupService($ibanValidator, $cache, 86400); // 24 hour TTL
```

### Backward Compatibility

- BIC lookup is **completely optional** - existing code works without changes
- Generators accept optional `BicLookupServiceInterface` parameter (backward compatible)
- If BIC lookup service is not provided, generators work exactly as before
- No breaking changes

### Additional New Features (Unreleased)

1. **Mandate Management**: Complete mandate lifecycle management system
2. **Validation Caching**: Cache validation results for improved performance
3. **Console Command for Direct Debit Parsing**: Parse Direct Debit XML files from command line
4. **Validation Events**: Event system for validation operations
5. **Export Service**: Export SEPA payment data to JSON and CSV formats
6. **Symfony Events**: Event system for extensibility
7. **Structured Logging**: Comprehensive logging for SEPA operations
8. **SEPA String Sanitization**: Validate and sanitize strings according to SEPA character rules
9. **SEPA Country Validation**: Validate SEPA member countries
10. **SEPA Business Rules Validation**: Validate SEPA limits and business rules

### Export Service

The new `ExportService` allows you to export parsed SEPA data to various formats:

```php
use Nowo\SepaPaymentBundle\Exporter\ExportService;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;

$parser = new CreditTransferParser();
$exporter = new ExportService();

// Parse XML
$data = $parser->parseCreditTransfer($xml);

// Export to JSON
$json = $exporter->exportCreditTransferToJson($data, true);

// Export to CSV
$csv = $exporter->exportCreditTransferToCsv($data);
```

The service is automatically registered and can be injected via dependency injection.

### Symfony Events

The bundle now dispatches events before and after XML generation. You can listen to these events to modify data or XML:

```php
use Nowo\SepaPaymentBundle\Event\BeforeCreditTransferGenerationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SepaPaymentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCreditTransferGenerationEvent::class => 'onBeforeGeneration',
        ];
    }

    public function onBeforeGeneration(BeforeCreditTransferGenerationEvent $event): void
    {
        $data = $event->getCreditTransferData();
        // Modify data before generation
        $event->setCreditTransferData($data);
    }
}
```

Register your event subscriber in `config/services.yaml`:

```yaml
services:
    App\EventListener\SepaPaymentSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

### Structured Logging

The new `SepaPaymentLogger` service provides structured logging for all SEPA operations. It integrates with PSR-3 logging interfaces for maximum compatibility.

**Basic Usage:**

```php
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Psr\Log\LoggerInterface;

// Logger is automatically registered and can be injected
$logger = new SepaPaymentLogger($psrLogger); // Optional: defaults to NullLogger

// Or inject via dependency injection
class PaymentService
{
    public function __construct(
        private SepaPaymentLogger $logger
    ) {
    }
}
```

**Automatic Integration in Generators:**

When you inject `SepaPaymentLogger` into generators, operations are automatically logged:

```php
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Logger\SepaPaymentLogger;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Component\Translation\IdentityTranslator;

$ibanValidator = new IbanValidator();
$translator    = new IdentityTranslator();
$logger        = new SepaPaymentLogger($psrLogger); // Your PSR-3 logger

$generator = new CreditTransferGenerator(
    $ibanValidator,
    $translator,
    null, // XSD validator (optional)
    false, // validate XSD (optional)
    null, // event dispatcher (optional)
    $logger // logger (optional)
);

// Generation events are automatically logged
$xml = $generator->generate($creditTransferData);
```

**Logging Methods:**

The logger provides structured methods for:
- Credit Transfer generation (start, success, failure)
- Direct Debit generation (start, success, failure)
- Validation events (IBAN, BIC, XSD)
- Parsing events (Credit Transfer and Direct Debit)

All log entries include contextual data like messageId, transactionCount, and error messages.

### Backward Compatibility

- All existing functionality remains unchanged
- Mandate management is optional and doesn't affect existing code
- No breaking changes

---

## Upgrading from 1.0.0 to 1.1.0

### ✨ New Features (1.1.0)

The following features were added in version 1.1.0:

1. **BIC Lookup Service**: Automatically look up BIC codes from IBANs
2. **Validation Caching**: Cache validation results for improved performance
3. **Console Command for Direct Debit Parsing**: Parse Direct Debit XML files from command line
4. **Validation Events**: Event system for validation operations
5. **Export Service**: Export SEPA payment data to JSON and CSV formats
6. **Symfony Events**: Event system for extensibility
7. **Structured Logging**: Comprehensive logging for SEPA operations
8. **SEPA String Sanitization**: Validate and sanitize strings according to SEPA character rules
9. **SEPA Country Validation**: Validate SEPA member countries
10. **SEPA Business Rules Validation**: Validate SEPA limits and business rules

### Backward Compatibility

- All existing functionality remains unchanged
- Generators accept optional `EventDispatcherInterface` parameter (backward compatible)
- Generators accept optional `SepaPaymentLogger` parameter (backward compatible)
- Export service is optional and doesn't affect existing code
- Logger service is optional and doesn't affect existing code
- No breaking changes

### Planned Breaking Changes (Future Versions)

**Note**: The following changes are planned for future versions but have not been released yet:

- **Version 2.0.0 (Planned)**: All "Remesa" classes will be removed (deprecated since 1.1.0)
- **Version 1.1.0**: All "Remesa" classes were deprecated in favor of "CreditTransfer" classes

These changes are documented here for reference.

#### Class Renames (Planned)

**Generators and Parsers:**
- `RemesaGenerator` → `CreditTransferGenerator`
- `RemesaParser` → `CreditTransferParser`

**Models:**
- `RemesaData` → `CreditTransferData`
- Namespace `Model\Remesa` → `Model\CreditTransfer`
- `Transaction` class moved to `Model\CreditTransfer` namespace

**Service Aliases:**
- `nowo_sepa_payment.generator.remesa_generator` → `nowo_sepa_payment.generator.credit_transfer_generator`
- `nowo_sepa_payment.parser.remesa_parser` → `nowo_sepa_payment.parser.credit_transfer_parser`

#### Migration Steps

1. **Update imports:**
```php
// Before
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Parser\RemesaParser;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction;

// After
use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
```

2. **Update class instantiations:**
```php
// Before
$generator = new RemesaGenerator($ibanValidator);
$parser = new RemesaParser();
$data = new RemesaData(/* ... */);

// After (translator is required since 1.2.6)
$translator = new \Symfony\Component\Translation\IdentityTranslator(); // or inject TranslatorInterface
$generator = new CreditTransferGenerator($ibanValidator, $translator);
$parser = new CreditTransferParser();
$data = new CreditTransferData(/* ... */);
```

3. **Update variable names (optional but recommended):**
```php
// Before
$remesaData = new RemesaData(/* ... */);
$xml = $generator->generate($remesaData);

// After
$creditTransferData = new CreditTransferData(/* ... */);
$xml = $generator->generate($creditTransferData);
```

4. **Update service aliases in configuration (if used):**
```yaml
# Before
services:
    my_service:
        arguments:
            $generator: '@nowo_sepa_payment.generator.remesa_generator'
            $parser: '@nowo_sepa_payment.parser.remesa_parser'

# After
services:
    my_service:
        arguments:
            $generator: '@nowo_sepa_payment.generator.credit_transfer_generator'
            $parser: '@nowo_sepa_payment.parser.credit_transfer_parser'
```

5. **Update dependency injection (if using type hints):**
```php
// Before
public function __construct(
    private RemesaGenerator $remesaGenerator,
    private RemesaParser $remesaParser
) {}

// After
public function __construct(
    private CreditTransferGenerator $creditTransferGenerator,
    private CreditTransferParser $creditTransferParser
) {}
```

#### Why This Change?

This change improves code clarity and consistency by using standard English terminology ("Credit Transfer") instead of the Spanish term "Remesa". This makes the bundle more accessible to international developers and aligns with SEPA documentation standards.

#### Timeline

- **Version 1.1.0** (Current): Old classes are deprecated but still functional
- **Version 2.0.0** (Future): Old classes will be completely removed

**Recommendation**: Start migrating to the new classes now to avoid issues when upgrading to version 2.0.0.

#### Impact Assessment

- **Low Immediate Impact**: Old classes still work but will show deprecation warnings
- **Medium Long-term Impact**: Old classes will be removed in version 2.0.0, so migration is recommended
- **Service Aliases**: Old service aliases still work but are deprecated. Update to new aliases when possible
- **Type Hints**: Update type hints in constructors and method signatures to avoid deprecation warnings
- **Variable Names**: While variable names are optional to update, it's recommended for consistency

#### Deprecation Warnings

When using deprecated classes, you will see PHP deprecation warnings like:
```
Deprecated: RemesaGenerator is deprecated since 1.1.0. Use CreditTransferGenerator instead.
```

To suppress these warnings temporarily (not recommended for production), you can:
- Set `error_reporting` to exclude `E_USER_DEPRECATED`
- Use `@` operator to suppress warnings (not recommended)
- **Best practice**: Migrate to new classes as soon as possible

#### Automated Migration

You can use find-and-replace in your IDE to quickly update:
- Find: `RemesaGenerator` → Replace: `CreditTransferGenerator`
- Find: `RemesaParser` → Replace: `CreditTransferParser`
- Find: `RemesaData` → Replace: `CreditTransferData`
- Find: `Model\Remesa` → Replace: `Model\CreditTransfer`
- Find: `remesa_generator` → Replace: `credit_transfer_generator`
- Find: `remesa_parser` → Replace: `credit_transfer_parser`

## Upgrading to 1.0.0

### 🎉 First Stable Release

This is the first stable release (1.0.0) of the SEPA Payment Bundle. The bundle is now considered production-ready with comprehensive features, extensive test coverage, and complete documentation.

### What's New

1. **DirectDebitParser**: Complete SEPA Direct Debit XML parser
   - Parse SEPA Direct Debit XML files (pain.008.001.02 format)
   - Extract all payment and transaction information
   - Validate XML structure
   - Full address support (creditor and debtor)

2. **Enhanced Test Coverage**: Additional test cases for both parsers
   - Better edge case coverage
   - Improved validation testing
   - More comprehensive scenarios

3. **Documentation Improvements**: Enhanced examples and documentation
   - Parser examples in all demo applications
   - Improved code documentation

### Breaking Changes

**None** - This release is fully backward compatible with 0.0.12.

### Migration Steps

No migration required. Simply update your `composer.json`:

```bash
composer require nowo-tech/sepa-payment-bundle:^1.0
```

### New Features You Can Use

**DirectDebitParser** - Parse SEPA Direct Debit XML files:

```php
use Nowo\SepaPaymentBundle\Parser\DirectDebitParser;

$parser = new DirectDebitParser();

// Parse a Direct Debit XML file
$data = $parser->parseDirectDebit($xml);

// Access parsed data
echo $data['messageId'];
echo $data['creditorName'];
echo $data['transactions'][0]['amount'];

// Validate XML
if ($parser->isValidDirectDebit($xml)) {
    // Process the file
}
```

See [docs/USAGE.md](docs/USAGE.md) for complete examples.

## Upgrading to 0.0.12

### CreditTransferGenerator: New Features and Feature Parity

`CreditTransferGenerator` (formerly `RemesaGenerator` in versions < 2.0.0) now has complete feature parity with `DirectDebitGenerator`, including array-based generation and postal address support.

#### What's New

1. **`generateFromArray()` Method**: You can now generate SEPA Credit Transfer XML directly from arrays
2. **Postal Address Support**: Both creditor and debtor addresses can be included in the XML
3. **Snake_case Support**: Field names can be in either camelCase or snake_case format

#### New Features

**Array-Based Generation:**

```php
// Before (only object-based)
$remesaData = new RemesaData(/* ... */);
$xml = $generator->generate($remesaData);

// Now (array-based, also available)
$data = [
    'reference' => 'MSG-001',
    'initiatingPartyName' => 'My Company',
    // ...
];
$xml = $generator->generateFromArray($data);
```

**Postal Address Support:**

```php
// Creditor address
$creditTransferData->setCreditorAddress([
    'street' => '123 Business Street',
    'city' => 'Madrid',
    'postalCode' => '28001',
    'country' => 'ES',
]);

// Debtor address in transaction
$transaction->setDebtorAddress([
    'street' => '456 Customer Avenue',
    'city' => 'London',
    'postalCode' => 'SW1A 1AA',
    'country' => 'GB',
]);
```

**Using with Arrays:**

```php
$data = [
    'reference' => 'MSG-001',
    // ...
    'creditorAddress' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postalCode' => '28001',
        'country' => 'ES',
    ],
    'transactions' => [
        [
            'amount' => 100.50,
            // ...
            'debtorAddress' => [
                'street' => '456 Customer Avenue',
                'city' => 'London',
                'postalCode' => 'SW1A 1AA',
                'country' => 'GB',
            ],
        ],
    ],
];
$xml = $generator->generateFromArray($data);
```

#### Impact Assessment

**✅ No breaking changes - this is a non-breaking addition:**

1. **Existing code continues to work**: The `generate()` method with objects still works exactly as before
2. **New methods are optional**: You can continue using the object-based approach or switch to arrays
3. **Addresses are optional**: If you don't provide addresses, the XML is generated without them (same as before)

#### Benefits

- **Consistency**: Both generators (`CreditTransferGenerator` and `DirectDebitGenerator`) now have the same API
- **Flexibility**: Choose between object-based or array-based generation
- **Address Support**: Include postal addresses in Credit Transfer XML files
- **Snake_case Support**: Use either naming convention for field names

**No action required**: Existing code will continue to work without any changes. The new features are optional additions.

### Demo Routes Translation

All demo application routes have been translated from Spanish to English:

**Before (Spanish routes):**
- `/demo-remesa-pago` → **Now**: `/demo-credit-transfer`
- `/demo-remesa-pago-array` → **Now**: `/demo-credit-transfer-array`
- `/demo-remesa-pago-with-addresses` → **Now**: `/demo-credit-transfer-with-addresses`
- `/demo-remesa-pago-snake-case` → **Now**: `/demo-credit-transfer-snake-case`
- `/demo-remesa-cobro` → **Now**: `/demo-direct-debit`
- `/demo-remesa-cobro-snake-case` → **Now**: `/demo-direct-debit-snake-case`
- `/demo-remesa-cobro-with-addresses` → **Now**: `/demo-direct-debit-with-addresses`

**Impact**: If you have bookmarks or links to demo endpoints, update them to use the new English routes. Route names have also changed (e.g., `demo_remesa_pago` → `demo_credit_transfer`).

### Code Documentation

All PHPDoc comments have been translated to English for consistency:
- Class descriptions now use English terminology
- Parameter descriptions use English
- Example filenames in comments use English names
- Improved documentation for address handling methods to clarify how DOM manipulation is used as a fallback when library methods are not available

**No code changes required**: This is a documentation-only change that doesn't affect functionality. The improved PHPDoc comments provide better clarity on how address handling works internally.

## Upgrading to 0.0.11

### Service Auto-Registration with `#[AsAlias]` Attributes

All services now use Symfony's `#[AsAlias]` attribute for automatic service registration. This is a **non-breaking change** that improves code organization and follows Symfony best practices.

#### What Changed

- **All services now use `#[AsAlias]` attributes**: Every service class includes the `#[AsAlias]` attribute with its service alias and `public: true`
- **Simplified `services.yaml`**: Service definitions are now handled automatically via resource discovery and `#[AsAlias]` attributes
- **Resource-based service discovery**: Services are automatically discovered using `resource` directives in `services.yaml`
- **Consistent pattern**: All services follow the same pattern with a `SERVICE_NAME` constant

#### Impact Assessment

**✅ No action required - this is a non-breaking change:**

1. **Service behavior unchanged**: All services work exactly the same way
2. **Service aliases unchanged**: All service aliases remain the same
3. **Autowiring unchanged**: Services can still be injected via constructor type-hinting
4. **Explicit service retrieval unchanged**: Services can still be retrieved by their aliases

#### Benefits

- **Better code organization**: Service registration is now declarative in the classes themselves
- **Easier maintenance**: No need to maintain service definitions in `services.yaml` for most services
- **Symfony best practices**: Aligns with Symfony's recommended approach for service registration
- **Consistency**: All services follow the same registration pattern

#### Technical Details

Each service class now includes:

```php
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: self::SERVICE_NAME, public: true)]
class MyService
{
    public const SERVICE_NAME = 'nowo_sepa_payment.category.service_name';
    // ...
}
```

The `services.yaml` file now uses resource-based discovery:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: true

    Nowo\SepaPaymentBundle\Validator\:
        resource: '../../Validator/*'
    # ... similar for other namespaces
```

**No action required**: This change is completely transparent to users of the bundle.

## Upgrading to 0.0.10

### Service Configuration Changes

The service definitions in `services.yaml` have been updated to use service aliases directly instead of fully qualified class names. This is an **internal change** that improves consistency and aligns with Symfony best practices.

#### What Changed

- **Service IDs**: Service definitions now use aliases (e.g., `nowo_sepa_payment.validator.iban_validator`) as the service ID instead of class names
- **Service Dependencies**: All service arguments now reference other services by their aliases instead of class names
- **Consistency**: All services now follow a consistent naming pattern: `nowo_sepa_payment.{category}.{service_name}`

#### Impact Assessment

**✅ No action required for most users:**

1. **Autowiring (Type-hinting)**: If you inject services via constructor type-hinting, no changes needed:
   ```php
   class MyService
   {
       public function __construct(
           private IbanValidator $ibanValidator,
           private DirectDebitGenerator $generator
       ) {
       }
   }
   ```

2. **Using `#[Autowire]` with aliases**: If you're already using service aliases, no changes needed:
   ```php
   class MyService
   {
       public function __construct(
           #[Autowire('nowo_sepa_payment.generator.direct_debit_generator')]
           private DirectDebitGenerator $generator
       ) {
       }
   }
   ```

**⚠️ Action required only if:**

You're manually retrieving services by their fully qualified class name using `$container->get()` or similar methods.

**Before (needs update):**
```php
// ❌ This will no longer work
$ibanValidator = $container->get('Nowo\\SepaPaymentBundle\\Validator\\IbanValidator');
$generator = $container->get('Nowo\\SepaPaymentBundle\\Generator\\DirectDebitGenerator');
```

**After (updated code):**
```php
// ✅ Use service aliases instead
$ibanValidator = $container->get('nowo_sepa_payment.validator.iban_validator');
$generator = $container->get('nowo_sepa_payment.generator.direct_debit_generator');
```

#### How to Check if You Need to Update

Search your codebase for patterns like:
- `$container->get('Nowo\\SepaPaymentBundle\\`
- `$container->get(Nowo\SepaPaymentBundle\`
- `$this->get('Nowo\\SepaPaymentBundle\\`
- Any service locator patterns using class names

If you find any matches, update them to use the service aliases listed below.

#### Complete Service Alias Reference

All services are available via these aliases:

**Validators:**
- `nowo_sepa_payment.validator.iban_validator` - IBAN validator
- `nowo_sepa_payment.validator.bic_validator` - BIC validator
- `nowo_sepa_payment.validator.credit_card_validator` - Credit card validator

**Converters:**
- `nowo_sepa_payment.converter.ccc_converter` - CCC to IBAN converter

**Generators:**
- `nowo_sepa_payment.generator.credit_transfer_generator` - Credit transfer generator
- `nowo_sepa_payment.generator.direct_debit_generator` - Direct debit generator
- `nowo_sepa_payment.generator.identifier_generator` - Identifier generator

**Parsers:**
- `nowo_sepa_payment.parser.credit_transfer_parser` - Credit transfer parser

#### Migration Example

If you have code like this:

```php
// Old way (needs update)
class PaymentService
{
    public function __construct(private ContainerInterface $container)
    {
    }
    
    public function validateIban(string $iban): bool
    {
        $validator = $this->container->get('Nowo\\SepaPaymentBundle\\Validator\\IbanValidator');
        return $validator->isValid($iban);
    }
}
```

Update it to:

```php
// New way (recommended - use autowiring)
class PaymentService
{
    public function __construct(private IbanValidator $ibanValidator)
    {
    }
    
    public function validateIban(string $iban): bool
    {
        return $this->ibanValidator->isValid($iban);
    }
}
```

Or if you must use the container:

```php
// Alternative (using alias)
class PaymentService
{
    public function __construct(private ContainerInterface $container)
    {
    }
    
    public function validateIban(string $iban): bool
    {
        $validator = $this->container->get('nowo_sepa_payment.validator.iban_validator');
        return $validator->isValid($iban);
    }
}
```

**Note**: Using autowiring (first example) is the recommended Symfony approach and doesn't require any changes.

## Upgrading to 0.0.9

### New Features

#### HTTP Response Helper Method

Both `DirectDebitGenerator` and `CreditTransferGenerator` now include a `createResponse()` method that simplifies returning XML files as HTTP responses in Symfony controllers.

**Before:**
```php
$xml = $generator->generateFromArray($data);
return new Response($xml, 200, [
    'Content-Type' => 'application/xml',
    'Content-Disposition' => 'attachment; filename="direct-debit.xml"',
]);
```

**After:**
```php
$xml = $generator->generateFromArray($data);
return $generator->createResponse($xml, 'direct-debit.xml');
```

This is a **non-breaking change** - existing code will continue to work. The new method is optional and provides a more convenient way to create HTTP responses.

## Upgrading to 0.0.8

### New Features

#### Postal Address Support (Optional)

Postal addresses for both creditor and debtor are now **optional** and will be **included in the generated XML only if provided** in the array. Addresses are added using structured format (PstlAdr) with elements: StrtNm, TwnNm, PstCd, and Ctry.

**Important Notes:**
- Addresses are **completely optional** - if you don't provide them, no address elements will be added to the XML
- Empty address arrays are ignored and will not create address elements
- At least one address field (street, city, postalCode, or country) must be provided for the address to be included
- This is a **non-breaking change** - existing code without addresses will continue to work exactly as before

**Using object methods:**
```php
// Creditor address
$directDebitData->setCreditorAddress([
    'street' => '123 Business Street',
    'city' => 'Madrid',
    'postalCode' => '28001',
    'country' => 'ES',
]);

// Debtor address
$transaction->setDebtorAddress([
    'street' => '456 Customer Avenue',
    'city' => 'London',
    'postalCode' => 'SW1A 1AA',
    'country' => 'GB',
]);
```

**Using array input (camelCase):**
```php
$data = [
    // ...
    'creditorAddress' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postalCode' => '28001',
        'country' => 'ES',
    ],
    'transactions' => [
        [
            // ...
            'debtorAddress' => [
                'street' => '456 Customer Avenue',
                'city' => 'London',
                'postalCode' => 'SW1A 1AA',
                'country' => 'GB',
            ],
        ],
    ],
];
```

**Using array input (snake_case):**
```php
$data = [
    // ...
    'creditor_address' => [
        'street' => '123 Business Street',
        'city' => 'Madrid',
        'postal_code' => '28001',
        'country' => 'ES',
    ],
    'items' => [
        [
            // ...
            'debtor_address' => [
                'street' => '456 Customer Avenue',
                'city' => 'London',
                'postal_code' => 'SW1A 1AA',
                'country' => 'GB',
            ],
        ],
    ],
];
```

**Using individual fields (snake_case):**
```php
$data = [
    // ...
    'creditor_street' => '123 Business Street',
    'creditor_city' => 'Madrid',
    'creditor_postal_code' => '28001',
    'creditor_country' => 'ES',
    'items' => [
        [
            // ...
            'debtor_street' => '456 Customer Avenue',
            'debtor_city' => 'London',
            'debtor_postal_code' => 'SW1A 1AA',
            'debtor_country' => 'GB',
        ],
    ],
];
```

**No breaking changes**: If you were previously storing addresses in `additionalData`, they will now be automatically included in the XML. The old methods continue to work, but addresses are now exported to XML.

### Important Notes

- Addresses are **completely optional** - if not provided, no address elements will be added to the XML
- Addresses are **included in the generated XML only if provided** in the array (previously they were only stored internally)
- Empty address arrays are ignored and will not create address elements
- At least one address field (street, city, postalCode, or country) must be provided for the address to be included
- Address format follows SEPA structured address format (PstlAdr)
- Addresses are added to XML using DOM manipulation to ensure compatibility with the SEPA pain.008.001.02 format
- See [DEPRECATED_FIELDS.md](DEPRECATED_FIELDS.md) for information about which fields are still not allowed

## Upgrading to 0.0.7

### New Features

#### Snake_case Field Name Support

The `DirectDebitGenerator::generateFromArray()` method now supports both camelCase and snake_case field names. This means you can use either format:

**Before (camelCase only):**
```php
$data = [
    'reference' => 'MSG-001',
    'bankAccountOwner' => 'My Company',
    'paymentInfoId' => 'PMTINF-1',
    // ...
];
```

**Now (both formats work):**
```php
// camelCase (still works)
$data = [
    'reference' => 'MSG-001',
    'bankAccountOwner' => 'My Company',
    // ...
];

// snake_case (new support)
$data = [
    'message_id' => 'MSG-001',
    'initiating_party_name' => 'My Company',
    // ...
];
```

**No breaking changes**: Existing code using camelCase continues to work without modification.

#### Additional Fields Support

You can now add custom fields to DirectDebit transactions:

```php
$transaction = new DirectDebitTransaction(/* ... */);
$transaction->setDebtorBic('WESTGB22'); // Optional BIC
$transaction->setAdditionalField('customField', 'value'); // Custom data
```

### PHP 8.2 Compatibility Fix

Fixed constant type declarations that caused syntax errors in PHP 8.2. If you were experiencing parse errors with constants like `SERVICE_NAME`, this is now resolved.

**No action required**: The fix is backward compatible.

## Upgrading to 0.0.6

### Service Registration

Services now use Symfony attributes for automatic registration. If you were manually retrieving services by alias, the aliases remain the same:

- `nowo_sepa_payment.generator.direct_debit_generator`
- `nowo_sepa_payment.generator.credit_transfer_generator`
- `nowo_sepa_payment.generator.identifier_generator`

**No action required**: Services are automatically registered and can be injected via constructor.

## Upgrading to 0.0.5

### Payment Method

The `setPaymentMethod()` calls have been removed from generators. Payment method is now automatically set by Digitick\Sepa v3.0 based on transfer file type.

**No action required**: This is handled internally.

## Upgrading to 0.0.4

### Digitick\Sepa v3.0 Compatibility

This version includes complete compatibility with Digitick\Sepa v3.0. The API changes are handled internally, so your code should continue to work.

**No action required**: The bundle handles all API changes internally.

## Upgrading to 0.0.3

### Breaking Changes from Digitick\Sepa v3.0

If you're upgrading from a version that used Digitick\Sepa v2.0, be aware that v3.0 introduced breaking changes. However, the bundle handles these changes internally, so your code should continue to work.

**No action required**: The bundle abstracts these changes.

## General Upgrade Notes

1. **Always test in a development environment first**
2. **Review the CHANGELOG** for detailed changes
3. **Check for deprecated methods** - they will be removed in future versions
4. **Update your tests** to match new behavior if needed

### Demo Applications

The demo applications included in this bundle have been refactored for better code organization:
- Controllers have been separated by functionality into focused classes
- All routes remain the same - no changes to URLs or endpoints
- This refactoring only affects the internal structure of the demo code
- If you're using the demos as reference, the API and functionality remain unchanged

## Getting Help

If you encounter issues during upgrade:

1. Check the [CHANGELOG.md](CHANGELOG.md) for detailed changes
2. Review the [README.md](../README.md) for usage examples
3. Open an issue on GitHub with details about your upgrade path

