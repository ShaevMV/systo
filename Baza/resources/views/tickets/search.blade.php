@php use Baza\Shared\Domain\ValueObject\Status;use Baza\Tickets\Services\DefineService; @endphp
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
                                   placeholder="{{ __('Поиск') }}">

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
                                <!-- ЭЛЕКТРОННЫЕ БИЛЕТЫ (активная) -->
                                @if (isset($result[DefineService::ELECTRON_TICKET]) && count($result[DefineService::ELECTRON_TICKET]) > 0 )
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab"
                                           href="#description">ЭЛЕКТРОННЫЕ БИЛЕТЫ</a>
                                    </li>
                                @endif
                                <!-- СПИСКИ -->
                                @if (isset($result[DefineService::SPISOK_TICKET]) && count($result[DefineService::SPISOK_TICKET]) > 0 )
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#characteristics">СПИСКИ</a>
                                    </li>
                                @endif
                                <!-- фРЕНДЛИ -->
                                @if (isset($result[DefineService::DRUG_TICKET]) && count($result[DefineService::DRUG_TICKET]) > 0 )
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#drug">ФРЕНДЛИ</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- ЭЛЕКТРОННЫЕ БИЛЕТЫ  -->
                                <div class="tab-pane fade" id="description">
                                    <div class="table-responsive">
                                        @if (isset($result[DefineService::ELECTRON_TICKET]) && count($result[DefineService::ELECTRON_TICKET]) > 0 )
                                            <table class="table">
                                                <thead class=" text-primary">
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
                                                    Дата заказа
                                                </th>
                                                <th>
                                                    Комментарий
                                                </th>
                                                <th>
                                                </th>
                                                </thead>
                                                <tbody>
                                                @foreach($result[DefineService::ELECTRON_TICKET] as $ticket)
                                                    <tr>
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
                                                        <td>
                                                            @if($ticket['date_change'] === null && $ticket['status'] === Status::PAID)
                                                                <form  method="post"
                                                                      action="{{ route('tickets.scan.enterForTable') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="id"
                                                                           value="{{$ticket['kilter']}}">
                                                                    <input type="hidden" name="type"
                                                                           value="{{DefineService::ELECTRON_TICKET}}">
                                                                    <input type="hidden" name="q" value="{{$q}}">
                                                                    <button type="submit" class="btn btn-fill btn-primary">Пропустить</button>
                                                                </form>
                                                            @elseif($ticket['date_change'] != null) Был пропущен {{$ticket['date_change']}}
                                                            @else
                                                                    Билдет находиться в статусе {{$ticket['status_human']}}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                                <!-- СПИСКИ -->
                                <div class="tab-pane fade" id="characteristics">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead class=" text-primary">
                                            <th>
                                                ID
                                            </th>
                                            <th>
                                                Name
                                            </th>
                                            <th>
                                                Country
                                            </th>
                                            <th>
                                                City
                                            </th>
                                            <th>
                                                Salary
                                            </th>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>
                                                    1
                                                </td>
                                                <td>
                                                    Dakota Rice
                                                </td>
                                                <td>
                                                    Niger
                                                </td>
                                                <td>
                                                    Oud-Turnhout
                                                </td>
                                                <td class="text-primary">
                                                    $36,738
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    2
                                                </td>
                                                <td>
                                                    Minerva Hooper
                                                </td>
                                                <td>
                                                    Curaçao
                                                </td>
                                                <td>
                                                    Sinaai-Waas
                                                </td>
                                                <td class="text-primary">
                                                    $23,789
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    3
                                                </td>
                                                <td>
                                                    Sage Rodriguez
                                                </td>
                                                <td>
                                                    Netherlands
                                                </td>
                                                <td>
                                                    Baileux
                                                </td>
                                                <td class="text-primary">
                                                    $56,142
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    4
                                                </td>
                                                <td>
                                                    Philip Chaney
                                                </td>
                                                <td>
                                                    Korea, South
                                                </td>
                                                <td>
                                                    Overland Park
                                                </td>
                                                <td class="text-primary">
                                                    $38,735
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    5
                                                </td>
                                                <td>
                                                    Doris Greene
                                                </td>
                                                <td>
                                                    Malawi
                                                </td>
                                                <td>
                                                    Feldkirchen in Kärnten
                                                </td>
                                                <td class="text-primary">
                                                    $63,542
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    6
                                                </td>
                                                <td>
                                                    Mason Porter
                                                </td>
                                                <td>
                                                    Chile
                                                </td>
                                                <td>
                                                    Gloucester
                                                </td>
                                                <td class="text-primary">
                                                    $78,615
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- ФРЕНДЛИ -->
                                <div class="tab-pane fade" id="drug">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead class=" text-primary">
                                            <th>
                                                ID
                                            </th>
                                            <th>
                                                Name
                                            </th>
                                            <th>
                                                Country
                                            </th>
                                            <th>
                                                City
                                            </th>
                                            <th>
                                                Salary
                                            </th>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>
                                                    1
                                                </td>
                                                <td>
                                                    Dakota Rice
                                                </td>
                                                <td>
                                                    Niger
                                                </td>
                                                <td>
                                                    Oud-Turnhout
                                                </td>
                                                <td class="text-primary">
                                                    $36,738
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    2
                                                </td>
                                                <td>
                                                    Minerva Hooper
                                                </td>
                                                <td>
                                                    Curaçao
                                                </td>
                                                <td>
                                                    Sinaai-Waas
                                                </td>
                                                <td class="text-primary">
                                                    $23,789
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    3
                                                </td>
                                                <td>
                                                    Sage Rodriguez
                                                </td>
                                                <td>
                                                    Netherlands
                                                </td>
                                                <td>
                                                    Baileux
                                                </td>
                                                <td class="text-primary">
                                                    $56,142
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    4
                                                </td>
                                                <td>
                                                    Philip Chaney
                                                </td>
                                                <td>
                                                    Korea, South
                                                </td>
                                                <td>
                                                    Overland Park
                                                </td>
                                                <td class="text-primary">
                                                    $38,735
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    5
                                                </td>
                                                <td>
                                                    Doris Greene
                                                </td>
                                                <td>
                                                    Malawi
                                                </td>
                                                <td>
                                                    Feldkirchen in Kärnten
                                                </td>
                                                <td class="text-primary">
                                                    $63,542
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    6
                                                </td>
                                                <td>
                                                    Mason Porter
                                                </td>
                                                <td>
                                                    Chile
                                                </td>
                                                <td>
                                                    Gloucester
                                                </td>
                                                <td class="text-primary">
                                                    $78,615
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
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
