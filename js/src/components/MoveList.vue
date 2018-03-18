<template>
    <div id="vchess-moves-list" class="vchess-moves-list">
      <table class="moves-list responsive-enabled table table-striped" data-striping="1">
        <thead>
          <tr>
            <th>Move #</th>
            <th>White</th>
            <th>Black</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(move, index) in moves">
            <td><b>{{ index }}</b></td>
            <td>{{ move['w'] }}</td>
            <td>{{ move['b'] }}</td>
          </tr>
        </tbody>
      </table>
    </div>
</template>

<script>
  export default {
    props: {
      moves: Object,
    }
  }
</script>
