<?php

namespace App\Form;

use Nowo\SepaPaymentBundle\Validator\Constraint\Bic;
use Nowo\SepaPaymentBundle\Validator\Constraint\CreditCard;
use Nowo\SepaPaymentBundle\Validator\Constraint\Iban;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCountry;
use Nowo\SepaPaymentBundle\Validator\Constraint\SepaCreditorIdentifier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Payment validation form type.
 * Demonstrates the use of SEPA Payment Bundle validation constraints.
 */
class PaymentValidationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('iban', TextType::class, [
                'label' => 'IBAN',
                'required' => true,
                'attr' => [
                    'placeholder' => 'ES9121000418450200051332',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'IBAN is required.']),
                    new Iban(),
                ],
            ])
            ->add('bic', TextType::class, [
                'label' => 'BIC',
                'required' => true,
                'attr' => [
                    'placeholder' => 'ESPBESMM',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'BIC is required.']),
                    new Bic(),
                ],
            ])
            ->add('countryCode', TextType::class, [
                'label' => 'SEPA Country Code',
                'required' => true,
                'attr' => [
                    'placeholder' => 'ES',
                    'class' => 'form-control',
                    'maxlength' => 2,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Country code is required.']),
                    new SepaCountry(),
                ],
            ])
            ->add('creditorIdentifier', TextType::class, [
                'label' => 'SEPA Creditor Identifier',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ES97ZZZM12345678',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new SepaCreditorIdentifier(),
                ],
            ])
            ->add('creditCard', TextType::class, [
                'label' => 'Credit Card Number',
                'required' => false,
                'attr' => [
                    'placeholder' => '4532015112830366',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new CreditCard(),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
        ]);
    }
}
