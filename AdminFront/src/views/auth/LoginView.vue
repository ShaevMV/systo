<script setup>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';
import { useRoute, useRouter } from 'vue-router';

import FloatingConfigurator from '@/components/FloatingConfigurator.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

const store = useStore();
const route = useRoute();
const router = useRouter();

const email = ref('');
const password = ref('');
const loading = ref(false);

// Ошибки логина приходят в стор через мутацию setError (как в старом UserModule).
const mainError = computed(() => store.getters['appUser/getError']('main'));
const emailError = computed(() => store.getters['appUser/getError']('email'));
const passwordError = computed(() => store.getters['appUser/getError']('password'));

function onSubmit() {
    store.dispatch('appUser/clearError');
    loading.value = true;

    store.dispatch('appUser/toLogin', {
        email: email.value,
        password: password.value,
        // toLogin зовёт callback только при успехе; иначе кладёт ошибки в стор.
        callback: () => {
            loading.value = false;
            const nextUrl = route.query.nextUrl;
            // Пускаем редирект только на относительный same-origin путь.
            if (typeof nextUrl === 'string' && nextUrl.startsWith('/')) {
                router.push(nextUrl);
            } else {
                router.push('/admin/qr-orders');
            }
        }
    });

    // Если логин упал — снимаем спиннер (callback не вызовется).
    setTimeout(() => {
        loading.value = false;
    }, 4000);
}
</script>

<template>
    <FloatingConfigurator />
    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20" style="border-radius: 53px">
                    <div class="text-center mb-8">
                        <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">Systo · Админка</div>
                        <span class="text-muted-color font-medium">Войдите для продолжения</span>
                    </div>

                    <form @submit.prevent="onSubmit">
                        <label for="email" class="block text-surface-900 dark:text-surface-0 text-xl font-medium mb-2">Email</label>
                        <InputText id="email" v-model="email" type="email" placeholder="Email" class="w-full md:w-[30rem] mb-2" :invalid="!!emailError" />
                        <small v-if="emailError" class="block text-red-500 mb-4">{{ emailError }}</small>
                        <div v-else class="mb-6"></div>

                        <label for="password" class="block text-surface-900 dark:text-surface-0 font-medium text-xl mb-2">Пароль</label>
                        <Password id="password" v-model="password" placeholder="Пароль" :toggleMask="true" :feedback="false" class="mb-2" fluid :invalid="!!passwordError" />
                        <small v-if="passwordError" class="block text-red-500 mb-4">{{ passwordError }}</small>
                        <div v-else class="mb-6"></div>

                        <Message v-if="mainError" severity="error" class="mb-4">{{ mainError }}</Message>

                        <Button type="submit" label="Войти" class="w-full" :loading="loading" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
