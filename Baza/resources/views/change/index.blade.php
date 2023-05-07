@extends('layouts.app', ['page' => __('Сканер'), 'pageSlug' => 'change'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="title">{{ __('Результат поиска') }}</h5>
                    <span id="error-result" class="error"></span>
                    <span id="massage-result" class="massage"></span>
                </div>
                <div class="card-body" id="scan-result" style="display: none;">
                    <p><b>Цвет браслета:</b> <span id="color" class="block-color"></span></p>

                    <b>ID: </b>
                    <p id="kilter"></p>
                    <b>Имя: </b>
                    <p id="name"></p>
                    <b>Email: </b>
                    <p id="email"></p>
                    <b>Дата получение билета: </b>
                    <p id="date-order"></p>
                    <b>Телефон: </b>
                    <p id="phone"></p>
                    <b>Статус: </b>
                    <p id="status"></p>
                    <b>Проект: </b>
                    <p id="project"></p>
                    <b>Куратор: </b>
                    <p id="curator"></p>
                    <b>Комментарий: </b>
                    <p id="comment"></p>

                </div>
                <div class="card-footer">
                    <button id="enter-result" class="btn btn-fill btn-primary" style="display: none;"> ПРОПУСТИТЬ
                    </button>
                    <span id="already-passed" class="btn btn-fill btn-primary error" style="display: none;"></span>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div id="video-container">
                        <video id="qr-video" style="display: none"></video>
                    </div>
                </div>
                <div class="card-footer">
                    <h5 class="title">{{ __('Настройки') }}</h5>
                    <div>
                        <label>
                            Highlight Style
                            <select id="scan-region-highlight-style-select">
                                <option value="default-style">Default style</option>
                                <option value="example-style-1">Example custom style 1</option>
                                <option value="example-style-2">Example custom style 2</option>
                            </select>
                        </label>
                        <label>
                            <input id="show-scan-region" type="checkbox">
                            Show scan region canvas
                        </label>
                    </div>
                    <div>
                        <select id="inversion-mode-select">
                            <option value="original">Scan original (dark QR code on bright background)</option>
                            <option value="invert">Scan with inverted colors (bright QR code on dark background)
                            </option>
                            <option value="both">Scan both</option>
                        </select>
                        <br>
                    </div>
                    <b>Device has camera: </b>
                    <span id="cam-has-camera"></span>
                    <br>
                    <div>
                        <b>Preferred camera:</b>
                        <select id="cam-list">
                            <option value="environment" selected>Environment Facing (default)</option>
                            <option value="user">User Facing</option>
                        </select>
                    </div>
                    <b>Camera has flash: </b>
                    <span id="cam-has-flash"></span>
                    <div>
                        <button id="flash-toggle">📸 Flash: <span id="flash-state">off</span></button>
                    </div>
                    <br>
                    <b>Detected QR code: </b>
                    <span id="cam-qr-result">None</span>
                    <br>
                    <b>Last detected at: </b>
                    <span id="cam-qr-result-timestamp"></span>
                    <br>
                    <button id="start-button">Start</button>
                    <button id="stop-button">Stop</button>
                    <hr>
                </div>
            </div>


        </div>

    </div>
@endsection

@section('js')

@endsection

@section('css')

@endsection
