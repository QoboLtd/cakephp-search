<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Controller\Traits;

use Cake\Core\App;
use Cake\ORM\Table;
use Exception;
use Qobo\Utils\ModuleConfig\ConfigType;
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
            $config = new ModuleConfig(ConfigType::MODULE(), $table);
            $result = (bool)$config->parse()->table->searchable;
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }
}
