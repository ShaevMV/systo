<template>
  <div
      class="form_overlay uk-padding uk-padding-remove-horizontal uk-padding-remove-bottom uk-width-1-1 uk-flex uk-flex-column uk-flex-middle">
    <h2 class="text-500 uk-text-uppercase h2-header uk-align-center font-26">Восстановить пароль</h2>
    <div class="form_overlay_block uk-margin-medium-bottom uk-padding-remove width_600">
      <div class="as_user padding-form">
        <div class="input_wrapper uk-flex uk-flex-column uk-flex-center">
          <!-- У input_wrapper есть классы success и failed-->
          <input placeholder="Пароль"
                 v-model="password"
                 type="password">
        </div>
        <div class="input_wrapper uk-flex uk-flex-column uk-flex-center">
          <input placeholder="Повторите пароль"
                 v-model="password_confirmation"
                 type="password">
        </div>
        <div class="mistake_alert hidden"
             v-html="getError('password')"
             v-bind:class="{'visible':getError('password')}">
        </div>
        <div class="mistake_alert hidden"
             v-html="getError('password_confirmation')"
             v-bind:class="{'visible':getError('password_confirmation')}">
        </div>
        <div class="button_continue uk-text-uppercase button_conf_reg uk-align-center uk-margin-remove-bottom"
             @click="send()">Сменить пароль/войти в систему
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapActions} from 'vuex';
export default {
  name: "ResetPassword",
  data() {
    return {
      password: '',
      password_confirmation: '',
    }
  },
  computed: {
    ...mapGetters('appUser', [
      'getError'
    ]),
  },
  methods: {
    ...mapActions('appUser', [
      'changePassword'
    ]),
    send() {
      this.changePassword({
        password: this.password,
        password_confirmation: this.password_confirmation,
        token: this.$route.params.token,
        callback: function () {
          document.location.href = "/";
        }
      })
    }

  }
}
</script>

<style scoped>

</style>
