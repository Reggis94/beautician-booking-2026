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
     *
     * Priority avoids a collision with the demo_front_pro_home_specific_user route.
     */
    #[Route('/demo/pro/dashboard', name: 'demo_pro_admin_dashboard', methods: ['GET'], priority: 1)]
    public function dashboard(Request $request): Response
    {
        return $this->renderDashboard('upcoming', $request);
    }

    #[Route('/fr/demo/pro/dashboard', name: 'demo_pro_admin_dashboard_fr', methods: ['GET'], priority: 1)]
    public function dashboardFr(Request $request): Response
    {
        return $this->renderDashboardFr('upcoming', $request);
    }

    #[Route('/fr/demo/pro/dashboard/calendrier', name: 'demo_pro_admin_calendar_fr', methods: ['GET'])]
    public function calendarFr(Request $request): Response
    {
        return $this->render('pro_admin/fr/calendar.html.twig', $this->getDemoUrlsFr($request));
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
    public function upcomingAppointments(Request $request): Response
    {
        return $this->renderDashboard('upcoming', $request);
    }

    #[Route('/fr/demo/pro/dashboard/upcoming', name: 'demo_pro_admin_appointments_upcoming_fr', methods: ['GET'])]
    public function upcomingAppointmentsFr(Request $request): Response
    {
        return $this->renderDashboardFr('upcoming', $request);
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
    public function pastAppointments(Request $request): Response
    {
        return $this->renderDashboard('past', $request);
    }

    #[Route('/fr/demo/pro/dashboard/past', name: 'demo_pro_admin_appointments_past_fr', methods: ['GET'])]
    public function pastAppointmentsFr(Request $request): Response
    {
        return $this->renderDashboardFr('past', $request);
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
    public function services(Request $request): Response
    {
        return $this->renderDashboard('services', $request);
    }

    #[Route('/fr/demo/pro/dashboard/services', name: 'demo_pro_admin_services_index_fr', methods: ['GET'])]
    public function servicesFr(Request $request): Response
    {
        return $this->renderDashboardFr('services', $request);
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
        $demoUrls = $this->getDemoUrls($request);
        $currentMonth = $this->resolveCurrentMonth($request->query->get('month'));
        $prevMonth = $currentMonth->modify('-1 month');
        $nextMonth = $currentMonth->modify('+1 month');

        return $this->render('pro_admin/opening_hours.html.twig', [
            'currentMonth' => $currentMonth,
            'daysInMonth' => (int) $currentMonth->format('t'),
            'firstWeekdayOffset' => ((int) $currentMonth->format('N')) - 1,
            'nextMonth' => $nextMonth,
            'openingHours' => $this->fakeOpeningHours($currentMonth),
            'openingHoursPath' => $demoUrls['openingHoursUrl'],
            'prevMonth' => $prevMonth,
            'saveOpeningHoursUrlTemplate' => '/demo/pro/dashboard/opening-hours/__date__',
            'today' => (new DateTimeImmutable())->format('Y-m-d'),
        ] + $demoUrls);
    }

    #[Route('/fr/demo/pro/dashboard/opening-hours', name: 'demo_pro_admin_opening_hours_fr', methods: ['GET'])]
    public function openingHoursFr(Request $request): Response
    {
        $demoUrls = $this->getDemoUrlsFr($request);
        $currentMonth = $this->resolveCurrentMonth($request->query->get('month'));

        return $this->render('pro_admin/fr/opening_hours.html.twig', [
            'currentMonth' => $currentMonth,
            'daysInMonth' => (int) $currentMonth->format('t'),
            'firstWeekdayOffset' => ((int) $currentMonth->format('N')) - 1,
            'nextMonth' => $currentMonth->modify('+1 month'),
            'openingHours' => $this->fakeOpeningHours($currentMonth),
            'openingHoursPath' => $demoUrls['openingHoursUrl'],
            'prevMonth' => $currentMonth->modify('-1 month'),
            'saveOpeningHoursUrlTemplate' => '/fr/demo/pro/dashboard/opening-hours/__date__',
            'today' => (new DateTimeImmutable())->format('Y-m-d'),
        ] + $demoUrls);
    }

    private function renderDashboard(string $activeTab, Request $request): Response
    {
        return $this->render('pro_admin/dashboard.html.twig', [
            'activeTab' => $activeTab,
            'pastAppointments' => $this->fakePastAppointments(),
            'services' => $this->fakeServices(),
            'upcomingAppointments' => $this->fakeUpcomingAppointments(),
        ] + $this->getDemoUrls($request));
    }

    private function renderDashboardFr(string $activeTab, Request $request): Response
    {
        return $this->render('pro_admin/fr/dashboard.html.twig', [
            'activeTab' => $activeTab,
            'pastAppointments' => $this->fakePastAppointmentsFr(),
            'services' => $this->fakeServicesFr(),
            'upcomingAppointments' => $this->fakeUpcomingAppointmentsFr(),
        ] + $this->getDemoUrlsFr($request));
    }

    /**
     * @return array{
     *     dashboardUrl: string,
     *     frontUrl: string,
     *     openingHoursUrl: string,
     *     pastUrl: string,
     *     servicesUrl: string,
     *     upcomingUrl: string
     * }
     */
    private function getDemoUrls(Request $request): array
    {
        $username = $request->query->getString('username');
        $username = preg_match('/^[a-zA-Z0-9_-]+$/', $username) === 1 ? $username : '';
        $adminParameters = $username === '' ? [] : ['username' => $username];
        $frontUrl = $username === ''
            ? $this->generateUrl('demo_front_pro_home')
            : $this->generateUrl('demo_front_pro_home_specific_user', ['username' => $username]);

        return [
            'dashboardUrl' => $this->generateUrl('demo_pro_admin_dashboard', $adminParameters),
            'frontUrl' => $frontUrl,
            'openingHoursUrl' => $this->generateUrl('demo_pro_admin_opening_hours', $adminParameters),
            'pastUrl' => $this->generateUrl('demo_pro_admin_appointments_past', $adminParameters),
            'servicesUrl' => $this->generateUrl('demo_pro_admin_services_index', $adminParameters),
            'upcomingUrl' => $this->generateUrl('demo_pro_admin_appointments_upcoming', $adminParameters),
        ];
    }

    /** @return array<string, string> */
    private function getDemoUrlsFr(Request $request): array
    {
        $username = $request->query->getString('username');
        $username = preg_match('/^[a-zA-Z0-9_-]+$/', $username) === 1 ? $username : '';
        $adminParameters = $username === '' ? [] : ['username' => $username];
        $frontRoute = $username === '' ? 'demo_front_pro_home_fr' : 'demo_front_pro_home_specific_user_fr';
        $frontParameters = $username === '' ? [] : ['username' => $username];

        return [
            'calendarUrl' => $this->generateUrl('demo_pro_admin_calendar_fr', $adminParameters),
            'dashboardUrl' => $this->generateUrl('demo_pro_admin_dashboard_fr', $adminParameters),
            'frontUrl' => $this->generateUrl($frontRoute, $frontParameters),
            'openingHoursUrl' => $this->generateUrl('demo_pro_admin_opening_hours_fr', $adminParameters),
            'pastUrl' => $this->generateUrl('demo_pro_admin_appointments_past_fr', $adminParameters),
            'servicesUrl' => $this->generateUrl('demo_pro_admin_services_index_fr', $adminParameters),
            'upcomingUrl' => $this->generateUrl('demo_pro_admin_appointments_upcoming_fr', $adminParameters),
        ];
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

    /**
     * @return list<array{id: int, clientName: string, clientPhone: string, serviceName: string, date: DateTimeImmutable, durationMinutes: int}>
     */
    private function fakeUpcomingAppointmentsFr(): array
    {
        return $this->translateAppointments($this->fakeUpcomingAppointments());
    }

    /**
     * @return list<array{id: int, clientName: string, clientPhone: string, serviceName: string, date: DateTimeImmutable, durationMinutes: int}>
     */
    private function fakePastAppointmentsFr(): array
    {
        return $this->translateAppointments($this->fakePastAppointments());
    }

    /**
     * @param list<array{id: int, clientName: string, clientPhone: string, serviceName: string, date: DateTimeImmutable, durationMinutes: int}> $appointments
     * @return list<array{id: int, clientName: string, clientPhone: string, serviceName: string, date: DateTimeImmutable, durationMinutes: int}>
     */
    private function translateAppointments(array $appointments): array
    {
        $serviceNames = [
            'Bridal trial' => 'Essai maquillage de mariée',
            'Brow shaping' => 'Restructuration des sourcils',
            'Classic manicure' => 'Manucure classique',
            'Hair gloss and styling' => 'Gloss et coiffage',
            'Lash lift' => 'Rehaussement de cils',
            'Makeup session' => 'Séance de maquillage',
            'Signature facial' => 'Soin du visage signature',
        ];

        return array_map(static function (array $appointment) use ($serviceNames): array {
            $appointment['serviceName'] = $serviceNames[$appointment['serviceName']] ?? $appointment['serviceName'];

            return $appointment;
        }, $appointments);
    }

    /**
     * @return list<array{id: int, name: string, description: string, durationMinutes: int, price: string}>
     */
    private function fakeServicesFr(): array
    {
        return [
            [
                'description' => 'Un soin sur mesure avec nettoyage doux, exfoliation et finition hydratante pour un teint lumineux.',
                'durationMinutes' => 60,
                'id' => 301,
                'name' => 'Soin du visage signature',
                'price' => '95',
            ],
            [
                'description' => 'Une mise en forme précise pour définir les sourcils tout en conservant un résultat naturel et soigné.',
                'durationMinutes' => 45,
                'id' => 302,
                'name' => 'Restructuration des sourcils',
                'price' => '42',
            ],
            [
                'description' => 'Un soin gloss et un coiffage pour lisser les cheveux, renforcer leur brillance et parfaire le résultat.',
                'durationMinutes' => 90,
                'id' => 303,
                'name' => 'Gloss et coiffage',
                'price' => '135',
            ],
            [
                'description' => 'Un maquillage personnalisé pour une finition élégante, adaptée à la cliente et à l’occasion.',
                'durationMinutes' => 75,
                'id' => 304,
                'name' => 'Séance de maquillage',
                'price' => '120',
            ],
        ];
    }
}
