<template>
  <div>
    <Clock :game="game" :user="user" />
    <Board :game="game" :user="user" @submit-move="submitMove" @fast-refresh="fastRefresh"/>
    <div class="game-left-side">
      <Captures :game="game" />
    </div>
    <div class="game-right-side">
      <MoveList :moves="game.moves" />
    </div>
    <div class="clear messages">
      <div>{{ lastMessage }}</div>
      <ul v-if="messages.length > 0">
        <li v-for="message in messages">{{ message }}</li>
      </ul>
      <div v-else>No messages</div>
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
      messages: [],
      lastMessage: '',
      refreshing: false,
      refreshDisabled: false,
      // Array holding the axios cancel tokens for purpose of stopping requests.
      cancels: [],
    }
  },

  methods: {
    // Reloads the game from the remote server.
    refreshGame(reEnableRefresh = null) {
      // Don't try to refresh if we are already refreshing or refreshing is
      // disabled (e.g. if submitting a move).
      // Also, there is no need to refresh a game that has ended.
      if (this.refreshing || this.refreshDisabled || this.game.status !== 'in progress') {
        return;
      }
      this.refreshing = true;
      const body = {
        white_time_left: this.game.white_time_left,
        black_time_left: this.game.black_time_left,
        board_flipped: this.game.boardFlipped,
      };
      const cancel = axios.CancelToken.source();
      this.cancels.push(cancel);
      axios
        .post(this.apiUrl + '/game', body, {cancelToken: cancel.token})
        .then((response) => {
          // Only update if refreshing is allowed at this time.
          if (!this.refreshDisabled && this.refreshing) {
            this.game = response.data.game;
            this.messages.push(response.data.messages);
            this.lastMessage = response.data.messages;
          }
          this.refreshing = false;
          // Flag to allow fast refresh.
          if (reEnableRefresh) {
            this.refreshDisabled = false;
          }
        })
        .catch((reason) => {
          console.log(reason);
          this.refreshing = false;
        });
    },

    // Fast refresh cancels all pending refreshes and initiates a new one. This is useful for board flipping.
    fastRefresh() {
      // Disable board refresh until request is completed.
      this.refreshDisabled = true;
      // Cancel already sent refresh requests.
      this.cancelRequests('Fast refresh, cancelling other pending requests.');
      this.refreshGame(true);
    },

    submitMove(move) {
      // Disable board refresh until request is completed.
      this.refreshDisabled = true;

      // Wait for already sent requests to complete.
      if (this.refreshing) {
        window.setTimeout(function() { this.submitMove(move) }.bind(this), 100);
        return;
      }

      // Cancel already sent refresh requests.
      //  this.cancelRequests('Move submission, cancelling other pending requests.');

      const body = {
        cmd: move,
        white_time_left: this.game.white_time_left,
        black_time_left: this.game.black_time_left,
        board_flipped: this.game.boardFlipped
      };
      axios
        .post(this.apiUrl + '/move', body)
        .then((response) => {
          if (response.data.game) {
            this.game = response.data.game;
          }
          if (response.data.message) {
            this.messages.push(response.data.messages);
            this.lastMessage = response.data.messages;
          }
          this.refreshDisabled = false;
        })
        .catch((reason) => {
          this.refreshDisabled = false;
        });
    },

    // Cancel all requests that have already been sent, these were tracked in the cancels object.
    cancelRequests(message) {
      for (let index = 0; index < this.cancels.length; index++) {
        this.cancels[index].cancel(message);
      }
      this.cancels = [];
    },

    // Callback for setTimeout() to delay the re-enabling of refresh after move is submitted.
    enableRefresh() {
      this.refreshDisabled = false;
    }

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

  .clear {
    float: none;
    clear: both;
    display: block;
  }

  .messages {
    display: none;
  }
</style>
