<template>
  <div class="title-block text-center">
    <h1 class="card-title">Промо коды</h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto">
      <div class="card">
        <div class="card-body">
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col">Название</th>
              <th scope="col">Тип скидки</th>
              <th scope="col">Скидка</th>
              <th scope="col">Кол-во использованей (Всего/Макс кол-во)</th>
              <th scope="col">Активность</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item,index) in getPromoCode"
                v-bind:key="index"
                @click="goItem(item.id)"
                style="cursor: pointer"
            >
              <td>{{ item.name }}</td>
              <td>{{ getTypeDiscount(item.is_percent) }}</td>
              <td>{{ item.discount }}</td>
              <td>{{ item.limit.count }} / {{ getLimit(item.limit.limit) }}</td>
              <td>{{ getActive(item.isSuccess) }}</td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script>

import {mapGetters, mapActions} from 'vuex';

export default {
  name: "PromoCodeList",
  computed: {
    ...mapGetters('appPromoCodeModule', [
      'getError',
      'getPromoCode',
    ]),
  },
  methods: {
    ...mapActions('appPromoCodeModule', [
      'loadListPromoCode',
    ]),
    goItem(idItem) {
      this.$router.push({name: 'promoCodeItem', params: {id: idItem}});
    },
    getTypeDiscount: function (isPercent) {
      if (isPercent) {
        return 'Процент';
      }

      return 'Фиксированная';
    },

    getActive: function (isActive) {
      if (isActive) {
        return 'Активный';
      }

      return 'Не активный';
    },
    getLimit: function (limit) {
      if (limit === null) {
        return '∞'
      }
      return limit
    },
  },
  async created() {
    await this.loadListPromoCode();
  },
}
</script>

<style scoped>

</style>