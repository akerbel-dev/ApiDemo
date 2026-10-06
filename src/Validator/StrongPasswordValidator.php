<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class StrongPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value) {
            return;
        }

        $isValid = strlen($value) >= 8
        && preg_match('/[A-Z]/', $value)
        && preg_match('/[a-z]/', $value)
        && preg_match('/[0-9]/', $value)
        && preg_match('/[\W]/', $value);

        if (!$isValid) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
