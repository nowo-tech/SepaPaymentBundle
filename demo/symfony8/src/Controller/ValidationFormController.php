<?php

namespace App\Controller;

use App\Form\PaymentValidationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Validation form controller.
 * Demonstrates the use of SEPA Payment Bundle validation constraints in forms.
 */
class ValidationFormController extends AbstractController
{
    /**
     * Demo form with SEPA validation constraints.
     *
     * @param Request $request Request object
     * @return Response
     */
    #[Route('/demo-validation-form', name: 'demo_validation_form')]
    public function validationForm(Request $request): Response
    {
        $form = $this->createForm(PaymentValidationFormType::class);
        $form->handleRequest($request);

        $submittedData = null;
        $isValid = false;

        if ($form->isSubmitted()) {
            $isValid = $form->isValid();
            if ($isValid) {
                $submittedData = $form->getData();
            }
        }

        return $this->render('demo/validation_form.html.twig', [
            'form' => $form,
            'submittedData' => $submittedData,
            'isValid' => $isValid,
            'isSubmitted' => $form->isSubmitted(),
        ]);
    }
}
