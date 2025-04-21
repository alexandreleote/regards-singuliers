<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\AnonymizationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Connection;
use ReflectionClass;

class AnonymizationServiceTest extends TestCase
{
    private $entityManager;
    private $security;
    private $requestStack;
    private $passwordHasher;
    private $anonymizationService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->anonymizationService = new AnonymizationService(
            $this->entityManager,
            $this->security,
            $this->requestStack,
            $this->passwordHasher
        );
    }

    private function setPrivateProperty($object, $propertyName, $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function createTestUser(): User
    {
        $user = new User();
        $this->setPrivateProperty($user, 'id', 999);
        $user->setName('John');
        $user->setFirstName('Doe');
        $user->setEmail('john.doe@example.com');
        $user->setPhoneNumber('0123456789');
        $user->setStreetNumber('123');
        $user->setStreetName('Main Street');
        $user->setCity('Paris');
        $user->setZip('75000');
        $user->setRegion('Île-de-France');
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        return $user;
    }

    public function testAnonymiseUserDataUpdatesUserProperties(): void
    {
        // Création d'un utilisateur de test
        $user = $this->createTestUser();

        // Configuration des mocks minimaux
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(User::class, 999, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        // Exécution du test
        $this->anonymizationService->anonymiseUserData($user);

        // Vérifications des propriétés anonymisées
        $this->assertStringStartsWith('ANON_', $user->getName());
        $this->assertStringStartsWith('ANON_', $user->getFirstName());
        $this->assertStringStartsWith('del_999_', $user->getEmail());
        $this->assertEquals('00000000', $user->getPhoneNumber());
        $this->assertEquals('0', $user->getStreetNumber());
        $this->assertStringStartsWith('ANON_', $user->getStreetName());
        $this->assertStringStartsWith('ANON_', $user->getCity());
        $this->assertEquals('00000', $user->getZip());
        $this->assertStringStartsWith('ANON_', $user->getRegion());
        
        // Vérification des rôles
        $roles = $user->getRoles();
        $this->assertContains('ROLE_DELETED', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(2, $roles);
        
        $this->assertNotNull($user->getDeletedAt());
    }

    public function testAnonymiseUserDataHandlesDatabaseError(): void
    {
        // Création d'un utilisateur de test
        $user = $this->createTestUser();

        // Configuration des mocks pour simuler une erreur
        $this->entityManager->expects($this->once())
            ->method('find')
            ->willThrowException(new \Exception('Database error'));

        // Vérification que l'exception est bien propagée
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erreur lors de l\'anonymisation : Database error');

        $this->anonymizationService->anonymiseUserData($user);
    }

    public function testIsIpBanned(): void
    {
        // Configuration des mocks
        $ip = '192.168.1.1';
        $hashedIp = password_hash($ip, PASSWORD_BCRYPT);

        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        // Exécution du test
        $result = $this->anonymizationService->isIpBanned($ip);

        // Vérification
        $this->assertTrue($result);
    }

    public function testFindDeletedUsers(): void
    {
        // Configuration des mocks
        $deletedUser = new User();
        $deletedUser->setDeletedAt(new \DateTimeImmutable());

        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$deletedUser]);

        // Exécution du test
        $result = $this->anonymizationService->findDeletedUsers();

        // Vérification
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertNotNull($result[0]->getDeletedAt());
    }
} 