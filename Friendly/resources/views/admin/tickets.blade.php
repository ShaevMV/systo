<x-app-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Имя') }}</th>
                    <th>{{ __('Проект (или с кем договорился о френдли)') }}</th>
                    <th>{{ __('ФИО покупателя') }}</th>
                    <th>{{ __('Стоимость') }}</th>
                    <th>{{ __('Комментарий') }}</th>
                    <th>{{ __('Дата') }}</th>
                    <th>{{ __('Действие') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tickets as $ticket)
                    <tr>
                        <td>f{{$ticket->id}}</td>
                        <td>{{$ticket->email}}</td>
                        <td>{{$ticket->fio}}</td>
                        <td>{{$ticket->seller}}</td>
                        <td>{{$ticket->fio_friendly}}</td>
                        <td>{{$ticket->price}}</td>
                        <td title="{{$ticket->comment}}">{{mb_substr($ticket->comment ?? '',0,10)}}</td>
                        <td>{{$ticket->created_at}}</td>

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
        </div>
    </div>
</x-app-layout>
