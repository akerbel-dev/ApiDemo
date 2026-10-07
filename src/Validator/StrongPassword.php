<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class StrongPassword extends Constraint
{
    public string $message = 'Password must be at least 8 characters long, contain uppercase, lowercase, number and special character.';
}
