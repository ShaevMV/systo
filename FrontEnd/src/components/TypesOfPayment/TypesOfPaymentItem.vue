<template>
  <div class="container-fluid">
    <div class="title-block text-center"><h1 class="card-title">Тии билета:  {{ name }}</h1></div>
    <div class="row">
      <div class="col-lg-12 mx-auto">
        <div class="card">
          <div class="card-body">
            <div class="tab-pane fade profile-edit pt-3 active show" id="profile-edit" role="tabpanel">

              <div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Название:</label>
                  <div class="col-8">
                    <input name="company"
                           type="text"
                           class="form-control"
                           v-model="name"
                           id="company">
                  </div>
                  <small class="form-text text-muted"> {{ getError('name') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Стартовая стоимсоть:</label>
                  <div class="col-8">
                    <input name="company"
                           type="number"
                           class="form-control"
                           v-model="price"
                           id="company">
                  </div>
                  <small class="form-text text-muted"> {{ getError('price') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Ограничение кол-во:</label>
                  <div class="col-8">
                    <input name="company"
                           type="number"
                           class="form-control"
                           v-model="groupLimit"
                           id="company">
                  </div>
                  <small class="form-text text-muted"> {{ getError('groupLimit') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Сорт</label>
                  <div class="col-8">
                    <input name="company"
                           type="number"
                           class="form-control"
                           v-model="sortItem"
                           id="company">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Для живых билетов:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="isLiveTicket"
                            id="validationDefault01">
                      <option value=null>Выберите</option>
                      <option value="false">Нет</option>
                      <option value="true">Да</option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('is_live_ticket') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Активность:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="active"
                            id="validationDefault01">
                      <option value=null>Выберите</option>
                      <option value="false">Фиксированная</option>
                      <option value="true">Процент</option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('active') }}</small>
                </div>
                <div class="row messager">{{ message }}</div>
                <div class="row b-row mt-2">
                  <button type="submit"
                          @click="save"
                          class="btn btn-primary">Сохранить изменения
                  </button>
                  <button type="submit"
                          @click="back"
                          class="btn btn-primary">Отмена/назад
                  </button>
                </div>
              </div><!-- End Profile Edit Form -->

            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from "vuex";

export default {
  name: "TypesOfPaymentItem",
  props: {
    id: {
      type: [String],
      default: null,
    }
  },
  data() {
    return {
      newName: null,
      newPrice: null,
      newGroupLimit: null,
      newSort: null,
      newActive: null,
      newIsLiveTicket: null,
      message: null,
    }
  },
  computed: {
    ...mapGetters('appTicketType', [
      'getError',
      'getItem',
    ]),
    name: {
      get: function () {
        if (this.newName === null) {
          return this.getItem.name;
        }
        return this.newName;
      },
      set: function (newValue) {
        this.newName = newValue;
      },
    },
    price: {
      get: function () {
        if (this.newPrice === null) {
          return this.getItem.price;
        }
        return this.newPrice;
      },
      set: function (newValue) {
        this.newPrice = newValue;
      },
    },
    groupLimit: {
      get: function () {
        if (this.newGroupLimit === null) {
          return this.getItem.groupLimit;
        }
        return this.newGroupLimit;
      },
      set: function (newValue) {
        this.newGroupLimit = newValue;
      },
    },
    sortItem: {
      get: function () {
        if (this.newSort === null) {
          return this.getItem.sort;
        }
        return this.newSort;
      },
      set: function (newValue) {
        this.newSort = newValue;
      },
    },
    active: {
      get: function () {
        if (this.newActive === null) {
          return this.getItem.active;
        }
        return this.newActive;
      },
      set: function (newValue) {
        this.newActive = newValue;
      },
    },
    isLiveTicket: {
      get: function () {
        if (this.newIsLiveTicket === null) {
          return this.getItem.is_live_ticket;
        }
        return this.newIsLiveTicket;
      },
      set: function (newValue) {
        this.newIsLiveTicket = newValue;
      },
    }
  },
  methods: {
    ...mapActions('appTicketType', [
      'clearError',
      'edit',
      'create'
    ]),
    back: function () {
      this.$router.push({name: 'TicketTypeListView'});
    },
    save: function () {
      let data = {
        'name': this.name,
        'price': this.price,
        'groupLimit': this.groupLimit,
        'sort': this.sortItem,
        'active': this.active,
        'is_live_ticket': this.isLiveTicket,
      };

      if (this.id !== null && this.id !== undefined && this.id !== '') {
        this.edit({
          id: this.id,
          data: data,
        })
      } else {
        this.create({
          data: data,
        })
      }

    }
  },
}
</script>

<style scoped>

</style>