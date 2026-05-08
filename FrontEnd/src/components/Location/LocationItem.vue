<template>
  <div class="container-fluid">
    <div class="title-block text-center">
      <h1 class="card-title"> {{ isEdit ? 'Редактирование локации' : 'Создание локации' }} </h1>
    </div>
    <div class="row">
      <div class="col-lg-12 mx-auto">
        <div class="card">
          <div class="card-body">

            <div class="row mb-3">
              <label class="col-4 col-form-label">Название локации:</label>
              <div class="col-8">
                <input type="text" class="form-control" v-model="name" placeholder="Например: Главная сцена">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Описание:</label>
              <div class="col-8">
                <textarea class="form-control" v-model="description" rows="3" placeholder="Краткое описание локации"></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Фестиваль:</label>
              <div class="col-8">
                <select class="form-control" v-model="festivalId">
                  <option value="" disabled>— выберите фестиваль —</option>
                  <option v-for="f in getFestivalList" :key="f.id" :value="f.id">
                    {{ f.name }} {{ f.year }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Тип анкеты:</label>
              <div class="col-8">
                <select class="form-control" v-model="questionnaireTypeId">
                  <option :value="null">— без анкеты —</option>
                  <option v-for="qt in getQuestionnaireTypeList" :key="qt.id" :value="qt.id">{{ qt.name }}</option>
                </select>
                <small class="form-text text-muted">Шаблон анкеты, который будут заполнять гости списка</small>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Шаблон письма (blade):</label>
              <div class="col-8">
                <select class="form-select" v-model="emailTemplate">
                  <option :value="''">— по умолчанию (orderListApproved) —</option>
                  <option v-for="item in getTemplateEmail" :key="item" :value="item">{{ item }}</option>
                </select>
                <small class="form-text text-muted">Файл из resources/views/email (без .blade.php)</small>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Шаблон билета (blade):</label>
              <div class="col-8">
                <select class="form-select" v-model="pdfTemplate">
                  <option :value="''">— по умолчанию (pdf) —</option>
                  <option v-for="item in getTemplatePdf" :key="item" :value="item">{{ item }}</option>
                </select>
                <small class="form-text text-muted">Файл из resources/views (без .blade.php)</small>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Активность:</label>
              <div class="col-8">
                <select class="form-select" v-model="active">
                  <option :value="true">Да</option>
                  <option :value="false">Нет</option>
                </select>
              </div>
            </div>

            <hr class="mt-4">
            <div class="row messager">{{ getMessage }}</div>
            <div class="row b-row mt-2">
              <button type="submit" @click="save" class="btn btn-primary">Сохранить</button>
              <button type="button" @click="back" class="btn btn-secondary ms-2">Отмена/назад</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'LocationItem',
  props: {
    id: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      newName: null,
      newDescription: null,
      newFestivalId: null,
      newQuestionnaireTypeId: null,
      newEmailTemplate: null,
      newPdfTemplate: null,
      newActive: null,
    };
  },
  computed: {
    ...mapGetters('appLocation', ['getItem', 'getMessage', 'getTemplateEmail', 'getTemplatePdf']),
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
    ...mapGetters('appQuestionnaireType', { getQuestionnaireTypeList: 'getList' }),
    isEdit() {
      return this.id !== null && this.id !== undefined && this.id !== '';
    },
    name: {
      get() { return this.newName ?? this.getItem.name ?? ''; },
      set(v) { this.newName = v; },
    },
    description: {
      get() { return this.newDescription ?? this.getItem.description ?? ''; },
      set(v) { this.newDescription = v; },
    },
    festivalId: {
      get() { return this.newFestivalId ?? this.getItem.festival_id ?? ''; },
      set(v) { this.newFestivalId = v; },
    },
    questionnaireTypeId: {
      get() {
        if (this.newQuestionnaireTypeId !== null) return this.newQuestionnaireTypeId;
        return this.getItem.questionnaire_type_id ?? null;
      },
      set(v) { this.newQuestionnaireTypeId = v; },
    },
    emailTemplate: {
      get() { return this.newEmailTemplate ?? this.getItem.email_template ?? ''; },
      set(v) { this.newEmailTemplate = v; },
    },
    pdfTemplate: {
      get() { return this.newPdfTemplate ?? this.getItem.pdf_template ?? ''; },
      set(v) { this.newPdfTemplate = v; },
    },
    active: {
      get() {
        if (this.newActive !== null) return this.newActive;
        if (this.getItem.active === undefined) return true;
        return !!this.getItem.active;
      },
      set(v) { this.newActive = v; },
    },
  },
  methods: {
    ...mapActions('appLocation', ['loadItem', 'create', 'edit', 'loadTemplate']),
    ...mapActions('appFestivalTickets', ['getListFestival']),
    ...mapActions('appQuestionnaireType', { loadQuestionnaireTypes: 'loadList' }),
    async save() {
      if (!this.name || !this.festivalId) {
        alert('Название и фестиваль обязательны');
        return;
      }
      const data = {
        name: this.name,
        description: this.description || null,
        festival_id: this.festivalId,
        questionnaire_type_id: this.questionnaireTypeId || null,
        email_template: this.emailTemplate || null,
        pdf_template: this.pdfTemplate || null,
        active: this.active,
      };

      if (this.isEdit) {
        await this.edit({ id: this.id, data });
      } else {
        await this.create({ data });
      }
      this.back();
    },
    back() {
      this.$router.push({ name: 'LocationListView' });
    },
  },
  async created() {
    this.getListFestival();
    this.loadQuestionnaireTypes({ filter: { active: '1' }, orderBy: {} });
    // Список шаблонов некритичен для сохранения — при ошибке селект остаётся пустым,
    // пользователь сможет оставить значения по умолчанию.
    try {
      await this.loadTemplate();
    } catch (e) {
      console.warn('Не удалось загрузить список blade-шаблонов:', e);
    }
  },
};
</script>

<style scoped>
</style>
