<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractPostgreSQLMigration;

/** PostgreSQL. Version20260923120000 is the MySQL/MariaDB twin. */
final class Version20260923120001 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Brevo configuration per channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE odiseo_brevo_channel_configuration (id SERIAL NOT NULL, channel_id INT NOT NULL, enabled BOOLEAN NOT NULL, api_key TEXT DEFAULT NULL, sender_name VARCHAR(255) DEFAULT NULL, sender_email VARCHAR(255) DEFAULT NULL, modules JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1FDD127172F5A1AA ON odiseo_brevo_channel_configuration (channel_id)');
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD CONSTRAINT FK_1FDD127172F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE odiseo_brevo_channel_configuration');
    }
}
