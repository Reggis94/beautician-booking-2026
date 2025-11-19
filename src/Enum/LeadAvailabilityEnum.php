<?php

namespace App\Enum;

/**
 * Lead availability preferences with French labels.
 */
enum LeadAvailabilityEnum: string
{
    case ASAP = 'ASAP';        // As soon as possible
    case WD_DAY = 'WD_DAY';    // Weekdays – daytime 
    case WD_EVE = 'WD_EVE';    // Weekdays – evening
    case WE_DAY = 'WE_DAY';    // Weekends – daytime
    case WE_EVE = 'WE_EVE';    // Weekends – evening
    case FLEX = 'FLEX';        // Flexible / not in a hurry

    /**
     * French label for display.
     */
    public function labelFr(): string
    {
        return match ($this) {
            self::ASAP => 'Dès que possible',
            self::WD_DAY => 'En semaine – journée',
            self::WD_EVE => 'En semaine – soirée',
            self::WE_DAY => 'Week‑end – journée',
            self::WE_EVE => 'Week‑end – soirée',
            self::FLEX => 'Flexible / pas pressé',
        };
    }

    /**
     * Convenience choices array for Symfony forms (label => value).
     */
    public static function choicesFr(): array
    {
        return [
            self::ASAP->labelFr() => self::ASAP->value,
            self::WD_DAY->labelFr() => self::WD_DAY->value,
            self::WD_EVE->labelFr() => self::WD_EVE->value,
            self::WE_DAY->labelFr() => self::WE_DAY->value,
            self::WE_EVE->labelFr() => self::WE_EVE->value,
            self::FLEX->labelFr() => self::FLEX->value,
        ];
    }
}
