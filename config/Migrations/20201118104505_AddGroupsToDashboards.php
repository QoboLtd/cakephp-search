<?php
use Migrations\AbstractMigration;

class AddGroupsToDashboards extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $this->table('qobo_search_dashboards')
            ->addColumn('group_id', 'uuid', [
            'limit' => 36,
            'null' => true
            ])
            ->update();
    }
}
