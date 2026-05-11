<template>
  <div class="container-fluid">
    <div class="title-block text-center"><h1 class="card-title">Тип билета: {{ name }}</h1></div>
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
                  <label for="company" class="col-4 col-form-label">Имя файла для шаблона билета
                    (Backend/resources/views):</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="festival_pdf"
                            id="validationDefault01">
                      <option value=null>Выберите</option>
                      <option v-for="item in (getTemplatePdf)"
                              v-bind:key="item"
                              :selected="item == festival_pdf"
                              v-bind:value="item">{{ item }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('festival_pdf') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Шаблон письма
                    (Backend/resources/views/email):</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="festival_email"
                            id="validationDefault01">
                      <option value=null>Выберите</option>
                      <option v-for="item in (getTemplateEmail)"
                              v-bind:key="item"
                              :selected="item == festival_email"
                              v-bind:value="item">{{ item }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('festival_email') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="questionnaire_howManyTimes">
                    Описание
                  </label>
                  <div class="input-group" id="promo-input">
                    <textarea
                        id="questionnaire_howManyTimes"
                        class="form-control"
                        v-model="festival_description"
                    ></textarea>
                  </div>
                  <small class="form-text text-muted"> {{ getError('festival_description') }}</small>
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
                  <label for="company" class="col-4 col-form-label">Для парковки:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="isParking"
                            id="validationDefault01">
                      <option value=null>Выберите</option>
                      <option value="false">Нет</option>
                      <option value="true">Да</option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('is_parking') }}</small>
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
                  <label for="company" class="col-4 col-form-label">Фестиваль:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="festival_id"
                            id="validationDefault01">
                      <option v-for="(festivalItem) in getFestivalList"
                              v-bind:key="festivalItem.id"
                              :selected="festivalItem.id == festival_id"
                              v-bind:value="festivalItem.id">{{ festivalItem.name }} {{ festivalItem.year }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('festival_id') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Тип анкеты:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="questionnaire_type_id"
                            id="validationDefault02">
                      <option value="">Не выбран</option>
                      <option v-for="(qItem) in getList"
                              v-bind:key="qItem.id"
                              :selected="qItem.id == questionnaire_type_id"
                              v-bind:value="qItem.id">{{ qItem.name }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('questionnaire_type_id') }}</small>
                </div>

                <div class="row messager">{{ getMessage }}</div>
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
  name: "TicketTypeItem",
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
      newIsParking: null,
      newFestivalId: null,
      newDescription: null,
      newEmail: null,
      newPdf: null,
      newQuestionnaireTypeId: null,
    }
  },
  computed: {
    ...mapGetters('appTicketType', [
      'getError',
      'getItem',
      'getMessage',
      'getTemplateEmail',
      'getTemplatePdf',
    ]),
    ...mapGetters('appQuestionnaireType', [
      'getList',
    ]),
    ...mapGetters('appFestivalTickets', [
      'getFestivalList',
    ]),
    festival_description: {
      get: function () {
        if (this.newDescription === null) {
          return this.getItem.festival?.description
        }
        return this.newDescription;
      },
      set: function (newValue) {
        this.newDescription = newValue;
      },
    },
    festival_email: {
      get: function () {
        if (this.newEmail === null) {
          return this.getItem.festival?.email
        }
        return this.newEmail;
      },
      set: function (newValue) {
        this.newEmail = newValue;
      },
    },
    festival_pdf: {
      get: function () {
        if (this.newPdf === null) {
          return this.getItem.festival?.pdf;
        }
        return this.newPdf;
      },
      set: function (newValue) {
        this.newPdf = newValue;
      },
    },
    festival_id: {
      get: function () {
        if (this.newFestivalId === null) {
          return this.getItem.festival?.id;
        }
        return this.newFestivalId;
      },
      set: function (newValue) {
        this.newFestivalId = newValue;
      },
    },
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
    },
    isParking: {
      get: function () {
        if (this.newIsParking === null) {
          return this.getItem.is_parking;
        }
        return this.newIsParking;
      },
      set: function (newValue) {
        this.newIsParking = newValue;
      },
    },
    questionnaire_type_id: {
      get: function () {
        if (this.newQuestionnaireTypeId === null) {
          return this.getItem.questionnaire_type_id;
        }
        return this.newQuestionnaireTypeId;
      },
      set: function (newValue) {
        this.newQuestionnaireTypeId = newValue;
      },
    }
  },
  methods: {
    ...mapActions('appTicketType', [
      'clearError',
      'edit',
      'create',
      'loadTemplate',
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
        'is_parking': this.isParking,
        'festival_id': this.festival_id,
        'festival_pdf': this.festival_pdf,
        'festival_email': this.festival_email,
        'festival_description': this.festival_description,
        'questionnaire_type_id': this.questionnaire_type_id || null,
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
  }
}
</script>

<style scoped>

</style>