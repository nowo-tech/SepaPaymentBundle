<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Generator;

use DateTime;
use Nowo\SepaPaymentBundle\Generator\RemesaGenerator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction as CreditTransferTransaction;
use Nowo\SepaPaymentBundle\Model\Remesa\RemesaData;
use Nowo\SepaPaymentBundle\Model\Remesa\Transaction as RemesaTransaction;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tests for RemesaGenerator (deprecated API).
 *
 * #[IgnoreDeprecations] is used so CI passes on all matrix combinations
 * (PHP 8.1–8.5, Symfony 6.4 / 7.0 / 8.0) without risky tests.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class RemesaGeneratorTest extends TestCase
{
    private RemesaGenerator $generator;

    protected function setUp(): void
    {
        $translator = new class implements TranslatorInterface {
            /** @param array<string, mixed> $parameters */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id;
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };
        $this->generator = new RemesaGenerator(new IbanValidator(), $translator);
    }

    #[IgnoreDeprecations]
    public function testGenerateFromArray(): void
    {
        $data = [
            'reference'              => 'MSG-001',
            'initiatingPartyName'    => 'My Company',
            'paymentInfoId'          => 'PMT-001',
            'debtorIban'             => 'ES9121000418450200051332',
            'debtorName'             => 'My Company Name',
            'requestedExecutionDate' => '2024-01-20',
            'transactions'           => [
                [
                    'amount'       => 100.50,
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId'   => 'E2E-001',
                ],
            ],
        ];

        $xml = $this->generator->generateFromArray($data);

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
        $this->assertStringContainsString('100.50', $xml);
    }

    #[IgnoreDeprecations]
    public function testGenerateWithRemesaData(): void
    {
        $remesaData = new RemesaData(
            'MSG-001',
            new DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new DateTime('2024-01-20'),
        );

        $transaction = new RemesaTransaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe',
        );
        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
        $this->assertStringContainsString('100.50', $xml);
    }

    #[IgnoreDeprecations]
    public function testGenerateWithCreditTransferData(): void
    {
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new DateTime('2024-01-20'),
        );
        $creditTransferData->addTransaction(new CreditTransferTransaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe',
        ));

        $xml = $this->generator->generate($creditTransferData);

        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-001', $xml);
    }

    #[IgnoreDeprecations]
    public function testCreateResponse(): void
    {
        $xml      = '<?xml version="1.0"?><test/>';
        $response = $this->generator->createResponse($xml, 'test-remesa.xml');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="test-remesa.xml"', (string) $response->headers->get('Content-Disposition'));
        $this->assertEquals($xml, $response->getContent());
    }

    /**
     * Tests generate() with RemesaData that has creditor BIC, creditor address, and transaction with debtor BIC, remittance, debtor address.
     * Covers all branches of convertRemesaDataToCreditTransferData().
     */
    #[IgnoreDeprecations]
    public function testGenerateWithRemesaDataFullOptionalFields(): void
    {
        $remesaData = new RemesaData(
            'MSG-FULL',
            new DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-FULL',
            'ES9121000418450200051332',
            'My Company Name',
            new DateTime('2024-01-20'),
        );
        $remesaData->setCreditorBic('CAIXESBBXXX');
        $remesaData->setCreditorAddressFromArray([
            'street'     => 'Calle Principal 1',
            'city'       => 'Madrid',
            'postalCode' => '28001',
            'country'    => 'ES',
        ]);

        $transaction = new RemesaTransaction(
            'E2E-FULL',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe',
        );
        $transaction->setDebtorBic('WESTGB22');
        $transaction->setRemittanceInformation('Invoice 12345');
        $transaction->setDebtorAddressFromArray([
            'street'     => '456 Customer Ave',
            'city'       => 'London',
            'postalCode' => 'SW1A 1AA',
            'country'    => 'GB',
        ]);
        $remesaData->addTransaction($transaction);

        $xml = $this->generator->generate($remesaData);

        $this->assertStringContainsString('CstmrCdtTrfInitn', $xml);
        $this->assertStringContainsString('MSG-FULL', $xml);
        $this->assertStringContainsString('100.50', $xml);
        $this->assertStringContainsString('PstlAdr', $xml);
        $this->assertStringContainsString('Invoice 12345', $xml);
    }
}
