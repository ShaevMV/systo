<template>
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Отчёт о билетах</h5>
          <p>
            <b>Продано:</b><span> {{ getTotalCountToPaid }} </span>
          </p>
          <p>
            <b>На сумму:</b><span> {{ getTotalAmount }} </span>
          </p>
          <p>
            <b>Всего заказов:</b><span> {{ getTotalCount }} </span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "totalNumber",
  computed: {
    ...mapGetters('appOrder', [
      'getTotalNumber'
    ]),
    getTotalCountToPaid: function () {
      return this.getTotalNumber.totalCountToPaid;
    },
    getTotalAmount: function () {
      return this.getTotalNumber.totalAmount;
    },
    getTotalCount: function () {
      return this.getTotalNumber.totalCount;
    }
  },
}
</script>

<style scoped>

</style>
