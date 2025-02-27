<?php

namespace App\Traits;

use Symfony\Component\String\Slugger\AsciiSlugger;

trait SluggerTrait
{
    public function generateUniqueSlug(string $title, callable $existenceCheck): string
    {
        $slugger = new AsciiSlugger('fr');
        $baseSlug = $slugger->slug(strtolower($title));

        $slug = $baseSlug;
        $counter = 1;

        while ($existenceCheck($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
