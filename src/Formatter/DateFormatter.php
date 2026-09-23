<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Formatter;

final class DateFormatter implements DateFormatterInterface
{
    public function formatDateTime(\DateTimeInterface $dateTime): string
    {
        return \DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z')
        ;
    }

    // No timezone shift: a date (e.g. a birthday) must not move to another day.
    public function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
