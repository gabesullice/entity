<?php

namespace Drupal\entity\Query;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Represents a group of access conditions.
 *
 * Used by query access handlers for filtering lists of entities based on
 * granted permissions.
 *
 * Examples:
 * @code
 *   // Filter by node type and uid.
 *   $condition_group = new ConditionGroup();
 *   $condition_group->addCondition('type', ['article', 'page']);
 *   $condition_group->addCondition('uid', '1');
 *
 *   // Filter by node type or status.
 *   $condition_group = new ConditionGroup('OR');
 *   $condition_group->addCondition('type', ['article', 'page']);
 *   $condition_group->addCondition('status', '1', '<>');
 *
 *   // Nested condition groups: node type AND (uid OR status).
 *   $condition_group = new ConditionGroup();
 *   $condition_group->addCondition('type', ['article', 'page']);
 *   $condition_group->addCondition((new ConditionGroup('OR'))
 *     ->addCondition('uid', 1)
 *     ->addCondition('status', '1')
 *   );
 * @endcode
 */
final class ConditionGroup implements \Countable, CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * The conditions.
   *
   * @var \Drupal\entity\Query\Condition[]|\Drupal\entity\Query\ConditionGroup[]
   */
  protected $conditions = [];

  /**
   * The conjunction.
   *
   * @var string
   */
  protected $conjunction;

  /**
   * Constructs a new ConditionGroup object.
   *
   * @param string $conjunction
   *   The conjunction.
   * @param \Drupal\Core\Cache\CacheableMetadata|null $cacheability
   *   The cacheability information for this condition group. In most cases,
   *   this is not required; all cacheability will be inherited from the group's
   *   conditions. However, in some cases, an empty group still requires
   *   cacheability.
   */
  public function __construct($conjunction = 'AND', CacheableMetadata $cacheability = NULL) {
    $this->conjunction = $conjunction;
    $this->setCacheability($cacheability ?: new CacheableMetadata());
  }

  /**
   * Gets the conjunction.
   *
   * @return string
   *   The conjunction. Possible values: AND, OR.
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * Adds a condition or condition group to this group.
   *
   * @param \Drupal\entity\Query\Condition|\Drupal\entity\Query\ConditionGroup $condition
   *   Either a condition group (for nested AND/OR conditions), or a
   *   field name with an optional column name. E.g: 'uid', 'address.locality'.
   *
   * @return $this
   */
  public function addCondition($condition) {
    assert($condition instanceof Condition || $condition instanceof ConditionGroup);
    if ($condition instanceof ConditionGroup && $condition->count() === 1) {
      // The condition group only has a single condition, merge it.
      $this->conditions[] = reset($condition->getConditions());
    }
    else {
      $this->conditions[] = $condition;
    }
    return $this;
  }

  /**
   * Gets all conditions and nested condition groups.
   *
   * @return \Drupal\entity\Query\Condition[]|\Drupal\entity\Query\ConditionGroup[]
   *   The conditions, where each one is either a Condition or a nested
   *   ConditionGroup. Returned by reference, to allow callers to replace
   *   or remove conditions.
   */
  public function &getConditions() {
    return $this->conditions;
  }

  /**
   * Clones the contained conditions when the condition group is cloned.
   */
  public function __clone() {
    foreach ($this->conditions as $i => $condition) {
      $this->conditions[$i] = clone $condition;
    }
  }

  /**
   * Gets the string representation of the condition group.
   *
   * @return string
   *   The string representation of the condition group.
   */
  public function __toString() {
    // Special case for a single, nested condition group:
    if (count($this->conditions) == 1) {
      return (string) reset($this->conditions);
    }
    $lines = [];
    foreach ($this->conditions as $condition) {
      $lines[] = str_replace("\n", "\n  ", (string) $condition);
    }
    return $lines ? "(\n  " . implode("\n    {$this->conjunction}\n  ", $lines) . "\n)" : '';
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return array_reduce($this->conditions, [Cache::class, 'mergeTags'], $this->cacheTags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return array_reduce($this->conditions, [Cache::class, 'mergeContexts'], $this->cacheContexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return array_reduce($this->conditions, [Cache::class, 'mergeMaxAges'], $this->cacheMaxAge);
  }

}
