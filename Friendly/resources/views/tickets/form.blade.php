<x-guest-layout>
    <div class="pt-4" id="fon">
        <form method="POST" action="{{ route('logout') }}" class="exit-sys">
            @csrf

            <x-jet-dropdown-link href="{{ route('logout') }}"
                                 onclick="event.preventDefault();
                                                this.closest('form').submit();">
                {{ __('Выйти из системы') }}
            </x-jet-dropdown-link>
        </form>
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">

            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose">
                @if (null !== $success)
                    <div class="alert alert-success" role="alert">
                        <h1>{{ $success }}</h1>
                        <hr>
                    </div>
                @endif
                <div class="buttonz">
                    <a href="#" class="btnx active">ЭЛЕКТРОННЫЙ</a>
                    <a href="{{ route('viewLiveTickets') }}" class="btnx">ЖИВОЙ БИЛЕТ</a>
                    @if (Auth::user()?->is_list || Auth::user()?->is_admin)
                        <a href="{{ route('viewListTickets') }}" class="btnx">СПИСОК</a>
                    @endif
                </div>
                    <a href="/profile" class="btnx full-btnx" target="_blank">Все мои билеты</a>
                <form method="POST" action="{{ route('addTickets') }}" id="main-former">
                    @csrf
                    <h2>Форма продажи электронного Френдли-билета</h2>
                    <small> Гость получит pdf-файлы с qr-кодом на почту после нажать на кнопку Продать </small>
                    <div class="mt-4">
                        <x-jet-label for="password" value="{{ __('Имя фамилия продавца') }}"/>
                        <x-jet-input id="password" readonly class="block mt-1 w-full" type="text" name="seller" required
                                     autocomplete="Seller" value="{{$user->name }}"/>
                    </div>
                    <div>
                        <x-jet-label for="name" value="{{ __('Проект (или с кем договорился о френдли)') }}"/>
                        <x-jet-input id="name" readonly class="block mt-1 w-full" type="text" name="fio_seller"
                                     value="{{$user->project }}" required autofocus autocomplete="fio_seller"/>
                    </div>
                    <div class="mt-4" id="red-form">
                        <x-jet-label for="email" value="{{ __('Email покупателя') }}"/><span class="red-span">(Внимание! Не используйте почту @icloud.com)</span>
                        <x-jet-input id="email" class="block mt-1 w-full" type="email" name="email"
                                     :value="old('email')" required/>
                    </div>

                    <div class="mt-4">
                        <x-jet-label for="password_confirmation" value="{{ __('Сколько билетов') }}"/>
                        <select id="count_ticket" class="block mt-1 w-full" name="count" required autocomplete="count"
                                onchange="showFio()">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="mt-4">
                        <x-jet-label value="{{ __('Телефон покупателя:') }}"/>
                        <x-jet-input class="block mt-1 w-full" type="tel" name="phone" required/>
                    </div>
                    <div id="listFio">
                        <div class="mt-4" id="cloneFio">
                            <x-jet-label value="{{ __('Фамилия Имя покупателя:') }}"/>
                            <x-jet-input class="block mt-1 w-full fio" type="text" name="fio[]" required/>
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-jet-label for="price" value="{{ __('Сумма, полученная за билеты:') }}"/>
                        <x-jet-input id="price" class="block mt-1 w-full" type="number" name="price" required
                                     autocomplete="price"/>
                    </div>
                    <div class="mt-4">
                        <x-jet-label for="comment" value="{{ __('Комментарий:') }}"/>
                        <textarea class="block mt-1 w-full" name="comment" id="comment" rows="3"> </textarea>
                    </div>
                    <div id="main-btn">
                        <x-jet-button class="ml-4">
                            {{ __('Продать') }}
                        </x-jet-button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
<script>
    function showFio() {
        var listFio = $("#listFio");
        var count = +document.getElementById('count_ticket').value;
        $('.fio').val('');
        var elem = $('#cloneFio');
        elem.val("");
        listFio.html('');
        console.log(elem);
        console.log(count);
        for (let i = 0; i < count; i++) {
            elem.clone().appendTo('#listFio')
        }
    }
</script>
