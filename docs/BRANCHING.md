# Branching Strategy

This project follows a simplified Git Flow workflow.

## Branch Types

| Branch | Purpose | Base | Merges to |
|--------|---------|------|-----------|
| `main` | Production releases only | - | - |
| `develop` | Development integration | `main` | `main` (releases) |
| `feature/*` | New features | `develop` | `develop` |
| `fix/*` | Bug fixes | `develop` | `develop` |
| `hotfix/*` | Urgent production fixes | `main` | `main` + `develop` |
| `release/*` | Release preparation | `develop` | `main` + `develop` |

## Workflow Diagram

```
main     ●─────────────────●─────────────────●  (v1.0.0)  (v1.1.0)
          \               /                 /
develop    ●─────●───●───●─────●───●───────●
                  \     /       \         /
feature/xxx        ●───●         \       /
                                  \     /
fix/yyy                            ●───●
```

## Creating Branches

### New Feature

```bash
git checkout develop
git pull origin develop
git checkout -b feature/my-feature
# ... work on feature ...
git push -u origin feature/my-feature
# Create Pull Request to develop
```

### Bug Fix

```bash
git checkout develop
git pull origin develop
git checkout -b fix/fix-description
# ... fix bug ...
git push -u origin fix/fix-description
# Create Pull Request to develop
```

### Hotfix (urgent production fix)

```bash
git checkout main
git pull origin main
git checkout -b hotfix/critical-fix
# ... fix issue ...
git push -u origin hotfix/critical-fix
# Create Pull Request to main AND develop
```

### Release

```bash
git checkout develop
git pull origin develop
git checkout -b release/1.2.0
# ... update version, changelog ...
git push -u origin release/1.2.0
# Create Pull Request to main
# After merge, tag the release and merge back to develop
```

## Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0): Breaking changes
- **MINOR** (0.X.0): New features, backward compatible
- **PATCH** (0.0.X): Bug fixes, backward compatible

## Tagging Releases

After merging a release to `main`:

```bash
git checkout main
git pull origin main
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0
```

## Branch Protection Rules (GitHub)

Recommended settings for `main` branch:

- ✅ Require pull request before merging
- ✅ Require status checks to pass (CI)
- ✅ Require conversation resolution before merging
- ✅ Require linear history (no merge commits)
- ✅ Do not allow force pushes
- ✅ Do not allow deletions

Recommended settings for `develop` branch:

- ✅ Require pull request before merging
- ✅ Require status checks to pass (CI)
- ⚠️ Allow force pushes (for rebasing, use with caution)

## Commit Message Convention

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, no code change)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(iban): add IBAN validation method
fix(remesa): fix XML generation with special characters
docs(readme): update installation instructions
test(iban): add tests for check digits calculation
```

## Pull Request Process

1. **Create a branch** from `develop` following naming conventions
2. **Make your changes** following code standards
3. **Write tests** for new features or bug fixes
4. **Ensure all tests pass** and coverage is 100%
5. **Update documentation** if needed
6. **Create a Pull Request** to `develop`
7. **Wait for review** and address feedback
8. **Merge** after approval

## Merging Strategy

- **Feature branches**: Merge to `develop` using "Squash and merge" or "Rebase and merge"
- **Release branches**: Merge to `main` using "Create a merge commit"
- **Hotfix branches**: Merge to `main` and `develop` using "Create a merge commit"

