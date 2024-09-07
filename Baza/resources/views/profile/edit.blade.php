@extends('layouts.app', ['page' => __('User Profile'), 'pageSlug' => 'profile'])

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="title">{{ __('Редактировать аккаунт') }}</h5>
                </div>
                <form method="post" action="/profile" autocomplete="off">
                    <div class="card-body">
                            @csrf

                            @include('alerts.success')

                            <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
                                <label>{{ __('Имя') }}</label>
                                <input type="text" name="name" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" placeholder="{{ __('Имя') }}" value="{{ old('name', auth()->user()->name) }}">
                                @include('alerts.feedback', ['field' => 'name'])
                            </div>

                            <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }}">
                                <label>{{ __('Email') }}</label>
                                <input type="email" name="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="{{ __('Email') }}" value="{{ old('email', auth()->user()->email) }}">
                                @include('alerts.feedback', ['field' => 'email'])
                            </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-fill btn-primary">{{ __('Сохранить') }}</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="title">{{ __('Пароль') }}</h5>
                </div>
                <form method="post" action="/password-request" autocomplete="off">
                    <div class="card-body">
                        @csrf

                        @include('alerts.success', ['key' => 'password_status'])

                        <div class="form-group{{ $errors->has('old_password') ? ' has-danger' : '' }}">
                            <label>{{ __('Актуальный пароль') }}</label>
                            <input type="password" name="old_password" class="form-control{{ $errors->has('old_password') ? ' is-invalid' : '' }}" placeholder="{{ __('Актуальный пароль') }}" value="" required>
                            @include('alerts.feedback', ['field' => 'old_password'])
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                            <label>{{ __('Новый пароль') }}</label>
                            <input type="password" name="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ __('Пароль') }}" value="" required>
                            @include('alerts.feedback', ['field' => 'password'])
                        </div>
                        <div class="form-group">
                            <label>{{ __('Подтвердить пароль') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="{{ __('Подтвердите пароль') }}" value="" required>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-fill btn-primary">{{ __('Сменить пароль') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-user">
                <div class="card-body">
                    <p class="card-text">
                        <div class="author">
                            <div class="block block-one"></div>
                            <div class="block block-two"></div>
                            <div class="block block-three"></div>
                            <div class="block block-four"></div>
                            <a href="#">
                                <img class="avatar" src="/black/img/systo.png" alt="">
                                <h5 class="title">{{ auth()->user()->name }}</h5>
                            </a>
                            <p class="description">
                                {{ __('Administrator') }}
                            </p>
                        </div>
                    </p>
                    <div class="card-description">
                        {{ __('Do not be scared of the truth because we need to restart the human foundation in truth And I love you like Kanye loves Kanye I love Rick Owens’ bed design but the back is...') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
