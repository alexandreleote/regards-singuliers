<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('dynamic_time', [$this, 'formatDynamicTime']),
        ];
    }

    public function formatDynamicTime(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $diff = $now->diff($date);

        if ($diff->days > 7) {
            return $date->format('d/m/Y H:i');
        }

        if ($diff->days > 0) {
            return match($diff->days) {
                1 => 'Hier',
                2 => 'Il y a 2 jours',
                default => "Il y a {$diff->days} jours"
            };
        }

        if ($diff->h > 0) {
            return $diff->h === 1 ? 'Il y a 1 heure' : "Il y a {$diff->h} heures";
        }

        if ($diff->i > 10) {
            return "Il y a {$diff->i} minutes";
        }

        if ($diff->i > 0) {
            return 'Il y a quelques minutes';
        }

        return 'À l\'instant';
    }
}
