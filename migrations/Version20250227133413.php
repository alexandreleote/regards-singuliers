<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227133413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog CHANGE slug slug VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE booking DROP client_name, DROP client_email, DROP client_phone, CHANGE service_id service_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog CHANGE slug slug VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE booking ADD client_name VARCHAR(255) NOT NULL, ADD client_email VARCHAR(255) NOT NULL, ADD client_phone VARCHAR(20) DEFAULT NULL, CHANGE service_id service_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
    }
}
