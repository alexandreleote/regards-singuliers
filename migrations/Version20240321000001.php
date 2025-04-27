<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240321000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove calendly_url column from service table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service DROP calendly_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service ADD calendly_url VARCHAR(255) DEFAULT NULL');
    }
} 