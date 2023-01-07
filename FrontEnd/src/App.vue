<template>
  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="/" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">NiceAdmin</span>
      </a>
    </div><!-- End Logo -->

  </header><!-- End Header -->

  <!-- ======= Sidebar ======= -->
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
