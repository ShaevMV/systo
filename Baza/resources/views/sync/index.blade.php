@extends('layouts.app', ['page' => __('Синхронизация'), 'pageSlug' => 'sync'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            @if(session('sync_error'))
                <div class="alert alert-danger">
                    {{ session('sync_error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('sync_export_stats'))
                <div class="alert alert-success">
                    <strong>Выгрузка завершена.</strong>
                    <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                        @foreach(session('sync_export_stats') as $table => $count)
                            <li>{{ $table }}: {{ $count }} строк</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('sync_import_stats'))
                <div class="alert alert-success">
                    <strong>Импорт завершён.</strong>
                    <div class="table-responsive" style="margin-top: 10px;">
                        <table class="table">
                            <thead class="text-primary">
                            <tr>
                                <th>Таблица</th>
                                <th>Создано</th>
                                <th>Обновлено</th>
                                <th>Пропущено</th>
                                <th>Прим.</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach(session('sync_import_stats') as $table => $info)
                                <tr>
                                    <td>{{ $table }}</td>
                                    @if(!empty($info['missing']))
                                        <td>—</td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td>нет файла</td>
                                    @else
                                        <td>{{ $info['inserted'] }}</td>
                                        <td>{{ $info['updated'] }}</td>
                                        <td>{{ $info['skipped'] }}</td>
                                        <td></td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Выгрузить данные</h4>
                    <p class="card-category">
                        Все таблицы (auto, changes, el_tickets, live_tickets, parking_tickets, spisok_tickets)
                        будут собраны в один ZIP-архив и предложены к скачиванию.
                    </p>
                </div>
                <div class="card-body">
                    <form method="post" action="/sync/export">
                        @csrf
                        <button type="submit" class="btn btn-fill btn-primary">
                            Выгрузить и скачать ZIP
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Загрузить данные</h4>
                    <p class="card-category">
                        Загрузите ZIP-архив, полученный при выгрузке на другом сервере. Записи обновятся
                        по метке <code>updated_at</code> (только новее текущих); удалённые записи не трогаются.
                    </p>
                </div>
                <div class="card-body">
                    <form method="post" action="/sync/import" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group" style="margin-bottom: 15px;">
                            <input type="file" name="archive" accept=".zip,application/zip" required>
                        </div>
                        <button type="submit" class="btn btn-fill btn-primary"
                                onclick="return confirm('Загрузить архив и обновить таблицы?');">
                            Загрузить ZIP
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
