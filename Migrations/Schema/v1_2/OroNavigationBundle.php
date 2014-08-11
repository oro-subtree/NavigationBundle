<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOrganization($schema);
    }

    /**
     * Adds organization_id into account
     *
     * @param Schema $schema
     */
    public static function addOrganization(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_item');
        $table->addColumn('organization_id', 'integer', ['notnull' => true]);
        $table->addIndex(['organization_id'], 'IDX_323B025832C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
