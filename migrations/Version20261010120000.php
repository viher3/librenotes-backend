<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Folders, notes and links can have a custom emoji icon: add icon';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE folder ADD icon VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE note ADD icon VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE link ADD icon VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE link DROP icon');
        $this->addSql('ALTER TABLE note DROP icon');
        $this->addSql('ALTER TABLE folder DROP icon');
    }
}
