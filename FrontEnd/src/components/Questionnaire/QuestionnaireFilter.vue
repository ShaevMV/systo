<template>
  <div class="row" id="filter">
    <div class="col-lg-12 mx-auto mb-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Фильтр</h5>

          <div class="d-flex flex-wrap">
            <!--  email -->
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">email</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2">@</span>
                <input type="text"
                       v-model="filter.email"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <!--  статус -->
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Статус анкеты</label>
              <select class="form-select"
                      v-model="filter.status"
                      id="validationDefault01">
                <option value="">Выберите статус</option>
                <option value="new">Новый</option>
                <option value="approve">Подверждён</option>
                <option value="in_clube">В клубе</option>
                <option value="cancel">Отменёный</option>
                <option value="difficulties_arose">Возникли трудности</option>
              </select>
            </div>
            <!--  telegram -->
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">telegram</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2">@</span>
                <input type="text"
                       v-model="filter.telegram"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <!--  vk -->
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">vk</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2">@</span>
                <input type="text"
                       v-model="filter.vk"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <!--  Хочет в ступить в клуб -->
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Хочет вступить в клуб</label>
              <select class="form-select"
                      v-model="filter.is_have_in_club"
                      id="validationDefault01">
                <option value="">Выберите статус</option>
                <option value="true">Хочет</option>
                <option value="false">Не хочет</option>
              </select>
            </div>
            <!--  Тип анкеты -->
            <div class="col-md-4">
              <label for="validationDefaultQuestionnaireType" class="form-label">Тип анкеты</label>
              <select class="form-select"
                      v-model="filter.questionnaire_type_id"
                      id="validationDefaultQuestionnaireType">
                <option value="">Все типы</option>
                <option v-for="type in getQuestionnaireTypeList" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div class="row b-row mt-2">
            <button class="btn btn-primary"
                    @click="sendFilter" :disabled="getIsLoading"
                    type="submit"><span v-if="getIsLoading">Загрузка...</span>
              <span v-else>Отправить</span>
            </button>
            <button class="btn btn-secondary"
                    @click="clearFilter"
                    type="button">Сбросить фильтр
            </button>
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
  name: "QuestionnaireFilter",
  data() {
    return {
      filter: {
        email: null,
        vk: null,
        status: '',
        is_have_in_club: '',
        telegram: null,
        questionnaire_type_id: '',
      }
    }
  },
  computed: {
    ...mapGetters('appTicketType', [
      'getQuestionnaireTypeList'
    ])
  },
  methods: {
    ...mapActions('appQuestionnaire',[
        'loadQuestionnaire'
    ]),
    ...mapActions('appTicketType', [
      'loadQuestionnaireTypeList'
    ]),
    sendFilter: function () {
      this.loadQuestionnaire({
        filter: this.filter,
      })
    },
    clearFilter: function () {
      this.filter = {
        email: null,
        vk: null,
        status: '',
        is_have_in_club: '',
        telegram: null,
        questionnaire_type_id: '',
      };
      this.loadQuestionnaire({
        filter: {},
      });
    }
  },
  async created() {
    await this.loadQuestionnaireTypeList();
  }
}
</script>

<style scoped>

</style>