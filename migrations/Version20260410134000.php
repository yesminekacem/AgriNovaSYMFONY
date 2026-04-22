<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_path column to post records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD image_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP image_path');
    }
}