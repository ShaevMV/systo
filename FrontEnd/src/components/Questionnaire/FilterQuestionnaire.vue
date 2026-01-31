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
                       v-model="email"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <!--  статус -->
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Статус анкеты</label>
              <select class="form-select"
                      v-model="status"
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
                       v-model="telegram"
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
                       v-model="vk"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <!--  Хочет в ступить в клуб -->
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Хочет в ступить в клуб</label>
              <select class="form-select"
                      v-model="is_have_in_club"
                      id="validationDefault01">
                <option value="">Выберите статус</option>
                <option value="true">Хочет</option>
                <option value="false">Не хочет</option>
              </select>
            </div>

            <div class="row b-row mt-2">
            <button class="btn btn-primary"
                    @click="sendFilter" :disabled="getIsLoading"
                    type="submit"><span v-if="getIsLoading">Загрузка...</span>
              <span v-else>Отправить</span>
            </button>
            <button class="btn btn-primary"
                    @click="clearFilter"
                    type="submit">Сбросить фильтр
            </button>
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "FilterQuestionnaire",
  data() {
    return {
      email: null,
      vk: null,
      status: '',
      is_have_in_club: '',
      telegram: null,
    }
  },
}
</script>

<style scoped>

</style>