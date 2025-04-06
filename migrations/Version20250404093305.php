<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404093305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ADD billing_number VARCHAR(50) NOT NULL, ADD name VARCHAR(255) NOT NULL, ADD first_name VARCHAR(255) NOT NULL, ADD billing_address VARCHAR(255) NOT NULL, ADD billing_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD name VARCHAR(255) NOT NULL, ADD first_name VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE payment DROP billing_number, DROP name, DROP first_name, DROP billing_address, DROP billing_date
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP name, DROP first_name
        SQL);
    }
}
