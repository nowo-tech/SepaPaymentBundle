# Release checklist (1.2.12)

## Documentación revisada

- **CHANGELOG.md**: [1.2.12] - 2026-03-04 con Added (Rector, PHPStan, release-check), Improved (static analysis, comments in English), Fixed (baseline).
- **UPGRADE.md**: "Upgrading from 1.2.11 to 1.2.12" con Added, Improved y backward compatibility.

## Pasos para publicar la release

1. **Commitear todos los cambios** de 1.2.12:

   ```bash
   git status
   git add -A
   git commit -m "Release 1.2.12: Rector and PHPStan integration, static analysis fixes, code comments in English"
   ```

2. **Crear el tag anotado**:

   ```bash
   git tag -a v1.2.12 -m "Release 1.2.12"
   ```

3. **Subir rama y tag**:

   ```bash
   git push origin main    # o master
   git push origin v1.2.12
   ```

4. **Comprobar en GitHub**: *Actions* → workflow "Release" en verde; *Releases* → **v1.2.12** con cuerpo desde `docs/CHANGELOG.md`.

## Si el tag ya existe pero el release falló

- Re-ejecutar el job "Release" desde *Actions* (Re-run jobs), o borrar el tag en remoto y volver a crear y subir el tag `v1.2.12`.

## Notas

- `.github/workflows/release.yml` se ejecuta al hacer push de un tag `v*`.
- El cuerpo del release se toma de la sección `## [1.2.12]` de `docs/CHANGELOG.md`.
