<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250207121815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation CHANGE status status VARCHAR(50) NOT NULL, CHANGE price price NUMERIC(6, 2) NOT NULL');
        $this->addSql('ALTER TABLE service CHANGE price price NUMERIC(6, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE price price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE service CHANGE price price NUMERIC(6, 2) DEFAULT \'0.00\' NOT NULL');
    }
}
