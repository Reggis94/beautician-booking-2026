<?php

namespace App\ProfilePro\UI\Http\Controller\Finance;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    '/profile-pro/finance/create-stripe-connected-account',
    name: 'pro_finance_create_stripe_connected_account_page',
    methods: ['GET']
)]
final class ShowCreateStripeConnectedAccountPageController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('profilepro/finance/create_stripe_connected_account.html.twig');
    }
}
