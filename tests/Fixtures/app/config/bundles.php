<?php

declare(strict_types=1);
use Nowo\SepaPaymentBundle\NowoSepaPaymentBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

return [
    FrameworkBundle::class       => ['all' => true],
    NowoSepaPaymentBundle::class => ['all' => true],
];
