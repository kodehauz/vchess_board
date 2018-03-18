<?php

namespace Drupal\vchess_board;

use Drupal\vchess\Game\Board;

/**
 * A board that can be serialized to allow transmission over HTTP.
 */
class NormalizableBoard extends Board {

  public function getNormalizedBoard() {
    $board = [];

    foreach ($this->board as $coordinate => $piece) {
      $board[$coordinate] = [
        'type' => $piece->getType(),
        'color' => $piece->getColor(),
        'name' => $piece->getName(),
        'fentype' => $piece->getFenType(),
      ];
    }

    return $board;
  }

}
