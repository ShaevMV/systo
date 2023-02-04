<template>
  <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
          <div class="d-flex justify-content-center py-4">
            <a href="index.html" class="logo d-flex align-items-center w-auto">
              <img src="assets/img/logo.png" alt="">
              <span class="d-none d-lg-block">NiceAdmin</span>
            </a>
          </div><!-- End Logo -->

          <div class="card mb-3">

            <div class="card-body">

              <div class="pt-4 pb-2">
                <h5 class="card-title text-center pb-0 fs-4">Create an Account</h5>
                <p class="text-center small">Enter your personal details to create account</p>
              </div>

              <div>
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

  </section>

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
