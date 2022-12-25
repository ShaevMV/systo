<template>
  <ul class="sidebar-nav" id="sidebar-nav">

    <li class="nav-item">
      <router-link
          class="nav-link"
          active-class="active"
          to="/">Регистрация оргвзноса
      </router-link>
    </li>
    <li class="nav-item" v-if="isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'Orders' }">Мои заказы
      </router-link>
    </li>
    <li class="nav-item" v-if="isAdmin">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'AllOrders' }">Все заказы
      </router-link>
    </li>
    <li class="nav-item" v-if="!isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          to="/login">Авторизоваться
      </router-link>
    </li>
    <li class="nav-item" v-if="isAuth">
      <a class="nav-link" @click="logOut" href="javascript:void(0);">Выйти</a>
    </li>
  </ul>

</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
  name: "MenuView",
  computed: {
    ...mapGetters('appUser', [
      'isAuth',
      'isAdmin'
    ]),
  },
  methods: {
    ...mapActions('appUser', [
      'logOut'
    ]),
  },
}
</script>

<style scoped>

</style>
