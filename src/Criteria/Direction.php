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
namespace Search\Criteria;

use Webmozart\Assert\Assert;

final class Direction
{
    /**
     * SQL sort directions
     */
    public const DIRECTIONS = ['DESC', 'ASC'];

    /**
     * @var string
     */
    private $value;

    /**
     * Create a new Direction.
     *
     * @param string $value direction
     * @return void
     */
    public function __construct(string $value)
    {
        $value = strtoupper($value);
        Assert::keyExists(array_flip(self::DIRECTIONS), $value);

        $this->value = $value;
    }

    /**
     * Return the object as a string
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->value;
    }
}
