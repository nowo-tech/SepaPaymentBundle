# Release checklist (1.2.11)

## Documentación revisada

- **CHANGELOG.md**: [1.2.11] - 2026-02-11 con Fixed, Improved (CI/test, CI/coverage 80%, release workflow make_latest fix).
- **UPGRADE.md**: "Upgrading from 1.2.10 to 1.2.11" con bugfixes, CI/Development (coverage 80%), enlace a COMMANDS.md.
- **COMMANDS.md**: Parse Direct Debit XML documentado con uso, `--json` y mensajes de error.
- **DEVELOPMENT.md**: Cobertura mínima 80% en ambos sitios.

## Pasos para publicar la release

1. **Commitear todos los cambios** de 1.2.11 (incl. fix del workflow `make_latest`):

   ```bash
   git status
   git add -A
   git commit -m "Release 1.2.11: ParseDirectDebitCommand fix, SepaBusinessRulesValidator PHP 8.4+, CI coverage 80%, release workflow make_latest fix, tests and docs"
   ```

2. **Crear el tag anotado**:

   ```bash
   git tag -a v1.2.11 -m "Release 1.2.11"
   ```

3. **Subir rama y tag**:

   ```bash
   git push origin main    # o master
   git push origin v1.2.11
   ```

4. **Comprobar en GitHub**: *Actions* → workflow "Release" en verde; *Releases* → **v1.2.11** con cuerpo desde `docs/CHANGELOG.md`.

## Si el tag ya existe pero el release falló

- El workflow se dispara con el push del tag. Si el release falló por el error 422 de `make_latest`, ya está corregido en el repo.
- Opciones: **Re-ejecutar** el job "Release" desde *Actions* (Re-run jobs), o borrar el tag en remoto, hacer push del fix y volver a crear y subir el tag `v1.2.11`.

## Notas

- `.github/workflows/release.yml` se ejecuta al hacer push de un tag `v*`.
- El cuerpo del release se toma de la sección `## [1.2.11]` de `docs/CHANGELOG.md`.
