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

class Version20131205113046_AddPluginTable extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
            CREATE TABLE plugin (
                name VARCHAR(255) NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                logo VARCHAR(256) DEFAULT NULL,
                date_install DATETIME NOT NULL,
                PRIMARY KEY(name)
            )'
        );
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('plugin');
    }
}
