<?php

declare(strict_types=1);

namespace App\Ics;

use DateTimeImmutable;
use DateTimeZone;

final class IcsCalendarBuilder
{
    private const CALENDAR_NAME = '1813 Wachtdienst';
    private const UID_DOMAIN = 'intranetcpz.be';

    /**
     * @param IcsEvent[] $events
     */
    public function build(array $events, bool $onlyOwn = true): string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $dtStamp = $this->toIcsDateTime($now);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Wachtblad Sync//NL',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . self::CALENDAR_NAME,
        ];

        foreach ($events as $event) {
            if ($onlyOwn && !$event->isOwn) {
                continue;
            }

            if ($event->start <= $now) {
                continue;
            }

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:wachtblad-' . $event->id . '@' . self::UID_DOMAIN;
            $lines[] = 'DTSTAMP:' . $dtStamp;
            $lines[] = 'DTSTART:' . $this->toIcsDateTime($event->start);
            $lines[] = 'DTEND:' . $this->toIcsDateTime($event->end);
            $lines[] = 'SUMMARY:' . $this->escape($event->summary);

            if ($event->description !== '') {
                $lines[] = 'DESCRIPTION:' . $this->escape($event->description);
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function toIcsDateTime(DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    private function escape(string $text): string
    {
        return str_replace(
            ["\\", ';', ',', "\n"],
            ['\\\\', '\\;', '\\,', '\\n'],
            $text,
        );
    }
}
