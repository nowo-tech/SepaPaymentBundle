<?php

namespace App\Controller;

use Nowo\SepaPaymentBundle\Generator\CreditTransferGenerator;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\CreditTransferData;
use Nowo\SepaPaymentBundle\Model\CreditTransfer\Transaction;
use Nowo\SepaPaymentBundle\Parser\CreditTransferParser;
use Nowo\SepaPaymentBundle\Validator\IbanValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreditTransferController extends AbstractController
{
    /**
     * Demo remesa de pago (Credit Transfer).
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param IbanValidator           $validator IBAN validator
     * @return Response
     */
    #[Route('/demo-credit-transfer', name: 'demo_credit_transfer')]
    public function demoRemesaPago(CreditTransferGenerator $generator, IbanValidator $validator): Response
    {
        $creditTransferData = new CreditTransferData(
            'MSG-001',
            new \DateTime('2024-01-15 10:00:00'),
            'My Company',
            'PMT-001',
            'ES9121000418450200051332',
            'My Company Name',
            new \DateTime('2024-01-20')
        );

        $creditTransferData->setCreditorBic('CAIXESBBXXX');
        $creditTransferData->setBatchBooking(true);
        // Set creditor address (will be included in XML)
        $creditTransferData->setCreditorAddress([
            'street' => '123 Business Street',
            'city' => 'Madrid',
            'postalCode' => '28001',
            'country' => 'ES',
        ]);

        $transaction = new Transaction(
            'E2E-001',
            100.50,
            'EUR',
            'GB82WEST12345698765432',
            'John Doe'
        );

        $transaction->setCreditorBic('WESTGB22');
        $transaction->setRemittanceInformation('Invoice 12345');
        // Set creditor address (will be included in XML)
        $transaction->setCreditorAddress([
            'street' => '456 Customer Avenue',
            'city' => 'London',
            'postalCode' => 'SW1A 1AA',
            'country' => 'GB',
        ]);

        $creditTransferData->addTransaction($transaction);

        try {
            $xml = $generator->generate($creditTransferData);

            return $generator->createResponse($xml, 'credit-transfer.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de pago from array (Credit Transfer).
     * Demonstrates generateFromArray() method with camelCase format.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-array', name: 'demo_credit_transfer_array')]
    public function demoRemesaPagoArray(CreditTransferGenerator $generator): Response
    {
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            // Debtor data (company that PAYS) - using debtor* keys for clarity
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'My Company Name',
            'requestedExecutionDate' => '2024-01-20',
            'debtorBic' => 'CAIXESBBXXX',
            'batchBooking' => true,
            'transactions' => [
                [
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    // Creditor data (who RECEIVES the payment)
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'creditorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Invoice 12345',
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'credit-transfer-array.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de pago with addresses from array (Credit Transfer).
     * Demonstrates address support in generateFromArray().
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-with-addresses', name: 'demo_credit_transfer_with_addresses')]
    public function demoRemesaPagoWithAddresses(CreditTransferGenerator $generator): Response
    {
        // Example with creditor and debtor addresses
        // Addresses will be included in the generated XML
        $data = [
            'reference' => 'MSG-001',
            'initiatingPartyName' => 'My Company',
            'paymentInfoId' => 'PMT-001',
            // Debtor data (company that PAYS)
            'debtorIban' => 'ES9121000418450200051332',
            'debtorName' => 'My Company Name',
            'requestedExecutionDate' => '2024-01-20',
            'debtorBic' => 'CAIXESBBXXX',
            'batchBooking' => true,
            // Debtor address (will be included in XML)
            'debtorAddress' => [
                'street' => '123 Business Street',
                'city' => 'Madrid',
                'postalCode' => '28001',
                'country' => 'ES',
            ],
            'transactions' => [
                [
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    'creditorIban' => 'GB82WEST12345698765432',
                    'creditorName' => 'John Doe',
                    'endToEndId' => 'E2E-001',
                    'creditorBic' => 'WESTGB22',
                    'remittanceInformation' => 'Invoice 12345',
                    // Creditor address (will be included in XML)
                    'creditorAddress' => [
                        'street' => '456 Customer Avenue',
                        'city' => 'London',
                        'postalCode' => 'SW1A 1AA',
                        'country' => 'GB',
                    ],
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'credit-transfer-with-addresses.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo remesa de pago with snake_case format (Credit Transfer).
     * Demonstrates support for snake_case field names.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @return Response
     */
    #[Route('/demo-credit-transfer-snake-case', name: 'demo_credit_transfer_snake_case')]
    public function demoRemesaPagoSnakeCase(CreditTransferGenerator $generator): Response
    {
        // Example using snake_case format (also supports camelCase)
        $data = [
            'message_id' => 'MSG-001',
            'initiating_party_name' => 'My Company',
            'payment_info_id' => 'PMT-001',
            // Debtor data (company that PAYS)
            'debtor_iban' => 'ES9121000418450200051332',
            'debtor_name' => 'My Company Name',
            'requested_execution_date' => '2024-01-20',
            'debtor_bic' => 'CAIXESBBXXX',
            'batch_booking' => true,
            'items' => [
                [
                    'instruction_id' => 'E2E-001',
                    'amount' => 100.50,
                    'currency' => 'EUR',
                    'creditor_iban' => 'GB82WEST12345698765432',
                    'creditor_name' => 'John Doe',
                    'creditor_bic' => 'WESTGB22',
                    'remittance_information' => 'Invoice 12345',
                ],
            ],
        ];

        try {
            $xml = $generator->generateFromArray($data);

            return $generator->createResponse($xml, 'credit-transfer-snake-case.xml');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo parsing SEPA Credit Transfer XML.
     * Generates a sample XML and then parses it to demonstrate the parser functionality.
     *
     * @param CreditTransferGenerator $generator Credit transfer generator
     * @param CreditTransferParser     $parser    Credit transfer parser
     * @return JsonResponse
     */
    #[Route('/demo-parse-credit-transfer', name: 'demo_parse_credit_transfer')]
    public function demoParseCreditTransfer(CreditTransferGenerator $generator, CreditTransferParser $parser): JsonResponse
    {
        try {
            // First, generate a sample XML
            $data = [
                'reference' => 'MSG-PARSE-001',
                'initiatingPartyName' => 'Demo Company',
                'paymentInfoId' => 'PMT-PARSE-001',
                // Debtor data (company that PAYS)
                'debtorIban' => 'ES9121000418450200051332',
                'debtorName' => 'Demo Company Name',
                'requestedExecutionDate' => '2024-01-20',
                'debtorBic' => 'CAIXESBBXXX',
                'transactions' => [
                    [
                        'amount' => 150.75,
                        'currency' => 'EUR',
                        // Creditor data (who RECEIVES the payment)
                        'creditorIban' => 'GB82WEST12345698765432',
                        'creditorName' => 'John Doe',
                        'endToEndId' => 'E2E-PARSE-001',
                        'creditorBic' => 'WESTGB22',
                        'remittanceInformation' => 'Demo Invoice 12345',
                    ],
                ],
            ];

            $xml = $generator->generateFromArray($data);

            // Validate XML format
            $isValid = $parser->isValidCreditTransfer($xml);

            // Parse the XML
            $parsedData = $parser->parseCreditTransfer($xml);

            $response = new JsonResponse([
                'message' => 'Successfully generated and parsed Credit Transfer XML',
                'isValid' => $isValid,
                'generatedXml' => $xml,
                'parsedData' => $parsedData,
            ]);
            $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
