@php use Baza\Shared\Domain\ValueObject\Status;use Baza\Shared\Services\DefineService; @endphp
@extends('layouts.app', ['page' => __('Поиск'), 'pageSlug' => 'search'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="title">{{ __('Поиск') }}</h5>
                </div>
                <form method="get" action="{{ route('tickets.search') }}" autocomplete="off">
                    <div class="card-body">
                        <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
                            <label>{{ __('Поля поиска') }}</label>
                            <input type="text"
                                   name="q"
                                   class="form-control"
                                   placeholder="{{ __('Поиск') }}"
                                   value="{{$q}}"
                            >
                            <b>Номер билета вводить только цифры без префикса в начале</b>
                            <h1 class="error" style="
    color: red;
">{{$error}}</h1>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-fill btn-primary">{{ __('Поиск') }}</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="title">{{ __('Результат поиска') }}</h5>
                </div>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header card-header-primary">
                            <ul class="nav nav-tabs">
                                <!-- ЭЛЕКТРОННЫЕ БИЛЕТЫ     -->
                                @if (isset($result[DefineService::ELECTRON_TICKET]) && count($result[DefineService::ELECTRON_TICKET]) > 0 )
                                    <li class="nav-item">
                                        <a class="nav-link @if($tab === DefineService::ELECTRON_TICKET) active @endif"
                                           data-toggle="tab"
                                           href="#{{DefineService::ELECTRON_TICKET}}">ЭЛЕКТРОННЫЕ БИЛЕТЫ</a>
                                    </li>
                                @endif
                                <!-- СПИСКИ -->
                                @if (isset($result[DefineService::SPISOK_TICKET]) && count($result[DefineService::SPISOK_TICKET]) > 0 )
                                    <li class="nav-item">
                                        <a class="nav-link @if($tab === DefineService::SPISOK_TICKET) active @endif"
                                           data-toggle="tab"
                                           href="#{{DefineService::SPISOK_TICKET}}">СПИСКИ</a>
                                    </li>
                                @endif
                                <!-- фРЕНДЛИ -->
                                @if (isset($result[DefineService::DRUG_TICKET]) && count($result[DefineService::DRUG_TICKET]) > 0 )
                                    <li class="nav-item">
                                        <a class="nav-link @if($tab === DefineService::DRUG_TICKET) active @endif"
                                           data-toggle="tab"
                                           href="#{{DefineService::DRUG_TICKET}}">ФРЕНДЛИ</a>
                                    </li>
                                @endif
                                <!-- Живые -->
                                @if (isset($result[DefineService::LIVE_TICKET]) && count($result[DefineService::LIVE_TICKET]) > 0 )
                                    <li class="nav-item">
                                        <a class="nav-link @if($tab === DefineService::LIVE_TICKET) active @endif"
                                           data-toggle="tab"
                                           href="#{{DefineService::LIVE_TICKET}}">Живые</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- ЭЛЕКТРОННЫЕ БИЛЕТЫ  -->
                                <div
                                    class="tab-pane fade @if($tab === DefineService::ELECTRON_TICKET) show active @endif"
                                    id="{{DefineService::ELECTRON_TICKET}}">
                                    <div class="table-responsive">
                                        @if (isset($result[DefineService::ELECTRON_TICKET]) && count($result[DefineService::ELECTRON_TICKET]) > 0 )
                                            <table class="table">
                                                <thead class=" text-primary">
                                                <th>
                                                </th>
                                                <th>
                                                    Цвет
                                                </th>
                                                <th>
                                                    ID
                                                </th>
                                                <th>
                                                    Имя
                                                </th>
                                                <th>
                                                    Email
                                                </th>
                                                <th>
                                                    Город
                                                </th>
                                                <th>
                                                    Телефон
                                                </th>
                                                <th>
                                                    Статус
                                                </th>
                                                <th>
                                                    Дата
                                                </th>
                                                <th>
                                                    Коммент
                                                </th>
                                                </thead>
                                                <tbody>
                                                @foreach($result[DefineService::ELECTRON_TICKET] as $ticket)
                                                    <tr>

                                                        <td>
                                                            @if($ticket['date_change'] === null && $ticket['status'] === Status::PAID)
                                                                <form method="post"
                                                                      action="{{ route('tickets.scan.enterForTable') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="id"
                                                                           value="{{$ticket['kilter']}}">
                                                                    <input type="hidden" name="type"
                                                                           value="{{DefineService::ELECTRON_TICKET}}">
                                                                    <input type="hidden" name="q" value="{{$q}}">
                                                                    <button type="submit"
                                                                            class="btn btn-fill btn-primary">Пропустить
                                                                    </button>
                                                                </form>
                                                            @elseif($ticket['date_change'] != null)
                                                                Был пропущен {{$ticket['date_change']}}
                                                            @else
                                                                Билет в статусе {{$ticket['status_human']}}
                                                            @endif
                                                        </td>

                                                        <td style="background: {{ $ticket['color'] }}">
                                                        </td>
                                                        <td>
                                                            {{$ticket['kilter']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['name']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['email']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['city']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['phone']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['status_human']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['date_order']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['comment']}}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                                <!-- СПИСКИ -->
                                <div class="tab-pane fade @if($tab === DefineService::SPISOK_TICKET) show active @endif"
                                     id="{{DefineService::SPISOK_TICKET}}">
                                    <div class="table-responsive">
                                        @if (isset($result[DefineService::SPISOK_TICKET]) && count($result[DefineService::SPISOK_TICKET]) > 0 )
                                            <table class="table">
                                                <thead class=" text-primary">
                                                <th>
                                                </th>
                                                <th>
                                                    Цвет
                                                </th>
                                                <th>
                                                    ID
                                                </th>
                                                <th>
                                                    Имя
                                                </th>
                                                <th>
                                                    Email
                                                </th>
                                                <th>
                                                    Проект
                                                </th>
                                                <th>
                                                    Куратор
                                                </th>
                                                <th>
                                                    Статус
                                                </th>
                                                <th>
                                                    Дата
                                                </th>
                                                <th>
                                                    Коммент
                                                </th>
                                                </thead>
                                                <tbody>
                                                @foreach($result[DefineService::SPISOK_TICKET] as $ticket)
                                                    <tr>
                                                        <td>
                                                            @if($ticket['date_change'] === null && $ticket['status'] === Status::PAID)
                                                                <form method="post"
                                                                      action="{{ route('tickets.scan.enterForTable') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="id"
                                                                           value="{{$ticket['kilter']}}">
                                                                    <input type="hidden" name="type"
                                                                           value="{{DefineService::SPISOK_TICKET}}">
                                                                    <input type="hidden" name="q" value="{{$q}}">
                                                                    <button type="submit"
                                                                            class="btn btn-fill btn-primary">Пропустить
                                                                    </button>
                                                                </form>
                                                            @elseif($ticket['date_change'] != null)
                                                                Был пропущен {{$ticket['date_change']}}
                                                            @else
                                                                Билет в статусе {{$ticket['status_human']}}
                                                            @endif
                                                        </td>
                                                        <td class="color-box"
                                                            style="background: {{ $ticket['color'] }}">
                                                        </td>
                                                        <td>
                                                            {{$ticket['kilter']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['name']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['email']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['project']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['curator']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['status_human']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['date_order']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['comment']}}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                                <!-- ФРЕНДЛИ -->
                                <div class="tab-pane fade @if($tab === DefineService::DRUG_TICKET) show active @endif"
                                     id="{{DefineService::DRUG_TICKET}}">
                                    <div class="table-responsive">
                                        @if (isset($result[DefineService::DRUG_TICKET]) && count($result[DefineService::DRUG_TICKET]) > 0 )
                                            <table class="table">
                                                <thead class=" text-primary">
                                                <th>
                                                </th>
                                                <th>
                                                    Цвет
                                                </th>
                                                <th>
                                                    ID
                                                </th>
                                                <th>
                                                    Имя
                                                </th>
                                                <th>
                                                    Email
                                                </th>
                                                <th>
                                                    Проект
                                                </th>
                                                <th>
                                                    Продавец
                                                </th>
                                                <th>
                                                    Статус
                                                </th>
                                                <th>
                                                    Дата
                                                </th>
                                                <th>
                                                    Коммент
                                                </th>
                                                </thead>
                                                <tbody>
                                                @foreach($result[DefineService::DRUG_TICKET] as $ticket)
                                                    <tr>
                                                        <td>
                                                            @if($ticket['date_change'] === null && $ticket['status'] === Status::PAID)
                                                                <form method="post"
                                                                      action="{{ route('tickets.scan.enterForTable') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="id"
                                                                           value="{{$ticket['kilter']}}">
                                                                    <input type="hidden" name="type"
                                                                           value="{{DefineService::DRUG_TICKET}}">
                                                                    <input type="hidden" name="q" value="{{$q}}">
                                                                    <button type="submit"
                                                                            class="btn btn-fill btn-primary">Пропустить
                                                                    </button>
                                                                </form>
                                                            @elseif($ticket['date_change'] != null)
                                                                Был пропущен {{$ticket['date_change']}}
                                                            @else
                                                                Билет в статусе {{$ticket['status_human']}}
                                                            @endif
                                                        </td>
                                                        <td class="color-box"
                                                            style="background: {{ $ticket['color'] }}">
                                                        </td>
                                                        <td>
                                                            {{$ticket['kilter']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['name']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['email']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['project']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['seller']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['status_human']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['date_order']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['comment']}}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                                <!-- Живые -->
                                <div class="tab-pane fade @if($tab === DefineService::LIVE_TICKET) show active @endif"
                                     id="{{DefineService::LIVE_TICKET}}">
                                    <div class="table-responsive">
                                        @if (isset($result[DefineService::LIVE_TICKET]) && count($result[DefineService::LIVE_TICKET]) > 0 )
                                            <table class="table">
                                                <thead class=" text-primary">
                                                <th>
                                                </th>
                                                <th>
                                                    Цвет
                                                </th>
                                                <th>
                                                    ID
                                                </th>
                                                <th>
                                                    Статус
                                                </th>
                                                <th>
                                                    Коммент
                                                </th>
                                                </thead>
                                                <tbody>
                                                @foreach($result[DefineService::LIVE_TICKET] as $ticket)
                                                    <tr>
                                                        <td>
                                                            @if($ticket['date_change'] === null && $ticket['status'] === Status::PAID)
                                                                <form method="post"
                                                                      action="{{ route('tickets.scan.enterForTable') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="id"
                                                                           value="{{$ticket['kilter']}}">
                                                                    <input type="hidden" name="type"
                                                                           value="{{DefineService::LIVE_TICKET}}">
                                                                    <input type="hidden" name="q" value="{{$q}}">
                                                                    <button type="submit"
                                                                            class="btn btn-fill btn-primary">Пропустить
                                                                    </button>
                                                                </form>
                                                            @elseif($ticket['date_change'] != null)
                                                                Был пропущен {{$ticket['date_change']}}
                                                            @else
                                                                Билет в статусе {{$ticket['status_human']}}
                                                            @endif
                                                        </td>
                                                        <td class="color-box"
                                                            style="background: {{ $ticket['color'] }}">
                                                        </td>
                                                        <td>
                                                            {{$ticket['kilter']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['status_human']}}
                                                        </td>
                                                        <td>
                                                            {{$ticket['comment']}}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
