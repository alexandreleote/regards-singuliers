<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250317073538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD civilite VARCHAR(20) NOT NULL, ADD nom VARCHAR(100) NOT NULL, ADD prenom VARCHAR(100) NOT NULL, ADD telephone VARCHAR(20) NOT NULL, ADD is_responded TINYINT(1) NOT NULL, ADD read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD responded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP phone, DROP name, DROP firstname, DROP status, CHANGE company entreprise VARCHAR(100) DEFAULT NULL, CHANGE location localisation VARCHAR(255) NOT NULL, CHANGE project description LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `contact` ADD phone VARCHAR(20) NOT NULL, ADD name VARCHAR(100) NOT NULL, ADD firstname VARCHAR(100) NOT NULL, ADD status VARCHAR(20) NOT NULL, DROP civilite, DROP nom, DROP prenom, DROP telephone, DROP is_responded, DROP read_at, DROP responded_at, CHANGE entreprise company VARCHAR(100) DEFAULT NULL, CHANGE description project LONGTEXT NOT NULL, CHANGE localisation location VARCHAR(255) NOT NULL');
    }
}
