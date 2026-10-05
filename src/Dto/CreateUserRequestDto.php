<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequestDto
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public string $email;

    #[Assert\Length(max: 255)]
    public string $firstName;

    #[Assert\Length(max: 255)]
    public string $lastName;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        max: 255,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    public string $password;

    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
        $this->firstName = $data['firstName'] ?? '';
        $this->lastName = $data['lastName'] ?? '';
        $this->password = $data['password'] ?? '';
    }
}
