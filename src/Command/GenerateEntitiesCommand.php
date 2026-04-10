<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate:entities',
    description: 'Generate Entity and Repository classes from existing database tables',
)]
class GenerateEntitiesCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectDir = getcwd();
        $entityDir = $projectDir . '/src/Entity';
        $repositoryDir = $projectDir . '/src/Repository';

        if (!is_dir($entityDir)) {
            mkdir($entityDir, 0777, true);
        }

        if (!is_dir($repositoryDir)) {
            mkdir($repositoryDir, 0777, true);
        }

        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTables();

        if (empty($tables)) {
            $io->warning('No tables found in the database.');
            return Command::SUCCESS;
        }

        foreach ($tables as $table) {
            $tableName = $table->getName();

            if ($tableName === 'doctrine_migration_versions') {
                continue;
            }

            $className = $this->tableToClassName($tableName);
            $repositoryClassName = $className . 'Repository';

            $entityCode = $this->generateEntityCode($table, $className, $repositoryClassName);
            $repositoryCode = $this->generateRepositoryCode($className, $repositoryClassName);

            file_put_contents($entityDir . '/' . $className . '.php', $entityCode);
            file_put_contents($repositoryDir . '/' . $repositoryClassName . '.php', $repositoryCode);

            $io->writeln("Generated: <info>{$className}</info> and <info>{$repositoryClassName}</info>");
        }

        $io->success('All entities and repositories were generated successfully.');

        return Command::SUCCESS;
    }

    private function generateEntityCode($table, string $className, string $repositoryClassName): string
    {
        $tableName = $table->getName();
        $columns = $table->getColumns();
        $primaryKey = $table->getPrimaryKey();
        $primaryColumns = $primaryKey ? $primaryKey->getColumns() : [];
        $foreignKeys = $table->getForeignKeys();

        $foreignKeyMap = [];
        foreach ($foreignKeys as $fk) {
            foreach ($fk->getLocalColumns() as $index => $localColumn) {
                $foreignKeyMap[$localColumn] = [
                    'fk' => $fk,
                    'referencedColumn' => $fk->getForeignColumns()[$index] ?? 'id',
                ];
            }
        }

        $properties = '';
        $methods = '';

        foreach ($columns as $column) {
            $columnName = $column->getName();

            if (isset($foreignKeyMap[$columnName])) {
                /** @var ForeignKeyConstraint $fk */
                $fk = $foreignKeyMap[$columnName]['fk'];
                $referencedColumn = $foreignKeyMap[$columnName]['referencedColumn'];

                $targetTable = $fk->getForeignTableName();
                $targetEntity = $this->tableToClassName($targetTable);
                $propertyName = $this->columnToPropertyName($columnName, true);

                $nullable = $column->getNotnull() ? 'false' : 'true';

                $properties .= "\n    #[ORM\\ManyToOne(targetEntity: {$targetEntity}::class)]\n";
                $properties .= "    #[ORM\\JoinColumn(name: '{$columnName}', referencedColumnName: '{$referencedColumn}', nullable: {$nullable})]\n";
                $properties .= "    private ?{$targetEntity} \${$propertyName} = null;\n";

                $methodSuffix = ucfirst($propertyName);

                $methods .= "\n    public function get{$methodSuffix}(): ?{$targetEntity}\n    {\n";
                $methods .= "        return \$this->{$propertyName};\n    }\n";

                $methods .= "\n    public function set{$methodSuffix}(?{$targetEntity} \${$propertyName}): self\n    {\n";
                $methods .= "        \$this->{$propertyName} = \${$propertyName};\n";
                $methods .= "        return \$this;\n    }\n";

                continue;
            }

            $propertyName = $this->columnToPropertyName($columnName);
            $phpType = $this->mapPhpType($column);
            $doctrineType = $this->getDoctrineTypeName($column);
            $nullable = !$column->getNotnull();

            $columnOptions = "type: '{$doctrineType}'";

            if ($nullable) {
                $columnOptions .= ", nullable: true";
            }

            if ($column->getLength()) {
                $columnOptions .= ", length: " . $column->getLength();
            }

            if (in_array($doctrineType, ['decimal', 'float'])) {
                if ($column->getPrecision()) {
                    $columnOptions .= ", precision: " . $column->getPrecision();
                }
                if ($column->getScale()) {
                    $columnOptions .= ", scale: " . $column->getScale();
                }
            }

            if (in_array($columnName, $primaryColumns, true)) {
                $properties .= "\n    #[ORM\\Id]\n";

                if ($column->getAutoincrement()) {
                    $properties .= "    #[ORM\\GeneratedValue]\n";
                }

                $properties .= "    #[ORM\\Column({$columnOptions})]\n";
                $properties .= "    private ?" . $phpType . " \${$propertyName} = null;\n";

                $methodSuffix = ucfirst($propertyName);

                $methods .= "\n    public function get{$methodSuffix}(): ?" . $phpType . "\n    {\n";
                $methods .= "        return \$this->{$propertyName};\n    }\n";
            } else {
                $typedProperty = $nullable ? '?' . $phpType : $phpType;
                $defaultValue = $nullable ? ' = null' : '';

                $properties .= "\n    #[ORM\\Column({$columnOptions})]\n";
                $properties .= "    private {$typedProperty} \${$propertyName}{$defaultValue};\n";

                $methodSuffix = ucfirst($propertyName);

                $methods .= "\n    public function get{$methodSuffix}(): {$typedProperty}\n    {\n";
                $methods .= "        return \$this->{$propertyName};\n    }\n";

                $methods .= "\n    public function set{$methodSuffix}({$typedProperty} \${$propertyName}): self\n    {\n";
                $methods .= "        \$this->{$propertyName} = \${$propertyName};\n";
                $methods .= "        return \$this;\n    }\n";
            }
        }

        return "<?php

namespace App\\Entity;

use App\\Repository\\{$repositoryClassName};
use Doctrine\\ORM\\Mapping as ORM;

#[ORM\\Entity(repositoryClass: {$repositoryClassName}::class)]
#[ORM\\Table(name: '{$tableName}')]
class {$className}
{
{$properties}
{$methods}
}
";
    }

    private function generateRepositoryCode(string $className, string $repositoryClassName): string
    {
        return "<?php

namespace App\\Repository;

use App\\Entity\\{$className};
use Doctrine\\Bundle\\DoctrineBundle\\Repository\\ServiceEntityRepository;
use Doctrine\\Persistence\\ManagerRegistry;

class {$repositoryClassName} extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry \$registry)
    {
        parent::__construct(\$registry, {$className}::class);
    }
}
";
    }

    private function tableToClassName(string $tableName): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));
    }

    private function columnToPropertyName(string $columnName, bool $removeIdSuffix = false): string
    {
        if ($removeIdSuffix && str_ends_with($columnName, '_id')) {
            $columnName = substr($columnName, 0, -3);
        }

        $parts = explode('_', $columnName);
        $property = array_shift($parts);

        foreach ($parts as $part) {
            $property .= ucfirst($part);
        }

        return $property;
    }

    private function mapPhpType(Column $column): string
    {
        return match ($this->getDoctrineTypeName($column)) {
            'integer', 'smallint', 'bigint' => 'int',
            'float', 'decimal' => 'float',
            'boolean' => 'bool',
            'date', 'datetime', 'datetimetz', 'time', 'timestamp' => '\\DateTimeInterface',
            default => 'string',
        };
    }

    private function getDoctrineTypeName(Column $column): string
    {
        $typeClass = strtolower($column->getType()::class);

        return match ($typeClass) {
            'doctrine\\dbal\\types\\integertype' => 'integer',
            'doctrine\\dbal\\types\\smallinttype' => 'smallint',
            'doctrine\\dbal\\types\\biginttype' => 'bigint',
            'doctrine\\dbal\\types\\floattype' => 'float',
            'doctrine\\dbal\\types\\decimaltype' => 'decimal',
            'doctrine\\dbal\\types\\booleantype' => 'boolean',
            'doctrine\\dbal\\types\\datetype' => 'date',
            'doctrine\\dbal\\types\\datetimetype' => 'datetime',
            'doctrine\\dbal\\types\\datetimetztype' => 'datetimetz',
            'doctrine\\dbal\\types\\timetype' => 'time',
            'doctrine\\dbal\\types\\texttype' => 'text',
            'doctrine\\dbal\\types\\stringtype' => 'string',
            default => 'string',
        };
    }
}