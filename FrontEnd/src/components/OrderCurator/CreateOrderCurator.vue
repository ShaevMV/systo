<template>
  <div class="container-fluid">
    <button
        type="button"
        class="btn btn-primary"
        v-show="false"
        data-toggle="modal"
        id="curatorModalOpenBtn"
        data-target="#curatorSuccessModal"
    >open
    </button>

    <div class="modal fade" id="curatorSuccessModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isSuccess ? 'Успех' : 'Ошибка' }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">х</span>
            </button>
          </div>
          <div class="modal-body" v-html="message"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center title-block">
      <h1>Форма регистрации участников от куратора</h1>
      <small class="form-text text-muted">Solar Systo Togathering 2026</small>
    </div>

    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">
            <div id="contact-form" role="form">
              <div class="controls">

                <div class="pp1 row">
                  <span>ШАГ 1.</span> Введи данные куратора и название проекта:
                </div>
                <div class="row y-row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="curator_email" class="hidder">Email основного гостя *</label>
                      <input id="curator_email"
                             type="email"
                             class="form-control"
                             placeholder="Email основного гостя: *"
                             required
                             v-model="email"/>
                      <small class="form-text text-muted">{{ getError('email') }}</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="curator_phone" class="hidder">Телефон основного гостя *</label>
                      <input id="curator_phone"
                             type="text"
                             class="form-control"
                             placeholder="Телефон основного гостя: *"
                             required
                             v-model="phone"/>
                      <small class="form-text text-muted">{{ getError('phone') }}</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="curator_project" class="hidder">Название проекта</label>
                      <input id="curator_project"
                             type="text"
                             class="form-control"
                             placeholder="Название проекта"
                             v-model="project"/>
                      <small class="form-text text-muted">{{ getError('project') }}</small>
                    </div>
                  </div>
                </div>

                <div class="pp1 row">
                  <span>ШАГ 2.</span> Выбери локацию:
                </div>

                <div class="mb-3" id="location-type">
                  <div class="mt-1 col-12">
                    <div class="in-choice">
                      <div v-if="locationList.length === 0" class="text-muted">
                        Загрузка локаций...
                      </div>
                      <div class="ticket-choice"
                           v-for="loc in locationList"
                           :key="loc.id">
                        <div class="form-check">
                          <label class="form-check-label" :for="'loc-' + loc.id">
                            <input type="radio"
                                   class="form-check-input"
                                   v-model="location_id"
                                   :value="loc.id"
                                   :id="'loc-' + loc.id"/>
                            <span class="intckt">
                              <p>{{ loc.name }}</p>
                              <small v-if="loc.description" class="text-muted">{{ loc.description }}</small>
                            </span>
                          </label>
                        </div>
                      </div>
                    </div>
                    <small class="form-text text-muted">{{ getError('location_id') }}</small>
                  </div>
                </div>

                <div class="row mt-3 mb-3" id="enter-guests">
                  <div class="pp2">Введи данные всех участников:</div>
                  <div class="not-first-guest input-group mb-3">
                    <input type="text"
                           id="newGuest"
                           class="form-control"
                           placeholder="Имя и фамилия участника"
                           v-model="newGuest"/>
                    <input type="email"
                           id="newGuestEmail"
                           class="form-control"
                           placeholder="Email участника (необязательно)"
                           v-model="newGuestEmail"/>
                    <div class="input-group-prepend">
                      <span class="input-group-text btn" @click="addGuest()" id="basic-addon1">
                        Добавить
                      </span>
                    </div>
                  </div>
                </div>

                <div class="row x-row" v-show="guests.length > 0" id="adding-guests">
                  <div class="col-12">
                    <div class="form-group">
                      <div class="input-group mb-3"
                           v-for="(itemGuest, index) in guests"
                           :key="index">
                        <input type="text"
                               class="form-control"
                               readonly
                               :value="itemGuest.value"/>
                        <input type="email"
                               class="form-control"
                               readonly
                               :value="itemGuest.email"/>
                        <div class="input-group-prepend">
                          <span class="input-group-text btn" @click="delGuest(index)">
                            <i class="fa fa-trash"></i>
                          </span>
                        </div>
                      </div>
                      <small class="form-text text-muted">{{ getError('guests') }}</small>
                    </div>
                  </div>
                </div>

                <div class="row col-12">
                  <h4 class="font-weight-normal" id="count-label">
                    Общее количество участников: <span>{{ guests.length }}</span>
                  </h4>
                </div>

                <div class="row mb-3">
                  <div class="col-12">
                    <label for="curator_comment">Комментарий</label>
                    <textarea id="curator_comment"
                              class="form-control"
                              rows="2"
                              v-model="comment"
                              placeholder="Дополнительный комментарий"></textarea>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-12">
                    <div class="form-check">
                      <input class="form-check-input"
                             type="checkbox"
                             id="curatorAgree"
                             v-model="agreed"/>
                      <label class="form-check-label" for="curatorAgree">
                        Регистрируя добровольный оргвзнос, ты соглашаешься с Правилами и условиями участия в туристическом слёте и Политикой обработки персональных данных.
                      </label>
                    </div>
                  </div>
                </div>

                <div class="row" style="justify-content: center">
                  <div class="col-12">
                    <button type="button"
                            :disabled="!isFormValid || isLoading"
                            @click="submit"
                            class="btn btn-lg btn-block btn-outline-primary reg-btn">
                      <span v-if="isLoading">Отправка...</span>
                      <span v-else>Зарегистрировать участников</span>
                    </button>
                  </div>
                </div>

                <div class="row justify-content-center" v-if="!isFormValid" style="text-align: center">
                  Если кнопка не активна — проверь, заполнены ли все обязательные поля!
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
import axios from 'axios';
import {mapActions, mapGetters} from 'vuex';

const FESTIVAL_ID = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

export default {
  name: "CreateOrderCurator",
  data() {
    return {
      email: '',
      phone: '',
      project: '',
      comment: '',
      location_id: null,
      locationList: [],
      guests: [],
      newGuest: '',
      newGuestEmail: '',
      isLoading: false,
      message: null,
      isSuccess: false,
      agreed: false,
    };
  },
  computed: {
    ...mapGetters('appOrder', ['getError']),
    isFormValid() {
      return this.email.length > 0
          && this.phone.length > 0
          && this.location_id !== null
          && this.guests.length > 0
          && this.agreed;
    },
  },
  methods: {
    ...mapActions('appOrder', ['goToCreateCuratorOrderTicket', 'clearError']),
    addGuest() {
      if (this.newGuest.trim().length > 0) {
        this.guests.push({
          value: this.newGuest.trim(),
          email: this.newGuestEmail.trim(),
        });
        this.newGuest = '';
        this.newGuestEmail = '';
      }
    },
    delGuest(index) {
      this.guests.splice(index, 1);
    },
    submit() {
      this.clearError();
      this.isLoading = true;

      this.goToCreateCuratorOrderTicket({
        festival_id: FESTIVAL_ID,
        location_id: this.location_id,
        project: this.project || null,
        comment: this.comment || null,
        guests: this.guests,
        price: 0,
        email: this.email,
        phone: this.phone,
        callback: (success, msg) => {
          this.isLoading = false;
          this.isSuccess = success;
          this.message = msg;
          document.getElementById('curatorModalOpenBtn').click();
          if (success) {
            this.clearData();
          }
        },
        callbackError: (msg) => {
          this.isLoading = false;
          this.isSuccess = false;
          this.message = msg || 'Ошибка при создании заказа';
          document.getElementById('curatorModalOpenBtn').click();
        },
      });
    },
    clearData() {
      this.email = '';
      this.phone = '';
      this.project = '';
      this.comment = '';
      this.location_id = null;
      this.guests = [];
      this.newGuest = '';
      this.newGuestEmail = '';
    },
  },
  async created() {
    await this.clearError();
    axios.post('/api/v1/location/getListForCurator', {
      filter: {festival_id: FESTIVAL_ID, active: true}
    }).then(response => {
      this.locationList = response.data.list || [];
    }).catch(() => {});
  },
};
</script>

<style scoped>
.intckt p {
  margin-bottom: 2px;
  font-weight: 500;
}

.intckt small {
  display: block;
  color: #6c757d;
}

.ticket-choice {
  margin-bottom: 8px;
}
</style>
