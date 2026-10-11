<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828192354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a FULLTEXT index on note (title, content) to back full-text note search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE FULLTEXT INDEX ft_note_title_content ON note (title, content)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX ft_note_title_content ON note');
    }
}
