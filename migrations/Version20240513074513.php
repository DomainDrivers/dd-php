<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240513074513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('create table if not exists availabilities (
            id uuid not null,
            resource_id uuid not null,
            resource_parent_id uuid,
            version bigserial not null,
            from_date timestamp not null,
            to_date timestamp not null,
            taken_by uuid,
            disabled boolean not null,
            primary key (id),
            unique(resource_id, from_date, to_date));
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE availabilities');
    }
}
