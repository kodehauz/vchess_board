<?php

namespace Drupal\vchess_board;

use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\serialization\Normalizer\EntityNormalizer;
use Drupal\vchess\Entity\Game;

class VChessGameNormalizer extends EntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = [Game::class];

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = 'json', array $context = []) {
    $context += [
      'account' => NULL,
    ];

    $attributes = [];
    /** @var \Drupal\Core\Entity\Entity $entity */
    foreach (TypedDataInternalPropertiesHelper::getNonInternalProperties($entity->getTypedData()) as $name => $field_items) {
      if ($field_items->access('view', $context['account'])) {
        // There is no field item that is multi-value, so we take the first value.
        if (isset($field_items->value)) {
          // Primitive items.
          if (is_int($field_items->value)) {
            $value = (int) $field_items->value;
          }
          else if (is_numeric($field_items->value)) {
            $value = (float) $field_items->value;
          }
          else {
            $value = $field_items->value;
          }
          $attributes[$name] = $this->serializer->normalize($value, $format, $context);
        }
        else if ($field_items->entity) {
          // Entity reference items.
          $attributes[$name] = $this->serializer->normalize((int) $field_items->target_id, $format, $context);
        }
        else {
          // Generic fallback.
          $attributes[$name] = $this->serializer->normalize($field_items, $format, $context);
        }
      }
    }

    return $attributes;
  }

}
