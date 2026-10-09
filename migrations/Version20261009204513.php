<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009204513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notes and links can be nested under a note: add parent_note_id';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE link ADD parent_note_id VARCHAR(36) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_link_owner_parent ON link (owner_id, parent_note_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD parent_note_id VARCHAR(36) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_note_owner_parent ON note (owner_id, parent_note_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX idx_link_owner_parent ON link
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE link DROP parent_note_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_note_owner_parent ON note
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP parent_note_id
        SQL);
    }
}
