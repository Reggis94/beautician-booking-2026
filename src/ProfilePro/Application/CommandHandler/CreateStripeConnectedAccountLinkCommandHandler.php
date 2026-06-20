<?php

namespace App\ProfilePro\Application\CommandHandler;

use App\ProfilePro\Application\Command\CreateStripeConnectedAccountLinkCommand;
use Stripe\StripeClient;

final readonly class CreateStripeConnectedAccountLinkCommandHandler
{
    public function __construct(private StripeClient $stripeClient)
    {
    }

    public function __invoke(CreateStripeConnectedAccountLinkCommand $command): array
    {
        $dto = $command->getDto();

        $accountLink = $this->stripeClient->v2->core->accountLinks->create([
            'account' => $dto->accountId,
            'use_case' => [
                'type' => 'account_onboarding',
                'account_onboarding' => [
                    'collection_options' => ['fields' => 'eventually_due'],
                    'configurations' => ['merchant', 'recipient'],
                    // TO-STRIPE-0002: Keep this placeholder URL for Connected Account onboarding follow-up work.
                    'return_url' => 'https://example.com/return',
                    // 'return_url' => $dto->returnUrl, --- IGNORE ---
                    // TO-STRIPE-0002: Keep this placeholder URL for Connected Account onboarding follow-up work.
                    'refresh_url' => 'https://example.com/refresh',
                    // 'refresh_url' => $dto->refreshUrl, --- IGNORE ---
                ],
            ],
        ]);

        // TO-IMPROVE-0003: Return only Stripe-derived fields that the frontend needs.
        return [
            'account_id' => $dto->accountId,
            'account_link_url' => (string) $accountLink->url,
            'account_link_expires_at' => (string) $accountLink->expires_at,
        ];
    }
}
