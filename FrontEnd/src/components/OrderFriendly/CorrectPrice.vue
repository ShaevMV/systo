<template>
  <div>
    <input type="text"
           v-model="price"
           class="form-control"
           placeholder="Введите номер билета">
    <small
        @click="correctPrice"
        style="color: #0f7afc;
                      text-decoration: underline;
                      cursor: pointer;
                    "> сменить цену </small>
    <small class="form-text text-muted"> {{ getError('price') }}</small>
  </div>
</template>

<script>
import {mapActions, mapGetters} from "vuex";

export default {
  name: "CorrectPrice",
  props: [
    'oldPrice',
    'id'
  ],
  data() {
    return {
      newPrice: null,
    };
  },
  computed: {
    ...mapGetters('appOrder', [
      'getError'
    ]),
    price: {
      get: function () {
        if (this.newPrice === null) {
          return this.oldPrice;
        }
        return this.newPrice;
      },
      set: function (newValue) {
        this.newPrice = newValue;
      },
    },
  },
  methods: {
    ...mapActions('appOrder', [
      'sendChangePrice'
    ]),
    correctPrice() {
      this.sendChangePrice({
        'id': this.id,
        'price': this.price,
        'callback': function (message) {
          self.message = message;
        }
      });
    },
  }

}
</script>

<style scoped>

</style>