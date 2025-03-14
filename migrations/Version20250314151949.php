<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250314151949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD name VARCHAR(100) NOT NULL, ADD firstname VARCHAR(100) NOT NULL, ADD status VARCHAR(20) NOT NULL, DROP last_name, DROP first_name, CHANGE email email VARCHAR(180) NOT NULL, CHANGE company company VARCHAR(100) DEFAULT NULL, CHANGE description project LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `contact` ADD last_name VARCHAR(255) NOT NULL, ADD first_name VARCHAR(255) NOT NULL, DROP name, DROP firstname, DROP status, CHANGE email email VARCHAR(255) NOT NULL, CHANGE company company VARCHAR(255) DEFAULT NULL, CHANGE project description LONGTEXT NOT NULL');
    }
}
