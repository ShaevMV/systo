<x-app-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Имя') }}</th>
                    <th>{{ __('Проект') }}</th>
                    <th>{{ __('Сумма') }}</th>
                    <th>{{ __('Кол-во') }}</th>
                    <th>{{ __('Действие') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{$user->id}}</td>
                        <td>{{$user->email}}</td>
                        <td>{{$user->name}}</td>
                        <td>{{$user->project}}</td>
                        <td>{{$user->sum_price ?? 0}}</td>
                        <td>{{$user->count_tickets ?? 0}}</td>
                        <td>
                            <a href="{{ route('editUser',['id' => $user->id]) }}">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('delUser') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{$user->id}}">
                                <x-jet-responsive-nav-link href="{{ route('delUser') }}"
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
            <div class="flex items-center justify-end mt-4">
                <x-jet-responsive-nav-link href="{{ route('createUser') }}" class="ml-4">
                    {{ __('Создать пользователя') }}
                </x-jet-responsive-nav-link>
            </div>
        </div>
    </div>
</x-app-layout>
