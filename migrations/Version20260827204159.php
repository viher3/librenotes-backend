<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827204159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the activation token columns used by public signup / account activation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            ADD
              activation_token VARCHAR(255) DEFAULT NULL,
            ADD
              activation_token_requested_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            DROP
              activation_token,
            DROP
              activation_token_requested_at
        SQL);
    }
}
