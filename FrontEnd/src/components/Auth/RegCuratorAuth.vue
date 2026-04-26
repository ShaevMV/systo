<template>
  <div class="container-fluid" id="reg-form">
    <div class="text-center title-block">
      <h1>Регистрация аккаунта куратора</h1>
    </div>

    <div class="row">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <p class="pp1 text-center">Чтобы зарегистрировать аккаунт куратора заполни все поля данной формы:</p>
          <div class="card-body">
            <div class="col-12 mb-3">
              <label for="curatorName" class="form-label hidder">ФИО</label>
              <input type="text" class="form-control" id="curatorName" required v-model="name" placeholder="ФИО:">
              <div class="invalid-feedback" style="display: block">{{ getError('name') }}</div>
            </div>

            <div class="col-12 mb-3">
              <label for="curatorEmail" class="form-label hidder">Email</label>
              <div class="input-group has-validation">
                <span class="input-group-text hidder" id="inputGroupPrepend">@</span>
                <input type="email" class="form-control" id="curatorEmail" required v-model="email" placeholder="Введи свой e-mail:">
                <div class="invalid-feedback" style="display: block">{{ getError('email') }}</div>
              </div>
            </div>

            <div class="col-12 mb-3">
              <label for="curatorPhone" class="form-label hidder">Телефон</label>
              <input type="text" class="form-control" id="curatorPhone" required v-model="phone" placeholder="Твой телефон:">
              <div class="invalid-feedback" style="display: block">{{ getError('phone') }}</div>
            </div>

            <div class="col-12 mb-3">
              <label for="curatorCity" class="form-label hidder">Город</label>
              <input type="text" class="form-control" id="curatorCity" required v-model="city" placeholder="Твой город:">
              <div class="invalid-feedback" style="display: block">{{ getError('city') }}</div>
            </div>

            <div class="col-12 mb-3">
              <label for="curatorPassword" class="form-label hidder">Пароль</label>
              <input type="password" class="form-control" id="curatorPassword" required
                     v-model="password" placeholder="Введи свой пароль:">
              <div class="invalid-feedback" style="display: block">{{ getError('password') }}</div>
            </div>

            <div class="col-12 mb-3">
              <label for="curatorPasswordConfirm" class="form-label hidder">Подтверждение пароля</label>
              <input type="password" class="form-control" id="curatorPasswordConfirm" required
                     v-model="password_confirmation" placeholder="Повтори пароль:">
            </div>

            <div class="col-12 mb-3">
              <button type="button"
                      @click="reg"
                      class="btn btn-lg btn-block btn-outline-primary">Зарегистрировать аккаунт куратора</button>
            </div>

            <div class="col-12">
              <p class="small text-center fw-bold">Уже есть аккаунт? <router-link to="/login">Авторизоваться</router-link></p>
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
  name: "RegCuratorAuth",
  data() {
    return {
      email: null,
      name: null,
      phone: null,
      city: null,
      password: null,
      password_confirmation: null,
    };
  },
  computed: {
    ...mapGetters('appUser', ['getError']),
  },
  methods: {
    ...mapActions('appUser', ['toRegistrationCurator', 'clearError']),
    reg() {
      this.toRegistrationCurator({
        email: this.email,
        name: this.name,
        phone: this.phone,
        city: this.city,
        password: this.password,
        password_confirmation: this.password_confirmation,
        callback: () => {
          location.href = '/ordersCurator/create';
        },
      });
    },
  },
  async created() {
    await this.clearError();
  },
};
</script>
