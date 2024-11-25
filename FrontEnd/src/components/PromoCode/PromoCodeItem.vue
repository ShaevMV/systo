<template>
  <div class="container-fluid">
    <div class="title-block text-center"><h1 class="card-title">Промокод {{ name }}</h1></div>
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
                  <label for="company" class="col-4 col-form-label">Тип скидки:</label>
                  <div class="col-8">
                    <select class="form-select"
                            v-model="isPercent"
                            id="validationDefault01">
                      <option value=null>Выберите тип скидки</option>
                      <option value="false">Фиксированная</option>
                      <option value="true">Процент</option>
                    </select>
                  </div>
                  <small class="form-text text-muted"> {{ getError('is_percent') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Скидка:</label>
                  <div class="col-8">
                    <input name="company"
                           type="number"
                           class="form-control"
                           v-model="discount"
                           id="company">
                  </div>
                  <small class="form-text text-muted"> {{ getError('discount') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Кол-во использований (оставти пустым если нужно
                    ∞):</label>
                  <div class="col-8">
                    <input name="company"
                           type="number"
                           class="form-control"
                           v-model="limit"
                           id="company">
                  </div>
                  <small class="form-text text-muted"> {{ getError('limit') }}</small>
                </div>
                <div class="row mb-3">
                  <label for="company" class="col-4 col-form-label">Активна:</label>
                  <div class="col-8">
                    <input name="company"
                           type="checkbox"
                           class="form-check-input"
                           v-model="isActive"
                           id="company">
                  </div>
                  <small class="form-text text-muted"> {{ getError('active') }}</small>
                </div>
                <div class="row massager">{{ massage }}</div>
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
import {mapGetters, mapActions} from 'vuex';

export default {
  name: "PromoCodeItem",
  props: {
    id: {
      type: [String],
      default: null,
    }
  },
  data() {
    return {
      newName: null,
      newIsPercent: null,
      newDiscount: null,
      newIsActive: false,
      newLimit: null,
      massage: null,
    }
  },
  computed: {
    ...mapGetters('appPromoCode', [
      'getError',
      'getPromoCodeItem',
    ]),
    name: {
      get: function () {
        if (this.newName === null) {
          return this.getPromoCodeItem.name;
        }
        return this.newName;
      },
      set: function (newValue) {
        this.newName = newValue;
      },
    },
    isPercent: {
      get: function () {
        if (this.newIsPercent === null) {
          return this.getPromoCodeItem.is_percent;
        }
        return this.newIsPercent;
      },
      set: function (newValue) {
        this.newIsPercent = newValue;
      },
    },
    isActive: {
      get: function () {
        if (this.newIsActive === null) {
          return this.getPromoCodeItem.active;
        }
        return this.newIsActive;
      },
      set: function (newValue) {
        this.newIsActive = newValue;
      },
    },
    discount: {
      get: function () {
        if (this.newDiscount === null) {
          return this.getPromoCodeItem.discount;
        }
        return this.newDiscount;
      },
      set: function (newValue) {
        this.newDiscount = newValue;
      },
    },
    limit: {
      get: function () {
        if (this.newLimit === null) {
          return this.getPromoCodeItem.limit;
        }
        return this.newLimit;
      },
      set: function (newValue) {
        this.newLimit = newValue;
      },
    },
  },
  methods: {
    ...mapActions('appPromoCode', [
      'sendSavePromoCode',
      'clearError'
    ]),
    back: function () {
      this.$router.push({name: 'PromoCodes'});
    },
    save: function () {
      let app = this;
      this.clearError();
      this.sendSavePromoCode({
        'id': this.id,
        'name': this.name,
        'discount': this.discount,
        'is_percent': this.isPercent === "true",
        'active': this.isActive,
        'limit': this.limit,
        'callback': function () {
          app.$router.push({name: 'PromoCodes'});
        }
      })
    }
  },
  created() {
    if (this.name === null) {
      document.title = "Создать новый промокод";
    } else {
      document.title = "Промокод " + this.name;
    }
    this.clearError();
  }
}
</script>

<style scoped>

</style>