<template>
  <div id="left-logo">
    <a :href="getLinkHome">
    <img src="/assets/img/logo-main.jpg" alt="main logo" class="left-logo">
    </a>
  </div>
  <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item" v-if="!isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'QuestionnaireNewUser' }">Заявка на вступление в клуб
      </router-link>
    </li>
    <li class="nav-item" v-if="isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'Orgvznos' }">Типы Оргвзносов
      </router-link>
    </li>
    <li class="nav-item">
      <router-link
          class="nav-link" v-if="isAuth"
          active-class="active"
          :to="getLinkHome">Регистрация оргвзноса
      </router-link>
    </li>
    <li class="nav-item" v-if="isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'InviteLink' }">Ссылка-Приглашение
      </router-link>
    </li>
    <li class="nav-item" v-if="isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{ name: 'Conditions' }">Правила участия
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
          :to="{name: 'AllOrders'}">Все оргвзносы
      </router-link>
    </li>
    <li class="nav-item" v-if="isAdmin && isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{name: 'QuestionnaireList'}">Все анкеты
      </router-link>
    </li>
    <li class="nav-item" v-if="isAdmin && isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{name: 'TicketTypeListView'}">Все Типы билетов
      </router-link>
    </li>
    <li class="nav-item" v-if="isAdmin && isAuth">
      <router-link
          class="nav-link"
          active-class="active"
          :to="{name: 'TypesOfPaymentListView'}">Все Типы оплат
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
      <li v-if="isAuth"><a href="/faq" class="mailer" target="_blank" >FAQ (Вопрос-Ответ)</a></li>
      <li><a href="tg://resolve?domain=systo_club" class="telegram" target="_blank">@systo_club</a></li>
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
    getLinkHome: function () {
      return this.isAuth ? '/hfjlsd65t4732' : '/';
    }
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
