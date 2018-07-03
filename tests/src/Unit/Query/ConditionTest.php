<?php

namespace Drupal\Tests\entity\Unit\Query;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity\Query\Condition;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity\Query\Condition
 * @group entity
 */
class ConditionTest extends UnitTestCase {

  /**
   * ::covers __construct.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Unrecognized operator "INVALID".
   */
  public function testInvalidOperator() {
    Condition::create('uid', '1', 'INVALID', new CacheableMetadata());
  }

  /**
   * ::covers getField
   * ::covers getValue
   * ::covers getOperator
   * ::covers __toString.
   */
  public function testGetters() {
    $condition = Condition::create('uid', '2', '=', new CacheableMetadata());
    $this->assertEquals('uid', $condition->getField());
    $this->assertEquals('2', $condition->getValue());
    $this->assertEquals('=', $condition->getOperator());
    $this->assertEquals("uid = '2'", $condition->__toString());

    $condition = Condition::create('type', ['article', 'page'], 'IN', new CacheableMetadata());
    $this->assertEquals('type', $condition->getField());
    $this->assertEquals(['article', 'page'], $condition->getValue());
    $this->assertEquals('IN', $condition->getOperator());
    $this->assertEquals("type IN ['article', 'page']", $condition->__toString());

    $condition = Condition::create('title', NULL, 'IS NULL', new CacheableMetadata());
    $this->assertEquals('title', $condition->getField());
    $this->assertEquals(NULL, $condition->getValue());
    $this->assertEquals('IS NULL', $condition->getOperator());
    $this->assertEquals("title IS NULL", $condition->__toString());
  }

}
