<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260410125500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner_id foreign key to inventory records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A367E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B12D4A367E3C61F9 ON inventory (owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A367E3C61F9');
        $this->addSql('DROP INDEX IDX_B12D4A367E3C61F9 ON inventory');
        $this->addSql('ALTER TABLE inventory DROP owner_id');
    }
}
