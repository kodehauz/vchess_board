<template>
  <div id="vchess-captured-pieces" class="vchess-captured-pieces">
    <div class="white board-captured">
      <div v-for="piece in game.captured" v-if="piece.color === 'w'" v-bind:class="getPieceClass(piece)"></div>
    </div>
    <div class="black board-captured">
      <div v-for="piece in game.captured" v-if="piece.color === 'b'" v-bind:class="getPieceClass(piece)"></div>
    </div>
  </div>
</template>

<script>
  export default {

    props: {
      game: Object
    },

    methods: {
      getPieceClass(piece) {
        return [piece.color + '-' + piece.name.toLowerCase(), 'default', 'board-piece'];
      }
    }

  }
</script>
