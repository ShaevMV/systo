<template>
  <div class="row">
    <div class="container">
      <div class="text-center title-block">
        <h1>Регистрация аккаунта</h1>
      </div>

      <div class="row">
        <div class="col-lg-10 mx-auto">
          <div class="card mt-2 mx-auto">
<p class="pp1 text-center">Чтобы зарегистрировать свой аккаунт в системе заполни все поля данной формы:</p>
            <div class="card-body">

                <div class="col-12">
                  <label for="yourName" class="form-label">Your Name</label>
                  <input type="text" name="name" class="form-control" id="yourName" required="" v-model="name">
                  <div class="invalid-feedback">Please, enter your name!</div>
                </div>

                <div class="col-12">
                  <label for="yourEmail" class="form-label">Phone</label>
                  <input type="text" name="email" class="form-control" id="yourPhone" required="" v-model="phone">
                  <div class="invalid-feedback">Please enter a valid Email adddress!</div>
                </div>
                <div class="col-12">
                  <label for="yourEmail" class="form-label">City</label>
                  <input type="text" name="email" class="form-control" id="yourCity" required="" v-model="city">
                  <div class="invalid-feedback">Please enter a valid Email adddress!</div>
                </div>
                <div class="col-12">
                  <label for="yourUsername" class="form-label">Your Email</label>
                  <div class="input-group has-validation">
                    <span class="input-group-text" id="inputGroupPrepend">@</span>
                    <input type="email" name="username" class="form-control" id="yourEmail" required="" v-model="email">
                    <div class="invalid-feedback">Please choose a username.</div>
                  </div>
                </div>

                <div class="col-12">
                  <label for="yourPassword" class="form-label">Password</label>
                  <input type="password" name="password" class="form-control" id="yourPassword" required=""  v-model="password">
                  <div class="invalid-feedback">Please enter your password!</div>
                </div>
                <div class="col-12">
                  <label for="yourPassword" class="form-label">Password confirmation</label>
                  <input type="password" name="password" class="form-control" id="yourPassword" required="" v-model="password_confirmation">
                  <div class="invalid-feedback">Please enter your password!</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" name="terms" type="checkbox" value="" id="acceptTerms" required="">
                    <label class="form-check-label" for="acceptTerms">I agree and accept the <a href="#">terms and conditions</a></label>
                    <div class="invalid-feedback">You must agree before submitting.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="button"
                          @click="reg"
                          class="btn btn-lg btn-block btn-outline-primary "> Create Account
                  </button>
                </div>
                <div class="col-12">
                  <p class="small mb-0">Already have an account?
                    <router-link
                      to="/login">Авторизоваться
                  </router-link>
                  </p>
                </div>
            </div>
          </div>
        </div>
    </div>
      </div>
  </div>

</template>

<script>
import {mapActions, mapGetters} from 'vuex';
export default {
  name: "RegAuth",
  data() {
    return {
      email: null,
      name: null,
      phone: null,
      city: null,
      password: null,
      password_confirmation: null
    }
  },
  computed: {
    ...mapGetters('appUser', [
      'getError'
    ])
  },
  methods: {
    ...mapActions('appUser', [
      'toRegistration'
    ]),

    reg: function () {
      let self = this;

      this.toRegistration({
        'email': this.email,
        'name': this.name,
        'phone': this.phone,
        'city': this.city,
        'password': this.password,
        'password_confirmation': this.password_confirmation,
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
