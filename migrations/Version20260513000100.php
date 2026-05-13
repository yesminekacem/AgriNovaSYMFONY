<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store crop image_path as an absolute filesystem path';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crop CHANGE image_path image_path VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crop CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
    }
}