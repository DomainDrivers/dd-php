<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * This class fixes two problems:
 * - default schema fix https://github.com/doctrine/migrations/issues/441
 * - migrations table schema fix https://github.com/doctrine/migrations/issues/1406
 */
final readonly class FixSchemaListener
{
    private TableMetadataStorageConfiguration $configuration;

    public function __construct(
        private DependencyFactory $dependencyFactory,
    ) {
        $configuration = $this->dependencyFactory->getConfiguration()->getMetadataStorageConfiguration();

        \assert($configuration instanceof TableMetadataStorageConfiguration);

        $this->configuration = $configuration;
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schemaManager = $args->getEntityManager()->getConnection()->createSchemaManager();

        if (!$schemaManager instanceof PostgreSQLSchemaManager) {
            return;
        }

        foreach ($schemaManager->listSchemaNames() as $namespace) {
            if (!$args->getSchema()->hasNamespace($namespace)) {
                $args->getSchema()->createNamespace($namespace);
            }
        }

        $schema = $args->getSchema();
        $table = $schema->createTable($this->configuration->getTableName());
        $table->addColumn(
            $this->configuration->getVersionColumnName(),
            'string',
            ['notnull' => true, 'length' => $this->configuration->getVersionColumnLength()],
        );
        $table->addColumn($this->configuration->getExecutedAtColumnName(), 'datetime', ['notnull' => false]);
        $table->addColumn($this->configuration->getExecutionTimeColumnName(), 'integer', ['notnull' => false]);

        $table->setPrimaryKey([$this->configuration->getVersionColumnName()]);
    }
}
