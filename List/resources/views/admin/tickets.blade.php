<x-app-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <p> Всего билетов: <b>{{count($tickets)}}</b></p>
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Куратор') }}</th>
                    <th>{{ __('Проект') }}</th>
                    <th>{{ __('ФИО участника') }}</th>
                    <th>{{ __('Комментарий') }}</th>
                    <th>{{ __('Дата') }}</th>
                    <th>{{ __('Отметить для удаления') }}</th>
                    <th>{{ __('Действие') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tickets as $ticket)
                    <tr>
                        <td><a href="/admin/tickets/{{$ticket->id}}" target="_blank" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">S{{$ticket->id}}</a></td>
                        <td>{{$ticket->email}}</td>
                        <td>{{$ticket->curator}}</td>
                        <td>{{$ticket->project}}</td>
                        <td>{{$ticket->fio}}</td>
                        <td title="{{$ticket->comment}}">{{mb_substr($ticket->comment ?? '',0,10)}}</td>
                        <td>{{$ticket->created_at}}</td>
                        <td><input class="form-check-input checkbox check_tickets" type="checkbox" value="{{$ticket->id}}"></td>
                        <td>
                            <form method="POST" action="{{ route('delTicket') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{$ticket->id}}">
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
            <form method="POST" action="{{ route('delTicketList') }}" id="list_ids_for_delete">
                @csrf
                <input type="hidden" name="ids" id="list_ids" value="">
            </form>
            <button type="button" class="btn btn-danger" onclick="listIds()">Удалить выбранное</button>
        </div>
    </div>

    <script>
        var script = document.createElement('script');
        script.src = "https://ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.min.js";
        document.getElementsByTagName('head')[0].appendChild(script);

        function listIds() {
            let ids = [];
            $('.check_tickets').each(function (i, elem) {
                if ($(this).attr("checked") == 'checked') {
                    ids.push($(this).attr("value"));
                }
            });
            $('#list_ids').val(ids.join());
            $('#list_ids_for_delete').submit();

        }

    </script>
</x-app-layout>
