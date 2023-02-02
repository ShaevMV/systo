<template>
  <div class="container">
    <div class="row ">
      <div class="col-lg-10 mx-auto">
        <div class="card mt-2 mx-auto p-4">
          <div class="card-body">

            <div class="container">
              <div id="contact-form" role="form">
                <div class="controls">

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="form_email" class="required">Ваш логин</label>
                        <input id="form_email"
                               type="email"
                               name="email"
                               class="form-control"
                               placeholder="Please enter your login *"
                               required="required"
                               v-model="email"
                               data-error="Valid email is required.">
                        <small class="form-text text-muted"> {{ getError('email') }}</small>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="form_password" class="required">Ваш пароль</label>
                        <input id="form_password"
                               type="password"
                               name="email"
                               class="form-control"
                               placeholder="Please enter your password *"
                               required="required"
                               v-model="password"
                               data-error="Valid email is required.">

                      </div>
                    </div>
                  </div>
                  <!--                  Подтвердить внесение-->
                  <div class="row">
                    <div class="col-md-12">
                      <button type="button"
                              @click="auth"
                              class="btn btn-lg btn-block btn-outline-primary "> Авторизоваться
                      </button>
                      <small class="form-text text-muted"> {{ getError('main') }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /.8 -->

      </div>
      <!-- /.row-->

    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
  name: "LoginAuth",
  data() {
    return {
      email: null,
      password: null,
    }
  },
  computed: {
    ...mapGetters('appUser', [
      'getError'
    ])
  },
  methods: {
    ...mapActions('appUser', [
      'toLogin'
    ]),

    auth: function () {
      let self = this;

      this.toLogin({
        'email': this.email,
        'password': this.password,
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
