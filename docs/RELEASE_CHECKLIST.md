# Release checklist (1.2.15)

## Documentación revisada

- **CHANGELOG.md**: [1.2.15] - 2026-03-04 con Added (tests), Improved (bundle/CI brick/math, demo brick/math, coverage), Backward compatibility.
- **UPGRADING.md**: "Upgrading from 1.2.12 to 1.2.15" con Added, Improved (bundle + demo brick/math, coverage) y backward compatibility.

## Pasos para publicar la release

1. **Commitear todos los cambios** de 1.2.15 (incl. docs y composer.json):

   ```bash
   git status
   git add -A
   git commit -m "Release 1.2.15: Tests, brick/math PHP 8.1 fix (bundle + demo), coverage and docs"
   ```

2. **Crear el tag anotado**:

   ```bash
   git tag -a v1.2.15 -m "Release 1.2.15"
   ```

3. **Subir rama y tag**:

   ```bash
   git push origin main
   git push origin v1.2.15
   ```

4. **Comprobar en GitHub**: *Actions* → workflow "Create Release" en verde; *Releases* → **v1.2.15** con cuerpo desde `docs/CHANGELOG.md`.

## Si el tag ya existe pero el release falló

- Re-ejecutar el job "Create GitHub Release" desde *Actions* (Re-run jobs), o borrar el tag en remoto y volver a crear y subir el tag `v1.2.15`.

## Notas

- `.github/workflows/release.yml` se ejecuta al hacer push de un tag `v*`.
- El cuerpo del release se genera a partir de la sección `## [1.2.15]` de `docs/CHANGELOG.md`.
