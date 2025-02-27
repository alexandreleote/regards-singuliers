<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227084357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD service_id INT DEFAULT NULL, ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDEED5CA9E6 ON booking (service_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDEA76ED395 ON booking (user_id)');
        $this->addSql('ALTER TABLE service ADD slug VARCHAR(100) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E19D9AD2989D9B62 ON service (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEED5CA9E6');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEA76ED395');
        $this->addSql('DROP INDEX IDX_E00CEDDEED5CA9E6 ON booking');
        $this->addSql('DROP INDEX IDX_E00CEDDEA76ED395 ON booking');
        $this->addSql('ALTER TABLE booking DROP service_id, DROP user_id');
        $this->addSql('DROP INDEX UNIQ_E19D9AD2989D9B62 ON service');
        $this->addSql('ALTER TABLE service DROP slug');
    }
}
