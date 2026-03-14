# Release

Maintainers: follow this process before creating a new tag.

## Pre-release checklist

1. **Update documentation**
   - Ensure [CHANGELOG.md](CHANGELOG.md) has an entry for the new version (e.g. `[1.2.16] - YYYY-MM-DD`) and that `[Unreleased]` is updated or empty.
   - Update [UPGRADING.md](UPGRADING.md) if there are behaviour changes or breaking changes for that version.

2. **Run quality checks**

   From the bundle root (with Docker up):

   ```bash
   make release-check
   ```

   This runs composer-sync, cs-fix, cs-check, rector-dry, phpstan, test-coverage, and demo verification (if present).

3. **Commit** any pending changes. Ensure the tree is clean and pushed:

   ```bash
   git status
   git add -A && git commit -m "Release vX.Y.Z"   # if needed
   git push origin main
   ```

## Tag and publish

4. **Create an annotated tag** (replace with the version you are releasing):

   ```bash
   git tag -a v1.2.16 -m "Release v1.2.16"
   git push origin v1.2.16
   ```

   If the bundle is released from a separate clone (e.g. `nowo-tech/sepa-payment-bundle`), run these commands in the clone that is pushed to the release remote.

5. **GitHub release**  
   The [release workflow](.github/workflows/release.yml) runs on tag push and creates the GitHub Release. The release body is typically taken from the tag message and/or [CHANGELOG.md](CHANGELOG.md).

6. **Packagist**  
   If the package is on [Packagist](https://packagist.org/packages/nowo-tech/sepa-payment-bundle), the new tag is picked up automatically (or use “Update” there).

## Version-specific checklist (optional)

For detailed version-specific notes (e.g. “Documentation reviewed for 1.2.15”), see [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md) when it exists for that release.
