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
      <h1>Форма подтверждения добровольного оргвзноса</h1>
      <small class="form-text text-muted">
        на создание туристического слёта Solar Systo Togathering 2026
      </small>
    </div>

    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">
            <div role="form">

              <!-- ШАГ 1: Данные покупателя -->
              <div class="pp1 row">
                <span>ШАГ 1.</span> Введи свои контактные данные, после чего система автоматически создаст тебе аккаунт:
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
                    <label class="hidder">Имя покупателя (для аккаунта)</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Имя покупателя"
                        v-model="name"
                    />
                    <small class="form-text text-muted">
                      Это имя для аккаунта получателя. Если ты тоже участвуешь — добавь себя отдельной карточкой гостя ниже.
                    </small>
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
                  :price-line="priceLines[index] || null"
                  :locked-live-mode="lockedLiveMode"
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

              <!-- Итоговая стоимость -->
              <div class="row itog-row mb-4" v-show="totalPrice !== null">
                <div class="col-12">
                  <h4 class="my-lg-2 font-weight-normal">
                    Итого к внесению:
                    <small class="text-muted">{{ totalPrice }} руб.</small>
                    <span
                        class="spinner-border spinner-border-sm ml-2"
                        role="status"
                        aria-hidden="true"
                        v-show="priceLoading"
                    ></span>
                  </h4>
                </div>
                <div class="col-12" v-if="totalDiscount > 0">
                  <h5 class="my-lg-1 font-weight-normal text-muted">
                    Общая скидка по промокодам: {{ totalDiscount }} руб.
                  </h5>
                </div>
              </div>

              <!-- ШАГ 3: Оплата -->
              <div class="pp1 row">
                <span>ШАГ 3.</span> Выбери способ оплаты, осуществи перевод и заполни данные о платеже!
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="form-group">
                    <label class="hidder">Способ оплаты: *</label>
                    <div class="in-choice">
                      <div
                          class="payment-choice"
                          v-for="typesOfPayment in getTypesOfPayment"
                          :key="typesOfPayment.id"
                      >
                        <div class="form-check">
                          <label class="form-check-label" :for="'pay-' + typesOfPayment.id">
                            <input
                                type="radio"
                                class="form-check-input"
                                v-model="selectTypesOfPayment"
                                :value="typesOfPayment.id"
                                :id="'pay-' + typesOfPayment.id"
                            />
                            <span>
                              <span v-html="typesOfPayment.name"></span>
                              <i
                                  class="copy-payment"
                                  title="Нажми, чтобы скопировать реквизиты"
                                  @click="copyTypesOfPayment(typesOfPayment.card)"
                              ></i>
                            </span>
                          </label>
                          <small class="form-text text-muted">{{ getError('types_of_payment_id') }}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="copy-btn">Нажми на <span></span> чтобы скопировать реквизиты</div>
                </div>
              </div>

              <div v-show="!selectTypesOfPaymentIsBilling">
                <div class="row flex-flex justify-content-center mt-7" style="color: var(--c-red); text-align: center; font-weight: bold;">
                  ТЕПЕРЬ СОВЕРШИ ПЕРЕВОД СРЕДСТВ САМОСТОЯТЕЛЬНО В ПРИЛОЖЕНИИ БАНКА
                </div>
                <div class="row mb-4 flex-flex justify-content-center" style="text-align: center; font-weight: bold;">
                  и только после этого заполни поля ниже
                </div>
                <div class="row mb-4 flex-flex">
                  <div class="col-3">
                    <label for="idBuy">Идентификатор платежа:</label>
                  </div>
                  <div class="col-3">
                    <input class="form-control" v-model="idBuy" id="idBuy" />
                  </div>
                  <div class="col-6">
                    <small class="form-text text-muted id-info">
                      При переводах на Сбербанк напиши сюда <b>последние 4 цифры номера карты</b>, с которой был сделан перевод
                    </small>
                  </div>
                </div>
                <div class="row mt-4">
                  <div class="col-3">
                    <label>Когда был сделан платеж?</label>
                  </div>
                  <div class="col-9 flex-flex">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Например: 18 февраля в 13.20"
                        v-model="date"
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-3">
                    <label>Комментарий к платежу:</label>
                  </div>
                  <div class="col-9">
                    <textarea class="form-control order-text" v-model="comment"></textarea>
                  </div>
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
                    После подтверждения перевода на твой e-mail придёт <strong>электронный билет с QR-кодом</strong><br>
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
import { ref, reactive, computed, watch, onMounted } from 'vue';
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

// --- Данные покупателя ---
const email = ref(null);
const phone = ref(null);
const city = ref(null);
const name = ref(null);
const comment = ref(null);
const date = ref(null);
const idBuy = ref(null);
const confirm = ref(false);
const preload = ref(false);
const message = ref(null);
const selectTypesOfPayment = ref(null);

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

// Разбивка цены по строкам (из /calculatePrice), в порядке guests[]
const priceLines = ref([]);
const totalPrice = ref(null);
const priceLoading = ref(false);
let priceDebounceTimer = null;

// --- Геттеры стора ---
const getTicketType = computed(() => store.getters['appFestivalTickets/getTicketType']);
const getTypesOfPayment = computed(() => store.getters['appFestivalTickets/getTypesOfPayment']);
const getError = computed(() => store.getters['appOrder/getError']);
const isAuth = computed(() => store.getters['appUser/isAuth']);
const getEmail = computed(() => store.getters['appUser/getEmail']);

/**
 * Выбранный способ оплаты — биллинговый (СБП и т.п.).
 */
const selectTypesOfPaymentIsBilling = computed(() => {
  if (selectTypesOfPayment.value === null) {
    return false;
  }
  const found = getTypesOfPayment.value?.find((p) => p.id === selectTypesOfPayment.value);
  return found?.is_billing ?? false;
});

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

const totalDiscount = computed(() =>
    priceLines.value.reduce((acc, line) => acc + (line?.discount || 0), 0),
);

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

/**
 * Собрать payload guests[] для бэка (склеить парковку, нормализовать промокод/опции).
 */
function buildGuestsPayload() {
  return guests.value.map((guest) => {
    const isParking = isParkingGuest(guest);
    const value = isParking
        ? [guest.carNumber, guest.carBrand, guest.driverName].map((v) => (v || '').trim()).join(' / ')
        : (guest.value || '').trim();

    const promo = (guest.promo_code || '').trim();

    return {
      value,
      email: (guest.email || '').trim(),
      ticket_type_id: guest.ticket_type_id,
      options: (guest.options || []).filter((o) => o.qty > 0),
      promo_code: promo.length > 0 ? promo : null,
    };
  });
}

/**
 * Проверка готовности одного гостя для live-расчёта/сабмита.
 */
function isGuestReady(guest) {
  if (!guest.ticket_type_id) {
    return false;
  }
  const email = (guest.email || '').trim();
  if (email.length === 0) {
    return false;
  }
  if (isParkingGuest(guest)) {
    return (
        (guest.carNumber || '').trim().length > 0 &&
        (guest.carBrand || '').trim().length > 0 &&
        (guest.driverName || '').trim().length > 0
    );
  }
  return (guest.value || '').trim().length > 0;
}

// --- Live-расчёт цены ---
function scheduleRecalc() {
  if (priceDebounceTimer) {
    clearTimeout(priceDebounceTimer);
  }
  priceDebounceTimer = setTimeout(recalcPrice, 400);
}

async function recalcPrice() {
  // Считаем только если каждый гость имеет тип билета (на бэке ticket_type_id обязателен)
  const ready = guests.value.length > 0 && guests.value.every((g) => Boolean(g.ticket_type_id) && (g.email || '').trim().length > 0);
  if (!ready) {
    priceLines.value = [];
    totalPrice.value = null;
    return;
  }

  priceLoading.value = true;
  try {
    const result = await store.dispatch('appOrder/calculatePrice', {
      body: {
        festival_id: FESTIVAL_ID,
        guests: buildGuestsPayload(),
      },
    });
    if (result.success) {
      priceLines.value = result.lines || [];
      totalPrice.value = result.totalPrice ?? null;
    } else {
      priceLines.value = [];
      totalPrice.value = null;
    }
  } finally {
    priceLoading.value = false;
  }
}

// Пересчитывать при любом изменении состава корзины
watch(
    guests,
    () => {
      scheduleRecalc();
    },
    { deep: true },
);

// Автовыбор первого способа оплаты, когда список загрузился
watch(
    getTypesOfPayment,
    (types) => {
      if (types && types.length > 0 && selectTypesOfPayment.value === null) {
        selectTypesOfPayment.value = types[0].id;
      }
    },
    { immediate: true },
);

// --- Валидация формы ---
const isFormValid = computed(() => {
  if (!email.value || !phone.value || !city.value) {
    return false;
  }
  if (!confirm.value) {
    return false;
  }
  if (selectTypesOfPayment.value === null) {
    return false;
  }
  if (guests.value.length === 0) {
    return false;
  }
  if (!guests.value.every(isGuestReady)) {
    return false;
  }
  // Не смешивать live + non-live
  if (!isLiveModeConsistent()) {
    return false;
  }
  // Для не-биллинговых способов — обязателен идентификатор и дата платежа
  if (!selectTypesOfPaymentIsBilling.value) {
    if (!date.value || date.value.length === 0) {
      return false;
    }
    if (!idBuy.value || idBuy.value.length === 0) {
      return false;
    }
  }
  return true;
});

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

const validationHint = computed(() => {
  if (!isLiveModeConsistent()) {
    return 'Нельзя смешивать живые и обычные билеты в одном заказе — оформите отдельным заказом.';
  }
  return 'Если кнопка не активна — проверь, все ли поля заполнены (у каждого гостя нужен тип билета и email).';
});

// --- Действия ---
function copyTypesOfPayment(card) {
  const area = document.createElement('textarea');
  document.body.appendChild(area);
  area.value = card;
  area.select();
  document.execCommand('copy');
  document.body.removeChild(area);
}

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
    types_of_payment_id: selectTypesOfPayment.value,
    guests: buildGuestsPayload(),
  };

  store.dispatch('appOrder/goToCreateOrderTicket', {
    body,
    callback: (success, msg) => {
      preload.value = false;
      if (success) {
        clearData();
        message.value = msg;
        document.getElementById('modalOpenBtn').click();
      } else {
        message.value = msg;
        document.getElementById('modalOpenBtn').click();
      }
    },
  });
}

function clearData() {
  guests.value = [makeGuest()];
  Object.keys(optionsByKey).forEach((k) => delete optionsByKey[k]);
  Object.keys(loadingOptionsByKey).forEach((k) => delete loadingOptionsByKey[k]);
  priceLines.value = [];
  totalPrice.value = null;
  comment.value = null;
  date.value = null;
  idBuy.value = null;
  confirm.value = false;
  email.value = isAuth.value ? getEmail.value : null;
}

// --- Инициализация ---
onMounted(async () => {
  await store.dispatch('appFestivalTickets/loadDataForOrderingTickets', {
    festival_id: FESTIVAL_ID,
  });
  await store.dispatch('appOrder/clearError');

  if (isAuth.value) {
    await store.dispatch('appUser/loadUserData', {
      callback: (data) => {
        phone.value = data.phone;
        city.value = data.city;
      },
    });
    email.value = getEmail.value;
  }
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

/* Согласие + кнопка: гарантируем нормальный поток (без наложения кнопки на текст) */
#check-check {
  position: static;
  text-align: left;
}

.reg-btn {
  position: static;
  margin-top: 0.5rem;
}
</style>
