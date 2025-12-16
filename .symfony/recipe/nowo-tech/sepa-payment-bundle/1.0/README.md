# Symfony Flex Recipe for SEPA Payment Bundle

This directory contains the Symfony Flex recipe for the SEPA Payment Bundle.

## What is a Flex Recipe?

Symfony Flex recipes are used to automatically configure bundles when they are installed via Composer. When you run `composer require nowo-tech/sepa-payment-bundle`, Flex will:

1. Register the bundle in `config/bundles.php`
2. Copy configuration files from this recipe to your project
3. Display the `post-install.txt` message

## Recipe Structure

- `manifest.json`: Defines which bundles to register and which files to copy
- `config/packages/nowo_sepa_payment.yaml`: Default configuration file
- `post-install.txt`: Message displayed after installation
- `README.md`: This file

## Publishing the Recipe

**Note**: Flex recipes only work when the bundle is published in the official Symfony Flex repository (Packagist). For private bundles or bundles installed from Git repositories, Flex recipes won't work automatically.

To publish a recipe:

1. Fork the [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib) repository
2. Copy this recipe directory to `contrib/nowo-tech/sepa-payment-bundle/1.0/`
3. Create a pull request to the recipes-contrib repository
4. Once merged, the recipe will be available for all users installing the bundle from Packagist

## Manual Installation

If the recipe doesn't work (e.g., for private bundles), users can manually:

1. Register the bundle in `config/bundles.php`
2. Create the configuration file manually (see `config/packages/nowo_sepa_payment.yaml`)

See the main README.md for more information.

