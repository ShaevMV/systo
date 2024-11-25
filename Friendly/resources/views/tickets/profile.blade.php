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
                <div class="buttonz">
                    <a href="/" class="btnx full-btnx">Назад</a>
                    @if (Auth::user()?->is_list || Auth::user()?->is_admin)
                        <a href="{{ route('viewListTickets') }}" class="btnx">СПИСОК</a>
                    @endif
                </div>
                <h4 class="x-warn">Чтобы скачать билет нажмите на его ID в первом столбце таблицы. Чтобы удалить билет нажмите Х (крестик) в последнем столбце таблицы</h4>
                    <div>
                        <h1> Электронные </h1>
                        <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('ФИО покупателя') }}</th>
                            <th>{{ __('Стоимость') }}</th>
                            <th>{{ __('Дата') }}</th>
                            <th>{{ __('Действие') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($ticketsEl as $ticket)
                            <tr>
                                <td><a href="/admin/tickets/f{{$ticket->id}}" target="_blank" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">f{{$ticket->id}}</a></td>
                                <td>{{$ticket->email}}</td>
                                    <td>{{$ticket->fio_friendly}}</td>
                                    <td>{{$ticket->price}}</td>
                                <td>{{$ticket->created_at}}</td>

                                <td>
                                    <form method="POST" action="{{ route('delTicket') }}">
                                        @csrf
                                        <input type="hidden" name="id" value="{{$ticket->id}}">
                                        <input type="hidden" name="type" value="friendly_tickets">
                                        <input type="hidden" name="url" value="/profile">
                                        <x-jet-responsive-nav-link href="{{ route('delTicket') }}"
                                                                   onclick="event.preventDefault();
                                    this.closest('form').submit();">
                                            {{ __('Х') }}
                                        </x-jet-responsive-nav-link>
                                    </form>
                                </td>
                            </tr>

                        @endforeach
                        </tbody>

                    </table>
                    </div>
                    <div>
                        <h1>Живые</h1>
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('ФИО покупателя') }}</th>
                                <th>{{ __('Телефон') }}</th>
                                <th>{{ __('Комментарий') }}</th>
                                <th>{{ __('Стоимость') }}</th>
                                <th>{{ __('Дата') }}</th>
                                <th>{{ __('Действие') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($ticketsLive as $ticket)
                                <tr>
                                    <td>{{\Shared\Services\TicketService::getZeroForKilter($ticket->kilter)}}</td>
                                    <td>{{$ticket->email}}</td>
                                    <td>{{$ticket->seller}}</td>
                                    <td>{{$ticket->phone}}</td>
                                    <td>{{$ticket->fio_friendly}}</td>
                                    <td>{{$ticket->price}}</td>
                                    <td>{{$ticket->created_at}}</td>

                                    <td>
                                        <form method="POST" action="{{ route('delTicket') }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$ticket->id}}">
                                            <input type="hidden" name="type" value="live_tickets">
                                            <input type="hidden" name="hidden" value="/profile">
                                            <x-jet-responsive-nav-link href="{{ route('delTicket') }}"
                                                                       onclick="event.preventDefault();
                                    this.closest('form').submit();">
                                                {{ __('Х') }}
                                            </x-jet-responsive-nav-link>
                                        </form>
                                    </td>
                                </tr>

                            @endforeach
                            </tbody>

                        </table>
                    </div>
                    <div>
                        <h1> Списки </h1>
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Имя') }}</th>
                                <th>{{ __('Проект') }}</th>
                                <th>{{ __('Телефон') }}</th>
                                <th>{{ __('Дата') }}</th>
                                <th>{{ __('Действие') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($ticketsList as $ticket)
                                <tr>
                                    <td><a href="/admin/tickets/s{{$ticket->id}}" target="_blank" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">s{{$ticket->id}}</a></td>
                                    <td>{{$ticket->email}}</td>
                                    <td>{{$ticket->fio}}</td>
                                    <td>{{$ticket->project}}</td>
                                    <td>{{$ticket->phone}}</td>
                                    <td>{{$ticket->created_at}}</td>

                                    <td>
                                        <form method="POST" action="{{ route('delTicket') }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$ticket->id}}">
                                            <input type="hidden" name="type" value="list_tickets">
                                            <input type="hidden" name="url" value="/profile">
                                            <x-jet-responsive-nav-link href="{{ route('delTicket') }}"
                                                                       onclick="event.preventDefault();
                                    this.closest('form').submit();">
                                                {{ __('Х') }}
                                            </x-jet-responsive-nav-link>
                                        </form>
                                    </td>
                                </tr>

                            @endforeach
                            </tbody>

                        </table>
                    </div>
                    <div>
                    <h1> Автомобили </h1>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Куратор') }}</th>
                            <th>{{ __('Проект') }}</th>
                            <th>{{ __('Номер автомобиля') }}</th>
                            <th>{{ __('Комментарий') }}</th>
                            <th>{{ __('Дата') }}</th>
                            <th>{{ __('Действие') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($auto as $ticket)
                            <tr>
                                <td>{{$ticket->id}}</a></td>
                                <td>{{$ticket->curator}}</td>
                                <td>{{$ticket->project}}</td>
                                <td>{{$ticket->auto}}</td>
                                <td>{{$ticket->comment}}</td>
                                <td>{{$ticket->created_at}}</td>

                                <td>
                                    <form method="POST" action="{{ route('delTicket') }}">
                                        @csrf
                                        <input type="hidden" name="id" value="{{$ticket->id}}">
                                        <input type="hidden" name="type" value="auto">
                                        <input type="hidden" name="url" value="/profile">
                                        <x-jet-responsive-nav-link href="{{ route('delTicket') }}"
                                                                   onclick="event.preventDefault();
                                    this.closest('form').submit();">
                                            {{ __('Х') }}
                                        </x-jet-responsive-nav-link>
                                    </form>
                                </td>
                            </tr>

                        @endforeach
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
