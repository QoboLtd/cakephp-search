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
use Cake\ORM\TableRegistry;
use Search\Event\EventName;
use Search\Utility;
use Search\Utility\Search;

class Export
{
    /**
     * @var string $id
     */
    protected $id;

    /**
     * @var array $displayColumns
     */
    protected $displayColumns = [];

    /**
     * @var string $filename
     */
    protected $filename;

    /**
     * @var \Cake\ORM\Query|null $query Search query
     */
    protected $query = null;

    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * @var \Search\Model\Entity\SavedSearch $search Search entity
     */
    protected $search;

    /**
     * @var array $user Current user
     */
    protected $user = [];

    /**
     * @var string $path
     */
    protected $path;

    /**
     * Constructor.
     *
     * @param string $id Saved search id
     * @param string $filename Search name
     * @param mixed[] $user Current user
     * @param string $extension Extension name
     */
    public function __construct(string $id, string $filename, array $user, string $extension = 'csv')
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
    public function count(): int
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
    public function execute(int $page, int $limit): void
    {
        $page = $page <= 1 ? 1 : $page;
        $rows = $this->getRows($page, $limit);

        $headers = [];
        $mode = 'a';
        if (1 === $page) {
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
     * Get export URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set search entity.
     *
     * @param string $id Saved search id
     * @return void
     */
    protected function setSearch(string $id): void
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
    protected function setFilename(string $filename, string $extension = 'csv'): void
    {
        $this->filename = $filename . '.' . $extension;
    }

    /**
     * Set search data.
     *
     * @return void
     */
    protected function setData(): void
    {
        $data = json_decode($this->search->content, true);
        $this->data = $data['latest'];
    }

    /**
     * Set current user.
     *
     * @param mixed[] $user Current user
     * @return void
     */
    protected function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * Set search query.
     *
     * @return void
     */
    protected function setQuery(): void
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
    protected function setUrl(): void
    {
        $url = trim(Configure::read('Search.export.url'), '/');

        $this->url = '/' . $url . '/' . $this->filename;
    }

    /**
     * Set export path.
     *
     * @return void
     */
    protected function setPath(): void
    {
        $path = trim(Configure::read('Search.export.url'), DS);

        $this->path = WWW_ROOT . $path . DS . $this->filename;
    }

    /**
     * Get export rows.
     *
     * @param int $page Pagination page
     * @param int $limit Pagination limit
     * @return mixed[]
     */
    protected function getRows(int $page, int $limit): array
    {
        $displayColumns = $this->getDisplayColumns();
        if (empty($displayColumns)) {
            return [];
        }

        $query = $this->query->page($page, $limit);
        if ($query->isEmpty()) {
            return [];
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

        $entities = $entities ? Utility::instance()->toCsv($entities, $displayColumns, $table) : [];

        if (empty($entities)) {
            return [];
        }

        $result = [];
        foreach ($entities as $k => $entity) {
            $result[$k] = [];
            foreach ($displayColumns as $column) {
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
     * @return mixed[]
     */
    protected function getHeaders(): array
    {
        $displayColumns = $this->getDisplayColumns();

        if (empty($displayColumns)) {
            return [];
        }

        $table = TableRegistry::get($this->search->model);

        $associationLabels = Utility::instance()->getAssociationLabels($table);
        $searchableFields = Utility::instance()->getSearchableFields($table, $this->user);

        $result = [];
        foreach ($displayColumns as $column) {
            $label = $column;
            if (array_key_exists($label, $searchableFields)) {
                $label = $searchableFields[$label]['label'];
            }

            list($fieldModel, ) = pluginSplit($column);
            if (array_key_exists($fieldModel, $associationLabels)) {
                $label .= ' (' . $associationLabels[$fieldModel] . ')';
            }

            $result[] = $label;
        }

        return $result;
    }

    /**
     * Display columns getter.
     *
     * @return mixed[]
     */
    protected function getDisplayColumns(): array
    {
        if (property_exists($this, 'displayColumns')) {
            return $this->displayColumns;
        }

        $this->displayColumns = !empty($this->data['display_columns']) ? $this->data['display_columns'] : [];

        $groupByField = !empty($this->data['group_by']) ? $this->data['group_by'] : '';

        if ($groupByField) {
            list($prefix, ) = pluginSplit($groupByField);
            $countField = $prefix . '.' . Search::GROUP_BY_FIELD;

            $this->displayColumns = [$groupByField, $countField];
        }

        return $this->displayColumns;
    }

    /**
     * Create export file.
     *
     * @todo Implement error handling
     * @param mixed[] $data CSV data
     * @param string $mode File mode
     * @return void
     */
    protected function create(array $data, string $mode = 'a'): void
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
