<template>
  <div class="container">
    <div class="text-center title-block">
      <h1>Восстановления пароля</h1>
    </div>
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">

            <div class="messager">{{ getError('email') }}</div>

            <div class="container">
              <div id="contact-form" role="form">
                <div class="row">
                  <div class="col-md-4">
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
                  <div class="col-md-4">
                    <button type="button"
                            @click="sendForgotPassword"
                            class="btn btn-lg btn-block btn-outline-primary "> Восстановить пароль
                    </button>
                    <small class="form-text text-muted"> {{ getError('main') }}</small>
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
import {toForgotPassword} from "@/store/modules/UserModule/actions";

export default {
  name: "ForgotPasswordAuth",
  data() {
    return {
      email: null,
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

      this.toForgotPassword({
        'email': this.email,
        'callback': function () {
          let url = self.$route.query.nextUrl || null;
          if (url !== null) {
            location.href = url;
          } else {
            location.reload();
          }
        }
      })
    }
  }
}
</script>

<style scoped>

</style>
