<template>
  <div id="intro">
    <h2><span>Дорогие друзья!</span>
    В 2026 году внести оргвзнос на Solar Systo Togathering возможно только по приглашению от ваших друзей.</h2>
    <h3>Если у Вас уже есть аккаунт, нажмите на кнопку <a href="/login">Авторизоваться</a><br><br>
      Для получения более подробной информации напишите нам в телеграм:</h3>
    <a href="tg://resolve?domain=systo_club" id="tg-intro-btn">@systo_club</a>
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
