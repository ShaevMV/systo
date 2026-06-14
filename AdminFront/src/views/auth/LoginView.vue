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
    <div class="login-screen bg-surface-50 dark:bg-surface-900 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <!-- Брендовый декор: спутник и НЛО лёгкими акцентами по углам, не мешают форме -->
        <img src="/img/brand/sputnik.webp" alt="" aria-hidden="true" class="brand-decor brand-decor--sputnik" />
        <img src="/img/brand/ufo.webp" alt="" aria-hidden="true" class="brand-decor brand-decor--ufo" />
        <div class="flex flex-col items-center justify-center relative z-10">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, transparent 30%)">
                <div class="w-full bg-surface-0 dark:bg-surface-800 py-20 px-8 sm:px-20" style="border-radius: 53px">
                    <div class="text-center mb-8">
                        <img src="/img/logo-solarsysto-2026.webp" alt="Solar Systo" class="login-logo mx-auto mb-5" />
                        <div class="font-display text-surface-900 dark:text-surface-0 text-3xl mb-2">SOLAR SYSTO</div>
                        <div class="text-muted-color text-lg mb-4">Админка</div>
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

<style scoped>
.login-logo {
    width: auto;
    height: 4.5rem;
    object-fit: contain;
}

.login-screen {
    position: relative;
}

/* Брендовый декор: тонкие фоновые акценты по углам.
   pointer-events: none — клики проходят сквозь, форму не перекрывают. */
.brand-decor {
    position: absolute;
    pointer-events: none;
    user-select: none;
    z-index: 0;
    opacity: 0.12;
    filter: grayscale(0.2);
}

/* В тёмной теме декор белый и заметнее, в светлой — приглушаем сильнее. */
:global(.app-dark) .brand-decor {
    opacity: 0.16;
}

.brand-decor--sputnik {
    top: 8%;
    left: 7%;
    width: clamp(80px, 12vw, 160px);
    transform: rotate(-12deg);
}

.brand-decor--ufo {
    bottom: 9%;
    right: 8%;
    width: clamp(90px, 14vw, 190px);
    transform: rotate(6deg);
}

/* На мобильных декор мельче и ещё деликатнее, чтобы не теснить форму. */
@media (max-width: 640px) {
    .brand-decor {
        opacity: 0.08;
    }

    .brand-decor--sputnik {
        width: 70px;
        top: 4%;
        left: 4%;
    }

    .brand-decor--ufo {
        width: 80px;
        bottom: 4%;
        right: 4%;
    }
}
</style>
