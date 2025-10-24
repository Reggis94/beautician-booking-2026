<?php

namespace App\Controller;

use App\Dao\AppointmentDao;
use App\Dto\AppointmentDto;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppointmentController extends AbstractController
{
    public function __construct(private readonly AppointmentDao $appointmentDao) {}

    #[Route('/pro/appointment', name: 'appointmentForm', methods: ['GET', 'POST'])]
    public function form(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not authenticated');
        }

        $id = $request->query->getInt('id') ?: null;
        $dto = $id ? $this->appointmentDao->findOneForPro((int) $user->getId(), $id) : null;
        $appointment = $dto ?? new AppointmentDto();

        $builder = $this->createFormBuilder($appointment, [
                'method' => 'POST',
                'action' => $this->generateUrl('appointmentForm', $id ? ['id' => $id] : []),
            ])
            ->add('startDt', DateTimeType::class, [
                'label' => 'Start',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'string',
                'input_format' => 'Y-m-d\TH:i',
                'required' => false,
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Duration (min)',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('save', SubmitType::class)
        ;

        // Normalize model and submitted string to the expected HTML5 datetime-local format
        $builder->get('startDt')->addModelTransformer(new CallbackTransformer(
            function ($modelValue) {
                if ($modelValue === null || $modelValue === '') {
                    return $modelValue;
                }
                $patterns = ['Y-m-d\\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d\\TH:i', 'Y-m-d H:i'];
                foreach ($patterns as $p) {
                    $dt = \DateTimeImmutable::createFromFormat($p, (string) $modelValue);
                    if ($dt instanceof \DateTimeImmutable) {
                        return $dt->format('Y-m-d\\TH:i');
                    }
                }
                try {
                    return (new \DateTimeImmutable((string) $modelValue))->format('Y-m-d\\TH:i');
                } catch (\Exception) {
                    return $modelValue; // let the form show the raw value if unparsable
                }
            },
            function ($submittedValue) {
                if ($submittedValue === null || $submittedValue === '') {
                    return null;
                }
                $patterns = ['Y-m-d\\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d\\TH:i', 'Y-m-d H:i'];
                foreach ($patterns as $p) {
                    $dt = \DateTimeImmutable::createFromFormat($p, (string) $submittedValue);
                    if ($dt instanceof \DateTimeImmutable) {
                        return $dt->format('Y-m-d\\TH:i');
                    }
                }
                try {
                    return (new \DateTimeImmutable((string) $submittedValue))->format('Y-m-d\\TH:i');
                } catch (\Exception) {
                    return $submittedValue; // fallback, validation may catch it
                }
            }
        ));

        $form = $builder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $savedId = $this->appointmentDao->upsert($appointment->getId(), (int) $user->getId(), $appointment);
            $this->addFlash('success', 'Appointment saved.');
            return $this->redirectToRoute('appointmentForm', ['id' => $savedId]);
        }

        return $this->render('appointment/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/pro/appointment/{id}/delete', name: 'deleteAppointment', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not authenticated');
        }

        $this->appointmentDao->softDelete((int) $user->getId(), $id);
        $this->addFlash('success', 'Appointment deleted.');
        return $this->redirectToRoute('appointmentForm');
    }
}
