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
namespace Qobo\Search\Criteria;

final class OrderBy
{
    /**
     * @var Field
     */
    private $field;

    /**
     * @var Direction
     */
    private $direction;

    /**
     * Create a new OrderBy.
     *
     * @param Field $field Field
     * @param Direction $direction Direction
     * @return void
     */
    public function __construct(Field $field, Direction $direction)
    {
        $this->field = $field;
        $this->direction = $direction;
    }

    /**
     * Order field getter.
     *
     * @return \Qobo\Search\Criteria\Field
     */
    public function field(): Field
    {
        return $this->field;
    }

    /**
     * Order direction getter.
     *
     * @return \Qobo\Search\Criteria\Direction
     */
    public function direction(): Direction
    {
        return $this->direction;
    }

    /**
     * Return the object as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->field . ' - ' . (string)$this->direction;
    }
}
