<?php
use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;
use Qobo\Search\Criteria\Conjunction;
use Qobo\Search\Criteria\Direction;

class ExtendSavedSearchColumns extends AbstractMigration
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
        $table = $this->table('saved_searches');

        $table->addColumn('conjunction', 'string', ['default' => Conjunction::DEFAULT_CONJUNCTION, 'limit' => 10, 'null' => false])
            ->addColumn('criteria', 'text', ['default' => null, 'limit' => MysqlAdapter::TEXT_LONG, 'null' => false])
            ->addColumn('fields', 'text', ['default' => null, 'limit' => MysqlAdapter::TEXT_LONG, 'null' => false])
            ->addColumn('group_by', 'string', ['default' => '', 'limit' => 255, 'null' => false])
            ->addColumn('order_by_field', 'string', ['default' => '', 'limit' => 255, 'null' => false])
            ->addColumn('order_by_direction', 'string', ['default' => Direction::DEFAULT_DIRECTION, 'limit' => 10, 'null' => false]);

        $table->update();
    }
}
