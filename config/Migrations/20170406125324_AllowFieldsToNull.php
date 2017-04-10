<?php
use Migrations\AbstractMigration;

class AllowFieldsToNull extends AbstractMigration
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
        $table->changeColumn('y_axis', 'string', [
            'default' => null,
            'limit' => 20,
            'null' => true,
        ]);
        $table->changeColumn('x_axis', 'string', [
            'default' => null,
            'limit' => 20,
            'null' => true,
        ]);
        $table->update();
    }
}
