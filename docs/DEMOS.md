# Demo Projects

The bundle includes demo projects for different Symfony versions. Each demo has its own `docker-compose.yml` and can be run independently.

## Available Demos

- **Symfony 6.4 Demo**: `demo/demo-symfony6/` (Port 8001 by default)
- **Symfony 7.0 Demo**: `demo/demo-symfony7/` (Port 8001 by default)
- **Symfony 8.0 Demo**: `demo/demo-symfony8/` (Port 8001 by default)

## Demo Endpoints

Each demo application includes the following endpoints to showcase bundle functionality:

### Validators
- `/validate-iban?iban=ES9121000418450200051332` - Validate IBAN and display detailed information
- `/validate-bic?bic=ESPBESMM` - Validate BIC and extract components
- `/validate-credit-card?card=4532015112830366` - Validate credit card number using Luhn algorithm

### Converters
- `/convert-ccc?ccc=21000418450200051332` - Convert Spanish CCC to IBAN format

### Generators
- `/generate-identifier` - Generate various types of identifiers (message, payment, end-to-end, mandate)
- `/demo-mandate` - Demo SEPA mandate creation

### Credit Transfer (Remesa de Pago)
- `/demo-remesa-pago` - Generate and download SEPA Credit Transfer XML (with addresses)
- `/demo-remesa-pago-array` - Generate from array (camelCase format)
- `/demo-remesa-pago-with-addresses` - Generate from array with addresses
- `/demo-remesa-pago-snake-case` - Generate from array (snake_case format)

### Direct Debit (Remesa de Cobro)
- `/demo-remesa-cobro` - Generate and download SEPA Direct Debit XML (from array - camelCase)
- `/demo-remesa-cobro-snake-case` - Generate from array (snake_case format)
- `/demo-remesa-cobro-with-addresses` - Generate from array with addresses

## Quick Start with Docker

```bash
cd demo
make up-symfony6
make install-symfony6
# Access at: http://localhost:8001
```

For more details, see `demo/README.md`.
