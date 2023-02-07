<template>
  <div class="container">
    <div class="text-center title-block">
      <h1>Забыли пароль?</h1>
    </div>
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">

            <div class="messager" v-show="getError('email')">{{ getError('email') }}</div>
            <div class="messager" v-show="message">{{ message }}</div>

            <p class="pp1 text-center">Забыл пароль? Бывает... Введите ваш e-mail и система напомните его Вам.</p>

            <div class="container">
              <div id="contact-form" role="form">
                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label for="form_email" class="required hidder">Ваш email</label>
                      <input id="form_email"
                             type="email"
                             name="email"
                             class="form-control"
                             placeholder="Введи свой e-mail: *"
                             required="required"
                             v-model="email"
                             data-error="Введи свою почту!">
                    </div>
                  </div>
                  <div class="col-md-12">
                    <button type="button"
                            @click="sendForgotPassword"
                            class="btn btn-lg btn-block btn-outline-primary ">Напомнить пароль</button>
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
import {mapActions, mapGetters} from "vuex";

export default {
  name: "ForgotPasswordAuth",
  data() {
    return {
      email: null,
      message: null,
    }
  },
  computed: {
    ...mapGetters('appUser', [
      'getError'
    ])
  },
  methods: {
    ...mapActions('appUser', [
      'toForgotPassword'
    ]),

    sendForgotPassword: function () {
      let self = this;
      self.message = null;
      this.toForgotPassword({
        'email': this.email,
        'callback': function (message) {
          self.message = message;
        }
      })
    }
  }
}
</script>

<style scoped>

</style>
