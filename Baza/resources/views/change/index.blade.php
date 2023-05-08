@extends('layouts.app', ['page' => __('Смены'), 'pageSlug' => 'change'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" id="scan-result">
                    <div class="table-responsive">
                            <table class="table">
                                <thead class=" text-primary">
                                <th>
                                    ID
                                </th>
                                <th>
                                    Состав
                                </th>
                                <th>
                                    Живые билеты
                                </th>
                                <th>
                                    Электронные билеты
                                </th>
                                <th>
                                    Френдли
                                </th>
                                <th>
                                    Списки
                                </th>
                                <th>
                                    Время начала смены
                                </th>
                                <th>
                                    Время окончание смены
                                </th>
                                <th>
                                </th>
                                </thead>
                                <tbody>
                                @foreach($report as $item)
                                    <tr>
                                        <td>
                                            <a href="{{ route('changes.edit',['id'=>$item['id']]) }}">{{$item['id']}}
                                        </td>
                                        <td>
                                            {{$item['userName']}}
                                        </td>
                                        <td>
                                            {{$item['count_live_tickets']}}
                                        </td>
                                        <td>
                                            {{$item['count_el_tickets']}}
                                        </td>
                                        <td>
                                            {{$item['count_drug_tickets']}}
                                        </td>
                                        <td>
                                            {{$item['count_spisok_tickets']}}
                                        </td>
                                        <td>
                                            {{$item['start']}}
                                        </td>
                                        <td>
                                            {{$item['end']}}
                                        </td>
                                        <td>
                                            @if($item['end'] === null)
                                                <form method="post"
                                                      action="{{ route('changes.close') }}">
                                                    @csrf
                                                    <input type="hidden" name="id"
                                                           value="{{$item['id']}}">
                                                    <button type="submit"
                                                            class="btn btn-fill btn-primary">Закрыть смену
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('changes.add') }}"> Добавить смену </a>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js')

@endsection

@section('css')

@endsection
