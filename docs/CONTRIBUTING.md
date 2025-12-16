# Contributing Guide

Thank you for your interest in contributing to SEPA Payment Bundle! This document provides guidelines for contributing to the project.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to hectorfranco@nowo.com.

## How can I contribute?

### Reporting Bugs

If you find a bug, please:

1. **Check that the bug hasn't already been reported** in the [issues](https://github.com/nowo-tech/sepa-payment-bundle/issues)
2. **Create a new issue** with:
   - A descriptive title
   - Steps to reproduce the problem
   - Expected behavior vs. actual behavior
   - PHP, Symfony, and bundle versions
   - Code examples if relevant

### Suggesting Enhancements

Enhancement suggestions are welcome:

1. **Check that the enhancement hasn't already been suggested** in the [issues](https://github.com/nowo-tech/sepa-payment-bundle/issues)
2. **Create a new issue** with:
   - A descriptive title
   - Detailed description of the proposed enhancement
   - Use cases and benefits
   - Possible implementations (if you have them)

### Contributing Code

#### Development Environment Setup

1. **Fork the repository** on GitHub
2. **Clone your fork**:
   ```bash
   git clone https://github.com/your-username/sepa-payment-bundle.git
   cd sepa-payment-bundle
   ```
3. **Install dependencies**:
   ```bash
   # With Docker (recommended)
   make install
   
   # Without Docker
   composer install
   ```

#### Code Standards

The project follows these standards:

- **PSR-12**: PHP code style
- **PHP 8.1+**: Modern PHP features
- **Strict type hints**: `declare(strict_types=1);` in all files
- **PHP-CS-Fixer**: Used to maintain code consistency

**Before committing**:

```bash
# Check code style
make cs-check
# or
composer cs-check

# Automatically fix code style
make cs-fix
# or
composer cs-fix
```

#### Tests

**The project requires 100% code coverage**. All tests must pass before merging.

```bash
# Run all tests
make test
# or
composer test

# Run tests with coverage
make test-coverage
# or
composer test-coverage

# View coverage report
open coverage/index.html
```

**Test structure**:
- Tests must be in the `tests/` directory
- Each class must have its corresponding test
- Tests must be descriptive and cover edge cases
- Use PHPUnit assertions appropriately

#### PHPDoc

All classes, methods, and properties must have complete PHPDoc comments:

```php
/**
 * Class description.
 *
 * @author Your Name <your.email@example.com>
 */
class MyClass
{
    /**
     * Property description.
     *
     * @var string
     */
    private string $property;

    /**
     * Method description.
     *
     * @param string $param Parameter description
     * @return bool Return description
     * @throws \InvalidArgumentException When parameter is invalid
     */
    public function method(string $param): bool
    {
        // Implementation
    }
}
```

**PHPDoc rules**:
- All documentation must be in **English**
- Use `@param` for all parameters
- Use `@return` for return values
- Use `@throws` when exceptions are thrown
- Use `@var` for all properties

## Pull Request Process

1. **Create a branch** from `develop` following naming conventions (see [BRANCHING.md](BRANCHING.md))
2. **Make your changes** following code standards
3. **Write tests** for new features or bug fixes
4. **Ensure all tests pass** and coverage is 100%
5. **Update documentation** if needed
6. **Create a Pull Request** to `develop`
7. **Wait for review** and address feedback
8. **Merge** after approval

## Development Workflow

1. **Start from `develop`**: Always create feature branches from `develop`
2. **Write tests first**: Follow TDD when possible
3. **Run QA checks**: Ensure `make qa` passes before committing
4. **Commit often**: Make small, logical commits
5. **Write good commit messages**: Follow Conventional Commits (see [BRANCHING.md](BRANCHING.md))
6. **Update CHANGELOG**: Add entries for user-facing changes

## Questions?

If you have questions about contributing, please:

- Open an issue with the `question` label
- Contact the maintainer: hectorfranco@nowo.com

Thank you for contributing! 🎉

