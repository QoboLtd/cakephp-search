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

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\ResultSetInterface;
use Cake\Filesystem\File;
use Cake\Log\LogTrait;
use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Psr\Log\LogLevel;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Search\Aggregate\AbstractAggregate;
use Search\Model\Entity\SavedSearch;
use Webmozart\Assert\Assert;

final class Export
{
    use LogTrait;

    /**
     * @var string
     */
    protected $id;

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
        $this->user = $user;

        $savedSearch = TableRegistry::getTableLocator()->get('Search.SavedSearches')->get($id);
        Assert::isInstanceOf($savedSearch, SavedSearch::class);

        $options = $this->getOptionsFromSavedSearch($savedSearch);

        $table = TableRegistry::getTableLocator()->get($savedSearch->get('model'));
        $this->query = $table->find('search', $options);
        $this->data = $options;
        $this->search = $savedSearch;

        $exportUrl = Configure::read('Search.export.url');
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
        $headers = 1 === $page ? $this->getHeaders() : [];
        $mode = 1 === $page ? 'w' : 'a';
        $rows = $this->getRows($page, $limit);

        if ([] !== $headers) {
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
     * Get export headers.
     *
     * @return mixed[]
     */
    private function getHeaders(): array
    {
        if (empty($this->data['fields'])) {
            return [];
        }

        $table = TableRegistry::get($this->search->get('model'));

        $associationLabels = self::getAssociationLabels($table);
        $fieldLabels = self::getFieldLabels($table, true);

        $result = [];
        foreach ($this->data['fields'] as $field) {
            $extraInfo = [];
            if (AbstractAggregate::isAggregate($field)) {
                $extraInfo[] = AbstractAggregate::extractAggregate($field);
                $field = AbstractAggregate::extractFieldName($field);
            }

            $label = array_key_exists($field, $fieldLabels) ? $fieldLabels[$field] : $field;

            list($fieldModel, ) = pluginSplit($field);
            if (array_key_exists($fieldModel, $associationLabels)) {
                $extraInfo[] = $associationLabels[$fieldModel];
            }

            $result[] = [] !== $extraInfo ? $label . ' (' . implode(' - ', $extraInfo) . ')' : $label;
        }

        return $result;
    }

    /**
     * Get export rows.
     *
     * @param int $page Pagination page
     * @param int $limit Pagination limit
     * @return mixed[]
     */
    private function getRows(int $page, int $limit): array
    {
        if (empty($this->data['fields'])) {
            return [];
        }

        $query = $this->query->page($page, $limit);
        if (0 === $query->count()) {
            return [];
        }

        return self::toCsv(
            $query->all(),
            array_map('strval', $this->data['fields']),
            TableRegistry::get($this->search->get('model'))
        );
    }

    /**
     * Method that formats entities for CSV export.
     *
     * @param \Cake\Datasource\ResultSetInterface $resultSet ResultSet
     * @param string[] $fields Display fields
     * @param \Cake\ORM\Table $table Table instance
     * @return mixed[]
     */
    private static function toCsv(ResultSetInterface $resultSet, array $fields, Table $table): array
    {
        $result = [];
        foreach ($resultSet as $key => $entity) {
            foreach ($fields as $field) {
                if (AbstractAggregate::isAggregate($field)) {
                    $result[$key][] = $entity->get($field);
                    continue;
                }

                list($tableName, $fieldName) = explode('.', $field);

                $targetEntity = $entity;
                $targetTable = $table;
                if ($table->getAlias() !== $tableName) {
                    $targetTable = $table->getAssociation($tableName)->getTarget();
                    $targetEntity = $entity->get('_matchingData')[$tableName];
                }

                $result[$key][] = self::formatValue($targetTable, $fieldName, $targetEntity->get($fieldName));
            }
        }

        return $result;
    }

    /**
     * Extracts search options from saved search.

     * @param \Search\Model\Entity\SavedSearch $savedSearch SavedSearch
     * @return mixed[]
     */
    private function getOptionsFromSavedSearch(SavedSearch $savedSearch): array
    {
        $options = [];

        if ($savedSearch->get('criteria')) {
            foreach ($savedSearch->get('criteria') as $field => $items) {
                foreach ($items as $item) {
                    $value = $item['value'];
                    if (is_string($value)) {
                        $value = $this->getMagicValue($value);
                    }

                    if (is_array($value)) {
                        $value = array_map(function ($item) {
                            return $this->getMagicValue($item);
                        }, $value);
                    }

                    $options['data'][] = ['field' => $field, 'operator' => $item['operator'], 'value' => $value];
                }
            }
        }
        if ($savedSearch->get('fields')) {
            $options['fields'] = $savedSearch->get('fields');
        }
        if ($savedSearch->get('conjunction')) {
            $options['conjunction'] = $savedSearch->get('conjunction');
        }
        if ($savedSearch->get('order_by_field') && $savedSearch->get('order_by_direction')) {
            $options['order'] = [$savedSearch->get('order_by_field') => $savedSearch->get('order_by_direction') ];
        }
        if ($savedSearch->get('group_by')) {
            $options['group'] = $savedSearch->get('group_by');
        }

        return $options;
    }

    /**
     * Formats value before extracing to CSV.
     *
     * @param \Cake\ORM\Table $table ORM table
     * @param string $field Field name
     * @param mixed $value Field value
     * @return mixed
     */
    private static function formatValue(Table $table, string $field, $value)
    {
        $association = self::getAssociationFromField($table, $field);
        if (null !== $association) {
            $value = self::getDisplayValueFromAssociation($association, $field, $value);
        }

        $listName = self::getListNameFromField($table, $field);
        if ('' !== $listName) {
            $value = self::getLabelFromList($table, $listName, $value);
        }

        if ($value instanceof \Cake\I18n\Date) {
            $value = $value->format('Y-m-d');
        }

        if ($value instanceof \Cake\I18n\Time) {
            $value = $value->format('Y-m-d H:i:s');
        }

        $value = trim(strip_tags(html_entity_decode($value, ENT_QUOTES)), " \t\n\r\0\x0B\xC2\xA0");

        return $value;
    }

    /**
     * Retrieves corresponding label from provided list.
     *
     * @param \Cake\ORM\Table $table ORM table
     * @param string $listName List name
     * @param mixed $value Field value
     * @return mixed
     */
    private static function getLabelFromList(Table $table, string $listName, $value)
    {
        list(, $module) = pluginSplit(App::shortName(get_class($table), 'Model/Table', 'Table'));
        try {
            $options = (new ModuleConfig(ConfigType::LISTS(), $module, $listName))->parseToArray();
        } catch (\InvalidArgumentException $e) {
            return $value;
        }

        if (! array_key_exists('items', $options)) {
            return $value;
        }

        return array_key_exists($value, $options['items']) ? $options['items'][$value]['label'] : $value;
    }

    /**
     * Extracts list name from field.
     *
     * @param \Cake\ORM\Table $table ORM table
     * @param string $field Field name
     * @return string
     */
    private static function getListNameFromField(Table $table, string $field): string
    {
        list(, $module) = pluginSplit(App::shortName(get_class($table), 'Model/Table', 'Table'));
        $config = (new ModuleConfig(ConfigType::MIGRATION(), $module))->parseToArray();
        foreach ($config as $fieldName => $params) {
            if (! is_array($params) || ! array_key_exists('type', $params)) {
                continue;
            }

            if ($fieldName !== $field) {
                continue;
            }

            if (! preg_match('/(.*?)\((.*?)\)/', $params['type'], $matches)) {
                continue;
            }

            if (! in_array($matches[1], ['list', 'sublist', 'country', 'currency'])) {
                continue;
            }

            return $matches[2];
        }

        return '';
    }

    /**
     * Extracts association instance from field.
     *
     * @param \Cake\ORM\Table $table ORM table
     * @param string $field Field name
     * @return \Cake\ORM\Association|null
     */
    private static function getAssociationFromField(Table $table, string $field): ?Association
    {
        foreach ($table->associations() as $association) {
            if ($association->getForeignKey() === $field) {
                return $association;
            }
        }

        return null;
    }

    /**
     * Retrieves corresponding display value from related record.
     *
     * This method will recurse until it retrieves a non-primary-key value.
     *
     * @param \Cake\ORM\Association $association Association
     * @param string $field Field name
     * @param mixed $value Field value
     * @return mixed
     */
    private static function getDisplayValueFromAssociation(Association $association, string $field, $value)
    {
        $targetTable = $association->getTarget();
        $displayField = $targetTable->getDisplayField();
        $primaryKey = $targetTable->getPrimaryKey();
        Assert::string($primaryKey);

        $entity = $targetTable->find()->select($displayField)->where([$primaryKey => $value])->first();
        if (null === $entity) {
            return $value;
        }
        Assert::isInstanceOf($entity, \Cake\Datasource\EntityInterface::class);
        $value = $entity->get($displayField);

        $association = self::getAssociationFromField($targetTable, $displayField);
        if (null !== $association) {
            $value = self::getDisplayValueFromAssociation($association, $displayField, $value);
        }

        return $value;
    }

    /**
     * Create export file.
     *
     * @param mixed[] $data CSV data
     * @param string $mode File mode
     * @return void
     */
    private function create(array $data, string $mode = 'a'): void
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

    /**
     * Associations labels getter.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return mixed[]
     */
    private static function getAssociationLabels(Table $table): array
    {
        $result = [];
        foreach ($table->associations() as $association) {
            if (Association::MANY_TO_ONE === $association->type()) {
                $result[$association->getName()] = Inflector::humanize(current((array)$association->getForeignKey()));
            }
        }

        return $result;
    }

    /**
     * Method that retrieves target table field labels.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param bool $withAssociated flag for including associations fields
     * @return mixed[]
     */
    private static function getFieldLabels(Table $table, bool $withAssociated = true): array
    {
        list(, $module) = pluginSplit(App::shortName(get_class($table), 'Model/Table', 'Table'));
        $config = (new ModuleConfig(ConfigType::FIELDS(), $module))->parseToArray();

        $filtered = array_filter($config, function ($item) {
            return array_key_exists('label', $item);
        });

        $result = (array)array_combine(
            array_map(function ($item) use ($table) {
                return $table->aliasField($item);
            }, array_keys($filtered)),
            array_column($filtered, 'label')
        );

        foreach ($table->getSchema()->columns() as $column) {
            if (! array_key_exists($table->aliasField($column), $result)) {
                $result[$table->aliasField($column)] = Inflector::humanize(Inflector::underscore($column));
            }
        }

        if ($withAssociated) {
            $result = array_merge($result, self::includeAssociated($table));
        }

        return $result;
    }

    /**
     * Get associated tables searchable fields.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return mixed[]
     */
    private static function includeAssociated(Table $table): array
    {
        $result = [];
        foreach ($table->associations() as $association) {
            if (Association::MANY_TO_ONE !== $association->type()) {
                continue;
            }
            if ($association->getTarget()->getTable() === $table->getTable()) {
                continue;
            }

            $result = array_merge($result, self::getFieldLabels($association->getTarget(), false));
        }

        return $result;
    }

    /**
     * Magic value getter.
     *
     * @param string $value Field value
     * @return mixed
     */
    public function getMagicValue(string $value)
    {
        $method = str_replace('%%', '', $value);

        if (! method_exists($this, $method)) {
            return $value;
        }

        return $this->{$method}();
    }

    /**
     * Current user id magic value getter.
     *
     * @return string
     */
    private function me(): string
    {
        return $this->user['id'];
    }

    /**
     * Today's date magic value getter.
     *
     * @return string
     */
    private function today(): string
    {
        return (new \DateTimeImmutable('today'))->format('Y-m-d');
    }

    /**
     * Yesterday's date magic value getter.
     *
     * @return string
     */
    private function yesterday(): string
    {
        return (new \DateTimeImmutable('yesterday'))->format('Y-m-d');
    }

    /**
     * Tomorrow's date magic value getter.
     *
     * @return string
     */
    private function tomorrow(): string
    {
        return (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');
    }
}
