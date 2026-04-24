<template>
  <div id="left-logo">
    <a :href="getLinkHome">
      <img src="/assets/img/logo-main.jpg" alt="main logo" class="left-logo">
    </a>
  </div>

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Не авторизован -->
    <template v-if="!isAuth">
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'QuestionnaireNewUser' }">
          Заявка на вступление в клуб
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" to="/login">
          Авторизоваться
        </router-link>
      </li>
    </template>

    <!-- Продавец живых билетов (seller) -->
    <template v-if="isAuth && isSeller && !isAdmin">
      <li class="nav-heading">Работа</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'AllOrders' }">
          Все оргвзносы
        </router-link>
      </li>
      <li class="nav-heading">Аккаунт</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Profile' }">
          Мой аккаунт
        </router-link>
      </li>
    </template>

    <!-- Продавец дружеских билетов (pusher) -->
    <template v-if="isAuth && isPusher && !isAdmin">
      <li class="nav-heading">Работа</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'frendlyOrder' }">
          Регистрация дружеского оргвзноса
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'AllOrdersFriendly' }">
          Все дружеские оргвзносы
        </router-link>
      </li>
      <li class="nav-heading">Аккаунт</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Profile' }">
          Мой аккаунт
        </router-link>
      </li>
    </template>

    <!-- Менеджер анкет (manager) -->
    <template v-if="isAuth && isManager && !isAdmin">
      <li class="nav-heading">Работа</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'QuestionnaireList' }">
          Все анкеты
        </router-link>
      </li>
      <li class="nav-heading">Аккаунт</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Profile' }">
          Мой аккаунт
        </router-link>
      </li>
    </template>

    <!-- Куратор (curator/curator_pusher) -->
    <template v-if="isAuth && isCurator && !isAdmin">
      <li class="nav-heading">Работа</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'AllOrders' }">
          Мои заказы-списки
        </router-link>
      </li>
      <li class="nav-heading">Аккаунт</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Profile' }">
          Мой аккаунт
        </router-link>
      </li>
    </template>

    <!-- Обычный участник (guest авторизован) -->
    <template v-if="isAuth && !isAdmin && !isSeller && !isPusher && !isManager && !isCurator">
      <li class="nav-heading">Участие</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Orgvznos' }">
          Типы оргвзносов
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="getLinkHome">
          Регистрация оргвзноса
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'InviteLink' }">
          Ссылка-Приглашение
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Conditions' }">
          Правила участия
        </router-link>
      </li>
      <li class="nav-heading">Мои данные</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Orders' }">
          Мои оргвзносы
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Profile' }">
          Мой аккаунт
        </router-link>
      </li>
    </template>

    <!-- Администратор (полный доступ) -->
    <template v-if="isAuth && isAdmin">
      <li class="nav-heading">Участие</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Orgvznos' }">
          Типы оргвзносов
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="getLinkHome">
          Регистрация оргвзноса
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'InviteLink' }">
          Ссылка-Приглашение
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Conditions' }">
          Правила участия
        </router-link>
      </li>

      <li class="nav-heading">Мои данные</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Orders' }">
          Мои оргвзносы
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'Profile' }">
          Мой аккаунт
        </router-link>
      </li>

      <li class="nav-heading">Управление</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'AllOrders' }">
          Все оргвзносы
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'AllOrdersFriendly' }">
          Все дружеские оргвзносы
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'AllOrders' }">
          Все заказы кураторов
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'QuestionnaireList' }">
          Все анкеты
        </router-link>
      </li>

      <li class="nav-heading">Настройки</li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'TicketTypeListView' }">
          Типы билетов
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'QuestionnaireTypeListView' }">
          Типы анкет
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'LocationListView' }">
          Локации
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'TypesOfPaymentListView' }">
          Типы оплат
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'AccountListView' }">
          Все пользователи
        </router-link>
      </li>
      <li class="nav-item">
        <router-link class="nav-link" active-class="active" :to="{ name: 'PromoCodes' }">
          Промокоды
        </router-link>
      </li>
    </template>

    <!-- Выход (все авторизованные) -->
    <li class="nav-item nav-item--exit" v-if="isAuth">
      <a class="exit-link" @click="logOut" href="javascript:void(0);">Выйти из аккаунта</a>
    </li>

  </ul>

  <div id="left-sub">
    <p>Если у вас возникли трудности <br>с внесением оргвзноса напишите нам:</p>
    <ul>
      <li v-if="isAuth"><a href="/faq" class="mailer" target="_blank">FAQ (Вопрос-Ответ)</a></li>
      <li><a href="tg://resolve?domain=systo_club" class="telegram" target="_blank">@systo_club</a></li>
    </ul>
  </div>

  <div id="prana">
    <span>Система разработана веб-студией</span>
    <a href="https://pranaweb.ru" target="_blank">PRANA</a>
  </div>
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
      'isSeller',
      'isManager',
      'isPusher',
      'isCurator',
    ]),
    getLinkHome() {
      return this.isAuth ? '/hfjlsd65t4732' : '/';
    },
  },
  methods: {
    ...mapActions('appUser', ['logOut']),
  },
}
</script>

<style scoped>
.nav-item--exit {
  margin-top: 10px;
}
</style>
