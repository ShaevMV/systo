<template>
  <div class="container-fluid">
    <button type="button" class="btn btn-primary" v-show="false" data-toggle="modal" id="modalOpenBtnLists" data-target="#listsModal">
      modal
    </button>

    <div class="modal fade" id="listsModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Результат</h5>
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
      <h1>Создание заказа-списка</h1>
      <small class="form-text text-muted">Куратор оформляет список гостей на выбранную локацию (сцену) фестиваля</small>
    </div>

    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">

            <div class="pp1 row mt-2">
              <span>ШАГ 1.</span> Введи данные основного получателя (на этот email придут билеты):
            </div>
            <div class="row y-row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="form_email">Email получателя билетов *</label>
                  <input id="form_email" type="email" class="form-control" placeholder="Email: *" required v-model="email" />
                  <small class="form-text text-muted">{{ getError('email') }}</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="form_phone">Телефон</label>
                  <input id="form_phone" type="text" class="form-control" placeholder="Телефон" v-model="phone" />
                </div>
              </div>
            </div>

            <div class="pp1 row mt-3">
              <span>ШАГ 2.</span> Выбери локацию (сцену) фестиваля:
            </div>
            <div class="mb-3">
              <select class="form-control" v-model="selectedLocationId">
                <option :value="null" disabled>— выберите локацию —</option>
                <option v-for="loc in activeLocations" :key="loc.id" :value="loc.id">
                  {{ loc.name }}{{ loc.description ? ' — ' + loc.description : '' }}
                </option>
              </select>
              <small class="form-text text-muted">{{ getError('location_id') }}</small>
            </div>

            <div class="pp1 row mt-3">
              <span>ШАГ 2.1.</span> Проект *
            </div>
            <div class="mb-3">
              <input type="text" class="form-control" v-model="project" placeholder="Например: Москва-весна, Команда волонтёров #4" maxlength="255" required />
              <small class="form-text text-muted">{{ getError('project') }}</small>
            </div>

            <div class="pp1 row mt-3">
              <span>ШАГ 3.</span> Добавь гостей в список:
            </div>
            <div class="row mt-3 mb-3" id="enter-guests">
              <div class="not-first-guest input-group mb-3">
                <input type="text" class="form-control" placeholder="Имя и фамилия гостя" v-model="newGuest" />
                <input type="email" class="form-control" placeholder="Email гостя *" v-model="newGuestEmail" />
                <div class="input-group-prepend">
                  <span class="input-group-text btn" @click="addGuest()">Добавить</span>
                </div>
              </div>
            </div>

            <div class="row x-row" v-show="guests.length > 0">
              <div class="col-12">
                <div class="form-group">
                  <div class="input-group mb-3" v-for="(g, index) in guests" :key="index">
                    <input type="text" class="form-control" readonly :value="g.value" />
                    <input type="email" class="form-control" readonly :value="g.email" />
                    <div class="input-group-prepend">
                      <span class="input-group-text btn" @click="delGuest(index)">
                        <i class="fa fa-trash">🗑️</i>
                      </span>
                    </div>
                  </div>
                  <small class="form-text text-muted">{{ getError('guests') }}</small>
                </div>
              </div>
            </div>

            <div class="row col-12">
              <h4 class="font-weight-normal">Гостей в списке: <span>{{ guests.length }}</span></h4>
            </div>

            <div class="pp1 row mt-3">
              <span>ШАГ 4.</span> Автомобили (опционально):
            </div>
            <div class="row mt-3 mb-3" id="enter-autos">
              <div class="not-first-guest input-group mb-3">
                <input type="text" class="form-control" placeholder="Номер автомобиля" v-model="newAuto" @keyup.enter="addAuto" />
                <div class="input-group-prepend">
                  <span class="input-group-text btn" @click="addAuto()">Добавить</span>
                </div>
              </div>
            </div>

            <div class="row x-row" v-show="autos.length > 0">
              <div class="col-12">
                <div class="form-group">
                  <div class="input-group mb-3" v-for="(a, index) in autos" :key="index">
                    <input type="text" class="form-control" readonly :value="a" />
                    <div class="input-group-prepend">
                      <span class="input-group-text btn" @click="delAuto(index)">
                        <i class="fa fa-trash">🗑️</i>
                      </span>
                    </div>
                  </div>
                  <small class="form-text text-muted">{{ getError('autos') }}</small>
                </div>
              </div>
            </div>

            <div class="row col-12" v-show="autos.length > 0">
              <h4 class="font-weight-normal">Авто в списке: <span>{{ autos.length }}</span></h4>
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                <label>Комментарий (опционально)</label>
                <textarea class="form-control" v-model="comment" rows="2" placeholder="Комментарий к списку"></textarea>
              </div>
            </div>

            <div class="row sub-warn mt-3">
              <b>ВНИМАНИЕ!</b> После одобрения списка администратором/менеджером гости получат ссылки на анкеты,
              а на email получателя придут электронные билеты.
            </div>

            <div class="row mt-3" style="justify-content: center">
              <div class="col-12 text-center">
                <button type="button" :disabled="!isValid" @click="orderList" class="btn btn-lg btn-block btn-outline-primary reg-btn">
                  Зарегистрировать список
                </button>
              </div>
              <div class="row justify-content-center" v-if="!isValid" style="text-align: center">
                Если кнопка не активна — заполните email, выберите локацию, введите проект и добавьте хотя бы одного гостя с email.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

const FESTIVAL_ID = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

export default {
  name: 'BuyTicketLists',
  data() {
    return {
      email: null,
      phone: null,
      newGuest: '',
      newGuestEmail: '',
      guests: [],
      newAuto: '',
      autos: [],
      selectedLocationId: null,
      project: null,
      comment: null,
      message: null,
    };
  },
  computed: {
    ...mapGetters('appLocation', { locationList: 'getList' }),
    ...mapGetters('appOrder', ['getError']),
    activeLocations() {
      return (this.locationList || []).filter((l) => l.active && l.festival_id === FESTIVAL_ID);
    },
    isValid() {
      return !!this.email && !!this.selectedLocationId && !!this.project && this.guests.length > 0;
    },
  },
  methods: {
    ...mapActions('appLocation', { loadLocations: 'loadList' }),
    ...mapActions('appOrder', ['goToCreateListOrder', 'clearError']),
    addGuest() {
      if (this.newGuest.length === 0 || this.newGuestEmail.length === 0) return;
      this.guests.push({
        value: this.newGuest,
        email: this.newGuestEmail,
      });
      this.newGuest = '';
      this.newGuestEmail = '';
    },
    delGuest(index) {
      this.guests.splice(index, 1);
    },
    addAuto() {
      const value = (this.newAuto || '').trim();
      if (value.length === 0) return;
      this.autos.push(value);
      this.newAuto = '';
    },
    delAuto(index) {
      this.autos.splice(index, 1);
    },
    orderList() {
      const self = this;
      const data = {
        email: this.email,
        phone: this.phone,
        festival_id: FESTIVAL_ID,
        location_id: this.selectedLocationId,
        project: this.project,
        guests: this.guests,
        autos: this.autos,
        comment: this.comment,
        callback(success, message) {
          self.message = message;
          document.getElementById('modalOpenBtnLists').click();
          if (success) {
            self.clearData();
          }
        },
      };
      this.goToCreateListOrder(data);
    },
    clearData() {
      this.email = null;
      this.phone = null;
      this.guests = [];
      this.autos = [];
      this.selectedLocationId = null;
      this.project = null;
      this.comment = null;
      this.newGuest = '';
      this.newGuestEmail = '';
      this.newAuto = '';
    },
  },
  async created() {
    await this.clearError();
    await this.loadLocations({
      filter: { festival_id: FESTIVAL_ID, active: '1' },
      orderBy: { name: 'asc' },
    });
  },
};
</script>

<style scoped>
.title-block { margin-bottom: 16px; }
.sub-warn { padding: 10px; background: #fff8e1; border-radius: 6px; margin-top: 10px; }
</style>
