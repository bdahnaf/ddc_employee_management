<?php

namespace App\Enums;

/**
 * The three kinds of user in DDC Workforce.
 *
 *  - Hr:       sees and manages everything, including sensitive details.
 *  - Head:     a division or department head; sees attendance and leave for their own team.
 *  - Employee: sees the directory and basic profiles, and their own full profile.
 */
enum Role: string
{
    case Hr = 'hr';
    case Head = 'head';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Hr => 'HR',
            self::Head => 'Division / department head',
            self::Employee => 'Employee',
        };
    }

    /** Short form for tags and badges. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Hr => 'HR',
            self::Head => 'Head',
            self::Employee => 'Employee',
        };
    }

    /** @return array<string, string> value => label, for select boxes */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
