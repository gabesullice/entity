<?php

namespace Drupal\Tests\entity\Unit;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity\Query\Condition;
use Drupal\entity\Query\ConditionGroup;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity\Query\ConditionGroup
 * @group entity
 */
class ConditionGroupTest extends UnitTestCase {

  /**
   * ::covers getConjunction
   * ::covers addCondition
   * ::covers getConditions
   * ::covers count.
   */
  public function testGetters() {
    $condition_group = new ConditionGroup();
    $condition_group->addCondition(Condition::create('uid', '2', '=', new CacheableMetadata()));
    $this->assertEquals('AND', $condition_group->getConjunction());
    $expected_conditions = [
      Condition::create('uid', '2', '=', new CacheableMetadata()),
    ];
    $this->assertEquals($expected_conditions, $condition_group->getConditions());
    $this->assertEquals(1, $condition_group->count());
    $this->assertEquals("uid = '2'", $condition_group->__toString());

    $condition_group = new ConditionGroup('OR');
    $condition_group->addCondition(Condition::create('type', ['article', 'page'], 'IN', new CacheableMetadata()));
    $condition_group->addCondition(Condition::create('status', '1', '<>', new CacheableMetadata()));
    $this->assertEquals('OR', $condition_group->getConjunction());
    $expected_conditions = [
      Condition::create('type', ['article', 'page'], 'IN', new CacheableMetadata()),
      Condition::create('status', '1', '<>', new CacheableMetadata()),
    ];
    $expected_lines = [
      "(",
      "  type IN ['article', 'page']",
      "    OR",
      "  status <> '1'",
      ")",
    ];
    $this->assertEquals($expected_conditions, $condition_group->getConditions());
    $this->assertEquals(2, $condition_group->count());
    $this->assertEquals(implode("\n", $expected_lines), $condition_group->__toString());

    // Nested condition group with a single condition.
    $condition_group = new ConditionGroup();
    $condition_group->addCondition(Condition::create('type', ['article', 'page'], 'IN', new CacheableMetadata()));
    $condition_group->addCondition((new ConditionGroup('AND'))
      ->addCondition(Condition::create('status', '1', '=', new CacheableMetadata()))
    );
    $expected_conditions = [
      Condition::create('type', ['article', 'page'], 'IN', new CacheableMetadata()),
      Condition::create('status', '1', '=', new CacheableMetadata()),
    ];
    $expected_lines = [
      "(",
      "  type IN ['article', 'page']",
      "    AND",
      "  status = '1'",
      ")",
    ];
    $this->assertEquals($expected_conditions, $condition_group->getConditions());
    $this->assertEquals('AND', $condition_group->getConjunction());
    $this->assertEquals(2, $condition_group->count());
    $this->assertEquals(implode("\n", $expected_lines), $condition_group->__toString());

    // Nested condition group with multiple conditions.
    $condition_group = new ConditionGroup();
    $condition_group->addCondition(Condition::create('type', ['article', 'page'], 'IN', (new CacheableMetadata())->addCacheContexts(['foo'])));
    $nested_condition_group = new ConditionGroup('OR');
    $nested_condition_group->addCondition(Condition::create('uid', '1', '=', (new CacheableMetadata())->addCacheContexts(['bar'])));
    $nested_condition_group->addCondition(Condition::create('status', '1', '=', new CacheableMetadata()));
    $condition_group->addCondition($nested_condition_group);
    $expected_conditions = [
      Condition::create('type', ['article', 'page'], '=', new CacheableMetadata()),
      $nested_condition_group,
    ];
    $expected_lines = [
      "(",
      "  type IN ['article', 'page']",
      "    AND",
      "  (",
      "    uid = '1'",
      "      OR",
      "    status = '1'",
      "  )",
      ")",
    ];
    $this->assertEquals($expected_conditions, $condition_group->getConditions());
    $this->assertEquals('AND', $condition_group->getConjunction());
    $this->assertEquals(2, $condition_group->count());
    $this->assertEquals(implode("\n", $expected_lines), $condition_group->__toString());
    $this->assertArrayEquals(['foo', 'bar'], $condition_group->getCacheContexts());
  }

}
