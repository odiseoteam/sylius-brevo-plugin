<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractPostgreSQLMigration;

/** PostgreSQL. Version20260925120000 is the MySQL/MariaDB twin. */
final class Version20260925120001 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Brevo newsletter list and double opt-in template per channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD newsletter_list_id INT DEFAULT NULL, ADD double_opt_in_template_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration DROP newsletter_list_id, DROP double_opt_in_template_id');
    }
}
