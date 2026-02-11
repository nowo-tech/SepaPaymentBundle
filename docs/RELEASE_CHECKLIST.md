# Release checklist (1.2.11)

## Documentación revisada

- **CHANGELOG.md**: [1.2.11] - 2026-02-11 con Fixed (ParseDirectDebitCommand, SepaBusinessRulesValidator, tests) e Improved (CI/test suite, CI/coverage 80%).
- **UPGRADE.md**: Sección "Upgrading from 1.2.10 to 1.2.11" con bugfixes, CI/Development (coverage 80%) y enlace a COMMANDS.md.
- **COMMANDS.md**: Parse Direct Debit XML documentado con errores (File not found, Could not read file, etc.).
- **DEVELOPMENT.md**: Cobertura mínima 80% reflejada.

## Pasos para publicar la release

1. **Asegurar que todo está commiteado** (cambios de 1.2.11 en `main` o `master`):

   ```bash
   git status   # No debe haber cambios sin commitear que quieras en 1.2.11
   git add -A
   git commit -m "Release 1.2.11: ParseDirectDebitCommand fix, SepaBusinessRulesValidator PHP 8.4+, CI coverage 80%, tests and docs"
   ```

2. **Crear el tag anotado** (el workflow de GitHub Actions usa el mensaje del tag):

   ```bash
   git tag -a v1.2.11 -m "Release 1.2.11"
   ```

3. **Subir rama y tag al remoto**:

   ```bash
   git push origin main    # o master, según tu rama por defecto
   git push origin v1.2.11
   ```

4. **Comprobar en GitHub** que el workflow "Release" se ha ejecutado y que en *Releases* aparece **v1.2.11** con el cuerpo generado desde `docs/CHANGELOG.md`.

## Notas

- El workflow `.github/workflows/release.yml` se dispara al hacer push del tag `v*`.
- La descripción del release se rellena con la sección `## [1.2.11]` de `docs/CHANGELOG.md`.
- Tras publicar, puedes borrar este archivo o dejarlo como plantilla para la siguiente release.
