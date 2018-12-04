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
namespace Search\Filter;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * Field name.
     *
     * @var string
     */
    private $field;

    /**
     * Search value.
     *
     * @var mixed
     */
    private $value;

    /**
     * Constructor method.
     *
     * @param string $field Field name
     * @param mixed $value Search value
     */
    public function __construct(string $field, $value)
    {
        if (! is_scalar($value) && ! is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf('Filter value must be a scalar or an array, %s provided', gettype($value))
            );
        }

        $this->field = $field;
        $this->value = $value;
    }

    /**
     * Field name getter.
     *
     * @return string
     */
    final public function getField() : string
    {
        return $this->field;
    }

    /**
     * Search value getter.
     *
     * @return mixed
     */
    final public function getValue()
    {
        return $this->value;
    }
}
