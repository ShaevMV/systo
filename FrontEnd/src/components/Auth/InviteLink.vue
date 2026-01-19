<template>
  <div class="container-fluid">
    <div class="text-center title-block">
      <h1>Страница получения персональной ссылки-приглашения</h1>
      <small class="form-text text-muted">для участия ваших друзей в туристическом слёте Solar Systo Togathering
        2026</small>
    </div>
    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body" id="invite-content">
            <p>Дорогой друг, тебе доступна ссылка для приглашение своих друзей.</p>
            <p>Просим тебя быть предельно бдительным и внимательным в том, кому ты будешь отправлять эту ссылку.</p>
            <p>Нажми на кнопку, чтобы перейти в браузер</p>
<!--            <div v-html="message"></div>-->
            <div v-if="link !== null">
              <a :href="link" target="_blank" class="blue-btn">ССЫЛКА-ПРИГЛАШЕНИЕ ДЛЯ РЕГИСТРАЦИИ ОРГВЗНОСА</a>
              <div id="invite-link">
                <p>или скопируй вручную и отправь своему другу любым удобным тебе способом!</p>
              <span>
                                {{ link }}</span>
                                <i
                                    class="copy-payment"
                                    title="Нажми, чтобы скопировать"
                                    @click="CopyTypesOfPayment(link)"
                                ></i>
              </div>
              <p>Если у Вас остались вопросы, прочитайте раздел <a href="/faq" target="_blank" style="font-weight: bold">FAQ (Ответы на часто задаваемые вопросы)</a> или напишите в поддержку <a href="tg://resolve?domain=systo_club" target="_blank" style="font-weight: bold">@systo_club</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>

import {mapGetters, mapActions} from 'vuex';


export default {
  name: "InviteLink",
  data() {
    return {
      message: null,
      link: null,
    }
  },
  computed: {
    ...mapGetters('appOrder', [
      'getInviteLink',
    ]),
  },
  methods: {
    ...mapActions('appOrder', [
      'pullInviteLink',
    ]),
    CopyTypesOfPayment: function (name) {
      let area = document.createElement('textarea');
      document.body.appendChild(area);
      area.value = name;
      area.select();
      document.execCommand('copy');
      document.body.removeChild(area);
    },
  },
  async created() {
    let self = this;
    await this.pullInviteLink({
      callback: function (response) {
        self.message = response.message;
        self.link = response.link;
      }
    });
  }
}
</script>

<style scoped>

</style>