<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240930094248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tables for NeosRulez.Acl';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $this->addSql(
            'CREATE TABLE neosrulez_acl_domain_model_node (persistence_object_identifier VARCHAR(40) NOT NULL, nodeidentifier VARCHAR(255) NOT NULL, kind VARCHAR(255) NOT NULL, sitenodepath VARCHAR(255) NOT NULL, created DATETIME NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE neosrulez_acl_domain_model_role (persistence_object_identifier VARCHAR(40) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, privileges LONGTEXT NOT NULL, parentroles LONGTEXT NOT NULL, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_484C32A5E237E06 (name), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $this->addSql('DROP TABLE neosrulez_acl_domain_model_node');
        $this->addSql('DROP TABLE neosrulez_acl_domain_model_role');
    }
}
