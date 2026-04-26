<template>
  <div class="container-fluid">
    <div class="title-block text-center">
      <h1 class="card-title">Создать заказ куратора</h1>
    </div>
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="card">
          <div class="card-body">

            <div class="row mb-3">
              <label class="col-4 col-form-label">Фестиваль:</label>
              <div class="col-8">
                <select class="form-select" v-model="festival_id" @change="onFestivalChange">
                  <option :value="null">Выберите фестиваль</option>
                  <option v-for="f in getFestivalList" :key="f.id" :value="f.id">
                    {{ f.name }} {{ f.year }}
                  </option>
                </select>
                <small class="form-text text-danger">{{ getOrderError('festival_id') }}</small>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Локация:</label>
              <div class="col-8">
                <select class="form-select" v-model="location_id" :disabled="!festival_id">
                  <option :value="null">Выберите локацию</option>
                  <option v-for="loc in locationList" :key="loc.id" :value="loc.id">
                    {{ loc.name }}
                  </option>
                </select>
                <div v-if="selectedLocation && selectedLocation.description" class="mt-1 text-muted small">
                  {{ selectedLocation.description }}
                </div>
                <small class="form-text text-danger">{{ getOrderError('location_id') }}</small>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Проект:</label>
              <div class="col-8">
                <input type="text"
                       class="form-control"
                       v-model="project"
                       placeholder="Название проекта">
                <small class="form-text text-danger">{{ getOrderError('project') }}</small>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Комментарий:</label>
              <div class="col-8">
                <textarea class="form-control" v-model="comment" rows="2"></textarea>
              </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong>Участники:</strong>
              <button type="button" class="btn btn-sm btn-outline-primary" @click="addGuest">
                + Добавить участника
              </button>
            </div>

            <div v-for="(guest, index) in guests" :key="index" class="row mb-2 align-items-center">
              <div class="col-5">
                <input type="text"
                       class="form-control"
                       v-model="guest.value"
                       :placeholder="'Имя участника ' + (index + 1)">
              </div>
              <div class="col-5">
                <input type="email"
                       class="form-control"
                       v-model="guest.email"
                       placeholder="Email (необязательно)">
              </div>
              <div class="col-2">
                <button type="button"
                        class="btn btn-sm btn-outline-danger"
                        @click="removeGuest(index)"
                        :disabled="guests.length <= 1">
                  ✕
                </button>
              </div>
            </div>
            <small class="form-text text-danger">{{ getOrderError('guests') }}</small>

            <div v-if="message" class="alert mt-3" :class="isSuccess ? 'alert-success' : 'alert-danger'">
              {{ message }}
            </div>

            <div class="row b-row mt-3">
              <button type="button"
                      class="btn btn-primary"
                      @click="submit"
                      :disabled="isLoading">
                <span v-if="isLoading">Отправка...</span>
                <span v-else>Создать заказ</span>
              </button>
              <button type="button"
                      class="btn btn-secondary"
                      @click="$router.push({ name: 'AllOrdersCurator' })">
                Отмена
              </button>
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

export default {
  name: "CreateOrderCurator",
  data() {
    return {
      festival_id: null,
      location_id: null,
      project: '',
      comment: '',
      guests: [{value: '', email: ''}],
      locationList: [],
      isLoading: false,
      message: null,
      isSuccess: false,
    };
  },
  computed: {
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
    ...mapGetters('appOrder', ['getError']),
    getOrderError() {
      return (field) => this.getError(field);
    },
    selectedLocation() {
      if (!this.location_id) return null;
      return this.locationList.find(loc => loc.id === this.location_id) || null;
    },
  },
  methods: {
    ...mapActions('appFestivalTickets', ['getListFestival']),
    ...mapActions('appOrder', ['goToCreateCuratorOrderTicket', 'clearError']),
    onFestivalChange() {
      this.location_id = null;
      this.locationList = [];
      if (!this.festival_id) return;
      axios.post('/api/v1/location/getListForCurator', {filter: {festival_id: this.festival_id, active: true}})
        .then(response => {
          this.locationList = response.data.list || [];
        })
        .catch(() => {});
    },
    addGuest() {
      this.guests.push({value: '', email: ''});
    },
    removeGuest(index) {
      if (this.guests.length > 1) {
        this.guests.splice(index, 1);
      }
    },
    submit() {
      this.clearError();
      this.message = null;
      this.isLoading = true;

      this.goToCreateCuratorOrderTicket({
        festival_id: this.festival_id,
        location_id: this.location_id,
        project: this.project || null,
        comment: this.comment || null,
        guests: this.guests.filter(g => g.value.trim()),
        price: 0,
        email: '',
        phone: '',
        callback: (success, msg) => {
          this.isLoading = false;
          this.isSuccess = success;
          this.message = msg;
          if (success) {
            this.resetForm();
          }
        },
        callbackError: (msg) => {
          this.isLoading = false;
          this.isSuccess = false;
          this.message = msg || 'Ошибка при создании заказа';
        },
      });
    },
    resetForm() {
      this.festival_id = null;
      this.location_id = null;
      this.project = '';
      this.comment = '';
      this.guests = [{value: '', email: ''}];
      this.locationList = [];
    },
  },
  created() {
    this.getListFestival();
  },
};
</script>
