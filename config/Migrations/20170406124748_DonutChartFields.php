<?php
use Migrations\AbstractMigration;

class DonutChartFields extends AbstractMigration
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
        $table->addColumn('chart_label', 'string', [
            'default' => null,
            'limit' => 30,
            'null' => true
        ]);
        $table->addColumn('chart_value', 'string', [
            'default' => null,
            'limit' => 30,
            'null' => true
        ]); 
        $table->addColumn('chart_max', 'integer', [
            'limit' => 11,
            'null' => true
        ]); 
        $table->update();
    }
}
