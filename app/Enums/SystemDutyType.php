<?php

namespace App\Enums;

/**
 * Duty types the public contact pages group by. Which type plays each part is chosen in
 * Settings → Sistemos paskirtys; the public URL segment never changes.
 */
enum SystemDutyType: string
{
    case Coordinator = 'coordinator';
    case Curator = 'curator';
    case Mentor = 'mentor';

    /** The `/kontaktai/{segment}` path, kept stable whatever type is configured. */
    public function publicSegment(): string
    {
        return match ($this) {
            self::Coordinator => 'koordinatoriai',
            self::Curator => 'kuratoriai',
            self::Mentor => 'mentors',
        };
    }

    /**
     * @return list<string>
     */
    public static function publicSegments(): array
    {
        return array_map(fn (self $type): string => $type->publicSegment(), self::cases());
    }

    public static function fromPublicSegment(string $segment): ?self
    {
        foreach (self::cases() as $type) {
            if ($type->publicSegment() === $segment) {
                return $type;
            }
        }

        return null;
    }

    /** Lithuanian pages list curators, English pages the mentors of English-taught programmes. */
    public function forLocale(string $locale): self
    {
        return match (true) {
            $this === self::Curator && $locale === 'en' => self::Mentor,
            $this === self::Mentor && $locale !== 'en' => self::Curator,
            default => $this,
        };
    }

    public function settingKey(): string
    {
        return "{$this->value}_type_id";
    }

    public function labelKey(): string
    {
        return "settings.purposes.duty_types.{$this->value}.label";
    }

    public function drivesKey(): string
    {
        return "settings.purposes.duty_types.{$this->value}.drives";
    }
}
