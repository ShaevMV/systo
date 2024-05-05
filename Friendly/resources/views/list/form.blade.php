
<x-guest-layout>

    <div class="pt-4 bg-gray-100">

        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">

            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-jet-dropdown-link href="{{ route('logout') }}"
                                         onclick="event.preventDefault();
                                                this.closest('form').submit();">
                        {{ __('Выйти из системы') }}
                    </x-jet-dropdown-link>
                </form>
                @if (null !== $success)
                    <div class="alert alert-success" role="alert">
                        <p>{{ $success }}</p>
                        <hr>
                    </div>
                @endif
                <div class="buttonz">
                    <a href="{{ route('viewAddTickets') }}" class="btnx">ЭЛЕКТРОННЫЙ</a>
                    <a href="{{ route('viewLiveTickets') }}" class="btnx">ЖИВОЙ БИЛЕТ</a>
                    @if (Auth::user()?->is_list || Auth::user()?->is_admin)
                        <a href="#" class="btnx active">СПИСОК</a>
                    @endif
                </div>
                <a href="/profile" class="btnx full-btnx" target="_blank">Все мои билеты</a>
                <form method="POST" action="{{ route('addListTicket') }}">
                    @csrf
                    <div>
                        <x-jet-label for="name" value="{{ __('Куратор') }}"/>
                        <x-jet-input id="name" readonly class="block mt-1 w-full" type="text" name="curator" value="{{$user->name }}" required autofocus autocomplete="fio_seller"/>
                    </div>
                    <div>
                        <x-jet-label for="project" value="{{ __('Проект') }}"/>
                        <x-jet-input id="project" name="project" class="block mt-1 w-full" type="text" :value="old('project')" required/>
                    </div>
                    <div class="mt-4" id="red-form">
                        <x-jet-label for="email" value="{{ __('Email куда придут билеты') }}"/><span class="red-span">(Внимание! Не используйте почту @icloud.com)</span>
                        <x-jet-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required/>
                    </div>

                    <div class="mt-4">
                        <x-jet-label for="email" value="{{ __('Телефон') }}"/>
                        <x-jet-input id="email" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')"/>
                    </div>

                    <div id="listFio">
                        <div class="mt-4" id="cloneFio">
                            <x-jet-label value="{{ __('Состав') }}"/>
                            <x-jet-label value="{{ __('Каждого участника проекта необходимо внести с новой строчки:') }}"/>
                            <textarea class="block mt-1 w-full" name="list" id="cloneFio" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-jet-label value="{{ __('Автомобили') }}"/>
                        <x-jet-label value="{{ __('При наличие автомобиля необходимо внести с новой строчки:') }}"/>
                        <textarea class="block mt-1 w-full" name="auto" rows="5"></textarea>
                    </div>
                    <div class="mt-4">
                        <x-jet-label for="comment" value="{{ __('Комментарий:') }}"/>
                        <textarea class="block mt-1 w-full" name="comment" id="comment" rows="3"></textarea>
                    </div>
                    <div class="flex items-center justify-end mt-4">
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
        var count = + document.getElementById('count_ticket').value;
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
