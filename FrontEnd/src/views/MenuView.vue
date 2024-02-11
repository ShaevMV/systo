<template>
  <div id="left-logo">
    <a href="/">
    <img src="/assets/img/logo-main.png" alt="main logo" class="left-logo">
    </a>
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
          :to="{ name: 'Profile' }">Мой аккаунт
      </router-link>
    </li>
    <li class="nav-item" v-if="isAdmin && isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{
                  name: 'AllOrders',
                  params: {id: '9d679bcf-b438-4ddb-ac04-023fa9bff4b4'}
          }">Все оргвзносы (Весна)
      </router-link>
    </li>
    <li class="nav-item" v-if="isAdmin && isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{
                  name: 'AllOrders',
                  params: {id: '9d679bcf-b438-4ddb-ac04-023fa9bff4b5'}
          }">Все оргвзносы (Осень)
      </router-link>
    </li>
    <li class="nav-item" v-if="(isAdmin && isAuth) && (false === isManager)">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'PromoCodes' }">Промокоды
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
      <a class="exit-link" @click="logOut" href="javascript:void(0);">Выйти из аккаунта</a>
    </li>
  </ul>

  <div id="left-sub">
    <p>Если у вас возникли трудности <br>с внесением оргвзноса напишите нам:</p>
    <ul>
      <li><a href="mailto:tickets@spaceofjoy.ru" class="mailer" target="_blank">tickets@spaceofjoy.ru</a></li>
      <li><a href="tg://resolve?domain=systo_vznos" class="telegram" target="_blank">@systo_vznos</a></li>
    </ul>

  </div>

  <div id="prana"><span>Система разработана веб-студией</span> <a href="https://pranaweb.ru" target="_blank">PRANA</a></div>

</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
  name: "MenuView",
  props: {
    active: {
      type: Boolean,
      default: true,
    },
  },
  computed: {
    ...mapGetters('appUser', [
      'isAuth',
      'isAdmin',
      'isManager'
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
