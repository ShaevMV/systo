@extends('layouts.app', ['page' => __('Добавить смену'), 'pageSlug' => 'change'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" id="scan-result">
                    @if(session('shift_error'))
                        <div class="alert alert-danger">{{ session('shift_error') }}</div>
                    @endif
                    <form class="form" method="post"
                          action="/change/save">
                        @csrf
                        <input name="id" value="@if(isset($findChange['id'])){{$findChange['id']}}@endif" type="hidden">
                        @if(!empty($festivals))
                            <div class="" style="margin-bottom: 10px;">
                                <label> Фестиваль смены @if(count($festivals) > 1)(обязательно)@endif </label>
                                <select name="festival_id" style="width: 254px;" @if(count($festivals) > 1) required @endif>
                                    @if(count($festivals) > 1)
                                        <option value="">— выбери фестиваль —</option>
                                    @endif
                                    @foreach($festivals as $f)
                                        <option value="{{ $f['id'] }}"
                                            @if((isset($findChange['festival_id']) && $findChange['festival_id'] === $f['id']) || count($festivals) === 1) selected @endif>
                                            {{ $f['name'] }}@if(!empty($f['year'])) {{ $f['year'] }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div id="Compound">
                            <p>Состав смены</p>
                        </div>
                        <div class="">
                            <select name="compound[]" multiple aria-label="multiple select example" style="width: 254px;" required>
                                @foreach($users as $user)
                                    <option value="{{$user->id}}" @if(isset($findChange['user_id']) && in_array($user->id, $findChange['user_id'])) selected @endif>
                                        {{$user->name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="" style="margin-top: 10px;">
                            <label> Начальник смены (обязательно) </label>
                            <select name="chief" style="width: 254px;" required>
                                <option value="">— выбери начальника —</option>
                                @foreach($users as $user)
                                    <option value="{{$user->id}}" @if(isset($findChange['chief_id']) && (int)$findChange['chief_id'] === (int)$user->id) selected @endif>
                                        {{$user->name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group">
                            <label> Дата начала смены </label>
                            <input name="start" type="datetime-local" value="@if(isset($findChange['start'])){{$findChange['start']}}@endif" required>
                        </div>
                        <button type="submit"
                                class="btn btn-fill btn-primary">@if(isset($findChange['id']))Сохранить @else Добавить@endif
                        </button>
                    </form>
                    @if(isset($findChange['id']))
                        <form class="form" method="post"
                              action="/change/remove">
                            @csrf
                            <input name="id" value="{{$findChange['id']}}" type="hidden">
                            <button type="submit"
                                    class="btn btn-fill btn-primary">Удалить
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js')

@endsection

@section('css')

@endsection
