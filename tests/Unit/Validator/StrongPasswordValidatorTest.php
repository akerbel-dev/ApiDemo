<?php

namespace App\Tests\Unit\Validator;

use App\Validator\StrongPassword;
use App\Validator\StrongPasswordValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

#[CoversClass(StrongPasswordValidator::class)]
final class StrongPasswordValidatorTest extends TestCase
{
    #[Test]
    public function itReturnsSilentlyWhenConstraintIsNotStrongpassword(): void
    {
        $validator = new StrongPasswordValidator();

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator->validateInContext('Anything', $this->createStub(\Symfony\Component\Validator\Constraint::class), $context);
    }

    #[Test]
    public function validPasswordProducesNoViolation(): void
    {
        $validator = new StrongPasswordValidator();

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $constraint = new StrongPassword();

        $validator->validateInContext('Valid123!', $constraint, $context);
    }

    #[Test]
    public function tooShortPasswordProducesViolation(): void
    {
        $validator = new StrongPasswordValidator();
        $constraint = new StrongPassword();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
        ->method('buildViolation')
        ->with($constraint->message)
        ->willReturn($builder);

        $validator->validateInContext('Ab1!', $constraint, $context);
    }

    #[Test]
    public function missingUppercaseProducesViolation(): void
    {
        $validator = new StrongPasswordValidator();
        $constraint = new StrongPassword();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
        ->method('buildViolation')
        ->with($constraint->message)
        ->willReturn($builder);

        $validator->validateInContext('valid123!', $constraint, $context);
    }

    #[Test]
    public function missingLowercaseProducesViolation(): void
    {
        $validator = new StrongPasswordValidator();
        $constraint = new StrongPassword();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
        ->method('buildViolation')
        ->with($constraint->message)
        ->willReturn($builder);

        $validator->validateInContext('VALID123!', $constraint, $context);
    }

    #[Test]
    public function missingDigitProducesViolation(): void
    {
        $validator = new StrongPasswordValidator();
        $constraint = new StrongPassword();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
        ->method('buildViolation')
        ->with($constraint->message)
        ->willReturn($builder);

        $validator->validateInContext('Valid!!!', $constraint, $context);
    }

    #[Test]
    public function missingSpecialCharacterProducesViolation(): void
    {
        $validator = new StrongPasswordValidator();
        $constraint = new StrongPassword();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
        ->method('buildViolation')
        ->with($constraint->message)
        ->willReturn($builder);

        $validator->validateInContext('Valid123', $constraint, $context);
    }
}
