# Release checklist (1.2.16)

## Documentation reviewed

- **CHANGELOG.md**: [1.2.16] - 2026-03-19 with Fixed (console commands Symfony 6.0/6.1), Changed (translation file names), Documentation (CONFIGURATION.md), Backward compatibility.
- **UPGRADING.md**: "Upgrading from 1.2.15 to 1.2.16" with Fixed, Changed, Documentation and backward compatibility.

## Steps to publish the release

1. **Run release checks** (from the bundle root):

   ```bash
   make release-check
   ```

2. **Commit all changes** for 1.2.16 (incl. docs, commands and translations):

   ```bash
   git status
   git add -A
   git commit -m "Release 1.2.16: Console commands Symfony 6.0/6.1 fix, translation domain NowoSepaPaymentBundle, docs"
   ```

3. **Create the annotated tag**:

   ```bash
   git tag -a v1.2.16 -m "Release v1.2.16"
   ```

4. **Push branch and tag**:

   ```bash
   git push origin main
   git push origin v1.2.16
   ```

5. **Verify on GitHub**: *Actions* → "Create Release" workflow green; *Releases* → **v1.2.16** with body from `docs/CHANGELOG.md`.

## If the tag already exists but the release failed

- Re-run the "Create GitHub Release" job from *Actions* (Re-run jobs), or delete the tag on the remote and recreate and push the tag `v1.2.16`.

## Notes

- `.github/workflows/release.yml` runs when pushing a tag `v*`.
- The release body is generated from the `## [1.2.16]` section of `docs/CHANGELOG.md`.
