<?php

namespace App\Dto;

class AppointmentDto
{
    private ?int $id = null;
    private ?int $serviceId = null;
    private ?string $startDt = null; // e.g. '2026-03-15T09:30'
    private ?int $duration = null;   // minutes
    private ?string $lastName = null;
    private ?string $firstName = null;
    private ?string $email = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }

    public function getServiceId(): ?int { return $this->serviceId; }
    public function setServiceId(?int $serviceId): self { $this->serviceId = $serviceId; return $this; }

    public function getStartDt(): ?string { return $this->startDt; }
    public function setStartDt(?string $startDt): self { $this->startDt = $startDt; return $this; }

    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(?int $duration): self { $this->duration = $duration; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): self { $this->lastName = $lastName; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): self { $this->firstName = $firstName; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
}

