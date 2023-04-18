<template>
    <div class="row" id="filter">
        <div class="col-lg-12 mx-auto mb-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Фильтр</h5>

                    <div class="d-flex flex-wrap">
                        <div class="col-md-4">
                            <label for="validationDefault01" class="form-label">Тип оргвзноса</label>
                            <select class="form-select"
                                    v-model="typeOrder"
                                    id="validationDefault01">
                                <option value=null>Выберите тип оргвзноса</option>
                                <option v-for="(typeTickets) in getTicketType"
                                        v-bind:key="typeTickets.price"
                                        v-bind:value="typeTickets">{{ typeTickets.name }} /
                                    {{ typeTickets.price }} руб.
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="validationDefault01" class="form-label">Способ покупки билета</label>
                            <select class="form-select"
                                    v-model="typesOfPayment"
                                    id="validationDefault01">
                                <option value=null>Выберите способ покупки билета</option>
                                <option v-for="(typesOfPayment) in getTypesOfPayment"
                                        v-bind:key="typesOfPayment.id"
                                        v-bind:value="typesOfPayment.id">{{ typesOfPayment.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="validationDefaultUsername" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text" id="inputGroupPrepend2">@</span>
                                <input type="text"
                                       v-model="email"
                                       class="form-control"
                                       id="validationDefaultUsername"
                                       aria-describedby="inputGroupPrepend2">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="validationDefault01" class="form-label">Статус заказа</label>
                            <select class="form-select"
                                    v-model="status"
                                    id="validationDefault01">
                                <option value=null>Выберите статус заказа</option>
                                <option value="new">Новый</option>
                                <option value="paid">Оплаченный</option>
                                <option value="cancel">Отменёный</option>
                                <option value="difficulties_arose">Возникли трудности</option>
                            </select>
                        </div>

                        <!-- промо код -->
                        <div class="col-md-4">
                            <label for="validationDefault03" class="form-label">Промо код</label>
                            <input type="text"
                                   v-model="promoCode"
                                   class="form-control"
                                   id="validationDefault03">
                        </div>
                    </div>

                    <div class="row b-row mt-2">
                        <button class="btn btn-primary"
                                @click="sendFilter"
                                type="submit">Применить фильтр
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
</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
    name: "FilterOrder",
    data() {
        return {
            email: null,
            typeOrder: null,
            status: null,
            promoCode: null,
            typesOfPayment: null,
        }
    },
    computed: {
        ...mapGetters('appFestivalTickets', [
            'getTypesOfPayment',
            'getTicketType',
        ]),
        /*typeOrder: {
            get: function () {
              return this.price;
            },
            set: function (newValue) {
                this.price = newValue.price;
                this.typePrice = newValue.id;

                console.log(newValue);
            },
        }*/
    },
    methods: {
        ...mapActions('appFestivalTickets', [
            'loadDataForOrderingTickets',
            'getListPriceFor',
        ]),
        ...mapActions('appOrder', [
            'getOrderListForAdmin',
        ]),
        /**
         * Отправить данные для фильтра
         */
        sendFilter: function () {
            this.getOrderListForAdmin({
                'price': this.typeOrder.price,
                'typePrice': this.typeOrder.id,
                'email': this.email,
                'status': this.status,
                'promoCode': this.promoCode,
                'typesOfPayment': this.typesOfPayment,
            });
        },
        clearFilter: function () {
            this.typePrice = null;
            this.price = null;
            this.email = null;
            this.status = null;
            this.promoCode = null;
            this.typesOfPayment = null;
            this.getOrderListForAdmin();
        }
    },
    async created() {
        await this.loadDataForOrderingTickets();
        await this.getListPriceFor();
    },
}
</script>

<style scoped>

</style>
