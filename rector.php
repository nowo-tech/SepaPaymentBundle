<?php

declare(strict_types=1);

/**
 * Rector configuration for SEPA Payment Bundle.
 *
 * Ensures PHP 8.1+ and Symfony 6|7|8 compatibility; applies dead code, code quality,
 * and type declaration rules. Only the src/ directory is processed (tests are skipped).
 *
 * Command help must stay in configure() via setHelp(), not in #[AsCommand](help: …), so Symfony
 * Console 6.0 and 6.1 remain supported (the help attribute exists only from 6.2+).
 *
 * @see https://getrector.com/documentation
 */
use Rector\Config\RectorConfig;
use Rector\Symfony\Symfony73\Rector\Class_\CommandHelpToAttributeRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withComposerBased(symfony: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withSkip([
        __DIR__ . '/demo',
        __DIR__ . '/vendor',
        __DIR__ . '/tests', // Skip tests: some Symfony rules (e.g. RequestStack constructor) don't match Symfony's actual API
        // Keep setHelp() in configure(): #[AsCommand(help: …)] needs Symfony Console 6.2+.
        CommandHelpToAttributeRector::class,
    ]);
