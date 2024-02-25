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
                        <p>{{ $success }}</p>
                        <hr>
                    </div>
                @endif
                <div class="buttonz">
                    <a href="{{ route('viewAddTickets') }}" class="btnx">ЭЛЕКТРОННЫЙ</a>
                    <a href="#" class="btnx active">ЖИВОЙ БИЛЕТ</a>
                </div>
                <form method="POST" action="{{ route('addLiveTicket') }}" id="main-former">
                    @csrf
                    <h2>Форма продажи живого билета</h2>
                    <small>Гость должен получить от вас конверт с карточкой лично в руки, либо забрать его в Лесистой.</small>
                    <div class="mt-4">
                        <x-jet-label for="password" value="{{ __('Имя фамилия продавца:') }}"/>
                        <x-jet-input id="password" readonly class="block mt-1 w-full" type="text" name="seller" required
                                     autocomplete="Seller" value="{{$user->name}}"/>
                    </div>
                    <div>
                        <x-jet-label for="name" value="{{ __('Проект (или с кем договорился о френдли):') }}"/>
                        <x-jet-input id="name" readonly class="block mt-1 w-full" type="text" name="fio_seller"
                                     value="{{$user->project}}" required autofocus autocomplete="fio_seller"/>
                    </div>
                    <div class="mt-4">
                        <x-jet-label for="email" value="{{ __('Email покупателя:') }}"/>
                        <x-jet-input id="email" class="block mt-1 w-full" type="email" name="email"
                                     :value="old('email')" required/>
                    </div>

                    <div class="mt-4">
                        <x-jet-label for="password_confirmation" value="{{ __('Сколько билетов:') }}"/>
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
                    <div class="mt-4">
                        <x-jet-label value="{{ __('Фамилия Имя покупателя:') }}"/>
                        <x-jet-input class="block mt-1 w-full fio" type="text" name="fio" required/>
                    </div>
                    <div id="listFio">
                        <div class="mt-4" id="cloneFio">
                            <x-jet-label value="{{ __('Номер билет (один номер в одной строке!)') }}"/>
                            <x-jet-input class="block mt-1 w-full fio" type="text" name="kilter[]" required/>
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
                            {{ __('Зарегистрировать') }}
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