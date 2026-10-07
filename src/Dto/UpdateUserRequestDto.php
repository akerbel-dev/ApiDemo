<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserRequestDto
{
    #[Assert\Email(message: 'Invalid email format')]
    public ?string $email = null;

    #[Assert\Length(max: 255)]
    public ?string $firstName = null;

    #[Assert\Length(max: 255)]
    public ?string $lastName = null;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? null;
        $this->firstName = $data['firstName'] ?? null;
        $this->lastName = $data['lastName'] ?? null;
    }
}
