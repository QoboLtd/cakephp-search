<?php
namespace Search\Controller\Traits;

use Cake\Core\App;
use Cake\ORM\Table;
use Exception;
use Qobo\Utils\ModuleConfig\ModuleConfig;

trait SearchableTrait
{
    /**
     * Returns true if table is searchable, false otherwise.
     *
     * @param  \Cake\ORM\Table|string $table Table object or name.
     * @return bool
     */
    protected function _isSearchable($table)
    {
        if ($table instanceof Table) {
            $table = App::shortName(get_class($table), 'Model/Table', 'Table');
            list(, $table) = pluginSplit($table);
        }

        try {
            $mc = new ModuleConfig(ModuleConfig::CONFIG_TYPE_MODULE, $table);

            $result = (bool)$mc->parse()->table->searchable;
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }
}
