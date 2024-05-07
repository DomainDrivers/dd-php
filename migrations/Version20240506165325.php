<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240506165325 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
        create table if not exists projects (
            id uuid not null,
            version bigint default 1 not null,
            name varchar(255) not null,
            parallelized_stages jsonb not null,
            chosen_resources jsonb  not null,
            schedule jsonb  not null,
            demands jsonb  not null,
            demands_per_stage jsonb  not null,
            primary key (id)
        )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table projects');
    }
}
