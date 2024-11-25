<template>
  <header id="header" class="header fixed-top d-flex align-items-center">
    <a href="/" class="logo d-flex"><span>Система регистрации оргвзносов на систо</span><img
        src="/assets/img/systo-nota.png" alt="systo-nota"></a>

    <button type="button" aria-label="Меню" id="menu-btn" @click.stop="toggleMenu"></button>
  </header>

  <aside v-bind:class="classObject" @click.stop>
    <MenuView/>
  </aside>
  <main id="main" class="main">
    <router-view/>
  </main>
</template>

<script>
import MenuView from "@/views/MenuView";

export default {
  name: "MainView",
  components: {MenuView},
  data() {
    return {
    }
  },
  computed: {
    classObject: function () {
      return {
        'active': this.$store.getters.isShowMenu,
        'sidebar': true
      }
    }
  },
  methods: {
    toggleMenu: function () {
        this.$store.commit('TOGGLE_MENU');
    }
    ,
    hideMenu: function () {
      this.$store.commit('HIDE_MENU');
    },
  },
  created() {
    document.addEventListener("click", this.hideMenu);
  }
}

</script>

