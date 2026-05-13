<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\Request;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \RuntimeException
{
    public function __construct(private readonly ConstraintViolationListInterface $violations)
    {
        parent::__construct((string) $violations);
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
