<template>
  <div>
    <div v-for="(guest, index) in oldGuests" :key="index">
      <div class="d-flex flex-wrap">
        <div class="col-md-4">
          <label class="form-label">{{ guest.email }}</label>
          <div class="input-group">
            <input type="email"
                   v-model="newEmail[guest.id]"
                   placeholder="Введите новый email"
                   class="form-control">
          </div>
          <!-- Выводим ошибку для email -->
          <div class="messager text-danger" v-if="getErrorMessage('email', guest.id)">
            {{ getErrorMessage('email', guest.id) }}
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ guest.value }}</label>
          <div class="input-group">
            <input type="text"
                   v-model="newValue[guest.id]"
                   placeholder="Введите новый ФИО"
                   class="form-control">
          </div>
          <!-- Выводим ошибку для value -->
          <div class="messager text-danger" v-if="getErrorMessage('value', guest.id)">
            {{ getErrorMessage('value', guest.id) }}
          </div>
        </div>
      </div>
      <button @click="editTicket">Сменить билеты</button>
    </div>
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
      newEmail: [],   // будет объект { uuid: 'email@example.com' }
      newValue: [],   // будет объект { uuid: 'ФИО' }
      errors: null,   // объект ошибок с сервера
    };
  },
  methods: {
    ...mapActions('appOrder', ['sendNewTicket']),

    // Метод для получения текста ошибки по полю и UUID
    getErrorMessage(field, uuid) {
      if (!this.errors) return null;

      // Сначала ищем ошибку для конкретного поля с UUID
      const specificKey = `${field}.${uuid}`;
      if (this.errors[specificKey]) {
        const err = this.errors[specificKey];
        return Array.isArray(err) ? err[0] : err;
      }

      // Если нет, ищем общую ошибку для поля (например, 'value' или 'email')
      if (this.errors[field]) {
        const err = this.errors[field];
        return Array.isArray(err) ? err[0] : err;
      }

      return null;
    },

    editTicket() {
      const rawEmail = toRaw(this.newEmail);
      const rawValue = toRaw(this.newValue);
      const email = Object.fromEntries(Object.entries(rawEmail));
      const value = Object.fromEntries(Object.entries(rawValue));

      this.sendNewTicket({
        id: this.$route.params.id,
        value: value,
        email: email,
        callbackError: (errorObject) => {
          console.log('Ошибки с сервера:', errorObject);
          this.errors = errorObject; // сохраняем объект ошибок
        }
      });
    }
  }
}
</script>