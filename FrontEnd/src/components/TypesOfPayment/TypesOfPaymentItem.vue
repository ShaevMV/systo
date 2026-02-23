<template>
  <div class="container-fluid">
    <div class="title-block text-center"><h1 class="card-title">Тии билета: {{ name }}</h1></div>
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
                  <label for="company" class="col-4 col-form-label">Отлено номер карты:</label>
                  <div class="col-8">
                    <input name="company"
                           type="text"
                           class="form-control"
                           v-model="card"
                           id="company">
                  </div>
                  <small class="form-text text-muted"> {{ getError('card') }}</small>
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
                <div class="col-md-3">
                  <label for="validationDefault01" class="form-label">Продовец живых билетов</label>
                  <select class="form-select"
                          v-model="userExternalId"
                          id="validationDefault01">
                    <option value=null>Выберите</option>
                    <option v-for="getlistSeller in getlistSellers"
                            v-bind:key="getlistSeller.id"
                            v-bind:value="getlistSeller">{{ getlistSeller.email }}
                    </option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="validationDefault01" class="form-label">Типы билетов</label>
                  <select class="form-select"
                          v-model="ticketTypeId"
                          id="validationDefault01">
                    <option value=null>Выберите</option>
                    <option v-for="item in ticketTypeGetList"
                            v-bind:key="item.id"
                            v-bind:value="item">{{ item.name }}
                    </option>
                  </select>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Активность:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="active"
                            id="validationDefault01">
                      <option value=null>Выберите</option>
                      <option value="false">Нет</option>
                      <option value="true">Да</option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('active') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Биллинг:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="isBilling"
                            id="validationDefault01">
                      <option value=null>Выберите</option>
                      <option value="false">Нет</option>
                      <option value="true">Да</option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('is_billing') }}</small>
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
      newCard: null,
      newSort: null,
      newActive: null,
      newBillind: null,
      newUserExternalId: null,
      newTicketTypeId: null,
      message: null,
    }
  },
  computed: {
    ...mapGetters('appAccount', {
      getlistSellers: 'getList'
    }),
    ...mapGetters('appTicketType', {
      ticketTypeGetList: 'getList'
    }),
    ...mapGetters('appTypesOfPayment', [
      'getItem',
      'getError'
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
    card: {
      get: function () {
        if (this.newCard === null) {
          return this.getItem.card;
        }
        return this.newCard;
      },
      set: function (newValue) {
        this.newCard = newValue;
      },
    },
    userExternalId: {
      get: function () {
        if (this.newUserExternalId === null) {
          return this.getItem.seller.id;
        }
        return this.newUserExternalId;
      },
      set: function (newValue) {
        this.newUserExternalId = newValue.id;
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
    isBilling: {
      get: function () {
        if (this.newBillind === null) {
          return this.getItem.is_billing;
        }
        return this.newBillind;
      },
      set: function (newValue) {
        this.newBillind = newValue;
      },
    },
    ticketTypeId: {
      get: function () {
        if (this.newTicketTypeId === null) {
          return this.getItem.ticket_type.id;
        }
        return this.newTicketTypeId;
      },
      set: function (newValue) {
        this.newTicketTypeId = newValue.id;
      },
    }
  },
  methods: {
    ...mapActions('appTypesOfPayment', [
      'clearError',
      'edit',
      'create'
    ]),
    back: function () {
      this.$router.push({name: 'TypesOfPaymentListView'});
    },
    save: function () {
      let data = {
        'name': this.name,
        'card': this.card,
        'is_billing': this.isBilling,
        'sort': this.sortItem,
        'active': this.active,
        'user_external_id': this.userExternalId,
        'ticket_type_id': this.ticketTypeId,
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