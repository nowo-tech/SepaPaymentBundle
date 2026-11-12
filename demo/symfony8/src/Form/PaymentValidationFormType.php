<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Nowo\SepaPaymentBundle\Validator\Constraint\Bic;
use Nowo\SepaPaymentBundle\Validator\Constraint\CreditCard;
use Nowo\SepaPaymentBundle\Validator\Constraint\Iban;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCountry;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCreditorIdentifier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * REQ-DEMO-015: a FormKit form (profile `demo`) with CSRF. Payment validation form type.
 * Demonstrates the use of SEPA Payment Bundle validation constraints.
 */
#[FormKitConfig('demo')]
class PaymentValidationFormType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addText($builder, 'iban', [
            'label'              => 'IBAN',
            'required'           => true,
            'help'               => 'Example: ES9121000418450200051332',
            'translation_domain' => false,
            'placeholder'        => false,
            'attr'               => [
                'placeholder' => 'ES9121000418450200051332',
            ],
            'constraints' => [
                new NotBlank(message: 'IBAN is required.'),
                new Iban(),
            ],
        ]);
        $this->addText($builder, 'bic', [
            'label'              => 'BIC',
            'required'           => true,
            'help'               => 'Example: ESPBESMM',
            'translation_domain' => false,
            'placeholder'        => false,
            'attr'               => [
                'placeholder' => 'ESPBESMM',
            ],
            'constraints' => [
                new NotBlank(message: 'BIC is required.'),
                new Bic(),
            ],
        ]);
        $this->addText($builder, 'countryCode', [
            'label'              => 'SEPA Country Code',
            'required'           => true,
            'help'               => 'SEPA country code (2 letters). Example: ES, FR, DE',
            'translation_domain' => false,
            'placeholder'        => false,
            'attr'               => [
                'placeholder' => 'ES',
                'maxlength'   => 2,
            ],
            'constraints' => [
                new NotBlank(message: 'Country code is required.'),
                new SepaCountry(),
            ],
        ]);
        $this->addText($builder, 'creditorIdentifier', [
            'label'              => 'SEPA Creditor Identifier',
            'required'           => false,
            'help'               => 'Example: ES97ZZZM12345678 (optional)',
            'translation_domain' => false,
            'placeholder'        => false,
            'attr'               => [
                'placeholder' => 'ES97ZZZM12345678',
            ],
            'constraints' => [
                new SepaCreditorIdentifier(),
            ],
        ]);
        $this->addText($builder, 'creditCard', [
            'label'              => 'Credit Card Number',
            'required'           => false,
            'help'               => 'Example: 4532015112830366 (optional)',
            'translation_domain' => false,
            'placeholder'        => false,
            'attr'               => [
                'placeholder' => '4532015112830366',
            ],
            'constraints' => [
                new CreditCard(),
            ],
        ]);
        $this->addWithDefaults($builder, 'validate', SubmitType::class, [
            'label'              => 'Validate',
            'translation_domain' => false,
            'attr'               => ['class' => 'nowo-ui-btn nowo-ui-btn--primary'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => null,
            'csrf_protection' => true,
        ]);
    }
}
