<template>
  <div class="row" id="reg-form">
    <div class="container">
      <div class="text-center title-block">
        <h1>Регистрация аккаунта</h1>
      </div>

      <div class="row">
        <div class="col-lg-10 mx-auto">
          <div class="card mt-2 mx-auto">
            <p class="pp1 text-center">Чтобы зарегистрировать свой аккаунт в системе заполни все поля данной формы:</p>
            <div class="card-body">

              <div class="col-12 mb-3">
                <label for="yourName" class="form-label hidder">Your Name</label>
                <input type="text" name="name" class="form-control" id="yourName" required="" v-model="name" placeholder="Введи свое имя:">
                <div class="invalid-feedback" style="display: block">{{ getError('name') }}</div>
              </div>

              <div class="col-12 mb-3">
                <label for="yourUsername" class="form-label hidder">Your Email</label>
                <div class="input-group has-validation">
                  <span class="input-group-text hidder" id="inputGroupPrepend">@</span>
                  <input type="email" name="username" class="form-control" id="yourEmail" required="" v-model="email" placeholder="Введи свой e-mail:">
                  <div class="invalid-feedback" style="display: block">{{ getError('email') }}</div>
                </div>
              </div>

              <div class="col-12 mb-3">
                <label for="yourEmail" class="form-label hidder">Phone</label>
                <input type="text" name="email" class="form-control" id="yourPhone" required="" v-model="phone" placeholder="Твой телефон:">
                <div class="invalid-feedback" style="display: block">{{ getError('phone') }}</div>
              </div>
              <div class="col-12 mb-3">
                <label for="yourEmail" class="form-label hidder">City</label>
                <input type="text" name="email" class="form-control" id="yourCity" required="" v-model="city" placeholder="Твой город:">
                <div class="invalid-feedback" style="display: block">{{ getError('city') }}</div>
              </div>

              <div class="col-12 mb-3">
                <label for="yourPassword" class="form-label hidder">Password</label>
                <input type="password" name="password" class="form-control" id="yourPassword" required=""
                       v-model="password" placeholder="Введи свой пароль:">
                <div class="invalid-feedback" style="display: block">{{ getError('password') }}</div>
              </div>
              <div class="col-12 mb-3">
                <label for="yourPassword" class="form-label hidder">Password confirmation</label>
                <input type="password" name="password" class="form-control" id="yourPassword" required=""
                       v-model="password_confirmation" placeholder="Повтори пароль:">
                <div class="invalid-feedback">Please enter your password!</div>
              </div>
              <div class="col-12 mb-3">
                <button type="button"
                        @click="reg"
                        class="btn btn-lg btn-block btn-outline-primary ">Зарегистрировать аккаунт</button>
              </div>
              <div class="col-12">
                <p class="small text-center fw-bold">Уже есть аккаунт? <router-link to="/login">Авторизоваться </router-link>
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
    ]),
  },
  methods: {
    ...mapActions('appUser', [
      'toRegistration',
      'clearError'
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
  },
  async created() {
    await this.clearError();
  },
}
</script>

<style scoped>

</style>
