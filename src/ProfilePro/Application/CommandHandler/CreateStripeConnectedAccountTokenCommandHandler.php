<?php

namespace App\ProfilePro\Application\CommandHandler;

use App\ProfilePro\Application\Command\CreateStripeConnectedAccountTokenCommand;
use Stripe\StripeClient;

final readonly class CreateStripeConnectedAccountTokenCommandHandler
{
    public function __construct(
        private StripeClient $stripeClient,
        private string $stripePreviewVersion
    ) {
    }

    public function __invoke(CreateStripeConnectedAccountTokenCommand $command): array
    {
        $dto = $command->getDto();

        $createAccountTokenPayload = [
            'display_name' => $dto->companyName,
            'identity' => [
                'entity_type' => 'company',
                'business_details' => [
                    'registered_name' => $dto->companyName,
                ],
            ],
        ];

        if ($dto->email !== null) {
            $createAccountTokenPayload['contact_email'] = $dto->email;
        }

        $accountToken = $this->stripeClient->v2->core->accountTokens->create(
            $createAccountTokenPayload,
            ['stripe_version' => $this->stripePreviewVersion]
        );

        // TO-IMPROVE-0003: Avoid exposing raw Stripe response objects to the frontend.
        return [
            'account_token_id' => (string) $accountToken->id,
            'raw' => $accountToken,
        ];
    }
}
