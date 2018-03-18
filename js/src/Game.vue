<template>
  <div>
    <Board :game="game" :user="user" @submit-move="submitMove" @board-flipped="flipBoard"/>
    <div class="game-left-side">
      <Captures :game="game" />
      <Clock :game="game" :user="user" />
    </div>
    <div class="game-right-side">
      <MoveList :moves="game.moves" />
    </div>
  </div>
</template>

<script>
import Board from './components/Board.vue';
import Clock from './components/Clock.vue';
import Captures from './components/Captures.vue';
import MoveList from './components/MoveList.vue';
import axios from 'axios'

export default {

  components: { Board, Clock, Captures, MoveList },

  props: {
    initialGame: {
      type: Object,
      required: true
    },
    user: Number,
    apiUrl: String,
    interval: Number,
  },

  mounted() {
    // Create interval for refreshing board.
    window.setInterval(this.refreshGame, 1000);
  },

  data() {
    return {
      game: this.initialGame,
      message: '',
      refreshing: false,
    }
  },

  methods: {
    // Reloads the game from the remove server.
    refreshGame() {
      // Don't try to refresh if we are already refreshing. Also if it is the
      // player's turn, no need to refresh.
      if (this.refreshing) {
        return;
      }
      this.refreshing = true;
      const body = {
        white_time_left: this.game.white_time_left.value,
        black_time_left: this.game.black_time_left.value
      };
      axios.post(this.apiUrl + '/game', body)
        .then((response) => {
          this.game = response.data.game;
          this.message = response.data.message;
          this.refreshing = false;
        })
        .catch((reason) => {
          console.log(reason);
        });
    },

    flipBoard() {
      const body = {
        board_flipped: this.game.boardFlipped,
        white_time_left: this.game.white_time_left.value,
        black_time_left: this.game.black_time_left.value
      };
      axios
        .post(this.apiUrl + '/flip', body)
        .then((response) => {
          if (response.data.game) {
            this.game = response.data.game;
          }
          this.message = response.data.message;
        });
    },

    submitMove(move) {
      const body = {
        cmd: move,
        white_time_left: this.game.white_time_left.value,
        black_time_left: this.game.black_time_left.value
      };
      axios
        .post(this.apiUrl + '/move', body)
        .then((response) => {
          if (response.data.game) {
            this.game = response.data.game;
          }
          this.message = response.data.message;
        });
    },

  }
}
</script>

<style>
  .game-left-side {
    float: left;
    display: inline-block;
  }

  .game-right-side {
    float: left;
    display: inline-block;
  }
</style>
