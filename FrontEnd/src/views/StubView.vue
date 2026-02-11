<template>
  <div class="container-fluid" id="stub">
    <div class="text-center title-block">
      <h1>Дорогие друзья!</h1>
    </div>
    <div class="col-md-10 mx-auto">
      <div class="card mt-2 mx-auto">
        <div class="card-body">
          <h2> В 2026 году внести оргвзнос на Solar Systo Togathering могут только члены нашего приватного лесного клуба.</h2>
          <p>Они могут внести оргвзнос за Вас или отправить Вам приглашение для регистрации в клубе.</p>

          <p>Если у Вас уже есть аккаунт с прошлых годов на нашем сайте, то Вы автоматически стали членом клуба, тогда
            просто нажмите на кнопку.</p>

          <a href="/login" class="blue-btn">Авторизоваться</a>

          <p>Если Вы у нас впервые и у Вас нет близких друзей в клубе или Вам не хочется к ним обращаться - подайте заявку на вступление в клуб. Для этого заполните и отправьте анкету. Мы рассмотрим Вашу заявку и свяжемся с Вами.</p>

          <a href="/questionnaire/newUser" class="blue-btn">Отправить анкету</a>


          <p>Для получения более подробной информации напишите нам в телеграм:</p>
          <a href="tg://resolve?domain=systo_club" id="tg-intro-btn">@systo_club</a>
        </div>
      </div>
    </div>
  </div>
</template>
<script>

export default {
  name: 'stubView',
  created() {
    document.title = "Система регистрации оргвзносов на систо"
  },
}
</script>
