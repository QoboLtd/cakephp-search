<?php
use Migrations\AbstractMigration;

class RenameSaveSearchesTable extends AbstractMigration
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
        $this->table('saved_searches')
            ->rename('qobo_search_saved_searches')
            ->save();
    }
}
