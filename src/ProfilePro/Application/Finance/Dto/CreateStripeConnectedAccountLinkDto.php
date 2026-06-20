<?php

namespace App\ProfilePro\Application\Finance\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateStripeConnectedAccountLinkDto
{
    #[Assert\NotBlank(message: 'account_id is required.', normalizer: 'trim')]
    #[Assert\Length(max: 255, maxMessage: 'account_id must be at most 255 characters.', normalizer: 'trim')]
    public readonly string $accountId;

    public readonly string $returnUrl;

    public readonly string $refreshUrl;

    public function __construct(string $accountId, string $returnUrl, string $refreshUrl)
    {
        $this->accountId = trim($accountId);
        $this->returnUrl = trim($returnUrl);
        $this->refreshUrl = trim($refreshUrl);
    }
}
