<?php

namespace App\Controller;

use DateTimeImmutable;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProAdminDashboardController extends AbstractController
{
    /**
     * ProAdmin landing page.
     *
     * Temporary fake-data route for previewing the professional dashboard shell.
     * Defaults to the upcoming appointments view until real ProAdmin navigation
     * and read models are connected.
     */
    #[Route('/pro/dashboard', name: 'pro_admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->renderDashboard('upcoming');
    }

    /**
     * ProAdmin upcoming appointments page.
     *
     * Temporary fake-data route for previewing future appointments managed by
     * the professional.
     */
    #[Route(
        '/pro/dashboard/upcoming',
        name: 'pro_admin_appointments_upcoming',
        methods: ['GET']
    )]
    public function upcomingAppointments(): Response
    {
        return $this->renderDashboard('upcoming');
    }

    /**
     * ProAdmin past appointments page.
     *
     * Temporary fake-data route for previewing appointment history managed by
     * the professional.
     */
    #[Route(
        '/pro/dashboard/past',
        name: 'pro_admin_appointments_past',
        methods: ['GET']
    )]
    public function pastAppointments(): Response
    {
        return $this->renderDashboard('past');
    }

    /**
     * ProAdmin services page.
     *
     * Temporary fake-data route for previewing service management.
     */
    #[Route(
        '/pro/dashboard/services',
        name: 'pro_admin_services_index',
        methods: ['GET']
    )]
    public function services(): Response
    {
        return $this->renderDashboard('services');
    }

    private function renderDashboard(string $activeTab): Response
    {
        return $this->render('pro_admin/dashboard.html.twig', [
            'activeTab' => $activeTab,
            'pastAppointments' => $this->fakePastAppointments(),
            'services' => $this->fakeServices(),
            'upcomingAppointments' => $this->fakeUpcomingAppointments(),
        ]);
    }

    #[Route('/pro/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new LogicException('This route is intercepted by the security firewall logout handler.');
    }

    /**
     * @return list<array{id: int, clientName: string, clientPhone: string, serviceName: string, date: DateTimeImmutable, durationMinutes: int}>
     */
    private function fakeUpcomingAppointments(): array
    {
        return [
            [
                'clientName' => 'Amelia Stone',
                'clientPhone' => '+1 212 555 0148',
                'date' => new DateTimeImmutable('tomorrow 09:30'),
                'durationMinutes' => 60,
                'id' => 101,
                'serviceName' => 'Signature facial',
            ],
            [
                'clientName' => 'Maya Bennett',
                'clientPhone' => '+1 212 555 0192',
                'date' => new DateTimeImmutable('tomorrow 11:00'),
                'durationMinutes' => 45,
                'id' => 102,
                'serviceName' => 'Brow shaping',
            ],
            [
                'clientName' => 'Sofia Garcia',
                'clientPhone' => '+1 212 555 0177',
                'date' => new DateTimeImmutable('+2 days 14:15'),
                'durationMinutes' => 90,
                'id' => 103,
                'serviceName' => 'Hair gloss and styling',
            ],
            [
                'clientName' => 'Claire Martin',
                'clientPhone' => '+1 212 555 0183',
                'date' => new DateTimeImmutable('+5 days 10:00'),
                'durationMinutes' => 75,
                'id' => 104,
                'serviceName' => 'Makeup session',
            ],
        ];
    }

    /**
     * @return list<array{id: int, clientName: string, clientPhone: string, serviceName: string, date: DateTimeImmutable, durationMinutes: int}>
     */
    private function fakePastAppointments(): array
    {
        return [
            [
                'clientName' => 'Nora Lewis',
                'clientPhone' => '+1 212 555 0160',
                'date' => new DateTimeImmutable('yesterday 16:00'),
                'durationMinutes' => 50,
                'id' => 201,
                'serviceName' => 'Lash lift',
            ],
            [
                'clientName' => 'Elena Brooks',
                'clientPhone' => '+1 212 555 0114',
                'date' => new DateTimeImmutable('-3 days 13:30'),
                'durationMinutes' => 60,
                'id' => 202,
                'serviceName' => 'Classic manicure',
            ],
            [
                'clientName' => 'Isabelle Wright',
                'clientPhone' => '+1 212 555 0188',
                'date' => new DateTimeImmutable('-3 days 09:15'),
                'durationMinutes' => 120,
                'id' => 203,
                'serviceName' => 'Bridal trial',
            ],
        ];
    }

    /**
     * @return list<array{id: int, name: string, durationMinutes: int, price: string}>
     */
    private function fakeServices(): array
    {
        return [
            [
                'durationMinutes' => 60,
                'id' => 301,
                'name' => 'Signature facial',
                'price' => '95',
            ],
            [
                'durationMinutes' => 45,
                'id' => 302,
                'name' => 'Brow shaping',
                'price' => '42',
            ],
            [
                'durationMinutes' => 90,
                'id' => 303,
                'name' => 'Hair gloss and styling',
                'price' => '135',
            ],
            [
                'durationMinutes' => 75,
                'id' => 304,
                'name' => 'Makeup session',
                'price' => '120',
            ],
        ];
    }
}
