<?php
namespace Search\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use \FileSystemIterator;
use \RuntimeException;

/**
 * Searchable component
 */
class SearchableComponent extends Component
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * This functions constructs the searachable tables and also append the fields which
     * can be searched.
     *
     * @return array All tables with their searchable columns.
     */
    public function getSearchableFields()
    {
        $db = ConnectionManager::get('default');
        $collection = $db->schemaCollection();
        $dbTables = $collection->listTables();
        $tables = $this->getSearchableTables();
        foreach ($tables as $container => &$containerTables) {
            foreach ($containerTables as $tableName => &$table) {
                if (in_array($tableName, $dbTables) && $table['searchable']) {
                    if ($container === 'app') {
                        $modelTable = TableRegistry::get($table['name']);
                    } else {
                        $modelTable = TableRegistry::get($container . '.' . $table['name']);
                    }
                    if (method_exists($modelTable, 'getSearchableFields')) {
                        $table['fields'] = $modelTable->getSearchableFields();
                    } else {
                        //By defeault, all schema fields can be searched.
                        $table['fields'] = $collection->describe($table['name'])->columns();
                    }
                }
            }
        }

        return $tables;
    }

    /**
     * Get all the tables with Searchable fields functionallity.
     *
     * @return array
     */
    public function getSearchableTables()
    {
        $tables = $this->_getAllTables();
        foreach ($tables as $container => &$containerTables) {
            foreach ($containerTables as $key => &$table) {
                if ($container === 'app') {
                    $modelTable = TableRegistry::get($table['name']);
                } else {
                    $modelTable = TableRegistry::get($container . '.' . $table['name']);
                }
                if (method_exists($modelTable, 'isSearchable')) {
                    $table['searchable'] = $modelTable->isSearchable();
                } else {
                    //By Default, table is not searchable.
                    $table['searchable'] = false;
                }
            }
        }

        return array_filter($tables);
    }

    /**
     * Get all the tables from application and plugins.
     *
     * @return [type]                [description]
     */
    protected function _getAllTables()
    {
        $result = [];
        $result['app'] = $this->_getTables(APP . 'Model' . DS . 'Table');
        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            $result[$plugin] = $this->_getTables(Plugin::path($plugin) . 'src' . DS . 'Model' . DS . 'Table');
        }

        return array_filter($result);
    }

    /**
     * Just get the tables from the the given path.
     *
     * @throws RuntimeException When path is not provided.
     * @param  string $path The directory which contains the tables.
     * @return array  Either empty array or the found tables.
     */
    protected function _getTables($path = '')
    {
        $result = [];

        if (empty($path)) {
            throw new RuntimeException('Please provide path of Tables to proceed.');
        }

        if (!file_exists($path)) {
            return $result;
        }

        $it = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach ($it as $file) {
            //Exclude anything without the table prefix.
            if (!strpos($file->getFilename(), 'Table.php')) {
                continue;
            }
            $table = strtolower($file->getBasename('Table.php'));
            $result[$table]['name'] = $table;
        }

        return $result;
    }
}
