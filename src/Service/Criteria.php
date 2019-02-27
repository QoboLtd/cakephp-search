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
namespace Search\Service;

/**
 * This class acts as a value object for search criteria.
 */
final class Criteria
{
    private const REQUIRED_PARAMS = [
        'operator',
        'field',
        'value'
    ];

    /**
     * Field name.
     *
     * @var string
     */
    private $field;

    /**
     * Search operator.
     *
     * @var string
     */
    private $operator;

    /**
     * Search value.
     *
     * @var mixed
     */
    private $value;

    /**
     * Constructor method.
     *
     * @param mixed[] $data Search data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->validate($data);

        $this->field = $data['field'];
        $this->operator = $data['operator'];
        $this->value = $data['value'];
    }

    /**
     * Validates search criteria.
     *
     * @param mixed[] $data Search criteria
     * @return void
     * @throws \InvalidArgumentException When invalid/incomplete data are provided
     */
    private function validate(array $data)
    {
        $diff = array_diff(self::REQUIRED_PARAMS, array_keys($data));

        if (! empty($diff)) {
            throw new \InvalidArgumentException(
                sprintf('Search criteria is missing required parameter(s): %s', implode(', ', $diff))
            );
        }

        if (! is_string($data['field'])) {
            throw new \InvalidArgumentException('Field parameter must be a string');
        }

        if (! is_string($data['operator'])) {
            throw new \InvalidArgumentException('Operator parameter must be a string');
        }

        if (! is_scalar($data['value']) && ! is_array($data['value'])) {
            throw new \InvalidArgumentException(sprintf('Unsupported value type provided: %s', gettype($data['value'])));
        }
    }

    /**
     * Field name getter.
     *
     * @return string
     */
    public function getField() : string
    {
        return $this->field;
    }

    /**
     * Search operator getter.
     *
     * @return string
     */
    public function getOperator() : string
    {
        return $this->operator;
    }

    /**
     * Search value getter.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
