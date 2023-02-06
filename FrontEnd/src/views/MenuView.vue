<template>
  <div id="left-logo">
    <img src="/assets/img/logo.png" alt="main logo" class="left-logo">
    <img src="/assets/img/systo-title-red.png" alt="main title" class="sub-logo">
    <img src="/assets/img/dataz.png" alt="main title" class="sub-logo dataz">
  </div>
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
          :to="{ name: 'Orders' }">Мои оргвзносы
      </router-link>
    </li>
    <li class="nav-item" v-if="isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'Profile' }">Личный кабинет
      </router-link>
    </li>
    <li class="nav-item" v-if="isAdmin && isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'AllOrders' }">Все оргвзносы
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

  <div id="left-sub">
    <p>Если у вас возникли трудности <br>с внесением оргвзноса напишите нам:</p>
    <ul>
      <li><a href="mailto:tickets@spaceofjoy.ru" class="mailer" target="_blank">tickets@spaceofjoy.ru</a></li>
      <li><a href="tg://resolve?domain=systo_vznos" class="telegram" target="_blank">@systo_vznos</a></li>
    </ul>

    <div id="prana">Система разработана веб-студией <a href="https://pranaweb.ru" target="_blank">PRANA</a></div>
  </div>

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
  }
  ,
  methods: {
    ...
        mapActions('appUser', [
          'logOut'
        ]),
  }
  ,
}
</script>

<style scoped>

</style>
