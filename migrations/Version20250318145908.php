<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250318145908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs Calendly à la table reservation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD calendly_event_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD calendly_invitee_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP calendly_event_id');
        $this->addSql('ALTER TABLE reservation DROP calendly_invitee_id');
    }
}
