<?php

use Migrations\AbstractMigration;

class MigrateDashboardsRolesToGroups extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<EOQ
        UPDATE qobo_search_dashboards SET role_id=(
            SELECT MIN(groups_roles.group_id)
              FROM groups_roles
              WHERE groups_roles.role_id=qobo_search_dashboards.role_id
        )
        WHERE role_id IS NULL AND group_id IS NULL
EOQ;
        $builder = $this->execute($sql);

        $this->table('qobo_search_dashboards')
            ->removeColumn('role_id')
            ->save();
    }
}
