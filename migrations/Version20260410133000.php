<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_path column to inventory records';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('inventory');

        if (!$table->hasColumn('image_path')) {
            $this->addSql('ALTER TABLE inventory ADD image_path VARCHAR(500) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory DROP image_path');
    }
}
