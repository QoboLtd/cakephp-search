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
use Cake\Log\LogTrait;
use Cake\ORM\TableRegistry;
use Psr\Log\LogLevel;
use Search\Event\EventName;
use Search\Utility;
use Search\Utility\Search;

class Export
{
    use LogTrait;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $displayColumns = [];

    /**
     * Search query
     *
     * @var \Cake\Datasource\QueryInterface
     */
    protected $query;

    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * Search entity
     *
     * @var \Search\Model\Entity\SavedSearch
     */
    protected $search;

    /**
     * Current user
     *
     * @var array
     */
    protected $user = [];

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $url;

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
        $exportUrl = Configure::read('Search.export.url');

        /**
         * @var \Search\Model\Entity\SavedSearch
         */
        $savedSearch = TableRegistry::get('Search.SavedSearches')->get($id);

        $data = json_decode($savedSearch->get('content'), true);
        $data = isset($data['latest']) ? $data['latest'] : [];

        $search = new Search(
            TableRegistry::get($savedSearch->get('model')),
            $user
        );

        $this->query = $search->execute($data);
        $this->data = $data;
        $this->search = $savedSearch;
        $this->user = $user;
        $this->url = sprintf('/%s/%s.%s', trim($exportUrl, '/'), $filename, $extension);
        $this->path = WWW_ROOT . trim($exportUrl, DS) . DS . $filename . '.' . $extension;
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
        $entities = $query->all();
        if ($entities->isEmpty()) {
            return [];
        }

        $table = TableRegistry::get($this->search->get('model'));

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

        $table = TableRegistry::get($this->search->get('model'));

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
        if (! empty($this->displayColumns)) {
            return $this->displayColumns;
        }

        if (! empty($this->data['display_columns'])) {
            $this->displayColumns = $this->data['display_columns'];
        }

        if (! empty($this->data['group_by'])) {
            list($prefix, ) = pluginSplit($this->data['group_by']);
            $countField = $prefix . '.' . Search::GROUP_BY_FIELD;

            $this->displayColumns = [$this->data['group_by'], $countField];
        }

        return $this->displayColumns;
    }

    /**
     * Create export file.
     *
     * @param mixed[] $data CSV data
     * @param string $mode File mode
     * @return void
     */
    protected function create(array $data, string $mode = 'a'): void
    {
        // create file path
        $file = new File($this->path, true);

        // skip if file is not writable
        if (! $file->writable()) {
            $this->log(sprintf('Export file is not writable: %s', $file->pwd()), LogLevel::ERROR);

            return;
        }

        /**
         * @var resource
         */
        $handler = fopen($file->pwd(), $mode);
        if (! is_resource($handler)) {
            $this->log(sprintf('Export interrupted: failed to bind resource to a stream'), LogLevel::ERROR);
        }

        foreach ($data as $row) {
            if (false === fputcsv($handler, $row)) {
                $this->log(sprintf('Export interrupted: failed to write data into the file'), LogLevel::ERROR);

                return;
            }
        }

        fclose($handler);
    }
}
