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
namespace Qobo\Search\Test\TestCase\Criteria;

use PHPUnit\Framework\TestCase;
use Qobo\Search\Aggregate\Maximum;
use Qobo\Search\Criteria\Aggregate;
use Qobo\Search\Criteria\Criteria;
use Qobo\Search\Criteria\Field;
use Qobo\Search\Criteria\Filter;
use Qobo\Search\Filter\Contains;

class CriteriaTest extends TestCase
{
    private $field;
    private $filter;
    private $aggregate;

    public function setUp(): void
    {
        $this->field = new Field('Articles.title');
        $this->filter = new Filter(Contains::class, 'foobar');
        $this->aggregate = new Aggregate(Maximum::class);
    }

    public function testShouldCreateNewCriteria(): void
    {
        $criteria = Criteria::create($this->field);

        $this->assertInstanceOf(Criteria::class, $criteria);
        $this->assertSame($this->field, $criteria->field());
    }

    public function testShouldHaveFilterAndValue(): void
    {
        $criteria = Criteria::create($this->field);
        $this->assertNull($criteria->filter());

        $criteria->setFilter($this->filter);
        $this->assertSame($this->filter, $criteria->filter());
    }

    public function testShouldHaveAggregate(): void
    {
        $criteria = Criteria::create($this->field);
        $this->assertNull($criteria->aggregate());

        $criteria->setAggregate($this->aggregate);
        $this->assertSame($this->aggregate, $criteria->aggregate());
    }
}
