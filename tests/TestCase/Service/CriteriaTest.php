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
namespace Search\Test\TestCase\Service;

use Cake\TestSuite\TestCase;
use Search\Service\Criteria;

class CriteriaTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();
    }

    public function tearDown() : void
    {
        parent::tearDown();
    }

    /**
     * @dataProvider invalidDataProvider
     * @param mixed[] $data
     * @return void
     */
    public function testValidationWithInvalidData(array $data) : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Criteria($data);
    }

    /**
     * @return void
     */
    public function testGetField() : void
    {
        $criteria = new Criteria(['operator' => 'foo', 'field' => 'Articles.title', 'value' => 'bla bla']);

        $this->assertSame('Articles.title', $criteria->getField());
    }

    /**
     * @return void
     */
    public function testGetValue() : void
    {
        $criteria = new Criteria(['operator' => 'foo', 'field' => 'bar', 'value' => true]);

        $this->assertSame(true, $criteria->getValue());
    }

    /**
     * @return void
     */
    public function testGetOperator() : void
    {
        $criteria = new Criteria(['operator' => 'Search\Filter\Equal', 'field' => 'foo', 'value' => 'bla bla']);

        $this->assertSame('Search\Filter\Equal', $criteria->getOperator());
    }

    /**
     * @return mixed[]
     */
    public function invalidDataProvider() : array
    {
        return [
            [['operator' => false, 'field' => 'ok', 'value' => 'ok']],
            [['operator' => 'ok', 'field' => false, 'value' => 'ok']],
            [['operator' => 'ok', 'field' => 'ok', 'value' => new \stdClass]],
            [['operator' => 'ok', 'field' => false]],
            [['operator' => 'ok', 'value' => 'ok']],
            [['field' => false, 'value' => 'ok']],
        ];
    }
}
