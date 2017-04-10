<?php
use Migrations\AbstractMigration;

class ModifyTableStructure extends AbstractMigration
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
        $table = $this->table('reports');
        $table->addColumn('chart_options', 'string', [
            'limit' => 255,
            'default' => null,
            'null' => false,
        ]);
        $table->changeColumn('name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->removeColumn('x_axis');
        $table->removeColumn('y_axis');
        $table->removeColumn('chart_label');
        $table->removeColumn('chart_value');
        $table->removeColumn('chart_max');
        $table->save();
    }
}
