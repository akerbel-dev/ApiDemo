<?php

namespace App\Dto;

use App\Validator\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequestDto
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public string $email;

    #[Assert\Length(max: 255)]
    public string $firstName;

    #[Assert\Length(max: 255)]
    public string $lastName;

    #[Assert\NotBlank(message: 'Password is required')]
    #[StrongPassword]
    public string $password;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
        $this->firstName = $data['firstName'] ?? '';
        $this->lastName = $data['lastName'] ?? '';
        $this->password = $data['password'] ?? '';
    }
}
