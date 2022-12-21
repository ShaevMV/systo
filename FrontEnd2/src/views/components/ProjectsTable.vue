<template>
  <div class="card mb-4">
    <div class="card-header pb-0">
      <h6>Мои заказы</h6>
    </div>
    <div class="card-body px-0 pt-0 pb-2">
      <div class="table-responsive p-0">
        <table class="table align-items-center justify-content-center mb-0">
          <thead>
            <tr>
              <th
                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
              >
                #
              </th>
              <th
                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >
                Название
              </th>
              <th
                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >
                Стоимость
              </th>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >
                Количество билетов в заказе
              </th>
              <th
                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >
                Дата покупики билета
              </th>
              <th
                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >
                Статус
              </th>
              <th
                class="text-uppercase text-secondary text-xxs font-weight-bolder text-center opacity-7 ps-2"
              >
                Комментарий
              </th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(itemOrder,index) in getOrderList" v-bind:key="index">
              <td>
                <div class="d-flex px-2">
                  <router-link
                      class="nav-link"
                      active-class="active"
                      :to="{ name: 'orderItems', params: { id: itemOrder.id }}">{{ index + 1 }}
                  </router-link>
                </div>
              </td>
              <td>
                <span class="text-xs font-weight-bold">{{ itemOrder.name }}</span>
              </td>
              <td>
                <p class="text-sm font-weight-bold mb-0">{{ itemOrder.price }}</p>
              </td>
              <td class="align-middle text-center">
                {{ itemOrder.count }}
              </td>
              <td class="align-middle">
                {{ itemOrder.dateCreate }}
              </td>
              <td class="align-middle">
                {{ itemOrder.humanStatus }}
              </td>
              <td class="align-middle">
                {{ itemOrder.lastComment }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
  name: "projects-table",
  computed: {
    ...mapGetters('appOrder', [
      'getOrderList'
    ]),
  },
};
</script>
