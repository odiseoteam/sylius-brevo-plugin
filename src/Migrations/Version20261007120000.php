<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

/** MySQL/MariaDB. Version20261007120001 is the PostgreSQL twin. */
final class Version20261007120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Brevo tracker client key per channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD tracker_client_key VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration DROP tracker_client_key');
    }
}
