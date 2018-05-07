<template>
    <div>
        <div v-show="visible">
            <input type="checkbox" v-for="piece in pieces" :value="piece" @click="selected = piece"/>{{ 'Promote to ' + pieces[piece] }}
            if (confirm('Promote to Queen? (Press Cancel for other options)')) {
            move = move + '=Q';
            }
            else if (confirm('Promote to Rook? (Press Cancel for other options)')) {
            move = move + '=R';
            }
            else if (confirm('Promote to Bishop? (Press Cancel for other options)')) {
            move = move + '=B';
            }
            else if (confirm('Promote to Knight? (Press Cancel to abort move)')) {
            move = move + '=N';
            }
            else {
            return false;
            }
            <button @click="promotionRejected">Cancel</button>
            <button @click="promotionSelected">Promote</button>
        </div>
    </div>
</template>

<script>
    export default {
      props: {
        default: String,
        visible: boolean,
      },

      data() {
        return {
          pieces: { Q: 'Queen', R: 'Rook', B: 'Bishop', N: 'Knight' },
          selected: this.default,
        }
      },

      methods: {
        promotionSelected() {
          this.$emit('promotion-selected', this.selected);
        },
        promotionRejected() {
          this.selected = this.default;
          this.$emit('promotion-selected', this.selected);
        }
      }
    }
</script>
