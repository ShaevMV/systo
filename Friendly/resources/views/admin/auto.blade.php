<x-app-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <p> Всего билетов:<b>{{count($tickets)}}</b></p>
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Номер') }}</th>
                    <th>{{ __('Куратор') }}</th>
                    <th>{{ __('Проект') }}</th>
                    <th>{{ __('Комментарий') }}</th>
                    <th>{{ __('Дата') }}</th>
                    <th>{{ __('Действие') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tickets as $ticket)
                    <tr>
                        <td>{{$ticket->id}}</td>
                        <td>{{$ticket->auto}}</td>
                        <td>{{$ticket->curator}}</td>
                        <td>{{$ticket->project}}</td>
                        <td title="{{$ticket->comment}}">{{mb_substr($ticket->comment ?? '',0,10)}}</td>
                        <td>{{$ticket->created_at}}</td>

                        <td>
                            <form method="POST" action="{{ route('delAuto') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{$ticket->id}}">
                                <x-jet-responsive-nav-link href="{{ route('delAuto') }}"
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
