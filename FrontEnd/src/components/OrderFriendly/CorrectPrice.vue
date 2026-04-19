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
  </div>
</template>

<script>
import {mapActions} from "vuex";

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