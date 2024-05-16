<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240518161028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE devices (device_id UUID NOT NULL, version BIGINT DEFAULT 1 NOT NULL, model VARCHAR(255) NOT NULL, capabilities JSONB NOT NULL, PRIMARY KEY(device_id))');
        $this->addSql('CREATE TABLE employees (employee_id UUID NOT NULL, version BIGINT DEFAULT 1 NOT NULL, name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, seniority VARCHAR(255) NOT NULL, capabilities JSONB NOT NULL, PRIMARY KEY(employee_id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE devices');
        $this->addSql('DROP TABLE employees');
    }
}
