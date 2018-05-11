<template>
  <div>
    <div class="board-frame">
      <div class="chess-player-label" v-bind:class="getColorAtBottom() === 'w' ? 'black-player' : 'white-player'">
        <span class="label">{{ getColorAtBottom() === 'w' ? 'Black' : 'White' }}</span>
        <span class="name">{{ getColorAtBottom() === 'w' ? this.game.usernames['black'] : this.game.usernames['white'] }}</span>
      </div>
      <table class="board-main table" data-striping="1">
        <caption v-if="caption">{{ caption }}</caption>

        <thead v-if="header">
          <tr>
            <th>{{ /** Empty row number */ }}</th>
            <th v-for="(column, index) in 8">
              {{ adjustFlipped(column) }}
            </th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="row in 8">
            <td class="board-coordinate"><b>{{ adjustFlipped(row) }}</b></td>
            <td v-for="column in 8"
                v-bind:id="getCoordinate(adjustFlipped(row), adjustFlipped(9 - column))"
                class="board-square"
                v-bind:class="getBoardSquareClasses(adjustFlipped(row), adjustFlipped(9 - column))"
                v-bind:data-chess-command="getBoardSquareCommand(adjustFlipped(row), adjustFlipped(9 - column))"
                v-on:click="assembleCommand(adjustFlipped(row), adjustFlipped(9 - column))">
              <div class="default" v-bind:class="getPieceClasses(adjustFlipped(row), adjustFlipped(9 - column))"></div>
            </td>
          </tr>
          <tr>
            <td>{{ /** Empty row number */ }}</td>
            <td v-for="column in 8" class="file-letters" align="center">
              {{ getFile(adjustFlipped(9 - column)) }}
            </td>
          </tr>
        </tbody>
      </table>
      <div class="chess-player-label" v-bind:class="getColorAtBottom() === 'w' ? 'white-player' : 'black-player'">
        <span class="label">{{ getColorAtBottom() === 'w' ? 'White' : 'Black' }}</span>
        <span class="name">{{ getColorAtBottom() === 'w' ? game.usernames['white'] : game.usernames['black'] }}</span>
      </div>
    </div>

    <input id="flip-board" name="flip_board" type="checkbox" value="Flip board" v-on:click="flipBoard()" v-bind:checked="game.boardFlipped"/>
    <label for="flip-board">Flip board</label>

    <promotion-dialog v-show="dialogOpen" @dialog-closed="hideDialog" default="Q"/>
  </div>

</template>

<script>
  import PromotionDialog from './PromotionDialog.vue';

  export default {

    components: { PromotionDialog },

    props: {
      game: Object,
      caption: null,
      header: null,
      user: Number,
    },

    data() {
      return {
        move: {
          source: "",
          destination: "",
        },
        savedMove: "",
        dialogOpen: false,
        promotion: "Q",
      }
    },

    methods: {

      // Confirms that the current user should be able to make moves.
      isActive() {
        return (this.game.turn === 'w' && this.game.white_uid === this.user)
          || (this.game.turn === 'b' && this.game.black_uid === this.user)
      },

      async playMove() {
        if (this.savedMove !== "") {
          let move = this.savedMove;
          const move_type = move[3];
          let to_rank;

          // Find out the rank of the square we are going to
          if (move_type === "-") {
            to_rank = move[5];
          }
          else { // move_type == "x"
            to_rank = move[6];
          }

          // If pawn enters last line ask for promotion.
          if (move[0] === 'P' && (to_rank === '8' || to_rank === '1')) {
            this.dialogOpen = true;
            // Wait until the user dismisses the dialog.
            while (this.dialogOpen) {
              await this.wait(500);
            }

            // If the promotion is cancelled, assume the move is not committed.
            if (this.promotion === 'cancel') {
              this.highlightMove();
              return;
            }

            move += '=' + this.promotion;
          }
          this.savedMove = move;
          this.$emit('submit-move', this.savedMove);
          this.savedMove = "";
          this.highlightMove();
        }
      },

      // wait ms milliseconds
      wait(ms) {
        return new Promise(r => setTimeout(r, ms));
      },

      hideDialog(value) {
        this.promotion = value;
        this.dialogOpen = false;
      },

      flipBoard() {
        this.game.boardFlipped = !this.game.boardFlipped;
        this.$emit('fast-refresh');
      },

      getPiece(row, column) {
        const coordinate = this.getCoordinate(row,  column);
        if (this.game.pieces[coordinate] === undefined) {
          return null;
        }
        return this.game.pieces[coordinate];
      },

      getFile(column) {
        column = Math.max(0, Math.min(9, column));
        return ['', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', ''][column];
      },

      getCoordinate(row, column) {
        return this.getFile(column) + row;
      },

      // Returns the CSS classes for the specified board square.
      getBoardSquareClasses(row, column) {
        let cssClass;
        if ((row + 1 + column) % 2 === 0) {
          cssClass = 'white';
        }
        else {
          cssClass = 'black';
        }
        return [cssClass, {enabled: this.isActive(), highlighted: this.getCoordinate(row, column) === this.move.source}]
      },

      getPieceClasses(row, column) {
        const piece = this.getPiece(row, column);
        if (piece) {
          return [(piece['color'] + '-' + piece['name']).toLowerCase(), 'board-piece'];
        }
        return ['empty-square'];
      },

      getPlayerColor () {
        if (this.game.white_uid === this.user) {
          return 'w';
        }
        if (this.game.black_uid === this.user) {
          return 'b';
        }
        return '';
      },

      getBoardSquareCommand(row, column) {
        if (this.isActive()) {
          // For active user, add ability to move the piece from this square.
          // Build the first part of the move e.g. "Nb1"
          const piece = this.getPiece(row, column);
          const playerColor = this.getPlayerColor();
          if (piece) {
            let cmdPart = piece['type'] + this.getCoordinate(row, column);
            // If the piece is the opposition then the cmdpart becomes e.g. "xNb1"
            if (piece['color'] !== playerColor) {
              cmdPart = 'x' + cmdPart;
            }
            return cmdPart;
          }
          else {
            // For active user, add ability to move a piece to this square.
            // For this we build the target part of the move e.g. "-c3", so the
            // move will end up as "Nb1-c3"
            return '-' + this.getCoordinate(row, column);
          }
        }
        return '';
      },

      assembleCommand(row, column) {
        if (this.isActive()) {
          let part = this.getBoardSquareCommand(row, column);
          if (this.savedMove === part) {
            // If I click the same spot again, then clear the move.
            this.savedMove = "";
          }
          else {
            // If no existing move or the move is complete, then update it.
            if (this.savedMove.length === 0 || this.savedMove.length >= 6) {
              if (part.charAt(0) !== '-' && part.charAt(0) !== 'x') {
                this.savedMove = part;
              }
            }
            else {
              // Complete the move if part of it has been made.
              if (part.charAt(0) === '-' || part.charAt(0) === 'x') {
                this.savedMove = this.savedMove + part;
              }
              else {
                this.savedMove = part;
              }
            }
            // Highlight the move.
            this.highlightMove();

            // If the move is complete, the send it to the server.
            if (this.savedMove.length >= 6) {
              this.playMove();
            }
          }
        }
      },

      highlightMove() {
        // Clear old highlighting.
        this.move.source = this.move.destination = "";

        // If command is empty don't highlight again.
        if (this.savedMove === null || this.savedMove === "") {
          return;
        }

        // Parse command for source/destination. It will be highlighted in
        // reaction to the change.
        // this.savedMove can be e.g. "Nb1" or "Nb1-c3"
        this.move.source = this.savedMove.substr(1, 2); // e.g. "b1"
        if (this.savedMove.length >= 6) {
          this.move.destination = this.savedMove.substr(4, 2); // e.g. "c3"
        }
        else {
          this.move.destination = "";
        }
      },

      adjustFlipped(row) {
        if (this.getColorAtBottom() === 'w') {
          return 9 - row;
        }
        else {
          return row;
        }
      },

      // Gets which player color is at the bottom of the table.
      getColorAtBottom() {
        const playerColor = this.getPlayerColor();
        if (this.game.boardFlipped) {
          if (playerColor === 'b') {
            return 'w';
          }
          else {
            return 'b';
          }
        }
        else {
          if (playerColor === 'b') {
            return 'b';
          }
          else {
            return 'w';
          }
        }
      },

    },

  }
</script>
