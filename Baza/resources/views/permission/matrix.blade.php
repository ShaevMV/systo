@extends('layouts.app', ['page' => __('Права доступа'), 'pageSlug' => 'permission'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('alerts.success')

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Матрица прав «роль × действие»</h4>
                    <p class="card-category">
                        Отметь, какие действия доступны роли смены. <strong>Администратор</strong> —
                        суперроль: имеет все права и не редактируется. Изменения применяются ко всем
                        ролям сразу (снятая галочка = право убрано).
                    </p>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('permission.save') }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="text-primary">
                                <tr>
                                    <th>Действие</th>
                                    @foreach($roles as $role)
                                        <th class="text-center">{{ $role['label'] }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($actions as $action)
                                    <tr>
                                        <td>
                                            {{ $action['label'] }}<br>
                                            <small class="text-muted">{{ $action['value'] }}</small>
                                        </td>
                                        @foreach($roles as $role)
                                            @php
                                                $isAdmin = $role['value'] === $adminRole;
                                                $checked = $isAdmin
                                                    || in_array($action['value'], $matrix[$role['value']] ?? [], true);
                                            @endphp
                                            <td class="text-center">
                                                <input type="checkbox"
                                                       name="perm[{{ $role['value'] }}][]"
                                                       value="{{ $action['value'] }}"
                                                       @checked($checked)
                                                       @disabled($isAdmin)>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-fill btn-primary">
                            Сохранить права
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
@endsection

@section('css')
@endsection
