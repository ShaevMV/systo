<template>
  <span class="date-display">
    {{ formattedDate }}
  </span>
</template>

<script>
import dayjs from 'dayjs'
import 'dayjs/locale/ru'

export default {
  name: "DateFormat",
  props: ['date'],
  computed: {
    formattedDate() {
      return dayjs(this.date).locale('ru').format('D MMMM YYYY, HH:mm')
    }
  }
}
</script>

<style scoped>
.date-display {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #666;
  font-size: 0.9em;
}

.icon {
  width: 16px;
  height: 16px;
}
</style>