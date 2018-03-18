// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import VueWorker from 'vue-worker'
import Game from './Game.vue'

Vue.config.productionTip = false;
Vue.use(VueWorker);

/* eslint-disable no-new */
new Vue({
  el: '#vchess-container',
  components: { Game },
  template: '<Game :initialGame="game" :user="currentUser" :apiUrl="apiUrl" :interval="interval"/>',
  data: {
    game: drupalSettings.vchess.game,
    currentUser: parseInt(drupalSettings.user.uid),
    apiUrl: drupalSettings.path.baseUrl + drupalSettings.path.currentPath,
    interval: Math.max(drupalSettings.vchess.refresh_interval, 10) * 1000,
  },
});
