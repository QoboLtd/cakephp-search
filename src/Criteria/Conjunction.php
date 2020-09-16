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

final class Conjunction
{
    /**
     * SQL conjunctions
     */
    public const CONJUNCTIONS = ['AND', 'OR'];

    /**
     * Default SQL conjunction
     */
    public const DEFAULT_CONJUNCTION = 'AND';

    /**
     * @var string
     */
    private $value;

    /**
     * Constructor method.
     *
     * @param string $value Conjunction
     * @return void
     */
    public function __construct(string $value)
    {
        $value = strtoupper($value);
        Assert::keyExists(array_flip(self::CONJUNCTIONS), $value);

        $this->value = $value;
    }

    /**
     * Return the object as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
