<?php

namespace App\ProfilePro\Application\CommandHandler;

use App\ProfilePro\Application\Command\CreateStripeConnectedAccountCommand;
use Stripe\StripeClient;

final readonly class CreateStripeConnectedAccountCommandHandler
{
    public function __construct(
        private StripeClient $stripeClient,
        private string $platformName
    ) {
    }

    public function __invoke(CreateStripeConnectedAccountCommand $command): array
    {
        $dto = $command->getDto();

        $account = $this->stripeClient->v2->core->accounts->create([
            'dashboard' => 'none',
            'configuration' => [
                'merchant' => [
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                    ],
                    'statement_descriptor' => [
                        'descriptor' => $this->platformName,
                    ],
                ],
                'recipient' => [
                    'capabilities' => [
                        'stripe_balance' => [
                            'stripe_transfers' => ['requested' => true],
                        ],
                    ],
                ],
            ],
            'defaults' => [
                'responsibilities' => [
                    'fees_collector' => 'stripe',
                    'losses_collector' => 'stripe',
                ],
            ],
            'include' => [
                'configuration.merchant',
                'configuration.recipient',
                'requirements',
            ],
            'identity' => [
                'country' => $dto->country,
            ],
            'account_token' => $dto->accountToken,
        ]);

        // TO-IMPROVE-0003: Return only Stripe-derived fields that the frontend needs.
        return [
            'account_token_id' => $dto->accountToken,
            'account_id' => (string) $account->id,
        ];
    }
}
