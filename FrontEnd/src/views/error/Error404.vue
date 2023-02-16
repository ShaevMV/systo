<template>
  <div class="container">

    <section class="section error-404 min-vh-100 d-flex flex-column align-items-center justify-content-center">
      <h1>404</h1>
      <h2>Вы потерялись в пространстве!</h2>
      <img src="assets/img/medved.png" alt="Page Not Found">
    </section>

  </div>
</template>

<script>
export default {
  name: "Error_404",
  created() {
    document.title = "Потеря потерь!!!!"
  },
}
</script>

<style scoped>

</style>
