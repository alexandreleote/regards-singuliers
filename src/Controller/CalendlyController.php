<?php

namespace App\Controller;

use App\Service\CalendlyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CalendlyController extends AbstractController
{
    private CalendlyService $calendlyService;

    public function __construct(CalendlyService $calendlyService)
    {
        $this->calendlyService = $calendlyService;
    }

    #[Route('/calendly/organizations', name: 'calendly_organizations')]
    public function getOrganizations(): JsonResponse
    {
        $organizations = $this->calendlyService->getOrganizationDetails();

        if ($organizations === null) {
            return $this->json(['error' => 'Failed to retrieve organizations'], 500);
        }

        return $this->json($organizations);
    }
}
