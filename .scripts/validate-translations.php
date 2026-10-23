#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * REQ-MAKE-004 — every translation domain must have the same flattened key set in every locale as its `en` catalogue.
 *
 * Usage: php .scripts/validate-translations.php [translations-dir]
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

$dir             = $argv[1] ?? dirname(__DIR__) . '/src/Resources/translations';
$referenceLocale = 'en';

$files = glob($dir . '/*.yaml') ?: [];
if ($files === []) {
    fwrite(STDERR, "No translation catalogues in {$dir}\n");
    exit(1);
}

/** @var array<string, array<string, string>> $byDomain domain => locale => file */
$byDomain = [];
foreach ($files as $file) {
    if (preg_match('/^(.+)\.([a-z]{2}(?:_[A-Z]{2})?)\.yaml$/', basename($file), $m) === 1) {
        $byDomain[$m[1]][$m[2]] = $file;
    }
}

$failed = false;
foreach ($byDomain as $domain => $locales) {
    if (!isset($locales[$referenceLocale])) {
        fwrite(STDERR, "Domain {$domain}: no {$referenceLocale} catalogue\n");
        $failed = true;
        continue;
    }
    try {
        $reference = array_keys(flatten(Yaml::parseFile($locales[$referenceLocale]) ?? []));
    } catch (ParseException $e) {
        fwrite(STDERR, "Failed to parse {$locales[$referenceLocale]}: {$e->getMessage()}\n");
        $failed = true;
        continue;
    }
    sort($reference);
    foreach ($locales as $locale => $file) {
        try {
            $keys = array_keys(flatten(Yaml::parseFile($file) ?? []));
        } catch (ParseException $e) {
            fwrite(STDERR, "Failed to parse {$file}: {$e->getMessage()}\n");
            $failed = true;
            continue;
        }
        sort($keys);
        $missing = array_diff($reference, $keys);
        $extra   = array_diff($keys, $reference);
        if ($missing !== [] || $extra !== []) {
            $failed = true;
            fwrite(STDERR, "Key mismatch in {$file}:\n");
            if ($missing !== []) {
                fwrite(STDERR, '  Missing keys: ' . implode(', ', $missing) . "\n");
            }
            if ($extra !== []) {
                fwrite(STDERR, '  Extra keys: ' . implode(', ', $extra) . "\n");
            }
        } else {
            echo "OK {$domain}.{$locale} (" . count($keys) . " keys)\n";
        }
    }
}

exit($failed ? 1 : 0);

/**
 * @param array<mixed> $data
 *
 * @return array<string, string>
 */
function flatten(array $data, string $prefix = ''): array
{
    $flat = [];
    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) {
            $flat += flatten($value, $path);
        } else {
            $flat[$path] = (string) $value;
        }
    }

    return $flat;
}
