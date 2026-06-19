<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useStore } from 'vuex';

import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

const store = useStore();

// Данные профиля (имя/телефон/город) — грузим с /api/user через стор.
const profile = reactive({ name: '', phone: '', city: '' });
const profileSaving = ref(false);
const profileMessage = ref('');
const profileError = ref('');

// Смена пароля.
const password = ref('');
const passwordConfirm = ref('');
const passwordSaving = ref(false);
const passwordMessage = ref('');
const passwordError = ref('');

const email = computed(() => store.getters['appUser/getEmail']);
const userInfo = computed(() => store.getters['appUser/getUserInfo']);

// Человекочитаемая роль для шапки ЛК.
const roleLabel = computed(() => {
    const i = userInfo.value || {};
    if (i.admin) return 'Администратор';
    if (i.manager) return 'Менеджер';
    if (i.seller) return 'Продавец';
    if (i.pusher) return 'Pusher (Friendly)';
    if (i.curator) return 'Куратор';
    if (i.pusherCurator) return 'Pusher + Куратор';
    return 'Пользователь';
});

onMounted(() => {
    store.dispatch('appUser/loadUserData', {
        callback: (data) => {
            profile.name = data?.name ?? '';
            profile.phone = data?.phone ?? '';
            profile.city = data?.city ?? '';
        }
    });
});

function saveProfile() {
    profileMessage.value = '';
    profileError.value = '';
    profileSaving.value = true;

    store.dispatch('appUser/editProfile', {
        name: profile.name,
        phone: profile.phone,
        city: profile.city,
        callback: (result) => {
            profileSaving.value = false;
            // Успех — строка-сообщение; ошибка — объект с полями.
            if (typeof result === 'string') {
                profileMessage.value = result;
            } else {
                profileError.value = 'Не удалось сохранить данные';
            }
        }
    });
}

function savePassword() {
    passwordMessage.value = '';
    passwordError.value = '';

    if (password.value.length < 6) {
        passwordError.value = 'Пароль должен быть не короче 6 символов';
        return;
    }
    if (password.value !== passwordConfirm.value) {
        passwordError.value = 'Пароли не совпадают';
        return;
    }

    passwordSaving.value = true;
    store.dispatch('appUser/editPassword', {
        password: password.value,
        password_confirmation: passwordConfirm.value,
        callback: (message) => {
            passwordSaving.value = false;
            password.value = '';
            passwordConfirm.value = '';
            passwordMessage.value = typeof message === 'string' && message ? message : 'Пароль изменён';
        }
    });
}

function logout() {
    store.dispatch('appUser/logOut');
}
</script>

<template>
    <div class="profile-page flex flex-col gap-6">
        <Card>
            <template #title>Личный кабинет</template>
            <template #subtitle>{{ email }} · {{ roleLabel }}</template>
            <template #content>
                <div class="flex justify-end">
                    <Button label="Выйти из системы" icon="pi pi-sign-out" severity="danger" outlined @click="logout" />
                </div>
            </template>
        </Card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
                <template #title>Данные профиля</template>
                <template #content>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label for="name">Имя</label>
                            <InputText id="name" v-model="profile.name" placeholder="Имя" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="phone">Телефон</label>
                            <InputText id="phone" v-model="profile.phone" placeholder="Телефон" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="city">Город</label>
                            <InputText id="city" v-model="profile.city" placeholder="Город" />
                        </div>

                        <Message v-if="profileMessage" severity="success" :closable="false">{{ profileMessage }}</Message>
                        <Message v-if="profileError" severity="error" :closable="false">{{ profileError }}</Message>

                        <div>
                            <Button label="Сохранить" icon="pi pi-check" :loading="profileSaving" @click="saveProfile" />
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Смена пароля</template>
                <template #content>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label for="password">Новый пароль</label>
                            <Password id="password" v-model="password" :feedback="false" toggleMask fluid placeholder="Минимум 6 символов" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="password-confirm">Повторите пароль</label>
                            <Password id="password-confirm" v-model="passwordConfirm" :feedback="false" toggleMask fluid placeholder="Повторите пароль" />
                        </div>

                        <Message v-if="passwordMessage" severity="success" :closable="false">{{ passwordMessage }}</Message>
                        <Message v-if="passwordError" severity="error" :closable="false">{{ passwordError }}</Message>

                        <div>
                            <Button label="Сменить пароль" icon="pi pi-lock" :loading="passwordSaving" @click="savePassword" />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
