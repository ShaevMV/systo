<x-app-layout>
    <div class="pt-4 bg-gray-100">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="flex items-center justify-end mt-4">
                <x-jet-responsive-nav-link href="{{ route('createUser') }}" class="ml-4">
                    {{ __('Создать пользователя') }}
                </x-jet-responsive-nav-link>
            </div>
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Имя') }}</th>
                    <th>{{ __('Проект') }}</th>
                    <th>{{ __('Имеет права вводить списки') }}</th>
                    <th>{{ __('Сумма за френдли') }}</th>
                    <th>{{ __('Кол-во за френдли') }}</th>
                    <th>{{ __('Сумма за живые') }}</th>
                    <th>{{ __('Кол-во за живые') }}</th>
                    <th>{{ __('Кол-во за списки') }}</th>
                    <th>{{ __('Действие') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{$user['id']}}</td>
                        <td>{{$user['email']}}</td>
                        <td>{{$user['name']}}</td>
                        <td>{{$user['project']}}</td>
                        <td>{{($user['is_list'] || $user['is_admin'])? 'да' : 'нет'}}</td>
                        <td>{{$user['sum_price_friendly'] ?? 0}}</td>
                        <td>{{$user['count_tickets_friendly'] ?? 0}}</td>
                        <td>{{$user['sum_price_live'] ?? 0}}</td>
                        <td>{{$user['count_tickets_live'] ?? 0}}</td>
                        <td>{{$user['count_tickets_list'] ?? 0}}</td>
                        <td>
                            <a href="{{ route('editUser',['id' => $user['id']]) }}">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('delUser') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{$user['id']}}">
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

        </div>
    </div>
</x-app-layout>
