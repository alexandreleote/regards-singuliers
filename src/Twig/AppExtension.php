<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_price', [$this, 'formatPrice']),
        ];
    }

    public function formatPrice(float $price): string
    {
        // Vérifier si le prix a des décimales différentes de .00
        if (fmod($price, 1) == 0) {
            return number_format($price, 0, ',', ' ');
        }
        
        return number_format($price, 2, ',', ' ');
    }
} 