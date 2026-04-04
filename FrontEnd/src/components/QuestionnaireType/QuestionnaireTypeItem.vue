<template>
  <div class="title-block text-center">
    <h1 class="card-title"> {{ isEdit ? 'Редактирование типа анкеты' : 'Создание типа анкеты' }} </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto">
      <div class="card">
        <div class="card-body">
          <div class="form-group">
            <label>Название типа анкеты</label>
            <input type="text" class="form-control" v-model="item.name" placeholder="Название">
          </div>
          <div class="form-group mt-3">
            <label>Сортировка</label>
            <input type="number" class="form-control" v-model.number="item.sort" placeholder="Сортировка">
          </div>
          <div class="form-group mt-3">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" v-model="item.active" id="activeCheck">
              <label class="form-check-label" for="activeCheck">Активный</label>
            </div>
          </div>

          <hr class="mt-4">
          <h3>Вопросы анкеты</h3>
          <div v-for="(question, index) in item.questions" :key="index" class="card mt-3">
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Название поля для пользователя (title)</label>
                    <input type="text" class="form-control" v-model="question.title" placeholder="Например: Возраст">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Название сущности в questionnaire.data (name)</label>
                    <input type="text" class="form-control" v-model="question.name" placeholder="Например: agy">
                  </div>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Тип данных</label>
                    <select class="form-control" v-model="question.type">
                      <option value="string">Строка</option>
                      <option value="number">Число</option>
                      <option value="text">Текст (большое поле)</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Регулярное выражение (validate)</label>
                    <input type="text" class="form-control" v-model="question.validate" placeholder="/^[0-9]+$/">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <div class="form-check mt-4">
                      <input type="checkbox" class="form-check-input" v-model="question.required" :id="'req' + index">
                      <label class="form-check-label" :for="'req' + index">Обязательное поле</label>
                    </div>
                  </div>
                </div>
              </div>
              <button class="btn btn-danger btn-sm mt-2" @click="removeQuestion(index)">Удалить вопрос</button>
            </div>
          </div>
          <button class="btn btn-secondary mt-3" @click="addQuestion">Добавить вопрос</button>

          <hr class="mt-4">
          <div class="mt-3">
            <button class="btn btn-primary" @click="save">Сохранить</button>
            <button class="btn btn-secondary ms-2" @click="goBack">Назад</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from "vuex";

export default {
  name: "QuestionnaireTypeItem",
  props: {
    id: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      item: {
        name: '',
        sort: 0,
        active: true,
        questions: [],
      }
    }
  },
  computed: {
    ...mapGetters('appQuestionnaireType', [
        'getItem',
    ]),
    isEdit() {
      return this.id !== null;
    }
  },
  methods: {
    ...mapActions('appQuestionnaireType', [
        'loadItem',
        'create',
        'edit',
    ]),
    addQuestion() {
      this.item.questions.push({
        title: '',
        name: '',
        type: 'string',
        validate: null,
        required: false,
      });
    },
    removeQuestion(index) {
      this.item.questions.splice(index, 1);
    },
    async save() {
      if (this.isEdit) {
        await this.edit({
          id: this.id,
          data: this.item
        });
      } else {
        await this.create({
          data: this.item
        });
      }
      this.goBack();
    },
    goBack() {
      this.$router.push({ name: 'QuestionnaireTypeListView' });
    },
  },
  async created() {
    if (this.isEdit) {
      await this.loadItem({ id: this.id });
      this.item = {...this.getItem};
      if (!this.item.questions) {
        this.item.questions = [];
      }
    } else {
      this.item.questions = [];
    }
  },
}
</script>

<style scoped>
</style>
