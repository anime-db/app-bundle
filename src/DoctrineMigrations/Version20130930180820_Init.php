<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130930180820_Init extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // create tables
        $this->addSql('CREATE TABLE `task` (
            `id` INTEGER NOT NULL,
            `command` VARCHAR(128) NOT NULL,
            `last_run` DATETIME DEFAULT NULL,
            `next_run` DATETIME NOT NULL,
            `modify` VARCHAR(128) DEFAULT NULL,
            `status` INTEGER NOT NULL,
            PRIMARY KEY(`id`)
        )');
        $this->addSql('CREATE TABLE notice (
            id INTEGER NOT NULL,
            message TEXT NOT NULL,
            date_closed DATETIME DEFAULT NULL,
            date_created DATETIME NOT NULL,
            lifetime INTEGER NOT NULL,
            status INTEGER NOT NULL,
            PRIMARY KEY(id)
        )');
        // add index
        $this->addSql('CREATE INDEX notice_show_idx ON notice (date_closed, date_created)');
    }

    public function down(Schema $schema)
    {
        // drop tables
        $schema->dropTable('task');
        $schema->dropTable('notice');
    }
}