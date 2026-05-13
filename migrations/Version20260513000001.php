<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen product_listing.picture column to 500 chars to store absolute file paths';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_listing CHANGE picture picture VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_listing CHANGE picture picture VARCHAR(255) DEFAULT NULL');
    }
}
