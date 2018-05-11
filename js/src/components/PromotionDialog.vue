<template>
    <div class="promotion-modal">
        <div class="modal-mask">
            <div class="modal-wrapper">
                <div class="modal-container">
                    <div class="modal-header">
                        <h5 class="modal-title">Promote pawn</h5>
                        <button class="modal-close" @click="promotionRejected" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div v-for="(piece, key) in pieces">
                        <input type="radio" name="promotion_piece" :value="key" @click="selected = key" :checked="key === selected"/><label>{{ 'Promote to ' + piece }}</label>
                    </div>
                    <div class="button-list">
                        <button @click="promotionRejected" aria-label="Cancel">Cancel</button>
                        <button @click="promotionSelected" aria-label="Promote">Promote</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
      props: {
        default: String,
      },

      data() {
        return {
          pieces: { Q: 'Queen', R: 'Rook', B: 'Bishop', N: 'Knight' },
          selected: this.default,
        }
      },

      methods: {
        promotionSelected() {
          this.$emit('dialog-closed', this.selected);
        },
        promotionRejected() {
          this.$emit('dialog-closed', 'cancel');
        }
      }
    }
</script>


<style>
    .promotion-modal {
        position: relative;
    }
    .promotion-modal .modal-close {
        margin-top: -28px;
        -webkit-appearance: none;
        padding: 0;
        cursor: pointer;
        background: transparent;
        border: 0;
        float: right;
        font-size: 21px;
        font-weight: bold;
        line-height: 1;
        color: #000;
        text-shadow: 0 1px 0 #fff;
        filter: alpha(opacity=20);
        opacity: .2;
    }
    .promotion-modal .modal-title {
        margin-bottom: 5px;
    }
    .promotion-modal .modal-header {
        min-height: 16px;
        padding: 3px;
        border-bottom: 1px solid #e5e5e5;
    }
    .promotion-modal .modal-wrapper {
        display: table-cell;
        vertical-align: middle;
    }
    .promotion-modal .modal-container {
        width: 30%;
        max-width: 320px;
        min-width: 220px;
        margin: 0 auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
        transition: all .3s ease;
        font-family: Helvetica, Arial, sans-serif;
    }
    .promotion-modal .modal-container label {
        margin: 5px 10px;
    }
    .promotion-modal .button-list {
        margin: 5px 0;
        padding: 10px 0;
        border-top: 1px solid #e5e5e5;
    }
    .promotion-modal .modal-mask {
        position: fixed;
        z-index: 9998;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, .5);
        display: table;
        transition: opacity .3s ease;
    }
</style>
