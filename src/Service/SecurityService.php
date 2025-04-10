<?php

namespace App\Service;

use App\Entity\BotIp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SecurityService
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private const BLOCK_DURATION = '24 hours';
    private const PROTECTED_ROUTES = [
        '/contact',
        '/inscription'
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function shouldProtectRoute(string $path): bool
    {
        foreach (self::PROTECTED_ROUTES as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }
        return false;
    }

    public function isIpBlocked(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $currentIp = $request->getClientIp();
        $repository = $this->entityManager->getRepository(BotIp::class);
        
        // Récupérer toutes les entrées des dernières 24 heures pour cette IP
        $recentAttempts = $repository->createQueryBuilder('b')
            ->where('b.detectedAt >= :blockTime')
            ->setParameter('blockTime', new \DateTimeImmutable('-' . self::BLOCK_DURATION))
            ->getQuery()
            ->getResult();

        foreach ($recentAttempts as $attempt) {
            if (password_verify($currentIp, $attempt->getIp())) {
                return true;
            }
        }

        return false;
    }

    public function blockIp(string $formType = null, string $reason = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $botIp = new BotIp();
        $botIp->setIp($request->getClientIp());
        $botIp->setUserAgent($request->headers->get('User-Agent'));
        $botIp->setFormType($formType ?? 'unknown');

        $this->entityManager->persist($botIp);
        $this->entityManager->flush();
    }

    public function getBlockReason(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return ['blocked' => false, 'reason' => null];
        }

        $currentIp = $request->getClientIp();
        $repository = $this->entityManager->getRepository(BotIp::class);
        
        $recentAttempts = $repository->createQueryBuilder('b')
            ->where('b.detectedAt >= :yesterday')
            ->setParameter('yesterday', new \DateTimeImmutable('-24 hours'))
            ->getQuery()
            ->getResult();

        foreach ($recentAttempts as $attempt) {
            if (password_verify($currentIp, $attempt->getIp())) {
                return [
                    'blocked' => true,
                    'reason' => 'Activité suspecte détectée',
                    'form_type' => $attempt->getFormType(),
                    'detected_at' => $attempt->getDetectedAt()->format('Y-m-d H:i:s'),
                    'expires_at' => $attempt->getDetectedAt()->modify('+24 hours')->format('Y-m-d H:i:s')
                ];
            }
        }

        return ['blocked' => false, 'reason' => null];
    }
}
