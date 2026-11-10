<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Model;

use function strtoupper;
use function trim;

/**
 * ISO 20022 reason codes used in SEPA R-transactions (rejects, returns, refunds, refusals, recalls) of direct debits
 * and credit transfers, as listed in the EPC "Guidance on reason codes for SDD R-transactions" (EPC173-14) and the
 * SCT / SDD rulebooks (ISO 20022 external code sets ExternalStatusReason1Code / ExternalReturnReason1Code).
 *
 * Each code has an English description and a translation key in the `NowoSepaPaymentBundle` domain
 * (`reason_code.AM04`, in en, es, de, fr, it, nl, pt, nb and ru):
 *
 *     $reason = ReasonCode::fromCode($returnedCode); // null for an unknown code
 *     $label  = $translator->trans($reason->getTranslationKey(), [], ReasonCode::TRANSLATION_DOMAIN);
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
enum ReasonCode: string
{
    /** Account identifier incorrect (invalid IBAN). */
    case AC01 = 'AC01';

    /** Creditor account identifier incorrect. */
    case AC03 = 'AC03';

    /** Account closed. */
    case AC04 = 'AC04';

    /** Account blocked, or blocked for direct debits by the debtor. */
    case AC06 = 'AC06';

    /** Debtor account is a consumer account (B2B not allowed). */
    case AC13 = 'AC13';

    /** Direct debit forbidden on this account for regulatory reasons. */
    case AG01 = 'AG01';

    /** Operation or transaction code incorrect, invalid file format. */
    case AG02 = 'AG02';

    /** Insufficient funds. */
    case AM04 = 'AM04';

    /** Duplicate collection. */
    case AM05 = 'AM05';

    /** Amount below the agreed minimum. */
    case AM06 = 'AM06';

    /** Wrong amount. */
    case AM09 = 'AM09';

    /** Transaction already returned. */
    case ARDT = 'ARDT';

    /** Debtor name does not match the account holder. */
    case BE01 = 'BE01';

    /** Creditor identifier incorrect. */
    case BE05 = 'BE05';

    /** Creditor bank is not registered in the scheme. */
    case CNOR = 'CNOR';

    /** Requested by the customer. */
    case CUST = 'CUST';

    /** Debtor bank is not registered in the scheme. */
    case DNOR = 'DNOR';

    /** Duplicate payment. */
    case DUPL = 'DUPL';

    /** Settlement failed. */
    case ED05 = 'ED05';

    /** Invalid file format. */
    case FF01 = 'FF01';

    /** Return following a cancellation request. */
    case FOCR = 'FOCR';

    /** Fraudulent origin. */
    case FRAD = 'FRAD';

    /** Legal decision. */
    case LEGL = 'LEGL';

    /** No valid mandate, or unauthorised transaction. */
    case MD01 = 'MD01';

    /** Mandate data missing or incorrect. */
    case MD02 = 'MD02';

    /** Refund requested by the debtor (authorised transaction). */
    case MD06 = 'MD06';

    /** Debtor deceased. */
    case MD07 = 'MD07';

    /** Refused by the debtor (reason not specified). */
    case MS02 = 'MS02';

    /** Reason not specified by the bank. */
    case MS03 = 'MS03';

    /** No answer from the beneficiary. */
    case NOAS = 'NOAS';

    /** Original transaction not received. */
    case NOOR = 'NOOR';

    /** Bank identifier (BIC) incorrect. */
    case RC01 = 'RC01';

    /** Debtor account or identification missing (regulatory reason). */
    case RR01 = 'RR01';

    /** Debtor name or address missing (regulatory reason). */
    case RR02 = 'RR02';

    /** Creditor name or address missing (regulatory reason). */
    case RR03 = 'RR03';

    /** Regulatory reason. */
    case RR04 = 'RR04';

    /** Specific service offered by the debtor's bank. */
    case SL01 = 'SL01';

    /** Creditor not on the debtor's whitelist. */
    case SL11 = 'SL11';

    /** Creditor on the debtor's blacklist. */
    case SL12 = 'SL12';

    /** Maximum number of direct debits set by the debtor exceeded. */
    case SL13 = 'SL13';

    /** Maximum direct debit amount set by the debtor exceeded. */
    case SL14 = 'SL14';

    /** Technical problem resulting in an erroneous transaction. */
    case TECH = 'TECH';

    /** Translation domain of the reason-code labels. */
    public const TRANSLATION_DOMAIN = 'NowoSepaPaymentBundle';

    /**
     * The reason code for a code as found in a bank file ("am04 " → AM04), or null when it is not a known code.
     */
    public static function fromCode(string $code): ?self
    {
        return self::tryFrom(strtoupper(trim($code)));
    }

    /**
     * The ISO 20022 name of the code (e.g. "InsufficientFunds").
     */
    public function getIsoName(): string
    {
        return match ($this) {
            self::AC01 => 'IncorrectAccountNumber',
            self::AC03 => 'InvalidCreditorAccountNumber',
            self::AC04 => 'ClosedAccountNumber',
            self::AC06 => 'BlockedAccount',
            self::AC13 => 'InvalidDebtorAccountType',
            self::AG01 => 'TransactionForbidden',
            self::AG02 => 'InvalidBankOperationCode',
            self::AM04 => 'InsufficientFunds',
            self::AM05 => 'Duplication',
            self::AM06 => 'TooLowAmount',
            self::AM09 => 'WrongAmount',
            self::ARDT => 'AlreadyReturnedTransaction',
            self::BE01 => 'InconsistentWithEndCustomer',
            self::BE05 => 'UnrecognisedInitiatingParty',
            self::CNOR => 'CreditorBankIsNotRegistered',
            self::CUST => 'RequestedByCustomer',
            self::DNOR => 'DebtorBankIsNotRegistered',
            self::DUPL => 'DuplicatePayment',
            self::ED05 => 'SettlementFailed',
            self::FF01 => 'InvalidFileFormat',
            self::FOCR => 'FollowingCancellationRequest',
            self::FRAD => 'FraudulentOrigin',
            self::LEGL => 'LegalDecision',
            self::MD01 => 'NoMandate',
            self::MD02 => 'MissingMandatoryInformationInMandate',
            self::MD06 => 'RefundRequestByEndCustomer',
            self::MD07 => 'EndCustomerDeceased',
            self::MS02 => 'NotSpecifiedReasonCustomerGenerated',
            self::MS03 => 'NotSpecifiedReasonAgentGenerated',
            self::NOAS => 'NoAnswerFromCustomer',
            self::NOOR => 'NoOriginalTransactionReceived',
            self::RC01 => 'BankIdentifierIncorrect',
            self::RR01 => 'MissingDebtorAccountOrIdentification',
            self::RR02 => 'MissingDebtorNameOrAddress',
            self::RR03 => 'MissingCreditorNameOrAddress',
            self::RR04 => 'RegulatoryReason',
            self::SL01 => 'SpecificServiceOfferedByDebtorAgent',
            self::SL11 => 'CreditorNotOnWhitelistOfDebtor',
            self::SL12 => 'CreditorOnBlacklistOfDebtor',
            self::SL13 => 'MaximumNumberOfDirectDebitTransactionsExceeded',
            self::SL14 => 'MaximumDirectDebitTransactionAmountExceeded',
            self::TECH => 'TechnicalProblem',
        };
    }

    /**
     * An English description of the code.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::AC01 => 'Account identifier incorrect (invalid IBAN)',
            self::AC03 => 'Creditor account identifier incorrect',
            self::AC04 => 'Account closed',
            self::AC06 => 'Account blocked, or blocked for direct debits by the debtor',
            self::AC13 => 'Debtor account is a consumer account (B2B not allowed)',
            self::AG01 => 'Direct debit forbidden on this account for regulatory reasons',
            self::AG02 => 'Operation or transaction code incorrect, invalid file format',
            self::AM04 => 'Insufficient funds',
            self::AM05 => 'Duplicate collection',
            self::AM06 => 'Amount below the agreed minimum',
            self::AM09 => 'Wrong amount',
            self::ARDT => 'Transaction already returned',
            self::BE01 => 'Debtor name does not match the account holder',
            self::BE05 => 'Creditor identifier incorrect',
            self::CNOR => 'Creditor bank is not registered in the scheme',
            self::CUST => 'Requested by the customer',
            self::DNOR => 'Debtor bank is not registered in the scheme',
            self::DUPL => 'Duplicate payment',
            self::ED05 => 'Settlement failed',
            self::FF01 => 'Invalid file format',
            self::FOCR => 'Return following a cancellation request',
            self::FRAD => 'Fraudulent origin',
            self::LEGL => 'Legal decision',
            self::MD01 => 'No valid mandate, or unauthorised transaction',
            self::MD02 => 'Mandate data missing or incorrect',
            self::MD06 => 'Refund requested by the debtor (authorised transaction)',
            self::MD07 => 'Debtor deceased',
            self::MS02 => 'Refused by the debtor (reason not specified)',
            self::MS03 => 'Reason not specified by the bank',
            self::NOAS => 'No answer from the beneficiary',
            self::NOOR => 'Original transaction not received',
            self::RC01 => 'Bank identifier (BIC) incorrect',
            self::RR01 => 'Debtor account or identification missing (regulatory reason)',
            self::RR02 => 'Debtor name or address missing (regulatory reason)',
            self::RR03 => 'Creditor name or address missing (regulatory reason)',
            self::RR04 => 'Regulatory reason',
            self::SL01 => 'Specific service offered by the debtor\'s bank',
            self::SL11 => 'Creditor not on the debtor\'s whitelist',
            self::SL12 => 'Creditor on the debtor\'s blacklist',
            self::SL13 => 'Maximum number of direct debits set by the debtor exceeded',
            self::SL14 => 'Maximum direct debit amount set by the debtor exceeded',
            self::TECH => 'Technical problem resulting in an erroneous transaction',
        };
    }

    /**
     * The translation key of the label (`reason_code.<CODE>` in the {@see self::TRANSLATION_DOMAIN} domain).
     */
    public function getTranslationKey(): string
    {
        return 'reason_code.' . $this->value;
    }
}
