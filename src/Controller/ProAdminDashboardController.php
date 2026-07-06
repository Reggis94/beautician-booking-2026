<?php

namespace App\Controller;

use DateTimeImmutable;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProAdminDashboardController extends AbstractController
{
    /**
     * ProAdmin customer demo landing page.
     *
     * Temporary fake-data route for customer preview of the professional dashboard shell.
     * Defaults to the upcoming appointments view until real ProAdmin navigation
     * and read models are connected.
     */
    #[Route('/demo/pro/dashboard', name: 'demo_pro_admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->renderDashboard('upcoming');
    }

    /**
     * ProAdmin customer demo upcoming appointments page.
     *
     * Temporary fake-data route for customer preview of future appointments
     * managed by the professional.
     */
    #[Route(
        '/demo/pro/dashboard/upcoming',
        name: 'demo_pro_admin_appointments_upcoming',
        methods: ['GET']
    )]
    public function upcomingAppointments(): Response
    {
        return $this->renderDashboard('upcoming');
    }

    /**
     * Backend-connected upcoming appointments page.
     *
     * This real ProAdmin page keeps the demo layout but loads appointment data
     * through the ProAdmin appointments API.
     */
    #[Route(
        '/pro/dashboard/upcoming',
        name: 'pro_admin_appointments_upcoming',
        methods: ['GET'],
        env: 'dev'
    )]
    public function connectedUpcomingAppointments(Request $request): Response
    {
        $proId = (int) $request->query->get('proId', 0);
        $upcomingUrlParameters = $proId > 0 ? ['proId' => $proId] : [];

        return $this->render('pro_admin/dashboard.html.twig', [
            'activeTab' => 'upcoming',
            'dashboardUrl' => $this->generateUrl('pro_admin_appointments_upcoming', $upcomingUrlParameters),
            'frontUrl' => $this->generateUrl('demo_front_pro_home'),
            'isDemoDashboard' => false,
            'openingHoursUrl' => $this->generateUrl('demo_pro_admin_opening_hours'),
            'pastAppointments' => [],
            'pastUrl' => $this->generateUrl('demo_pro_admin_appointments_past'),
            'services' => [],
            'servicesUrl' => $this->generateUrl('demo_pro_admin_services_index'),
            'upcomingAppointments' => [],
            'upcomingAppointmentsApiUrl' => $proId > 0
                ? $this->generateUrl('api_pro_admin_appointments_upcoming', ['proId' => $proId])
                : null,
            'upcomingAppointmentsLoadError' => $proId <= 0
                ? 'Add a positive proId query parameter to load appointments.'
                : null,
            'upcomingUrl' => $this->generateUrl('pro_admin_appointments_upcoming', $upcomingUrlParameters),
        ]);
    }

    /**
     * ProAdmin customer demo past appointments page.
     *
     * Temporary fake-data route for customer preview of appointment history
     * managed by the professional.
     */
    #[Route(
        '/demo/pro/dashboard/past',
        name: 'demo_pro_admin_appointments_past',
        methods: ['GET']
    )]
    public function pastAppointments(): Response
    {
        return $this->renderDashboard('past');
    }

    /**
     * ProAdmin customer demo services page.
     *
     * Temporary fake-data route for customer preview of service management.
     */
    #[Route(
        '/demo/pro/dashboard/services',
        name: 'demo_pro_admin_services_index',
        methods: ['GET']
    )]
    public function services(): Response
    {
        return $this->renderDashboard('services');
    }

    /**
     * ProAdmin customer demo opening hours page.
     *
     * Temporary fake-data route for customer preview of opening hours management.
     */
    #[Route(
        '/demo/pro/dashboard/opening-hours',
        name: 'demo_pro_admin_opening_hours',
        methods: ['GET']
    )]
    public function openingHours(Request $request): Response
    {
        $currentMonth = $this->resolveCurrentMonth($request->query->get('month'));
        $prevMonth = $currentMonth->modify('-1 month');
        $nextMonth = $currentMonth->modify('+1 month');

        return $this->render('pro_admin/opening_hours.html.twig', [
            'currentMonth' => $currentMonth,
            'daysInMonth' => (int) $currentMonth->format('t'),
            'firstWeekdayOffset' => ((int) $currentMonth->format('N')) - 1,
            'nextMonth' => $nextMonth,
            'openingHours' => $this->fakeOpeningHours($currentMonth),
            'openingHoursPath' => $this->generateUrl('demo_pro_admin_opening_hours'),
            'prevMonth' => $prevMonth,
            'saveOpeningHoursUrlTemplate' => '/demo/pro/dashboard/opening-hours/__date__',
            'today' => (new DateTimeImmutable())->format('Y-m-d'),
        ]);
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

    #[Route('/pro/logout', name: 'app_logout', methods: ['GET'], env: 'dev')]
    public function logout(): void
    {
        throw new LogicException('This route is intercepted by the security firewall logout handler.');
    }

    private function resolveCurrentMonth(?string $month): DateTimeImmutable
    {
        if ($month === null || $month === '') {
            return new DateTimeImmutable('first day of this month');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');

        if (!$date instanceof DateTimeImmutable) {
            return new DateTimeImmutable('first day of this month');
        }

        return $date;
    }

    /**
     * @return array<string, array{isOpen: bool, openTime: string, closeTime: string}>
     */
    private function fakeOpeningHours(DateTimeImmutable $currentMonth): array
    {
        $openingHours = [];
        $daysInMonth = (int) $currentMonth->format('t');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentMonth->setDate(
                (int) $currentMonth->format('Y'),
                (int) $currentMonth->format('m'),
                $day
            );
            $weekday = (int) $date->format('N');
            $isOpen = $weekday <= 6;
            $openTime = $weekday === 6 ? '10:00' : '09:00';
            $closeTime = $weekday === 6 ? '15:00' : '18:00';

            $openingHours[$date->format('Y-m-d')] = [
                'closeTime' => $closeTime,
                'isOpen' => $isOpen,
                'openTime' => $openTime,
            ];
        }

        return $openingHours;
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
     * @return list<array{id: int, name: string, description: string, durationMinutes: int, price: string}>
     */
    private function fakeServices(): array
    {
        return [
            [
                'description' => 'A tailored skin refresh with gentle cleansing, exfoliation, and finishing care for a soft glow.',
                'durationMinutes' => 60,
                'id' => 301,
                'name' => 'Signature facial',
                'price' => '95',
            ],
            [
                'description' => 'Precise shaping and detailing to define the brows while keeping a natural, polished look.',
                'durationMinutes' => 45,
                'id' => 302,
                'name' => 'Brow shaping',
                'price' => '42',
            ],
            [
                'description' => 'Gloss treatment and styling designed to smooth the hair, boost shine, and finish the look.',
                'durationMinutes' => 90,
                'id' => 303,
                'name' => 'Hair gloss and styling',
                'price' => '135',
            ],
            [
                'description' => 'Personalized makeup application for an elegant finish suited to the client and occasion.',
                'durationMinutes' => 75,
                'id' => 304,
                'name' => 'Makeup session',
                'price' => '120',
            ],
        ];
    }
}
