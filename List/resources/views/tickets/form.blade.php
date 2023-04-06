
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
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        <p>{{ session('status') }}</p>
                        <hr>
                    </div>
                @endif
                <form method="POST" action="{{ route('addTickets') }}">
                    @csrf
                    <div>
                        <x-jet-label for="name" value="{{ __('Куратор') }}"/>
                        <x-jet-input id="name" readonly class="block mt-1 w-full" type="text" name="curator" value="{{$user->curator }}" required autofocus autocomplete="fio_seller"/>
                    </div>
                    <div>
                        <x-jet-label for="project" value="{{ __('Проект') }}"/>
                        <x-jet-input id="project" name="project" class="block mt-1 w-full" type="text" :value="old('project')" required/>
                    </div>
                    <div class="mt-4">
                        <x-jet-label for="email" value="{{ __('Email покупателя') }}"/>
                        <x-jet-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required/>
                    </div>

                    <div id="listFio">
                        <div class="mt-4" id="cloneFio">
                            <x-jet-label value="{{ __('Состав') }}"/>
                            <x-jet-label value="{{ __('Каждого участника проекта необходимо внести с новой строчки:') }}"/>
                            <textarea class="block mt-1 w-full" name="list" id="cloneFio" rows="5"></textarea>
                        </div>
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
