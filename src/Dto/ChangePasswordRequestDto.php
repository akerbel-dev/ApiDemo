<?php

namespace App\Dto;

use App\Validator\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordRequestDto
{
    #[Assert\NotBlank]
    public ?string $oldPassword = null;

    #[Assert\NotBlank]
    #[StrongPassword]
    public ?string $newPassword = null;

    /**
     * @param array<string, string|null> $data
     */
    public function __construct(array $data)
    {
        $this->oldPassword = $data['oldPassword'] ?? '';
        $this->newPassword = $data['newPassword'] ?? '';
    }
}
