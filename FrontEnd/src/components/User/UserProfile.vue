<template>
  <div class="row" id="accounter">
    <div class="container">
      <div class="title-block text-center"><h1 class="card-title">Мой аккаунт</h1></div>
      <div class="row">
        <div class="col-lg-10 mx-auto">
          <div class="card">
            <div class="card-body pt-3">
              <div class="tab-pane fade profile-edit pt-3 active show" id="profile-edit" role="tabpanel">

                <!-- Profile Edit Form -->
                <div>
                  <div class="row mb-3">
                    <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Имя</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="fullName"
                             type="text"
                             v-model="name"
                             class="form-control"
                             id="fullName">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="company" class="col-md-4 col-lg-3 col-form-label">Город</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="company"
                             type="text"
                             class="form-control"
                             v-model="city"
                             id="company">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="Job" class="col-md-4 col-lg-3 col-form-label">Телефон</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="job"
                             type="text"
                             class="form-control"
                             v-model="phone"
                             id="Job">
                    </div>
                  </div>
                  <div class="messager" v-show="message">{{ message }}</div>
                  <div class="text-center">
                    <button type="submit"
                            @click="sendUserData"
                            class="btn btn-primary">Сохранить изменения
                    </button>
                  </div>
                </div><!-- End Profile Edit Form -->

              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
          <div class="col-lg-10 mx-auto">
    <div class="card">
      <div class="card-body">
        <div class="row mb-3">
          <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Сменить пароль</label>
          <div class="col-md-8 col-lg-9">
            <input name="fullName"
                   type="password"
                   v-model="password"
                   class="form-control"
                   id="fullName">
          </div>
        </div>
        <div class="row mb-3">
          <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Повторите пароль</label>
          <div class="col-md-8 col-lg-9">
            <input name="fullName"
                   type="password"
                   v-model="password_confirmation"
                   class="form-control"
                   id="fullName">
          </div>
        </div>
        <div class="text-center">
          <div class="messager" v-show="messagePassword">{{ messagePassword }}</div>
          <button type="submit"
                  @click="sendEditPassword"
                  class="btn btn-primary">Сменить пароль
          </button>
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
  name: "UserProfile",
  data() {
    return {
      newName: null,
      newCity: null,
      newPhone: null,
      password: null,
      password_confirmation: null,
      message: null,
      messagePassword: null,
    }
  },
  computed: {
    ...mapGetters('appUser', [
      'getError',
      'getUserData'
    ]),
    name: {
      get: function () {
        if(this.newName === null) {
          return this.getUserData('name');
        }
        return this.newName;
      },
      set: function (newValue) {
        this.newName = newValue;
      },
    },
    phone: {
      get: function () {
        if(this.newPhone === null) {
          return this.getUserData('phone');
        }
        return this.newPhone;
      },
      set: function (newValue) {
        this.newPhone = newValue;
      },
    },
    city: {
      get: function () {
        if(this.newCity === null) {
          return this.getUserData('city');
        }
        return this.newCity;
      },
      set: function (newValue) {
        this.newCity = newValue;
      },
    },
  },
  methods: {
    ...mapActions('appUser', [
      'editProfile',
      'loadUserData',
      'editPassword',
    ]),

    sendUserData: function () {
      let self = this;
      self.message = null;
      this.editProfile({
        'city': this.city,
        'phone': this.phone,
        'name': this.name,
        'callback': function (message) {
          self.message = message;
        }
      })
    },
    sendEditPassword: function () {
      let self = this;
      self.messagePassword = null;
      this.editPassword({
        'password': this.password,
        'password_confirmation': this.password_confirmation,
        'callback': function (message) {
          console.log(message);
          self.messagePassword = message;
        }
      })
    }
  },
  async created() {
    await this.loadUserData();
  },
}
</script>

<style scoped>

</style>
