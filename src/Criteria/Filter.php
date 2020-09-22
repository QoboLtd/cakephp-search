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

use Qobo\Search\Filter\FilterInterface;
use Webmozart\Assert\Assert;

final class Filter
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $value;

    /**
     * Create a new Filter.
     *
     * @param string $type Filter type
     * @param  mixed $value Ftiler Value
     * @return void
     */
    public function __construct(string $type, $value)
    {
        Assert::classExists($type);
        if (! in_array(FilterInterface::class, class_implements($type))) {
            throw new \InvalidArgumentException(sprintf('"%s" does not implement "%s"', $type, FilterInterface::class));
        }

        $items = is_array($value) ? $value : [$value];
        foreach ($items as $item) {
            Assert::scalar($item);
        }

        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Filter type getter.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Filter value getter.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }
}
