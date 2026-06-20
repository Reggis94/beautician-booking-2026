<?php

namespace App\ProfilePro\Application\Finance\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateStripeConnectedAccountTokenDto
{
    #[Assert\NotBlank(message: 'company_name is required.', normalizer: 'trim')]
    #[Assert\Length(max: 255, maxMessage: 'company_name must be at most 255 characters.', normalizer: 'trim')]
    public readonly string $companyName;

    #[Assert\Email(message: 'email must be a valid email address.')]
    #[Assert\Length(max: 255, maxMessage: 'email must be at most 255 characters.', normalizer: 'trim')]
    public readonly ?string $email;

    public function __construct(string $companyName, ?string $email)
    {
        $this->companyName = trim($companyName);

        $normalizedEmail = $email === null ? null : trim($email);
        $this->email = $normalizedEmail === '' ? null : $normalizedEmail;
    }
}
