<template>
  <div class="container-fluid">
    <div class="title-block text-center"><h1 class="card-title">Локация: {{ name }}</h1></div>
    <div class="row">
      <div class="col-lg-12 mx-auto">
        <div class="card">
          <div class="card-body">
            <div class="tab-pane fade profile-edit pt-3 active show" id="profile-edit" role="tabpanel">

              <div>
                <div class="row mb-3">
                  <label for="location-name" class="col-4 col-form-label">Название:</label>
                  <div class="col-8">
                    <input name="location-name"
                           type="text"
                           class="form-control"
                           v-model="name"
                           id="location-name">
                  </div>
                  <small class="form-text text-muted"> {{ getError('name') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="location-festival-id" class="col-4 col-form-label">Фестиваль:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="festival_id"
                            id="location-festival-id">
                      <option v-for="festivalItem in getFestivalList"
                              v-bind:key="festivalItem.id"
                              :selected="festivalItem.id == festival_id"
                              v-bind:value="festivalItem.id">{{ festivalItem.name }} {{ festivalItem.year }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('festival_id') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="location-pdf-template" class="col-4 col-form-label">Имя файла для шаблона билета
                    (Backend/resources/views):</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="pdf_template"
                            id="location-pdf-template">
                      <option value=null>Выберите</option>
                      <option v-for="item in getTemplatePdf"
                              v-bind:key="item"
                              :selected="item == pdf_template"
                              v-bind:value="item">{{ item }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('pdf_template') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="location-email-template" class="col-4 col-form-label">Шаблон письма
                    (Backend/resources/views/email):</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="email_template"
                            id="location-email-template">
                      <option value=null>Выберите</option>
                      <option v-for="item in getTemplateEmail"
                              v-bind:key="item"
                              :selected="item == email_template"
                              v-bind:value="item">{{ item }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('email_template') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="location-sort" class="col-4 col-form-label">Сортировка:</label>
                  <div class="col-8">
                    <input name="location-sort"
                           type="number"
                           class="form-control"
                           v-model="sort"
                           id="location-sort">
                  </div>
                  <small class="form-text text-muted"> {{ getError('sort') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="location-description" class="col-4 col-form-label">Описание:</label>
                  <div class="col-8">
                    <textarea name="location-description"
                              class="form-control"
                              v-model="description"
                              id="location-description"
                              rows="3"></textarea>
                  </div>
                  <small class="form-text text-muted"> {{ getError('description') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="location-questionnaire-type" class="col-4 col-form-label">Тип анкеты участника:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="questionnaire_type_id"
                            id="location-questionnaire-type">
                      <option :value="null">Не выбрано</option>
                      <option v-for="type in questionnaireTypeList" :key="type.id" :value="type.id">
                        {{ type.name }}
                      </option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('questionnaire_type_id') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="location-active" class="col-4 col-form-label">Активность:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="active"
                            id="location-active">
                      <option value=null>Выберите</option>
                      <option value="false">Нет</option>
                      <option value="true">Да</option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('active') }}</small>
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
import axios from 'axios';
import {mapActions, mapGetters} from "vuex";

export default {
  name: "LocationItem",
  props: {
    id: {
      type: [String],
      default: null,
    }
  },
  data() {
    return {
      newName: null,
      newFestivalId: null,
      newSort: null,
      newActive: null,
      newDescription: null,
      newQuestionnaireTypeId: undefined,
      newEmailTemplate: undefined,
      newPdfTemplate: undefined,
      questionnaireTypeList: [],
    }
  },
  created() {
    this.loadQuestionnaireTypes();
  },
  computed: {
    ...mapGetters('appLocation', [
      'getError',
      'getItem',
      'getMessage',
    ]),
    ...mapGetters('appFestivalTickets', [
      'getFestivalList',
    ]),
    ...mapGetters('appTicketType', [
      'getTemplateEmail',
      'getTemplatePdf',
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
    festival_id: {
      get: function () {
        if (this.newFestivalId === null) {
          return this.getItem.festival_id;
        }
        return this.newFestivalId;
      },
      set: function (newValue) {
        this.newFestivalId = newValue;
      },
    },
    pdf_template: {
      get: function () {
        if (this.newPdfTemplate === undefined) {
          return this.getItem.pdf_template ?? null;
        }
        return this.newPdfTemplate;
      },
      set: function (newValue) {
        this.newPdfTemplate = newValue;
      },
    },
    email_template: {
      get: function () {
        if (this.newEmailTemplate === undefined) {
          return this.getItem.email_template ?? null;
        }
        return this.newEmailTemplate;
      },
      set: function (newValue) {
        this.newEmailTemplate = newValue;
      },
    },
    sort: {
      get: function () {
        if (this.newSort === null) {
          const val = this.getItem?.sort;
          return typeof val === 'number' ? val : 0;
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
    description: {
      get: function () {
        if (this.newDescription === null) {
          return this.getItem.description ?? null;
        }
        return this.newDescription;
      },
      set: function (newValue) {
        this.newDescription = newValue;
      },
    },
    questionnaire_type_id: {
      get: function () {
        if (this.newQuestionnaireTypeId === undefined) {
          return this.getItem.questionnaire_type_id ?? null;
        }
        return this.newQuestionnaireTypeId;
      },
      set: function (newValue) {
        this.newQuestionnaireTypeId = newValue;
      },
    },
  },
  methods: {
    ...mapActions('appLocation', [
      'clearError',
      'edit',
      'create',
    ]),
    loadQuestionnaireTypes() {
      axios.post('/api/v1/questionnaireType/getList', {})
        .then(response => {
          this.questionnaireTypeList = response.data.list || [];
        })
        .catch(() => {});
    },
    back: function () {
      this.$router.push({name: 'LocationListView'});
    },
    save: function () {
      let data = {
        'name': this.name,
        'festival_id': this.festival_id,
        'sort': this.sort,
        'active': this.active,
        'description': this.description,
        'questionnaire_type_id': this.questionnaire_type_id,
        'email_template': this.email_template,
        'pdf_template': this.pdf_template,
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
