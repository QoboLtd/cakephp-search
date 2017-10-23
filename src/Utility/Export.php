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
namespace Search\Utility;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Filesystem\File;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Search\Event\EventName;
use Search\Utility;
use Search\Utility\Search;

class Export
{
    protected $id;

    /**
     * Filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * Search query.
     *
     * @var \Cake\ORM\Query|null
     */
    protected $query = null;

    protected $data = [];

    /**
     * Search entity.
     *
     * @var \Search\Model\Entity\SavedSearch
     */
    protected $search;

    /**
     * Current logged in user.
     *
     * @var array
     */
    protected $user = [];

    protected $path = null;

    /**
     * Constructor.
     *
     * @param string $id Saved search id
     * @param string $filename Search name
     * @param array $user Current user
     * @param string|null $extension Extension name
     */
    public function __construct($id, $filename, $user, $extension = 'csv')
    {
        $this->setSearch($id);
        $this->setFilename($filename, $extension);
        $this->setData();
        $this->setUser($user);
        $this->setQuery();
        $this->setUrl();
        $this->setPath();
    }

    /**
     * Get search result count.
     *
     * @return int
     */
    public function count()
    {
        return $this->query->count();
    }

    /**
     * Execute export.
     *
     * @param int $page Pagination page
     * @param int $limit Pagination limit
     * @return void
     */
    public function execute($page, $limit)
    {
        $page = (int)$page;
        $limit = (int)$limit;
        $rows = $this->getRows($page, $limit);

        $headers = [];
        $mode = 'a';
        if (1 === (int)$page) {
            $headers = $this->getHeaders();
            $mode = 'w';
        }

        // Prepend columns to result
        if (!empty($headers)) {
            array_unshift($rows, $headers);
        }

        $this->create($rows, $mode);
    }

    /**
     * Get export path.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set search entity.
     *
     * @param string $id Saved search id
     * @return void
     */
    protected function setSearch($id)
    {
        $table = TableRegistry::get('Search.SavedSearches');

        $this->search = $table->get($id);
    }

    /**
     * Set filename.
     *
     * @param string $filename Filename
     * @param string $extension File extension
     * @return void
     */
    protected function setFilename($filename, $extension = 'csv')
    {
        $time = Time::now();
        $filename .= ' ' . $time->i18nFormat('yyyy-MM-dd HH-mm-ss');

        $this->filename = $filename . '.' . $extension;
    }

    /**
     * Set search data.
     *
     * @return void
     */
    protected function setData()
    {
        $data = json_decode($this->search->content, true);
        $this->data = $data['latest'];
    }

    /**
     * Set current user.
     *
     * @param array $user Current user
     * @return void
     */
    protected function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Set search query.
     *
     * @return void
     */
    protected function setQuery()
    {
        $table = TableRegistry::get($this->search->get('model'));
        $search = new Search($table, $this->user);
        $this->query = $search->execute($this->data);
    }

    /**
     * Set export url.
     *
     * @return void
     */
    protected function setUrl()
    {
        $url = trim(Configure::read('Search.export.url'), '/');

        $this->url = '/' . $url . '/' . $this->filename;
    }

    /**
     * Set export path.
     *
     * @return void
     */
    protected function setPath()
    {
        $path = trim(Configure::read('Search.export.url'), DS);

        $this->path = WWW_ROOT . $path . DS . $this->filename;
    }

    /**
     * Get export rows.
     *
     * @param int $page Pagination page
     * @param int $limit Pagination limit
     * @return array
     */
    protected function getRows($page, $limit)
    {
        $result = [];

        $query = $this->query->page($page, $limit);
        if ($query->isEmpty()) {
            return $result;
        }

        $entities = $query->all();
        $table = TableRegistry::get($this->search->model);

        $event = new Event((string)EventName::MODEL_SEARCH_AFTER_FIND(), $this, [
            'entities' => $entities,
            'table' => $table
        ]);
        EventManager::instance()->dispatch($event);
        if ($event->result) {
            $entities = $event->result;
        }

        $entities = $entities ? Utility::instance()->toCsv($entities, $this->data['display_columns'], $table) : [];

        if (empty($entities)) {
            return $result;
        }

        foreach ($entities as $k => $entity) {
            $result[$k] = [];
            foreach ($this->data['display_columns'] as $column) {
                // @todo this is temporary fix to stripping out html tags from results columns
                $value = trim(strip_tags($entity[$column]));
                // end of temporary fix
                $result[$k][] = $value;
            }
        }

        return $result;
    }

    /**
     * Get export headers.
     *
     * @return array
     */
    protected function getHeaders()
    {
        $result = [];

        if (empty($this->data['display_columns'])) {
            return $result;
        }

        $table = TableRegistry::get($this->search->model);

        $associationLabels = Utility::instance()->getAssociationLabels($table);
        $searchableFields = Utility::instance()->getSearchableFields($table, $this->user);

        foreach ($this->data['display_columns'] as $column) {
            $tableName = substr($column, 0, strpos($column, '.'));

            $label = array_key_exists($tableName, $associationLabels) ?
                $associationLabels[$tableName] :
                $tableName;

            list(, $modelName) = pluginSplit($this->search->model);
            $suffix = $modelName === $label ? '' : ' (' . $label . ')';

            $result[] = $searchableFields[$column]['label'] . $suffix;
        }

        return $result;
    }

    /**
     * Create export file.
     *
     * @param array $data CSV data
     * @param string $mode File mode
     * @return void
     */
    protected function create(array $data, $mode = 'a')
    {
        // create file path
        $file = new File($this->path, true);

        // write to file
        $handler = fopen($file->path, $mode);
        foreach ($data as $row) {
            fputcsv($handler, $row);
        }
        fclose($handler);
    }
}
