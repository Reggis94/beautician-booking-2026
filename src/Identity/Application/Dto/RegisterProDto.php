<?php

namespace App\Identity\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterProDto
{
    #[Assert\NotBlank(message: 'First name cannot be empty.', normalizer: 'trim')]
    #[Assert\Length(max: 50, maxMessage: 'First name must be at most 50 characters.', normalizer: 'trim')]
    public readonly string $firstName;

    #[Assert\NotBlank(message: 'Last name cannot be empty.', normalizer: 'trim')]
    #[Assert\Length(max: 50, maxMessage: 'Last name must be at most 50 characters.', normalizer: 'trim')]
    public readonly string $lastName;

    #[Assert\NotBlank(message: 'Email cannot be empty.', normalizer: 'trim')]
    #[Assert\Email(message: 'Email must be a valid email address.')]
    public readonly string $email;

    public function __construct(string $firstName, string $lastName, string $email)
    {
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
        $this->email = trim($email);
    }
}
