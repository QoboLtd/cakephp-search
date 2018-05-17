<?php
use Migrations\AbstractMigration;

class RenameAppWidgetsTable extends AbstractMigration
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
        $this->table('app_widgets')
            ->rename('qobo_search_app_widgets');
    }
}
