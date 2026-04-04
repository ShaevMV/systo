<template>
  <div class="container-fluid">
    <div class="title-block text-center">
      <h1 class="card-title"> {{ isEdit ? 'Редактирование типа анкеты' : 'Создание типа анкеты' }} </h1>
    </div>
    <div class="row">
      <div class="col-lg-12 mx-auto">
        <div class="card">
          <div class="card-body">
            <div class="row mb-3">
              <label class="col-4 col-form-label">Название типа анкеты:</label>
              <div class="col-8">
                <input type="text" class="form-control" v-model="name" placeholder="Название">
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Сортировка:</label>
              <div class="col-8">
                <input type="number" class="form-control" v-model.number="sortItem" placeholder="Сортировка">
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-4 col-form-label">Активность:</label>
              <div class="col-8">
                <select class="form-select" v-model="active">
                  <option value="true">Да</option>
                  <option value="false">Нет</option>
                </select>
              </div>
            </div>

            <hr class="mt-4">
            <h3>Вопросы анкеты</h3>
            <div v-for="(question, index) in questionsList" :key="index" class="card mt-3">
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
            <div class="row messager">{{ getMessage }}</div>
            <div class="row b-row mt-2">
              <button type="submit" @click="save" class="btn btn-primary">Сохранить</button>
              <button type="submit" @click="back" class="btn btn-secondary ms-2">Отмена/назад</button>
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
  name: "QuestionnaireTypeItem",
  props: {
    id: {
      type: String,
      default: null,
    }
  },
  data() {
    return {
      newName: null,
      newSort: null,
      newActive: null,
      newQuestions: null,
    }
  },
  computed: {
    ...mapGetters('appQuestionnaireType', [
        'getItem',
        'getMessage',
    ]),
    isEdit() {
      return this.id !== null && this.id !== undefined && this.id !== '';
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
    questionsList: {
      get: function () {
        if (this.newQuestions === null) {
          let questions = this.getItem.questions;
          if (typeof questions === 'string') {
            try {
              questions = JSON.parse(questions);
            } catch (e) {
              questions = [];
            }
          }
          return questions || [];
        }
        return this.newQuestions;
      },
      set: function (newValue) {
        this.newQuestions = newValue;
      },
    },
  },
  methods: {
    ...mapActions('appQuestionnaireType', [
        'loadItem',
        'create',
        'edit',
    ]),
    addQuestion() {
      let questions = this.questionsList;
      questions.push({
        title: '',
        name: '',
        type: 'string',
        validate: null,
        required: false,
      });
      this.newQuestions = [...questions];
    },
    removeQuestion(index) {
      let questions = this.questionsList;
      questions.splice(index, 1);
      this.newQuestions = [...questions];
    },
    async save() {
      let data = {
        'name': this.name,
        'sort': this.sortItem,
        'active': this.active,
        'questions': this.questionsList,
      };

      if (this.isEdit) {
        await this.edit({
          id: this.id,
          data: data
        });
      } else {
        await this.create({
          data: data
        });
      }
      this.back();
    },
    back() {
      this.$router.push({ name: 'QuestionnaireTypeListView' });
    },
  },
}
</script>

<style scoped>
</style>
