<template>
  <div>
    <div v-for="(guest, index) in oldGuests" :key="index" class="mb-3 pb-3 border-bottom">
      <div class="d-flex flex-wrap gap-3">
        <div class="col-md-4">
          <label class="form-label text-muted small">Текущий email: <strong>{{ guest.email }}</strong></label>
          <input type="email"
                 v-model="newEmail[guest.id]"
                 placeholder="Введите новый email"
                 class="form-control">
          <div class="text-danger small mt-1" v-if="getErrorMessage('email', guest.id)">
            {{ getErrorMessage('email', guest.id) }}
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted small">Текущее ФИО: <strong>{{ guest.value }}</strong></label>
          <input type="text"
                 v-model="newValue[guest.id]"
                 placeholder="Введите новое ФИО"
                 class="form-control">
          <div class="text-danger small mt-1" v-if="getErrorMessage('value', guest.id)">
            {{ getErrorMessage('value', guest.id) }}
          </div>
        </div>
      </div>
    </div>
    <button class="btn btn-primary mt-2" @click="editTicket">Сменить билеты</button>
  </div>
</template>

<script>
import { mapActions } from "vuex";
import { toRaw } from 'vue';

export default {
  name: "NewTicket",
  props: ['oldGuests'],
  data() {
    return {
      newEmail: {},
      newValue: {},
      errors: null,
    };
  },
  methods: {
    ...mapActions('appOrder', ['sendNewTicket']),

    getErrorMessage(field, uuid) {
      if (!this.errors) return null;

      const specificKey = `${field}.${uuid}`;
      if (this.errors[specificKey]) {
        const err = this.errors[specificKey];
        return Array.isArray(err) ? err[0] : err;
      }

      if (this.errors[field]) {
        const err = this.errors[field];
        return Array.isArray(err) ? err[0] : err;
      }

      return null;
    },

    editTicket() {
      this.errors = null;
      const email = Object.fromEntries(Object.entries(toRaw(this.newEmail)));
      const value = Object.fromEntries(Object.entries(toRaw(this.newValue)));

      this.sendNewTicket({
        id: this.$route.params.id,
        value,
        email,
        callbackError: (errorObject) => {
          this.errors = errorObject;
        }
      });
    }
  }
}
</script>
