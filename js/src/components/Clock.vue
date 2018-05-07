<template>
  <div class="game-timer">
    <div class="visually-hidden" id="controls">
      <h1>Game Timer</h1>
    </div>

    <div class="time-container left">
      <div>
        {{ game.usernames.white }}
      </div>
      <div class="time-display" :class="getActiveClass('w')">
        <span>{{ whiteHms.hrs }}:{{ whiteHms.min }}:{{ whiteHms.sec }}</span>
      </div>
    </div>

    <div class="time-container right">
      <div>
        {{ game.usernames.black }}
      </div>
      <div class="time-display" :class="getActiveClass('b')">
        <span>{{ blackHms.hrs }}:{{ blackHms.min }}:{{ blackHms.sec }}</span>
      </div>
    </div>
  </div>
</template>

<script>
  export default {

    props: {
      game: Object,
      user: Number,
    },

    mounted() {
      // Initialize timer times and update the displays.
      this.whiteTimer.timeleft = this.game.white_time_left;
      this.blackTimer.timeleft = this.game.black_time_left;

      this.whiteHms = this.calculateHrsMinSec(this.whiteTimer.timeleft);
      this.blackHms = this.calculateHrsMinSec(this.blackTimer.timeleft);

      if (this.game.turn === 'w' && this.game.white_uid === this.user) {
        this.startTimer(this.whiteTimer);
      }
      if (this.game.turn === 'b' && this.game.black_uid === this.user) {
        this.startTimer(this.blackTimer);
      }
      window.setInterval(this.updateTimeLeft, 1000);
    },

    data() {
      return {
        whiteTimer: {outlook: 0, timeleft: 0, enabled: false},
        blackTimer: {outlook: 0, timeleft: 0, enabled: false},
        whiteHms: {hrs: '00', min: '00', sec: '00'},
        blackHms: {hrs: '00', min: '00', sec: '00'},
      }
    },

    watch: {
      game(game, oldgame) {
        // Update the time left for both timers.
        this.whiteTimer.timeleft = this.game.white_time_left;
        this.blackTimer.timeleft = this.game.black_time_left;

        // Start the timer that is currently active.
        if ((game.turn === 'w' && game.white_uid === this.user)) {
          this.startTimer(this.whiteTimer);
        }
        else {
          this.stopTimer(this.whiteTimer);
        }

        if ((game.turn === 'b' && game.black_uid === this.user)) {
          this.startTimer(this.blackTimer);
        }
        else {
          this.stopTimer(this.blackTimer);
        }

        // Update the rendered display.
        this.whiteHms = this.calculateHrsMinSec(this.whiteTimer.timeleft);
        this.blackHms = this.calculateHrsMinSec(this.blackTimer.timeleft);
      },
    },

    methods: {

      updateTimeLeft() {
        // Update the rendered display.
        this.whiteHms = this.updateTimer(this.whiteTimer);
        this.blackHms = this.updateTimer(this.blackTimer);

        // Update the game object.
        this.game.white_time_left = this.whiteTimer.timeleft;
        this.game.black_time_left = this.blackTimer.timeleft;
      },

      startTimer(timer) {
        timer.enabled = true;
        timer.outlook = timer.timeleft + new Date().getTime() / 1000;
      },

      updateTimer(timer) {
        // Only update if the clock is enabled.
        if (timer.enabled) {
          // Ensure negative number doesn't get assigned.
          timer.timeleft = Math.max(timer.outlook - new Date().getTime() / 1000, 0);
        }
        return this.calculateHrsMinSec(timer.timeleft);
      },

      stopTimer(timer) {
        timer.enabled = false;
        return this.calculateHrsMinSec(timer.timeleft);
      },

      calculateHrsMinSec(timeleft) {
        let hms = {};
        hms.hrs = Math.floor(timeleft / 3600).toString();
        hms.min = Math.floor((timeleft % 3600) / 60).toString();
        hms.sec = Math.floor(timeleft % 60).toString();

        if (hms.hrs < 10) hms.hrs = '0' + hms.hrs;
        if (hms.min < 10) hms.min = '0' + hms.min;
        if (hms.sec < 10) hms.sec = '0' + hms.sec;

        return hms;
      },

      getActiveClass(color) {
        if ((color === 'w' && this.game.white_uid === this.user)
            || (color === 'b' && this.game.black_uid === this.user)) {
          return 'active';
        }
        return '';
      }

    }

  }
</script>
