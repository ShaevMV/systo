<template>
  <div class="card mb-3 guest-card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="font-weight-normal mb-0">Гость №{{ index + 1 }}</h5>
        <button
            v-if="removable"
            type="button"
            class="btn btn-sm btn-outline-danger"
            @click="$emit('remove')"
            title="Удалить гостя"
        >
          <i class="fa fa-trash"></i> Удалить
        </button>
      </div>

      <!-- Тип билета -->
      <div class="form-group">
        <label class="font-weight-bold">Тип оргвзноса: *</label>
        <div class="in-choice">
          <div
              class="ticket-choice"
              v-for="typeTicket in ticketTypes"
              :key="typeTicket.id"
          >
            <div class="form-check">
              <label class="form-check-label" :for="'tt-' + index + '-' + typeTicket.id">
                <input
                    type="radio"
                    class="form-check-input"
                    :id="'tt-' + index + '-' + typeTicket.id"
                    :value="typeTicket.id"
                    :checked="modelValue.ticket_type_id === typeTicket.id"
                    :disabled="isTicketTypeDisabled(typeTicket)"
                    @change="onSelectTicketType(typeTicket.id)"
                />
                <span class="intckt">
                  <p>{{ typeTicket.name }} / {{ typeTicket.price }} руб.</p>
                  <p v-if="typeTicket.description" v-html="typeTicket.description"></p>
                </span>
              </label>
            </div>
          </div>
        </div>
        <small
            class="form-text text-danger"
            v-if="liveLockHint"
        >{{ liveLockHint }}</small>
      </div>

      <!-- Опции (доп. услуги) -->
      <div class="form-group" v-if="options.length > 0">
        <label class="font-weight-bold">Дополнительные опции:</label>
        <div
            class="option-row d-flex align-items-center mb-2"
            v-for="option in options"
            :key="option.id"
        >
          <div class="form-check mr-3">
            <input
                class="form-check-input"
                type="checkbox"
                :id="'opt-' + index + '-' + option.id"
                :checked="optionQty(option.id) > 0"
                @change="toggleOption(option)"
            />
            <label class="form-check-label" :for="'opt-' + index + '-' + option.id">
              {{ option.name }} / {{ option.price }} руб.
              <small v-if="option.description" class="text-muted d-block" v-html="option.description"></small>
            </label>
          </div>
          <div class="qty-control ml-auto" v-if="optionQty(option.id) > 0">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="changeQty(option, -1)">−</button>
            <span class="mx-2">{{ optionQty(option.id) }}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="changeQty(option, 1)">+</button>
          </div>
        </div>
      </div>
      <div class="form-group" v-else-if="loadingOptions">
        <small class="text-muted">Загрузка опций…</small>
      </div>

      <!-- Данные гостя: обычный билет -->
      <template v-if="!isParking">
        <div class="form-group">
          <label class="font-weight-bold">Имя и Фамилия гостя: *</label>
          <input
              type="text"
              class="form-control"
              placeholder="Имя и Фамилия"
              :value="modelValue.value"
              @input="updateField('value', $event.target.value)"
          />
        </div>
        <div class="form-group">
          <label class="font-weight-bold">Email гостя: *</label>
          <input
              type="email"
              class="form-control"
              placeholder="Email (на него придёт анкета)"
              :value="modelValue.email"
              @input="updateField('email', $event.target.value)"
          />
          <small class="form-text text-muted">На этот email придёт ссылка на анкету.</small>
        </div>
      </template>

      <!-- Данные гостя: парковка -->
      <template v-else>
        <div class="form-group">
          <label class="font-weight-bold">Гос. номер: *</label>
          <input
              type="text"
              class="form-control"
              placeholder="Например, А123АА777"
              :value="modelValue.carNumber"
              @input="updateParkingField('carNumber', $event.target.value)"
          />
        </div>
        <div class="form-group">
          <label class="font-weight-bold">Марка автомобиля: *</label>
          <input
              type="text"
              class="form-control"
              placeholder="Марка автомобиля"
              :value="modelValue.carBrand"
              @input="updateParkingField('carBrand', $event.target.value)"
          />
        </div>
        <div class="form-group">
          <label class="font-weight-bold">ФИО водителя: *</label>
          <input
              type="text"
              class="form-control"
              placeholder="ФИО водителя"
              :value="modelValue.driverName"
              @input="updateParkingField('driverName', $event.target.value)"
          />
        </div>
        <div class="form-group">
          <label class="font-weight-bold">Email водителя: *</label>
          <input
              type="email"
              class="form-control"
              placeholder="Email для отправки анкеты"
              :value="modelValue.email"
              @input="updateField('email', $event.target.value)"
          />
        </div>
      </template>

      <!-- Промокод гостя -->
      <div class="form-group">
        <label class="font-weight-bold">Промокод (необязательно):</label>
        <input
            type="text"
            class="form-control"
            placeholder="Промокод"
            :value="modelValue.promo_code"
            @input="updateField('promo_code', $event.target.value)"
        />
      </div>

      <!-- Разбивка цены строки -->
      <div class="line-price text-muted" v-if="priceLine">
        <small>
          Билет: {{ priceLine.basePrice }} руб.
          <template v-if="priceLine.optionsSum > 0"> + опции: {{ priceLine.optionsSum }} руб.</template>
          <template v-if="priceLine.discount > 0"> − скидка: {{ priceLine.discount }} руб.</template>
          = <b>{{ priceLine.total }} руб.</b>
        </small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  index: { type: Number, required: true },
  modelValue: { type: Object, required: true },
  ticketTypes: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  loadingOptions: { type: Boolean, default: false },
  removable: { type: Boolean, default: true },
  priceLine: { type: Object, default: null },
  // Тип билета (live/non-live), на который заблокирован весь заказ. null — свободно.
  lockedLiveMode: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'select-ticket-type', 'remove']);

/**
 * Выбранный тип билета (полный объект) — нужен для признака парковки.
 */
const selectedTicketType = computed(() =>
    props.ticketTypes.find((t) => t.id === props.modelValue.ticket_type_id) ?? null,
);

const isParking = computed(() => selectedTicketType.value?.isParking === true);

/**
 * Блокировка смешивания live + non-live в одном заказе.
 * Если в заказе уже зафиксирован режим (по первому гостю с типом), типы другого режима недоступны.
 */
function isTicketTypeDisabled(typeTicket) {
  if (props.lockedLiveMode === null) {
    return false;
  }
  // Тип этой же карточки всегда доступен (чтобы можно было снять выбор)
  if (typeTicket.id === props.modelValue.ticket_type_id) {
    return false;
  }
  return Boolean(typeTicket.isLiveTicket) !== Boolean(props.lockedLiveMode.isLiveTicket);
}

const liveLockHint = computed(() => {
  if (props.lockedLiveMode === null) {
    return '';
  }
  return props.lockedLiveMode.isLiveTicket
      ? 'В заказе уже есть живой билет — остальные гости только с живыми билетами.'
      : 'В заказе есть обычные билеты — живые билеты оформите отдельным заказом.';
});

function emitUpdate(patch) {
  emit('update:modelValue', { ...props.modelValue, ...patch });
}

function updateField(field, value) {
  emitUpdate({ [field]: value });
}

/**
 * Поля парковки склеиваются в value уже на уровне родителя при сабмите,
 * здесь храним их по отдельности в той же модели гостя.
 */
function updateParkingField(field, value) {
  emitUpdate({ [field]: value });
}

function onSelectTicketType(ticketTypeId) {
  // При смене типа сбрасываем опции (они привязаны к типу билета)
  emitUpdate({ ticket_type_id: ticketTypeId, options: [] });
  emit('select-ticket-type', { index: props.index, ticketTypeId });
}

function optionQty(optionId) {
  const found = (props.modelValue.options || []).find((o) => o.option_id === optionId);
  return found ? found.qty : 0;
}

function setOptions(nextOptions) {
  emitUpdate({ options: nextOptions });
}

function toggleOption(option) {
  const current = [...(props.modelValue.options || [])];
  const idx = current.findIndex((o) => o.option_id === option.id);
  if (idx === -1) {
    current.push({ option_id: option.id, qty: 1 });
  } else {
    current.splice(idx, 1);
  }
  setOptions(current);
}

function changeQty(option, delta) {
  const current = [...(props.modelValue.options || [])];
  const idx = current.findIndex((o) => o.option_id === option.id);
  if (idx === -1) {
    if (delta > 0) {
      current.push({ option_id: option.id, qty: 1 });
    }
  } else {
    const nextQty = current[idx].qty + delta;
    if (nextQty <= 0) {
      current.splice(idx, 1);
    } else {
      current[idx] = { ...current[idx], qty: nextQty };
    }
  }
  setOptions(current);
}
</script>

<style scoped>
.guest-card {
  border: 1px solid #e3e6ea;
}

.line-price {
  margin-top: 8px;
  border-top: 1px dashed #e3e6ea;
  padding-top: 8px;
}

.qty-control {
  white-space: nowrap;
}
</style>
