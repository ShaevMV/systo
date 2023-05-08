@extends('layouts.app', ['page' => __('Добавить смену'), 'pageSlug' => 'change'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" id="scan-result">
                    <form class="form" method="post"
                          action="{{ route('changes.save') }}">
                        @csrf
                        <div id="Compound">
                            <p>Состав смены</p>
                        </div>
                        <div class="">
                            <select name="compound[]" multiple aria-label="multiple select example" style="    width: 254px;" required>
                                @foreach($users as $user)
                                    <option value="{{$user->id}}">
                                        {{$user->name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group">
                            <label> Дата начала смены </label>
                            <input name="start" type="datetime-local" required>
                        </div>
                        <button type="submit"
                                class="btn btn-fill btn-primary">Добавить
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
