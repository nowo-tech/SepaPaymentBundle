<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Model\Mandate;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Nowo\SepaPaymentBundle\Model\Mandate\Mandate;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateAmendment;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateEvidence;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateHistory;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateSnapshot;
use Nowo\SepaPaymentBundle\Model\Mandate\MandateStatus;
use Nowo\SepaPaymentBundle\Repository\MandateRepositoryInterface;
use Nowo\SepaPaymentBundle\Service\MandateService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

use function json_decode;
use function json_encode;
use function strlen;

use const JSON_THROW_ON_ERROR;

/**
 * P8 (evidence, debtor address, owner reference, amendment) and bar gap 2 (snapshot / reconstitution, DDD path).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MandateSnapshotTest extends TestCase
{
    public function testSnapshotRoundTripThroughJsonKeepsEveryField(): void
    {
        $mandate = self::fullMandate();

        $json     = json_encode($mandate->toSnapshot()->toArray(), JSON_THROW_ON_ERROR);
        $restored = Mandate::reconstitute(MandateSnapshot::fromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR)));

        self::assertEquals($mandate->toSnapshot()->toArray(), $restored->toSnapshot()->toArray());
        self::assertSame('UMR-0001', $restored->getMandateId());
        self::assertSame(MandateStatus::REVOKED, $restored->getStatus());
        self::assertFalse($restored->isActive());
        self::assertSame('NWBKGB2LXXX', $restored->getDebtorBic());
        self::assertSame('2030-01-01', $restored->getExpirationDate()->format('Y-m-d'));
        self::assertSame('2026-10-01', $restored->getLastCollectionDate()?->format('Y-m-d'));
        self::assertSame('2026-11-01', $restored->getRevocationDate()?->format('Y-m-d'));
        self::assertSame('customer request', $restored->getRevocationReason());
        self::assertSame(['street' => 'Calle Mayor 1', 'city' => 'Madrid', 'postalCode' => '28001', 'country' => 'ES'], $restored->getDebtorAddress());
        self::assertSame('customer:42', $restored->getOwnerReference());
        self::assertSame('203.0.113.7', $restored->getEvidence()?->getIpAddress());
        self::assertSame('UMR-OLD', $restored->getAmendment()?->getOriginalMandateId());
    }

    public function testReconstituteRunsNoLifecycleRule(): void
    {
        // An expired, revoked mandate comes back as stored (reactivate() would refuse it)
        $snapshot = new MandateSnapshot('UMR-X', new DateTimeImmutable('2010-01-01'), 'ES9121000418450200051332', 'Old', 'B2B', 'RCUR', null, MandateStatus::EXPIRED, false, null, null, null, null);
        $mandate  = Mandate::reconstitute($snapshot);

        self::assertSame(MandateStatus::EXPIRED, $mandate->getStatus());
        self::assertSame('B2B', $mandate->getType());
        self::assertTrue($mandate->isExpired());
        self::assertNull($mandate->toSnapshot()->getExpirationDate(), 'the inactivity rule stays computed, not stored');
        self::assertNull($mandate->getEvidence());
        self::assertNull($mandate->toSnapshot()->toArray()['evidence']);
    }

    /**
     * DDD path: MandateService over a host repository that stores only snapshot arrays (as a table or JSON column would).
     */
    public function testMandateServiceOverAHostRepositoryStoringSnapshots(): void
    {
        $repository = new class implements MandateRepositoryInterface {
            /** @var array<string, string> mandate id => JSON row */
            public array $rows = [];

            public function save(Mandate $mandate): void
            {
                $this->rows[$mandate->getMandateId()] = json_encode($mandate->toSnapshot()->toArray(), JSON_THROW_ON_ERROR);
            }

            public function findById(string $mandateId): ?Mandate
            {
                return isset($this->rows[$mandateId]) ? Mandate::reconstitute(MandateSnapshot::fromArray(json_decode($this->rows[$mandateId], true, 512, JSON_THROW_ON_ERROR))) : null;
            }

            public function findByDebtorIban(string $debtorIban): array
            {
                return [];
            }

            public function findActive(): array
            {
                return [];
            }

            public function findExpired(?DateTimeInterface $beforeDate = null): array
            {
                return [];
            }

            public function delete(string $mandateId): bool
            {
                return false;
            }

            public function addHistory(MandateHistory $history): void
            {
            }

            public function getHistory(string $mandateId): array
            {
                return [];
            }
        };
        $service = new MandateService($repository, new MockClock('2026-11-10 12:00:00'));

        $service->createMandate('UMR-9', new DateTimeImmutable('2026-11-01'), 'ES9121000418450200051332', 'Debtor');
        $service->updateSequenceType('UMR-9', 'RCUR');
        $service->suspendMandate('UMR-9');

        $stored = $service->findMandate('UMR-9');
        self::assertNotNull($stored);
        self::assertSame('RCUR', $stored->getSequenceType());
        self::assertSame(MandateStatus::SUSPENDED, $stored->getStatus());
    }

    public function testEvidence(): void
    {
        $evidence = new MandateEvidence(hash('sha256', 'I authorise…'), new DateTimeImmutable('2026-11-01T10:00:00+01:00'), '2001:db8::1', 'web', 'signup-123');

        self::assertSame(64, strlen($evidence->getAcceptedTextHash()));
        self::assertSame('2026-11-01T10:00:00+01:00', $evidence->getAcceptedAt()->format(DateTimeInterface::ATOM));
        self::assertSame('web', $evidence->getChannel());
        self::assertSame('signup-123', $evidence->getExternalId());
        self::assertEquals($evidence, MandateEvidence::fromArray($evidence->toArray()));
    }

    /**
     * @return iterable<string, array{callable(): object, string}>
     */
    public static function invalidProvider(): iterable
    {
        $now = new DateTimeImmutable();
        yield 'hash not hex' => [static fn (): object => new MandateEvidence('not-a-hash', $now), 'accepted text hash'];
        yield 'hash too short' => [static fn (): object => new MandateEvidence('abcd', $now), 'accepted text hash'];
        yield 'bad ip' => [static fn (): object => new MandateEvidence(str_repeat('a', 64), $now, '999.1.1.1'), 'IP address'];
        yield 'empty channel' => [static fn (): object => new MandateEvidence(str_repeat('a', 64), $now, null, ''), 'channel'];
        yield 'long external id' => [static fn (): object => new MandateEvidence(str_repeat('a', 64), $now, null, null, str_repeat('x', 101)), 'external id'];
        yield 'empty amendment' => [static fn (): object => new MandateAmendment(), 'at least one'];
        yield 'long mandate id' => [static fn (): object => new MandateAmendment(str_repeat('M', 36)), 'OrgnlMndtId'];
        yield 'bad iban' => [static fn (): object => new MandateAmendment(null, null, null, 'es91 2100'), 'original debtor IBAN'];
        yield 'iban and smnda' => [static fn (): object => new MandateAmendment(null, null, null, 'ES9121000418450200051332', true), 'not both'];
        yield 'owner reference' => [static fn (): object => (new Mandate('U', new DateTimeImmutable(), 'ES9121000418450200051332', 'D'))->setOwnerReference(''), 'owner reference'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidValuesAreRejected(callable $factory, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $factory();
    }

    /**
     * A stored evidence without acceptedAt must not become "now" (CodeRabbit on #48).
     *
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function badEvidenceRowProvider(): iterable
    {
        $hash = str_repeat('a', 64);
        yield 'missing' => [['acceptedTextHash' => $hash], 'acceptedAt: it is missing or empty'];
        yield 'empty' => [['acceptedTextHash' => $hash, 'acceptedAt' => ' '], 'acceptedAt: it is missing or empty'];
        yield 'null' => [['acceptedTextHash' => $hash, 'acceptedAt' => null], 'acceptedAt: it is missing or empty'];
        yield 'not a date' => [['acceptedTextHash' => $hash, 'acceptedAt' => 'yesterday-ish'], 'use an absolute date'];
        yield 'now' => [['acceptedTextHash' => $hash, 'acceptedAt' => 'now'], 'use an absolute date'];
        yield 'tomorrow' => [['acceptedTextHash' => $hash, 'acceptedAt' => 'tomorrow'], 'use an absolute date'];
        yield 'timestamp' => [['acceptedTextHash' => $hash, 'acceptedAt' => '@0'], 'use an absolute date'];
        yield 'invalid day' => [['acceptedTextHash' => $hash, 'acceptedAt' => '2026-02-30T10:00:00+01:00'], 'use an absolute date'];
    }

    /**
     * @param array<string, mixed> $evidence
     */
    #[DataProvider('badEvidenceRowProvider')]
    public function testEvidenceWithoutAcceptedAtIsRejected(array $evidence, string $message): void
    {
        $row             = self::fullMandate()->toSnapshot()->toArray();
        $row['evidence'] = $evidence;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        MandateSnapshot::fromArray($row);
    }

    /**
     * LF-L1: a damaged row never becomes "now": required fields and absolute dates.
     *
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function badSnapshotRowProvider(): iterable
    {
        foreach (['mandateId', 'signatureDate', 'debtorIban', 'debtorName'] as $field) {
            yield $field . ' missing' => [[$field => '__unset__'], $field];
            yield $field . ' empty' => [[$field => ''], $field];
        }
        foreach (['now', 'tomorrow', '@0', 'next monday', '2026-13-01'] as $value) {
            yield 'signatureDate ' . $value => [['signatureDate' => $value], 'use an absolute date'];
            yield 'expirationDate ' . $value => [['expirationDate' => $value], 'expirationDate'];
        }
        yield 'revocationDate empty' => [['revocationDate' => ''], 'revocationDate: it is missing or empty'];
    }

    /**
     * @param array<string, mixed> $changes
     */
    #[DataProvider('badSnapshotRowProvider')]
    public function testDamagedSnapshotRowsAreRejected(array $changes, string $message): void
    {
        $row = self::fullMandate()->toSnapshot()->toArray();
        foreach ($changes as $field => $value) {
            if ($value === '__unset__') {
                unset($row[$field]);
            } else {
                $row[$field] = $value;
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        MandateSnapshot::fromArray($row);
    }

    /**
     * LF-L1: the absolute formats a host may store are accepted.
     */
    public function testAbsoluteDateFormatsAreAccepted(): void
    {
        $row = self::fullMandate()->toSnapshot()->toArray();
        foreach (['2026-01-15' => '2026-01-15 00:00:00', '2026-01-15 10:20:30' => '2026-01-15 10:20:30', '2026-01-15T10:20:30+01:00' => '2026-01-15 10:20:30', '2026-01-15T10:20:30.123456+01:00' => '2026-01-15 10:20:30'] as $stored => $expected) {
            $row['signatureDate'] = $stored;
            self::assertSame($expected, MandateSnapshot::fromArray($row)->getSignatureDate()->format('Y-m-d H:i:s'), $stored);
        }
        $row['expirationDate'] = null;
        self::assertNull(MandateSnapshot::fromArray($row)->getExpirationDate());
    }

    public function testAmendmentArrayRoundTripAndEmptyAddress(): void
    {
        $amendment = new MandateAmendment('UMR-OLD', 'ES59ZZZX1234567', 'Old Creditor', null, true);
        self::assertEquals($amendment, MandateAmendment::fromArray($amendment->toArray()));
        self::assertTrue($amendment->isNewDebtorAgent());
        self::assertSame('Old Creditor', $amendment->getOriginalCreditorName());
        self::assertSame('ES59ZZZX1234567', $amendment->getOriginalCreditorSchemeId());
        self::assertNull($amendment->getOriginalDebtorIban());

        $mandate = new Mandate('U', new DateTimeImmutable(), 'ES9121000418450200051332', 'D');
        self::assertNull($mandate->setDebtorAddress(null)->getDebtorAddress());
    }

    private static function fullMandate(): Mandate
    {
        $mandate = new Mandate('UMR-0001', new DateTimeImmutable('2026-01-15'), 'GB82WEST12345698765432', 'John Doe', 'CORE', 'RCUR');
        $mandate->setDebtorBic('NWBKGB2LXXX')
            ->setExpirationDate(new DateTimeImmutable('2030-01-01'))
            ->setLastCollectionDate(new DateTimeImmutable('2026-10-01'))
            ->setDebtorAddress('Calle Mayor 1', 'Madrid', '28001', 'ES')
            ->setOwnerReference('customer:42')
            ->setEvidence(new MandateEvidence(hash('sha256', 'mandate text v3'), new DateTimeImmutable('2026-01-15T09:00:00+00:00'), '203.0.113.7', 'web', 'evt-1'))
            ->setAmendment(new MandateAmendment('UMR-OLD', null, null, 'ES7921000813610123456789'))
            ->revoke('customer request', new DateTimeImmutable('2026-11-01'));

        return $mandate;
    }
}
