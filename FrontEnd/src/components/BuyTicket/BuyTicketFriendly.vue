<template>
  <div class="container-fluid">
    <!-- Кнопка-триггер модалки успеха (скрыта) -->
    <button
        type="button"
        class="btn btn-primary"
        v-show="false"
        data-toggle="modal"
        id="modalOpenBtn"
        data-target="#exampleModal"
    >
      Launch demo modal
    </button>

    <!-- Модалка успеха -->
    <div
        class="modal fade"
        id="exampleModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true"
    >
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Успех</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">х</span>
            </button>
          </div>
          <div class="modal-body" v-html="message"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center title-block">
      <h1>Форма подтверждения дружеского оргвзноса</h1>
      <small class="form-text text-muted">
        на создание туристического слёта Solar Systo Togathering 2026
      </small>
    </div>

    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">
            <div role="form">

              <!-- ШАГ 1: Данные получателя -->
              <div class="pp1 row">
                <span>ШАГ 1.</span> Введи контактные данные получателя билетов (на этот email система создаст аккаунт):
              </div>
              <div class="row y-row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="hidder">Email *</label>
                    <input
                        type="email"
                        class="form-control"
                        placeholder="Email: *"
                        v-model="email"
                    />
                    <small class="form-text text-muted">{{ getError('email') }}</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="hidder">Телефон *</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Телефон: *"
                        v-model="phone"
                    />
                    <small class="form-text text-muted">{{ getError('phone') }}</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="hidder">Город *</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Город: *"
                        v-model="city"
                    />
                    <small class="form-text text-muted">{{ getError('city') }}</small>
                  </div>
                </div>
              </div>
              <div class="row y-row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="hidder">Имя получателя (для аккаунта)</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Имя получателя"
                        v-model="name"
                    />
                  </div>
                </div>
              </div>

              <!-- ШАГ 2: Корзина гостей -->
              <div class="pp1 row">
                <span>ШАГ 2.</span> Добавь гостей. Для каждого выбери тип оргвзноса, опции и при необходимости промокод:
              </div>

              <GuestCard
                  v-for="(guest, index) in guests"
                  :key="guest._key"
                  :index="index"
                  :model-value="guest"
                  :ticket-types="getTicketType"
                  :options="optionsByKey[guest._key] || []"
                  :loading-options="loadingOptionsByKey[guest._key] === true"
                  :removable="guests.length > 1"
                  :locked-live-mode="lockedLiveMode"
                  :allow-live-number="true"
                  @update:model-value="updateGuest(index, $event)"
                  @select-ticket-type="onGuestSelectTicketType"
                  @remove="removeGuest(index)"
              />

              <small class="form-text text-danger" v-if="getError('guests')">{{ getError('guests') }}</small>

              <div class="row mb-3">
                <div class="col-12">
                  <button
                      type="button"
                      class="btn btn-outline-primary"
                      @click="addGuest"
                      :disabled="guests.length >= MAX_GUESTS"
                  >
                    <i class="fa fa-plus"></i> Добавить гостя
                  </button>
                  <span class="ml-3 text-muted">Гостей в заказе: {{ guests.length }} / {{ MAX_GUESTS }}</span>
                </div>
              </div>

              <div class="row sub-warn">
                <b>ВНИМАНИЕ!</b> После оформления заказа на почту каждого гостя придёт ссылка на анкету,
                которую необходимо заполнить, чтобы активировать QR-код и получить доступ к закрытому чату
                гостей Solar Systo Togathering 2026.
              </div>

              <!-- ШАГ 3: Итоговая стоимость заказа (вводит пушер) -->
              <div class="row itog-row mb-4">
                <div class="col-12">
                  <h4 class="my-lg-2 font-weight-normal">
                    Внесите <b>итоговую стоимость заказа</b> (за вычетом вашей комиссии):
                    <input
                        type="number"
                        min="0"
                        class="form-control"
                        placeholder="Итоговая стоимость заказа"
                        v-model="price"
                    />
                  </h4>
                  <small class="form-text text-muted" v-if="guests.length > 1 && Number(price) > 0">
                    Сумма распределится по гостям: ≈ {{ Math.floor(Number(price) / guests.length) }} руб. на билет
                    (остаток — первому гостю).
                  </small>
                </div>
              </div>

              <!-- Согласие + сабмит -->
              <div class="row" style="justify-content: center">
                <div class="col-12 mb-3">
                  <div class="form-check" id="check-check">
                    <input class="form-check-input" type="checkbox" v-model="confirm" id="defaultCheck1" />
                    <label class="form-check-label" for="defaultCheck1">
                      Регистрируя добровольный оргвзнос, ты соглашаешься с&nbsp;<a href="/conditions" target="_blank"><b>Правилами и условиями участия в туристическом слёте</b></a>
                      и <a href="/private" target="_blank"><b>Политикой обработки персональных данных.</b></a>
                    </label>
                  </div>
                </div>
                <div class="col-12">
                  <button
                      type="button"
                      :disabled="preload || !isFormValid"
                      @click="orderTicket"
                      class="btn btn-lg btn-block btn-outline-primary reg-btn"
                  >
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-show="preload"></span>
                    Зарегистрировать оргвзнос
                  </button>
                </div>
              </div>
              <div class="row justify-content-center" v-if="!isFormValid" style="text-align: center">
                {{ validationHint }}
              </div>

              <div class="row mt-4" id="sub-order">
                <div class="after-order">
                  <p>
                    После подтверждения перевода на e-mail гостя придёт <strong>электронный билет с QR-кодом</strong><br>
                    для входа на Solar Systo Togathering 2026! А также ссылка на анкету для добавления в новый закрытый чат.
                  </p>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useRoute } from 'vue-router';
import GuestCard from '@/components/BuyTicket/GuestCard.vue';

const props = defineProps({
  userId: { type: String, default: null },
});

const store = useStore();
const route = useRoute();

const FESTIVAL_ID = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
const MAX_GUESTS = 10;

// --- Данные получателя ---
const email = ref(null);
const phone = ref(null);
const city = ref(null);
const name = ref(null);
const comment = ref(null);
const confirm = ref(false);
const preload = ref(false);
const message = ref(null);

// Ручной итог заказа (за вычетом комиссии пушера). Распределяется по гостям на бэке.
const price = ref(0);

// --- Корзина гостей ---
let guestKeySeq = 0;

function makeGuest() {
  guestKeySeq += 1;
  return {
    _key: 'g' + guestKeySeq,
    ticket_type_id: null,
    value: '',
    email: '',
    promo_code: '',
    options: [],
    number: '',
    // парковочные поля (склеиваются в value при сабмите)
    carNumber: '',
    carBrand: '',
    driverName: '',
  };
}

const guests = ref([makeGuest()]);

// Опции и индикатор загрузки опций — по ключу гостя
const optionsByKey = reactive({});
const loadingOptionsByKey = reactive({});

// --- Геттеры стора ---
const getTicketType = computed(() => store.getters['appFestivalTickets/getTicketType']);
const getError = computed(() => store.getters['appOrder/getError']);

/**
 * Зафиксированный режим заказа (live / non-live) по первому гостю с выбранным типом.
 * null — пока ни один тип не выбран (смешивать свободно).
 */
const lockedLiveMode = computed(() => {
  for (const guest of guests.value) {
    if (guest.ticket_type_id) {
      const tt = getTicketType.value?.find((t) => t.id === guest.ticket_type_id);
      if (tt) {
        return { isLiveTicket: Boolean(tt.isLiveTicket) };
      }
    }
  }
  return null;
});

// --- Работа с гостями ---
function addGuest() {
  if (guests.value.length >= MAX_GUESTS) {
    return;
  }
  guests.value.push(makeGuest());
}

function removeGuest(index) {
  const removed = guests.value[index];
  guests.value.splice(index, 1);
  if (removed) {
    delete optionsByKey[removed._key];
    delete loadingOptionsByKey[removed._key];
  }
}

function updateGuest(index, nextGuest) {
  guests.value.splice(index, 1, nextGuest);
}

/**
 * При выборе типа билета в карточке — подгрузить активные опции этого типа.
 */
async function onGuestSelectTicketType({ ticketTypeId, index }) {
  const guest = guests.value[index];
  if (!guest) {
    return;
  }
  const key = guest._key;
  optionsByKey[key] = [];
  loadingOptionsByKey[key] = true;
  try {
    const list = await store.dispatch('appOrder/loadOptionsForTicketType', { ticketTypeId });
    optionsByKey[key] = list;
  } finally {
    loadingOptionsByKey[key] = false;
  }
}

/**
 * Признак: гость — парковочный (для склейки value перед отправкой).
 */
function isParkingGuest(guest) {
  const tt = getTicketType.value?.find((t) => t.id === guest.ticket_type_id);
  return tt?.isParking === true;
}

function isLiveGuest(guest) {
  const tt = getTicketType.value?.find((t) => t.id === guest.ticket_type_id);
  return tt?.isLiveTicket === true;
}

/**
 * Собрать payload guests[] для бэка (склеить парковку, нормализовать промокод/опции/номер).
 */
function buildGuestsPayload() {
  return guests.value.map((guest) => {
    const isParking = isParkingGuest(guest);
    const value = isParking
        ? [guest.carNumber, guest.carBrand, guest.driverName].map((v) => (v || '').trim()).join(' / ')
        : (guest.value || '').trim();

    const promo = (guest.promo_code || '').trim();
    const number = Number(guest.number) > 0 ? Number(guest.number) : null;

    return {
      value,
      email: (guest.email || '').trim(),
      ticket_type_id: guest.ticket_type_id,
      options: (guest.options || []).filter((o) => o.qty > 0),
      promo_code: promo.length > 0 ? promo : null,
      number,
    };
  });
}

/**
 * Проверка готовности одного гостя для сабмита.
 */
function isGuestReady(guest) {
  if (!guest.ticket_type_id) {
    return false;
  }
  if ((guest.email || '').trim().length === 0) {
    return false;
  }
  if (isParkingGuest(guest)) {
    if (
        (guest.carNumber || '').trim().length === 0 ||
        (guest.carBrand || '').trim().length === 0 ||
        (guest.driverName || '').trim().length === 0
    ) {
      return false;
    }
  } else if ((guest.value || '').trim().length === 0) {
    return false;
  }
  // Для живых билетов пушер обязан указать номер конверта.
  if (isLiveGuest(guest) && !(Number(guest.number) > 0)) {
    return false;
  }
  return true;
}

function isLiveModeConsistent() {
  const modes = guests.value
      .map((g) => getTicketType.value?.find((t) => t.id === g.ticket_type_id))
      .filter(Boolean)
      .map((t) => Boolean(t.isLiveTicket));
  if (modes.length === 0) {
    return true;
  }
  return modes.every((m) => m === modes[0]);
}

// --- Валидация формы ---
const isFormValid = computed(() => {
  if (!email.value || !phone.value || !city.value) {
    return false;
  }
  if (!confirm.value) {
    return false;
  }
  if (guests.value.length === 0) {
    return false;
  }
  if (!guests.value.every(isGuestReady)) {
    return false;
  }
  if (!isLiveModeConsistent()) {
    return false;
  }
  if (!(Number(price.value) > 0)) {
    return false;
  }
  return true;
});

const validationHint = computed(() => {
  if (!isLiveModeConsistent()) {
    return 'Нельзя смешивать живые и обычные билеты в одном заказе — оформите отдельным заказом.';
  }
  if (!(Number(price.value) > 0)) {
    return 'Укажи итоговую стоимость заказа (больше 0).';
  }
  return 'Если кнопка не активна — проверь, все ли поля заполнены (у каждого гостя нужен тип билета и email, у живых — номер конверта).';
});

// --- Действия ---
function orderTicket() {
  preload.value = true;

  const invite = props.userId || route.params.userId || null;

  const body = {
    email: email.value,
    phone: phone.value,
    city: city.value,
    name: name.value || '',
    comment: comment.value,
    invite: invite,
    festival_id: FESTIVAL_ID,
    price: Number(price.value),
    guests: buildGuestsPayload(),
  };

  store.dispatch('appOrder/goToCreateFrendlyOrderTicket', {
    body,
    callback: (success, msg) => {
      preload.value = false;
      message.value = msg;
      if (success) {
        clearData();
      }
      document.getElementById('modalOpenBtn').click();
    },
  });
}

function clearData() {
  guests.value = [makeGuest()];
  Object.keys(optionsByKey).forEach((k) => delete optionsByKey[k]);
  Object.keys(loadingOptionsByKey).forEach((k) => delete loadingOptionsByKey[k]);
  price.value = 0;
  comment.value = null;
  confirm.value = false;
  email.value = null;
  phone.value = null;
  city.value = null;
  name.value = null;
}

// --- Инициализация ---
onMounted(async () => {
  await store.dispatch('appFestivalTickets/loadDataForOrderingTickets', {
    festival_id: FESTIVAL_ID,
  });
  await store.dispatch('appOrder/clearError');
});
</script>

<style scoped>
body {
  font-family: 'Lato', sans-serif;
}

h1 {
  margin-bottom: 40px;
}

label {
  color: #333;
}

.card {
  margin-left: 10px;
  margin-right: 10px;
}

#check-check {
  position: static;
  text-align: left;
}

.reg-btn {
  position: static;
  margin-top: 0.5rem;
}
</style>
