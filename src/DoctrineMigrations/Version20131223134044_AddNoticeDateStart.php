<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20131223134044_AddNoticeDateStart extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // create temp table from new structure
        $this->addSql('CREATE TABLE "_new" (
            id INTEGER NOT NULL,
            message TEXT NOT NULL,
            date_closed DATETIME DEFAULT NULL,
            date_created DATETIME NOT NULL,
            date_start DATETIME NOT NULL,
            lifetime INTEGER NOT NULL,
            status INTEGER NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('
            INSERT INTO
                "_new"
            SELECT
                id, message, date_closed, date_created, date_created, lifetime, status
            FROM
                "notice"
        ');
        // rename new to origin and drop origin
        $this->addSql('ALTER TABLE notice RENAME TO _origin');
        $this->addSql('ALTER TABLE _new RENAME TO notice');
        $this->addSql('DROP TABLE _origin');

        $this->addSql('CREATE INDEX notice_show_idx ON notice (date_closed, date_start)');
    }

    public function down(Schema $schema)
    {
        // create temp table from origin structure
        $this->addSql('CREATE TABLE "_new" (
            id INTEGER NOT NULL,
            message TEXT NOT NULL,
            date_closed DATETIME DEFAULT NULL,
            date_created DATETIME NOT NULL,
            lifetime INTEGER NOT NULL,
            status INTEGER NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('
            INSERT INTO
                "_new"
            SELECT
                id, message, date_closed, date_created, lifetime, status
            FROM
                "notice"
        ');
        // rename new to origin and drop origin
        $this->addSql('ALTER TABLE notice RENAME TO _origin');
        $this->addSql('ALTER TABLE _new RENAME TO notice');
        $this->addSql('DROP TABLE _origin');

        $this->addSql('CREATE INDEX notice_show_idx ON notice (date_closed, date_created)');
    }
}
