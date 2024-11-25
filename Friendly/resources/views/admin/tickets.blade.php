<x-app-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <p> Всего билетов:<b>{{count($tickets)}}</b></p>
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Имя') }}</th>
                    @if($type === 'friendly_tickets')
                        <th>{{ __('Проект (или с кем договорился о френдли)') }}</th>
                    @else
                        <th>{{ __('Куратор') }}</th>
                    @endif
                    @if($type === 'list_tickets')
                        <th>{{ __('Проект') }}</th>
                    @endif
                    <th>{{ __('Телефон') }}</th>
                    @if($type !== 'list_tickets')
                        <th>{{ __('ФИО покупателя') }}</th>
                        <th>{{ __('Стоимость') }}</th>
                    @endif
                    <th>{{ __('Комментарий') }}</th>
                    <th>{{ __('Дата') }}</th>
                    <th>{{ __('Действие') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tickets as $ticket)
                    <tr>
                        @if($type === 'friendly_tickets')
                            <td><a href="/admin/tickets/f{{$ticket->id}}" target="_blank" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">f{{$ticket->id}}</a></td>
                        @elseif($type === 'list_tickets')
                            <td><a href="/admin/tickets/s{{$ticket->id}}" target="_blank" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">s{{$ticket->id}}</a></td>
                        @else
                            <td>{{\Shared\Services\TicketService::getZeroForKilter($ticket->kilter)}}</td>
                        @endif
                        <td>{{$ticket->email}}</td>
                        <td>{{$ticket->fio}}</td>
                        <td>{{$ticket->seller ?? $ticket->curator}}</td>
                        @if($type === 'list_tickets')
                            <td>{{$ticket->project}}</td>
                        @endif
                        <td>{{$ticket->phone}}</td>
                        @if($type !== 'list_tickets')
                            <td>{{$ticket->fio_friendly}}</td>
                            <td>{{$ticket->price}}</td>
                        @endif
                        <td title="{{$ticket->comment}}">{{mb_substr($ticket->comment ?? '',0,10)}}</td>
                        <td>{{$ticket->created_at}}</td>

                        <td>
                            <form method="POST" action="{{ route('delTicket') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{$ticket->id}}">
                                <input type="hidden" name="type" value="{{$type}}">
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
</x-app-layout>
