<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Identifier;

/**
 * based on: https://gist.github.com/gskema/a182aaf7cc04001aebba9c1aad86b40b.
 */
final class BulkInsertQuery
{
    private Connection $connection;

    private Identifier $table;

    /** @var string[] */
    private array $columns = [];

    /** @var array<int, mixed> */
    private array $valueSets = [];

    /** @var array<int, string> */
    private array $types = [];

    public function __construct(
        Connection $connection,
        string $table
    ) {
        $this->connection = $connection;
        $this->table = new Identifier($table);
    }

    /**
     * @param string[] $columns
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param array<int, mixed>  $valueSets
     * @param array<int, string> $types
     */
    public function setValues(array $valueSets, array $types = []): self
    {
        $this->valueSets = $valueSets;
        $this->types = $types;

        return $this;
    }

    public function execute(): self
    {
        $sql = $this->getSQL();

        $parameters = array_reduce($this->valueSets, function (array $flattenedValues, $valueSet): array {
            /** @var mixed[] $valueSet */
            return array_merge($flattenedValues, array_values($valueSet));
        }, []);

        $this->connection->executeQuery($sql, $parameters, $this->getPositionalTypes());

        return $this;
    }

    protected function getSQL(): string
    {
        $platform = $this->connection->getDatabasePlatform();

        $escapedColumns = array_map(function (string $column) use ($platform) {
            return (new Identifier($column))->getQuotedName($platform);
        }, $this->columns);

        // (id, name, ..., date)
        $columnString = $this->columns === [] ? '' : '('.implode(', ', $escapedColumns).')';
        // (?, ?, ?, ... , ?)
        $singlePlaceholder = '('.implode(', ', array_fill(0, count($this->columns), '?')).')';
        // (?, ?), ... , (?, ?)
        $placeholders = implode(', ', array_fill(0, count($this->valueSets), $singlePlaceholder));

        return sprintf(
            'INSERT INTO %s %s VALUES %s;',
            $this->table->getQuotedName($platform),
            $columnString,
            $placeholders
        );
    }

    /**
     * @return string[]
     */
    protected function getPositionalTypes(): array
    {
        if ($this->types === []) {
            return [];
        }

        $types = array_values($this->types);

        $repeat = count($this->valueSets);

        $positionalTypes = [];
        for ($i = 1; $i <= $repeat; ++$i) {
            $positionalTypes = array_merge($positionalTypes, $types);
        }

        return $positionalTypes;
    }
}
