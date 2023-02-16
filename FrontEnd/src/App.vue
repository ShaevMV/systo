<template>
  <header id="header" class="header fixed-top d-flex align-items-center">
    <a href="/" class="logo d-flex"><span>Система регистрации оргвзносов на систо</span><img src="/assets/img/systo-nota.png" alt="systo-nota"></a>

    <button type="button" aria-label="Меню" id="menu-btn"  @click="chanceOpen"></button>
  </header>

  <aside id="sidebar" class="sidebar">
    <MenuView/>
  </aside>
  <main id="main" class="main">
    <router-view/>
  </main>
</template>

<script>
import MenuView from "@/views/MenuView";

export default {
  components: {MenuView}
}

</script>

