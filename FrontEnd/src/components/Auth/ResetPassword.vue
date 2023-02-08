<template>
  <div class="container">
    <div class="text-center title-block">
      <h1>Сменить пароль</h1>
    </div>
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">
            <div class="container">
              <div id="contact-form" role="form">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="form_password" class="required hidder">Ваш пароль</label>
                      <input id="form_password"
                             type="password"
                             name="email"
                             class="form-control"
                             placeholder="Введи свой пароль: *"
                             required="required"
                             v-model="password"
                             data-error="Введи свой пароль!">

                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="form_password" class="required hidder">Повторите пароль, что бы его не забыть</label>
                      <input id="form_password"
                             type="password"
                             name="email"
                             class="form-control"
                             placeholder="Повторите пароль: *"
                             required="required"
                             v-model="password_confirmation"
                             data-error="Повторите свой пароль!">

                    </div>
                  </div>
                  <div class="col-md-4">
                    <button type="button"
                            @click="send()"
                            class="btn btn-lg btn-block btn-outline-primary "> Сменить пароль/войти в систему
                    </button>
                    <small class="form-text text-muted"> {{ getError('password') }}</small>
                  </div>
                </div>
              </div>
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
      'changePassword',
      'clearError'
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
  },
  async created() {
    await this.clearError();
  },
}
</script>

<style scoped>

</style>
