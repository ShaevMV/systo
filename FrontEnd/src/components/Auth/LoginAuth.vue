<template>
    <div class="container">
        <div class="text-center title-block">
            <h1>Авторизация</h1>
        </div>
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card mt-2 mx-auto">
                    <div class="card-body">

                        <div class="messager">{{ getError('email') }}</div>

                        <p class="pp1"><strong>Внимание!</strong> Твой пароль придёт вам в первом письме после регистрации оргвзноса.<br>
                            Либо ты можешь создать свой аккаунт отдельно, нажав на кнопку <a href=""><b>Зарегистрироваться</b></a></p>
                        <div class="container">
                            <div id="contact-form" role="form">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="form_email" class="required hidder">Ваш логин</label>
                                                <input id="form_email"
                                                       type="email"
                                                       name="email"
                                                       class="form-control"
                                                       placeholder="Введи свой e-mail: *"
                                                       required="required"
                                                       v-model="email"
                                                       data-error="Введи свою почту!">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="form_password" class="required hidder">Ваш пароль</label>
                                                <input id="form_password"
                                                       type="password"
                                                       name="email"
                                                       class="form-control"
                                                       placeholder="Введи свой пароль: *"
                                                       required="required"
                                                       v-model="password"
                                                       data-error="Введи свой пароль!">

                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <button type="button"
                                                    @click="auth"
                                                    class="btn btn-lg btn-block btn-outline-primary "> Авторизоваться
                                            </button>
                                            <small class="form-text text-muted"> {{ getError('main') }}</small>
                                        </div>
                                    </div>
                                    <div class="row forgotten-pass">
                                        <a href="#"><strong>Забыли пароль?</strong></a>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapActions, mapGetters} from 'vuex';

    export default {
        name: "LoginAuth",
        data() {
            return {
                email: null,
                password: null,
            }
        },
        computed: {
            ...mapGetters('appUser', [
                'getError'
            ])
    }
    ,
    methods: {
    ...
        mapActions('appUser', [
            'toLogin'
        ]),

            auth
    :

        function () {
            let self = this;

            this.toLogin({
                'email': this.email,
                'password': this.password,
                'callback': function () {
                    let url = self.$route.query.nextUrl || null;
                    if (url !== null) {
                        location.href = url;
                    } else {
                        location.reload();
                    }
                }
            })
        }
    }
    }
</script>

<style scoped>

</style>
