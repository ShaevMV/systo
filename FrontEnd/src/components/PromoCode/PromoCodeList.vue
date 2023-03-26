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
              <th scope="col">Кол-во использованей Всего/Макс кол-во</th>
              <th scope="col">Активность</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(itemOrder,index) in getOrderList"
                v-bind:key="index"
                @click="goItemOrderForUser(itemOrder.id, itemOrder.status)">
              <th scope="row" class="mobile">
                {{ itemOrder.kilter }}
              </th>
              <td v-if="isAdmin" class="mobile">
                <div class="btn-group" v-show="isAdmin && Object.keys(itemOrder.listCorrectNextStatus).length > 0">
                  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown"
                          aria-haspopup="true"
                          :style="{'background-color': activeColor(itemOrder.status)}"
                          aria-expanded="false">
                    ...
                  </button>
                  <div class="dropdown-menu">
                <span class="dropdown-item btn-link"
                      role="button"
                      v-for="(statusItem, key) in itemOrder.listCorrectNextStatus" v-bind:key="key"
                      @click="chanceStatus(key,itemOrder.id)">{{ statusItem }}</span>
                  </div>
                </div>
              </td>
              <td v-if="isAdmin">{{ itemOrder.email }}</td>
              <td v-if="isAdmin" :title="getListQuests(itemOrder.guests, true) ">
                {{ getListQuests(itemOrder.guests, false) }}
              </td>
              <td>{{ itemOrder.name }}</td>
              <td>{{ itemOrder.price }} рублей</td>
              <td>{{ itemOrder.count }}</td>
              <td>{{ itemOrder.dateBuy }}</td>
              <td v-if="isAdmin">{{ itemOrder.promoCode }}</td>

              <td>{{ itemOrder.typeOfPaymentName }}</td>
              <td v-if="isAdmin">{{ itemOrder.idBuy }}</td>
              <td :style="styleObject(itemOrder.status)" class="mobile" style="text-align: left;">
                {{ itemOrder.humanStatus }}
              </td>
              <td v-if="isAdmin" :title="itemOrder.lastComment">

                {{ cuttedText(itemOrder.lastComment) }}
              </td>
              <td v-if="isAdmin" class="mobile" style="text-align: left;">
                <router-link
                    :to="{
                    name: 'orderItems',
                    params: {id: itemOrder.id}
                }">
                  Перейти к билету
                </router-link>
              </td>
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
  },
  async created() {
    await this.loadListPromoCode();
  },
}
</script>

<style scoped>

</style>