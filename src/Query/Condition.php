<?php

namespace Drupal\entity\Query;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Represents a single access condition.
 */
final class Condition {

  use CacheableDependencyTrait;

  /**
   * The supported operators.
   *
   * @var array
   */
  protected static $supportedOperators = [
    '=', '<>', '<', '<=', '>', '>=', 'BETWEEN', 'NOT BETWEEN',
    'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL',
  ];

  /**
   * The field.
   *
   * @var string
   */
  protected $field;

  /**
   * The value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The operator.
   *
   * @var string
   */
  protected $operator;

  /**
   * Constructs a new Condition object.
   *
   * @param string $field
   *   The field, with an optional column name. E.g: 'uid', 'address.locality'.
   * @param mixed $value
   *   The value.
   * @param string $operator
   *   The operator.
   *   Possible values: =, <>, <, <=, >, >=, BETWEEN, NOT BETWEEN,
   *                   IN, NOT IN, IS NULL, IS NOT NULL.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cacheability information for this condition.
   */
  public function __construct($field, $value, $operator, CacheableMetadata $cacheability) {
    // Validate the selected operator.
    if (!in_array($operator, self::$supportedOperators)) {
      throw new \InvalidArgumentException(sprintf('Unrecognized operator "%s".', $operator));
    }

    $this->field = $field;
    $this->value = $value;
    $this->operator = $operator;
    $this->setCacheability($cacheability);
  }

  /**
   * Creates a new Condition object.
   *
   * @see self::__construct().
   */
  public static function create($field, $value, $operator, CacheableMetadata $cacheability) {
    return new static($field, $value, $operator, $cacheability);
  }

  /**
   * {@inheritdoc}
   */
  public function getField() {
    return $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperator() {
    return $this->operator;
  }

  /**
   * Gets the string representation of the condition.
   *
   * Used for debugging purposes.
   *
   * @return string
   *   The string representation of the condition.
   */
  public function __toString() {
    if (in_array($this->operator, ['IS NULL', 'IS NOT NULL'])) {
      return "{$this->field} {$this->operator}";
    }
    else {
      if (is_array($this->value)) {
        $value = "['" . implode("', '", $this->value) . "']";
      }
      else {
        $value = "'" . $this->value . "'";
      }

      return "{$this->field} {$this->operator} $value";
    }
  }

}
