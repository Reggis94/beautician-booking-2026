<?php

namespace App\ProfilePro\Application\Finance\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateStripeConnectedAccountDto
{
    #[Assert\NotBlank(message: 'country is required.', normalizer: 'trim')]
    #[Assert\Regex(pattern: '/^[A-Z]{2}$/', message: 'country must be a two-letter uppercase country code.')]
    public readonly string $country;

    #[Assert\NotBlank(message: 'account_token is required.', normalizer: 'trim')]
    #[Assert\Length(max: 255, maxMessage: 'account_token must be at most 255 characters.', normalizer: 'trim')]
    public readonly string $accountToken;

    public function __construct(string $country, string $accountToken)
    {
        $this->country = strtoupper(trim($country));
        $this->accountToken = trim($accountToken);
    }
}
