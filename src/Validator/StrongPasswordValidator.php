<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class StrongPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->validateInContext($value, $constraint, $this->context);
    }

    public function validateInContext(mixed $value, Constraint $constraint, ExecutionContextInterface $context): void
    {
        if (!$constraint instanceof StrongPassword) {
            return;
        }

        $isValid = strlen($value) >= 8
        && preg_match('/[A-Z]/', $value)
        && preg_match('/[a-z]/', $value)
        && preg_match('/[0-9]/', $value)
        && preg_match('/[\W]/', $value);

        if (!$isValid) {
            $context
            ->buildViolation($constraint->message)
            ->addViolation();
        }
    }
}
