<template>
  <header id="header" class="header fixed-top d-flex align-items-center">
    <a href="/" class="logo d-flex"><span>Система регистрации оргвзносов на систо</span><img
        src="/assets/img/systo-nota.png" alt="systo-nota"></a>

    <button type="button" aria-label="Меню" id="menu-btn" @click="chanceOpen"></button>
  </header>

  <aside v-bind:class="classObject">
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
      isOpen: false
    }
  },
  computed: {
    classObject: function () {
      return {
        'active': this.isOpen,
        'sidebar': true
      }
    }
  },
  methods: {
    chanceOpen: function () {
      this.isOpen = !this.isOpen
      console.log(this.isOpen);
    }
  },
}

</script>

