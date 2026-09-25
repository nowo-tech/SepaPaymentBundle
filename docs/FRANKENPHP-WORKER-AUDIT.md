# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/sepa-payment-bundle` (`symfony-bundle`) |
| Audited revision | `v1.2.27` |
| Audit date | 2026-09-25 |
| Method | Manual review of every file under `src/` (DI extension, `services.yaml`, generators, parsers, validators, constraint validators, lookup, cache, exporter, logger, repository, mandate service, commands); models and events skimmed |
| Remediation (2026-09-23) | W-01/W-02 resolved with a bundle-owned `kernel.request` subscriber (`src/EventSubscriber/WorkerStateResetSubscriber.php`) that resets `ResetInterface` services tagged `nowo_sepa_payment.request_scoped` on every main request; W-03 resolved (libxml flag restored in `finally`). Regression tests: `tests/Integration/WorkerModeIntegrationTest.php`, `tests/Unit/EventSubscriber/WorkerStateResetSubscriberTest.php` |
| **Verdict** | ✅ **Viable under scenario B** — the default in-memory `MandateRepository` and runtime `BicLookupService::addMapping()` mappings are now request-scoped (reset at the start of each main request, and on `kernel.reset`); everything else is stateless. The in-memory repository is still not a persistence layer: bind `MandateRepositoryInterface` to a persistent implementation in production. |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | `MandateRepository` (`$mandates` / `$history`) and `BicLookupService` (`$bicDatabase` after `addMapping()`) are reset at the start of each main request by `WorkerStateResetSubscriber` |
| Static properties / `static` locals | ✅ | None in `src/` (only `static fn` closures and pure static enum helpers in `MandateStatus`) |
| `ResetInterface` / `kernel.reset` coverage | ✅ | `MandateRepository` and `BicLookupService` implement `ResetInterface` (autoconfigured `kernel.reset` + `nowo_sepa_payment.request_scoped`); scenario B does not depend on `services_resetter` |
| Request / user / locale captured in services | ✅ | Data is passed as method arguments; translator is only called at runtime; dates are created per call |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ✅ | None of those; `XsdValidator` saves and restores the process-wide `libxml_use_internal_errors()` flag in `finally` |
| Doctrine / EntityManager | ✅ N/A | No Doctrine; mandate persistence is an in-memory array (see W-01) |
| Output, headers, `exit`, shutdown functions | ✅ | None; `createResponse()` returns a Symfony `Response` |
| Resources (files, sockets, cURL) held open | ✅ | `php://temp` stream opened and closed per CSV export; `file_get_contents()` only in a CLI command |
| Memory growth across requests | ✅ | Both stores are bounded by one request (reset per main request) |
| Blocking I/O and timeouts | ✅ | No network I/O; XSD validation reads local files only |
| Third-party static state | ⚠️ Info | `digitick/sepa-xml` has a static `Sanitizer::$callback`; the bundle never changes it |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker.neon` included in `phpstan.neon.dist` |

## Services reviewed

All classes are registered by resource in `src/Resources/config/services.yaml` with `public: true`, `autowire: true`, `autoconfigure: true`, so every service is shared. Interfaces with a single implementation in the same resource (`MandateRepositoryInterface`, `BicLookupServiceInterface`, `ValidationCacheInterface`, `CsvStreamHandlerInterface`) are auto-aliased to that implementation. `Model/` and `Event/` are not registered.

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `Repository\MandateRepository` (default for `MandateRepositoryInterface`) | yes | `$mandates`, `$history` arrays filled by `save()` / `addHistory()`; request-scoped | ✅ `reset()` via `kernel.reset` | ✅ reset per main request |
| `Service\MandateService` | yes | none itself (`readonly` repository); inherits repository state | ✅ | ✅ |
| `Lookup\BicLookupService` (default for `BicLookupServiceInterface`) | yes | `$bicDatabase` mutated by public `addMapping()` (request-scoped); `$customMappings` readonly; `$cache` untyped, stays `null` under autowiring | ✅ `reset()` via `kernel.reset` | ✅ reset per main request |
| `Generator\CreditTransferGenerator`, `Generator\DirectDebitGenerator` | yes | `$validateXsd` set once in constructor; all deps `readonly` | ✅ (uses `BicLookupService`, see W-02) | ✅ |
| `Generator\RemesaGenerator` (deprecated) | yes | `readonly` inner `CreditTransferGenerator` | ✅ | ✅ |
| `Generator\IdentifierGenerator` | yes | none (`date()` + `random_bytes()` per call) | ✅ | ✅ |
| `Validator\XsdValidator` | yes | none (`readonly` translator); saves/restores global libxml flag | ✅ | ✅ |
| `Validator\CachedIbanValidator`, `Validator\CachedBicValidator` | yes | none (`readonly` deps) | ✅ | ✅ |
| `Cache\ValidationCache` | yes | `$cache` untyped, not autowired (stays `null`); `readonly` TTL | ✅ | ✅ |
| 7 plain validators (`IbanValidator`, `BicValidator`, `CreditCardValidator`, `SepaCountryValidator`, `SepaCreditorIdentifierValidator`, `SepaBusinessRulesValidator`, `SepaStringSanitizer`) | yes | none (constants only) | ✅ | ✅ |
| 5 constraint validators (`Validator\Constraint\*Validator`) | yes | only Symfony `ConstraintValidator::$context`, re-initialised by the validator on each call | ✅ | ✅ |
| `Parser\CreditTransferParser`, `Parser\DirectDebitParser`, `Parser\RemesaParser` | yes | none (DOM objects are local) | ✅ | ✅ |
| `Converter\CccConverter` | yes | none | ✅ | ✅ |
| `Exporter\ExportService`, `Exporter\PhpTempCsvStreamHandler` | yes | none | ✅ | ✅ |
| `Logger\SepaPaymentLogger` | yes | `readonly` PSR logger | ✅ | ✅ |
| 4 console commands | yes | `readonly` deps; CLI only | ✅ N/A | ✅ N/A |
| `EventSubscriber\WorkerStateResetSubscriber` | yes (private) | `readonly` tagged iterator of resettables | ✅ | ✅ |

Value objects (`CreditTransferData`, `DirectDebitData`, `Mandate`, `MandateHistory`, transactions, events) are mutable but created per call by the caller or the generators; the bundle never stores them in a service, except `Mandate` / `MandateHistory` inside `MandateRepository` (W-01).

## Findings

### W-01 — In-memory `MandateRepository` keeps mandates across requests and users (High)

- **Where:** `src/Repository/MandateRepository.php:31` (`$mandates`), `:38` (`$history`), written by `save()` at `:47` and `addHistory()` at `:150`; read by `findById()`, `findByDebtorIban()`, `findActive()`, `findExpired()`, `getHistory()`. Only `clear()` at `:189` empties it and nothing calls it. It is the only implementation of `MandateRepositoryInterface` in `src/Repository/`, so it is auto-wired into `MandateService` (`src/Service/MandateService.php:49-52`).
- **Worker impact:** in classic mode the store is empty at the start of every request. In worker mode it lives for the whole worker lifetime, in both scenario A (no `ResetInterface`, no `kernel.reset` tag) and B. Consequences:
  - Personal data (debtor IBAN and name, mandate status, revocation reason, history) created in a request of user X is returned to any later request on the same worker, e.g. `MandateService::findActiveMandates()` or `findMandatesByDebtorIban()` for user Y. This is a cross-user data leak.
  - Behaviour changes after the first request: `MandateService::createMandate()` throws `Mandate with ID '...' already exists` for an ID used in a previous request; `validateMandateForTransaction()` can accept a mandate that another request created.
  - Each worker has its own copy, so results differ depending on which worker serves the request.
  - Unbounded memory growth: every saved mandate and history entry stays until the worker is recycled.
- **Recommendation:** do not use the default repository in any HTTP context. Bind `MandateRepositoryInterface` to a persistent implementation (Doctrine or similar) in the application. In the bundle: stop auto-registering `MandateRepository` as the default (or register it only in `test`), or implement `ResetInterface` with `reset(): void { $this->clear(); }` so scenario A is at least isolated per request. Document the worker implication next to the "in-memory for dev" note.
- **Status:** Resolved — `MandateRepository` implements `ResetInterface` (`reset()` calls `clear()`), and the new `WorkerStateResetSubscriber` (`src/EventSubscriber/WorkerStateResetSubscriber.php`, `kernel.request` priority 4096, main requests only) resets it at the start of every main request, so scenario B matches classic-mode semantics (empty store per request). Removing the default alias was rejected to keep BC. Documented in `docs/USAGE.md` (SEPA Mandates) and `docs/UPGRADING.md`. Tests: `tests/Integration/WorkerModeIntegrationTest.php`, `tests/Unit/Repository/MandateRepositoryTest.php::testResetEmptiesStore`.

### W-02 — `BicLookupService::addMapping()` mutates a shared lookup table (Medium)

- **Where:** `src/Lookup/BicLookupService.php:29` (`$bicDatabase`), filled in the constructor at `:52` and mutated by the public `addMapping()` at `:248-255`. The service is injected into `CreditTransferGenerator` (`src/Generator/CreditTransferGenerator.php:93`, used at `:154-155` and `:190-191`) and `DirectDebitGenerator` (`src/Generator/DirectDebitGenerator.php:88`) to auto-fill missing BICs.
- **Worker impact:** `docs/USAGE.md` shows calling `$bicLookup->addMapping(...)` on the container service. If an application does that in a controller, listener or per-tenant code path, the mapping stays for every later request on that worker (scenario A and B, no reset). A tenant-specific or wrong mapping then silently changes the BIC written into other requests' SEPA XML files. Memory grows by one entry per distinct key. Calls done once at boot (e.g. in a compiler pass or a decorator constructor) are fine.
- **Recommendation:** only add mappings at container build / service construction time (decorate the service or pass the extra mappings via configuration). In the bundle, consider making custom mappings a constructor/config argument and deprecating runtime `addMapping()`, or implement `ResetInterface` restoring `getDefaultBicDatabase()`.
- **Status:** Resolved — `BicLookupService` implements `ResetInterface`; `reset()` restores the default database merged with the new optional constructor argument `$customMappings` (permanent mappings). Runtime `addMapping()` mappings are request-scoped and dropped by `WorkerStateResetSubscriber` at the next main request. Note: mappings added once in a decorator constructor must move to `$customMappings` (documented in `docs/USAGE.md` and `docs/UPGRADING.md`). Tests: `tests/Unit/Lookup/BicLookupServiceTest.php::testResetDropsRuntimeMappingsAndKeepsConstructorMappings`, worker-mode tests above.

### W-03 — `XsdValidator` forces the process-wide libxml error mode to `false` (Low)

- **Where:** `src/Validator/XsdValidator.php:64-68`, `:85-89`, `:173-177`, `:188-192` call `libxml_use_internal_errors(true)` and then `libxml_use_internal_errors(false)` instead of restoring the previous value.
- **Worker impact:** the libxml error mode is a PHP global that is no longer reset at request end in worker mode. If the application (or another library) relies on internal errors being enabled, this validator switches it off for all later requests on the worker. Errors are cleared with `libxml_clear_errors()`, so no buffer grows here. The parsers and generators use `@$dom->loadXML()` without touching the flag; if the application enables internal errors globally and never clears them, the libxml error buffer can accumulate across requests.
- **Recommendation:** store the previous value (`$previous = libxml_use_internal_errors(true);`) and restore it in a `finally` block.
- **Status:** Resolved — the four call sites in `src/Validator/XsdValidator.php` now save the previous value and restore it in `finally`. Test: `tests/Unit/Validator/XsdValidatorTest.php::testLibxmlInternalErrorsModeIsRestored` (both previous modes, success and failure paths).

### W-04 — Third-party static sanitizer in `digitick/sepa-xml` (Info)

- **Where:** `vendor/digitick/sepa-xml/src/Util/Sanitizer.php:31` (`private static $callback`), used when the library builds the XML via `DomBuilderFactory::createDomBuilder()` (`src/Generator/CreditTransferGenerator.php:217`).
- **Worker impact:** none from the bundle; `src/` never calls `Sanitizer::setSanitizer()` / `disableSanitizer()`. If application code changes it during a request, the change persists for the worker lifetime.
- **Recommendation:** configure the sanitizer once at boot, never per request.
- **Status:** Accepted — third-party static state not touched by the bundle; application responsibility.

Good patterns observed: every generator, parser, validator and exporter is stateless with `readonly` dependencies; dates are created per call (`SepaBusinessRulesValidator.php:75`, `:120`), not in constructors; `ValidationCache` delegates to an external PSR-16 cache keyed by `md5` of the value and stores only booleans.

## Usage recommendations in worker mode

- Bind `MandateRepositoryInterface` to a persistent, stateless repository before using `MandateService` for real data. The default `MandateRepository` is request-scoped (emptied at each main request): safe across users, but not a persistence layer.
- `BicLookupService::addMapping()` is request-scoped. For mappings that must always apply, pass them via the `$customMappings` constructor argument.
- If your own services keep per-request state, reset them yourself: the bundle only resets its own services.
- If you wire a PSR-16 cache into `ValidationCache` or `BicLookupService`, use a shared pool (APCu/Redis/filesystem) rather than an in-process `ArrayAdapter`, which would grow for the worker lifetime.
- The demo (`demo/symfony8`) has a `FRANKENPHP_MODE=worker` switch in `docker/entrypoint.sh`, but the shipped `docker/frankenphp/Caddyfile` has no `worker` directive, so the demo does not actually exercise worker mode.

## Re-audit triggers

Re-run this audit when a change adds or modifies: properties on any service (especially `MandateRepository`, `BicLookupService`, `ValidationCache`), a new default implementation of a repository/lookup interface, an event listener or subscriber, a `ResetInterface` implementation, global libxml/`ini_set` handling, or any use of `$_SERVER` / `$_ENV` at runtime.
