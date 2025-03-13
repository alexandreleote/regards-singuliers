<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313134612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE individual_or_professional individual_or_professional VARCHAR(20) NOT NULL, CHANGE civility civility VARCHAR(20) NOT NULL, CHANGE name name VARCHAR(50) NOT NULL, CHANGE first_name first_name VARCHAR(50) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE phone_number phone_number VARCHAR(20) NOT NULL, CHANGE entreprise entreprise VARCHAR(100) DEFAULT NULL, CHANGE localisation localisation VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP created_at, CHANGE individual_or_professional individual_or_professional VARCHAR(255) NOT NULL, CHANGE civility civility VARCHAR(255) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE phone_number phone_number VARCHAR(255) NOT NULL, CHANGE entreprise entreprise VARCHAR(255) DEFAULT NULL, CHANGE localisation localisation VARCHAR(255) NOT NULL');
    }
}
