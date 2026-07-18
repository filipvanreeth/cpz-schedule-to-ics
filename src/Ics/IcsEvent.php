<?php

declare(strict_types=1);

namespace App\Ics;

use DateTimeImmutable;

final class IcsEvent
{
    private function __construct(
        public readonly string $id,
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
        public readonly string $summary,
        public readonly string $description,
        public readonly bool $isOwn,
    ) {
    }

    /**
     * @param array<string, mixed> $raw Ruwe event-data zoals teruggegeven door de wachtblad-endpoint
     */
    public static function fromApiResponse(array $raw): self
    {
        $id = $raw["id"] ?? '';
        $title = 'CPZ - Wachtdienst';
        $summary = str_replace("\n", ' ', $title);

        $description = (string) ($raw['extendedProps']['description'] ?? '');
        $color = (string) ($raw['color'] ?? '');

        return new self(
            id: $id,
            start: new DateTimeImmutable($raw['start']),
            end: new DateTimeImmutable($raw['end']),
            summary: trim($summary),
            description: $description,
            isOwn: str_contains($color, 'calendar-event-own'),
        );
    }
}
