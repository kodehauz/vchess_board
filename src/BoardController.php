<?php

namespace Drupal\vchess_board;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Drupal\gamer\Entity\GamerStatistics;
use Drupal\user\Entity\User;
use Drupal\vchess\Entity\Game;
use Drupal\vchess\Entity\Move;
use Drupal\vchess\Game\GamePlay;
use Drupal\vchess\Game\Piece;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Allows the VueJS front end to interact with the VChess game entity.
 */
class BoardController extends ControllerBase {

  /**
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface|
   *      \Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer
   */
  protected $normalizer;

  /**
   * The Drupal state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Creates a new controller with normalizer for serializing the game board.
   */
  public function __construct(SerializerInterface $serializer, NormalizerInterface $normalizer, StateInterface $state) {
    $this->normalizer = $normalizer;
    $this->normalizer->setSerializer($serializer);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serializer'),
      $container->get('serializer.normalizer.vchess_game'),
      $container->get('state')
    );
  }

  /**
   * Returns the rendered HTML template for mounting the VueJS board.
   *
   * @param \Drupal\vchess\Entity\Game $vchess_game
   *   The upcasted VChess Game entity.
   *
   * @return array
   *   A render array containing the HTML template.
   */
  public function renderBoard(Game $vchess_game) {
    // Serialize and normalize the game entity.
    return [
      '#theme' => 'vchess_board',
      '#game' => $vchess_game,
      '#attached' => [
        'library' => [
          'vchess_board/board-compiled',
        ],
        'drupalSettings' => [
          'vchess' => [
            'game' => $this->getNormalizedGame($vchess_game),
            'refresh_interval' => 5,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => $vchess_game->getCacheContexts(),
        'tags' => $vchess_game->getCacheTags(),
      ],
    ];
  }

  /**
   * Returns the VChess Game entity in JSON format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\vchess\Entity\Game $vchess_game
   *   The upcasted VChess Game entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response in JSON format.
   */
  public function getGame(Request $request, Game $vchess_game) {
    $messages = [];
    try {
      // Update the game clocks.
      $post = Json::decode($request->getContent());
      $user = User::load($this->currentUser()->id());
      if (isset($post['white_time_left'], $post['black_time_left']) && $vchess_game->isPlayersMove($user)) {
        switch ($vchess_game->getPlayerColor($user)) {
          case 'w':
            $vchess_game
              ->setWhiteTimeLeft($post['white_time_left'])
              ->save();
            break;

          case 'b':
            $vchess_game
              ->setBlackTimeLeft($post['black_time_left'])
              ->save();
            break;
        }

        // Save board time left.
        $vchess_game->save();
      }

      if (isset($post['board_flipped'])) {
        // Update board position.
        $gid = $vchess_game->id();
        $uid = $this->currentUser()->id();
        $vchess_board = $this->state->get('vchess_board', []);
        $vchess_board['flipped'][$gid][$uid] = $post['board_flipped'];
        $this->state->set('vchess_board', $vchess_board);
      }
    }
    catch (\Exception $e) {
      $messages[] = $e->getMessage();
    }
    return new JsonResponse([
      'game' => $this->getNormalizedGame($vchess_game),
      'messages' => implode("\n", $messages),
    ]);
  }

  /**
   * Plays a submitted move and updates the game board.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request containing the posted game data.
   * @param \Drupal\vchess\Entity\Game $vchess_game
   *   The enhanced game object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response in JSON format.
   */
  public function makeMove(Request $request, Game $vchess_game) {
    $post = Json::decode($request->getContent());
    $messages = [];
    $errors = [];
    $saved = NULL;
    try {
      // Verify that it is the user's turn to play.
      $user = User::load($this->currentUser()->id());
      if ($vchess_game->getStatus() === GamePlay::STATUS_AWAITING_PLAYERS) {
        $messages[] = $this->t('Game is awaiting players');
      }
      else if ($vchess_game->getStatus() !== GamePlay::STATUS_IN_PROGRESS) {
        $messages[] = $this->t('Game is over');
      }
      else if ($vchess_game->isPlayersMove($user)) {
        // Command: e.g. Pe2-e4
        if ($cmd = $post['cmd']) {
          $gameplay = new GamePlay($vchess_game);

          if ($cmd === 'abort') {
            $move_made = $gameplay->abort($user, $messages, $errors);
          }
          elseif ($cmd === 'acceptdraw') {
            $move_made = $gameplay->acceptDraw($user, $messages, $errors);
          }
          elseif ($cmd === 'refusedraw') {
            $move_made = $gameplay->rejectDraw($user, $messages, $errors);
          }
          else { // try as chess move
            /** @var \Drupal\vchess\Entity\Move $move */
            $move = Move::create()->setLongMove($cmd);
            $move_made = $gameplay->makeMove($user, $move, $messages, $errors);
          }

          // Update the player's times left.
          $vchess_game
            ->setWhiteTimeLeft($post['white_time_left'])
            ->setBlackTimeLeft($post['black_time_left']);

          // Only save move and game if a move has actually been made.
          if ($move_made) {
            // Save game.
            $saved = $vchess_game->save();
            $vchess_game = Game::load($vchess_game->id());
            if ($vchess_game->getStatus() !== GamePlay::STATUS_IN_PROGRESS) {
              GamerStatistics::updatePlayerStatistics($vchess_game);
            }
          }
          else {
            // Add the errors to the messages.
            $messages = array_merge($messages, $errors);
          }
        }
        else {
          $messages[] = $this->t('Invalid move command');
        }
      }
      else {
        $messages[] = $this->t('Not your turn to play');
      }
    }
    catch (\Exception $e) {
      $messages[] = $e->getMessage();
    }

    return new JsonResponse([
      'game' => $this->getNormalizedGame($vchess_game),
      'saved' => $saved,
      'messages' => implode("\n", $messages),
    ]);
  }

  /**
   * Flips the game board.
   */
  public function flipBoard(Request $request, Game $vchess_game) {
  }

  /**
   * Normalizes the Game Entity to a format usable by the JS front end.
   *
   * @param \Drupal\vchess\Entity\Game $game
   *   The vchess Game entity to normalize.
   *
   * @return array
   *   The game entity normalized into an array.
   */
  protected function getNormalizedGame(Game $game) {
    $board = (new NormalizableBoard())->setupPosition($game->getBoard());
    return $this->normalizer->normalize($game, 'json') + [
      'pieces' => $board->getNormalizedBoard(),
      'boardFlipped' => $this->isBoardFlipped($game),
      'usernames' => $this->getPlayerNames($game),
      'moves' => array_map(function(array $moves) {
        return [
          'w' => $moves['w']->getAlgebraic(),
          'b' => isset($moves['b']) ? $moves['b']->getAlgebraic() : '',
        ];
      }, $game->getScoresheet()->getMoves()),
      'captured' => array_map(function(Piece $piece) {
        return [
          'type' => $piece->getType(),
          'color' => $piece->getColor(),
          'name' => $piece->getName(),
          'fentype' => $piece->getFenType(),
        ];
      }, $board->getCapturedPieces())
    ];
  }

  /**
   * Checks if a game board is flipped.
   *
   * @param \Drupal\vchess\Entity\Game $game
   *
   * @return boolean
   *   true if the game board is flipped, false otherwise.
   */
  protected function isBoardFlipped(Game $game) {
    // Check orientation of board.
    $gid = $game->id();
    $uid = $this->currentUser()->id();
    $vchess_board = $this->state->get('vchess_board', []);
    return isset($vchess_board['flipped'][$gid][$uid]) && $vchess_board['flipped'][$gid][$uid] === TRUE;
  }

  /**
   * Get names of the players on the board.
   *
   * @param \Drupal\vchess\Entity\Game $game
   *   The chess game.
   *
   * @return array
   *   A keyed list of player names.
   */
  protected function getPlayerNames(Game $game) {
    // Determine the game players, take note that this element will also display
    // challenge games without complete players.
    if ($game->getWhiteUser()) {
      $white_name = $game->getWhiteUser()->getDisplayName();
    }
    else {
      $white_name = t('TBD');
    }

    if ($game->getBlackUser()) {
      $black_name = $game->getBlackUser()->getDisplayName();
    }
    else {
      $black_name = t('TBD');
    }

    return [
      'white' => $white_name,
      'black' => $black_name,
    ];
  }

}
