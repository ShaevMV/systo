<template>
  <div class="container-fluid">
    <div class="text-center title-block">
      <h1>Странница получение персональной ссылке</h1>
      <small class="form-text text-muted">на создание туристического слёта Solar Systo Togathering 2026</small>
    </div>
    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">
            <div v-html="message"></div>
            <div v-if="link !== null">
              <a :href="link" target="_blank">Ссылка для покупки билета</a>
              <span>
                                {{ link }}
                                <i
                                    class="copy-payment"
                                    title="Нажми, чтобы скопировать"
                                    @click="CopyTypesOfPayment(link)"
                                ></i>
                              </span>
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
  name: "InviteLink",
  data() {
    return {
      message: null,
      link: null,
    }
  },
  computed: {
    ...mapGetters('appOrder', [
      'getInviteLink',
    ]),
  },
  methods: {
    ...mapActions('appOrder', [
      'pullInviteLink',
    ]),
    CopyTypesOfPayment: function (name) {
      let area = document.createElement('textarea');
      document.body.appendChild(area);
      area.value = name;
      area.select();
      document.execCommand('copy');
      document.body.removeChild(area);
    },
  },
  async created() {
    let self = this;
    await this.pullInviteLink({
      callback: function (response) {
        self.message = response.message;
        self.link = response.link;
      }
    });
  }
}
</script>

<style scoped>

</style>