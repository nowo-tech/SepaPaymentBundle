# Release checklist (1.2.17)

## Documentation reviewed

- **CHANGELOG.md**: `[1.2.17] - 2026-03-30` with Added (Scrutinizer `.scrutinizer.yml`), Changed (demos, dev tooling), Documentation (README/USAGE/UPGRADING/DEMO-FRANKENPHP/INSTALLATION), Backward compatibility.
- **UPGRADING.md**: "Upgrading from 1.2.16 to 1.2.17" with Added, Changed, Documentation and backward compatibility.

## Steps to publish the release

1. **Run release checks** (from the bundle root):

   ```bash
   make release-check
   ```

2. **Commit all changes** for 1.2.17 (incl. docs and `.scrutinizer.yml`):

   ```bash
   git status
   git add -A
   git commit -m "Release 1.2.17: Scrutinizer CI, docs and demo tooling"
   ```

3. **Create the annotated tag**:

   ```bash
   git tag -a v1.2.17 -m "Release v1.2.17"
   ```

4. **Push branch and tag**:

   ```bash
   git push origin main
   git push origin v1.2.17
   ```

5. **Verify on GitHub**: *Actions* → "Create Release" workflow green; *Releases* → **v1.2.17** with body from `docs/CHANGELOG.md`.

## If the tag already exists but the release failed

- Re-run the "Create GitHub Release" job from *Actions* (Re-run jobs), or delete the tag on the remote and recreate and push the tag `v1.2.17`.

## Notes

- `.github/workflows/release.yml` runs when pushing a tag `v*`.
- The release body is generated from the `## [1.2.17]` section of `docs/CHANGELOG.md`.
