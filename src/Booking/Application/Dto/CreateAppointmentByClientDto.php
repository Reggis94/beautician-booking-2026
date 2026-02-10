<?php

namespace App\Booking\Application\Dto;

final class CreateAppointmentByClientDto
{
    private int $proId;
    private int $serviceId;
    private \DateTimeImmutable $startDateTimeLocal;
    private string $lastName;
    private string $firstName;
    private string $email;

    public function __construct(
        int $proId,
        int $serviceId,
        string $startDateTime,
        string $lastName,
        string $firstName,
        string $email
    ) {
        if ($proId <= 0) {
            throw new \InvalidArgumentException('pro_id must be a positive integer.');
        }

        if ($serviceId <= 0) {
            throw new \InvalidArgumentException('service_id must be a positive integer.');
        }

        $startDateTime = trim($startDateTime);
        if ($startDateTime === '') {
            throw new \InvalidArgumentException('start_dt is required.');
        }

        $startDateTimeLocal = new \DateTimeImmutable($startDateTime);

        $lastName = $this->normalizeName($lastName, 'last_name');
        $firstName = $this->normalizeName($firstName, 'first_name');
        $email = $this->normalizeEmail($email);

        $this->proId = $proId;
        $this->serviceId = $serviceId;
        $this->startDateTimeLocal = $startDateTimeLocal;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->email = $email;
    }

    public function getProId(): int
    {
        return $this->proId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getStartDateTimeLocal(): \DateTimeImmutable
    {
        return $this->startDateTimeLocal;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    private function normalizeName(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException($field . ' must be a non-empty string.');
        }

        if (mb_strlen($value) > 50) {
            throw new \InvalidArgumentException($field . ' must be at most 50 characters.');
        }

        if (!preg_match('/^[\\p{L}]+(?:[\\p{L} \\-\\\']*[\\p{L}])?$/u', $value)) {
            throw new \InvalidArgumentException($field . ' must only contain letters, spaces, hyphens, or apostrophes.');
        }

        return $value;
    }

    private function normalizeEmail(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('email must be a non-empty string.');
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('email must be a valid email address.');
        }

        return $value;
    }
}
