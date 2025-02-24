<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250221091700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing tables for Regards Singuliers project';
    }

    public function up(Schema $schema): void
    {
        // Service table
        $this->addSql('CREATE TABLE IF NOT EXISTS service (
            id INT AUTO_INCREMENT NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            description LONGTEXT DEFAULT NULL, 
            price NUMERIC(6, 2) NOT NULL, 
            duration INT NOT NULL, 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Category table for photos
        $this->addSql('CREATE TABLE IF NOT EXISTS category (
            id INT AUTO_INCREMENT NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            description LONGTEXT DEFAULT NULL, 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Photo table
        $this->addSql('CREATE TABLE IF NOT EXISTS photo (
            id INT AUTO_INCREMENT NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            description LONGTEXT DEFAULT NULL, 
            filename VARCHAR(255) NOT NULL, 
            created_at DATETIME NOT NULL, 
            category_id INT DEFAULT NULL, 
            INDEX IDX_14B7841812469DE2 (category_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Add foreign key constraint for photo-category relationship
        $this->addSql('ALTER TABLE IF NOT EXISTS photo 
            ADD CONSTRAINT IF NOT EXISTS FK_14B7841812469DE2 
            FOREIGN KEY (category_id) REFERENCES category (id) 
            ON UPDATE NO ACTION ON DELETE NO ACTION');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE IF EXISTS photo DROP FOREIGN KEY IF EXISTS FK_14B7841812469DE2');
        $this->addSql('DROP TABLE IF EXISTS service');
        $this->addSql('DROP TABLE IF EXISTS category');
        $this->addSql('DROP TABLE IF EXISTS photo');
    }
}
