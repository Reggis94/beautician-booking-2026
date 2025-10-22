<?php

namespace App\Controller;

use App\Dao\AvailabilityDao;
use App\Dto\AvailabilityDto;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
class AvailabilityController extends AbstractController
{
    public function __construct(private readonly AvailabilityDao $availabilityDao)
    {
    }

    #[Route('/pro/disponibilite', name: 'createUpdateAvailabilty', methods: ['GET', 'POST'])]
    public function createUpdateAvailabilty(Request $request): Response
    {
        $dateLocal = $request->query->get('dateLocal');
        if (!$dateLocal) {
            throw new BadRequestHttpException('Missing dateLocal query parameter');
        }

        $availability = (new AvailabilityDto())
            ->setDateLocal($dateLocal);

        // Pre-fill with existing availability if present
        $user = $this->getUser();
        if ($user instanceof User) {
            $existing = $this->availabilityDao->findActiveByProAndDate((int) $user->getId(), $dateLocal);
            if ($existing instanceof AvailabilityDto) {
                $availability = $existing;
            }
        }

        $builder = $this->createFormBuilder($availability, [
            'method' => 'POST',
            'action' => $this->generateUrl('createUpdateAvailabilty', ['dateLocal' => $dateLocal]),
        ])
            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'string',
            ])
            ->add('endTime', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'string',
            ])
            ->add('save', SubmitType::class);

        $padSeconds = function ($time) {
            if ($time === null || $time === '') {
                return $time;
            }
            if (is_string($time) && preg_match('/^\d{2}:\d{2}$/', $time)) {
                return $time . ':00';
            }
            return $time;
        };

        $builder->get('startTime')->addModelTransformer(new CallbackTransformer(
            fn ($modelValue) => $padSeconds($modelValue),
            fn ($submittedValue) => $padSeconds($submittedValue)
        ));
        $builder->get('endTime')->addModelTransformer(new CallbackTransformer(
            fn ($modelValue) => $padSeconds($modelValue),
            fn ($submittedValue) => $padSeconds($submittedValue)
        ));

        $form = $builder->getForm();

        $form->handleRequest($request);

        // Inline validation: ensure start < end using DateTime objects
        if ($form->isSubmitted()) {
            $startStr = $availability->getStartTime();
            $endStr = $availability->getEndTime();
            if ($startStr !== null && $startStr !== '' && $endStr !== null && $endStr !== '') {
                $startStrNorm = strlen($startStr) === 5 ? ($startStr . ':00') : $startStr; // HH:MM -> HH:MM:00
                $endStrNorm = strlen($endStr) === 5 ? ($endStr . ':00') : $endStr;

                $startAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$dateLocal $startStrNorm")
                    ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i', "$dateLocal $startStr");
                $endAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$dateLocal $endStrNorm")
                    ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i', "$dateLocal $endStr");

                if ($startAt instanceof \DateTimeImmutable && $endAt instanceof \DateTimeImmutable) {
                    if ($startAt >= $endAt) {
                        $form->get('endTime')->addError(new FormError('End time must be after start time.'));
                    }
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('User not authenticated');
            }

            $this->availabilityDao->upsert((int) $user->getId(), $availability);

            $this->addFlash('success', 'Disponibilité enregistrée.');
            return $this->redirectToRoute('createUpdateAvailabilty', [
                'dateLocal' => $availability->getDateLocal(),
            ]);
        }

        return $this->render('new.html.twig', [
            'form' => $form->createView(),
            'dateLocal' => $dateLocal,
        ]);
    }

    #[Route('/pro/disponibilite/supprimer', name: 'deleteAvailability', methods: ['GET', 'POST'])]
    public function deleteAvailability(Request $request): Response
    {
        $dateLocal = $request->query->get('dateLocal');
        if (!$dateLocal) {
            throw new BadRequestHttpException('Missing dateLocal query parameter');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not authenticated');
        }

        $this->availabilityDao->softDelete((int) $user->getId(), $dateLocal);
        $this->addFlash('success', 'Disponibilité supprimée.');
        return $this->redirectToRoute('createUpdateAvailabilty', ['dateLocal' => $dateLocal]);
    }
}
