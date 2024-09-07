@extends('layouts.app', ['page' => __('Сканер'), 'pageSlug' => 'scan'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="title">{{ __('Результат поиска') }}</h5>
                    <span id="error-result" class="error" style="
    color: red;
"></span>
                    <span id="massage-result" class="massage"></span>
                </div>
                <div class="card-body" id="scan-result" style="display: none;">
                    <p style="display: none"><b>Цвет браслета:</b> <span id="color" class="block-color"></span></p>

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
    <script type="module">
        import QrScanner from '../black/js/scan/qr-scanner.min.js';

        const video = document.getElementById('qr-video');
        const videoContainer = document.getElementById('video-container');
        const camHasCamera = document.getElementById('cam-has-camera');
        const camList = document.getElementById('cam-list');
        const camHasFlash = document.getElementById('cam-has-flash');
        const flashToggle = document.getElementById('flash-toggle');
        const flashState = document.getElementById('flash-state');
        const camQrResult = document.getElementById('cam-qr-result');
        const camQrResultTimestamp = document.getElementById('cam-qr-result-timestamp');

        const errorResult = document.getElementById('error-result');
        const scanResult = document.getElementById('scan-result');
        const enterResult = document.getElementById('enter-result');
        const alreadyPassedResult = document.getElementById('already-passed');
        const massageResult = document.getElementById('massage-result');

        var idTicket = null;
        var typeTicket = null;

        function setResult(label, result) {
            scanner.stop();
            $.ajax({
                type: 'POST',
                url: '/api/scan',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "search": result.data
                },
                success: function (data) {
                    scanResult.style.display = "block";
                    idTicket = data.kilter;
                    typeTicket = data.type;
                    massageResult.textContent = '';
                    showResult(data);
                },
                error: function (data) {
                    console.error(data.responseJSON);
                    errorResult.textContent = data.responseJSON;
                    //scanner.start();
                }
            });

            console.log(result.data);
            label.textContent = result.data;
            camQrResultTimestamp.textContent = new Date().toString();
            label.style.color = 'teal';
            clearTimeout(label.highlightTimeout);
            label.highlightTimeout = setTimeout(() => label.style.color = 'inherit', 100);
        }

        const nameResult = document.getElementById('name');
        const kilterResult = document.getElementById('kilter');
        const projectResult = document.getElementById('project');
        const curatorResult = document.getElementById('curator');
        const emailResult = document.getElementById('email');
        const dateOrderResult = document.getElementById('date-order');
        const phoneResult = document.getElementById('phone');
        const statusResult = document.getElementById('status');
        const commentResult = document.getElementById('comment');
        const colorResult = document.getElementById('color');

        function showResult(data) {
            if (data.date_change === null && data.status === 'paid') {
                enterResult.style.display = "block";
            } else {

                alreadyPassedResult.style.display = "block";
                if(data.date_change !== null) {
                    alreadyPassedResult.textContent = "Был пропущен " + data.date_change;
                } else {
                    alreadyPassedResult.textContent = "Билет находиться в статусе " + data.status_human;
                }

                idTicket = null;
                typeTicket = null;
            }
            nameResult.textContent = data.name;
            kilterResult.textContent = data.kilter || '';
            projectResult.textContent = data.project || '';
            curatorResult.textContent = data.curator || '';
            phoneResult.textContent = data.phone || '';
            statusResult.textContent = data.status_human || '';
            emailResult.textContent = data.email;
            commentResult.textContent = data.comment;
            dateOrderResult.textContent = data.date_order || '';
            colorResult.style.background = data.color || '';
        }

        function clearResult() {
            scanResult.style.display = "none";
            enterResult.style.display = "none";
            alreadyPassedResult.style.display = "none";
            alreadyPassedResult.textContent = '';
            nameResult.textContent = '';
            kilterResult.textContent = '';
            projectResult.textContent = '';
            curatorResult.textContent = '';
            emailResult.textContent = '';
            dateOrderResult.textContent = '';
            commentResult.textContent = '';
            phoneResult.textContent = '';
            statusResult.textContent = '';
            idTicket = null;
            typeTicket = null;
            errorResult.textContent = '';
            colorResult.style.background = '';
        }

        enterResult.addEventListener('click', () => {
            $.ajax({
                type: 'POST',
                url: '/api/enter',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": idTicket,
                    "type": typeTicket,
                    "user_id": {{ auth()->user()->id }}
                },
                success: function (data) {
                    massageResult.textContent = 'Билет '+idTicket+' прошел';
                    console.log(data);
                    clearResult();

                    scanner.start();
                },
                error: function (data) {
                    console.error(data);
                    errorResult.textContent = data.responseJSON;
                    //scanner.start();
                }
            });
        });

        alreadyPassedResult.addEventListener('click', () => {
            clearResult();
            scanner.start();
        });

        // ####### Web Cam Scanning #######

        const scanner = new QrScanner(video, result => setResult(camQrResult, result), {
            onDecodeError: error => {
                camQrResult.textContent = error;
                camQrResult.style.color = 'inherit';
            },
            highlightScanRegion: true,
            highlightCodeOutline: true,
        });


        const updateFlashAvailability = () => {
            scanner.hasFlash().then(hasFlash => {
                camHasFlash.textContent = hasFlash;
                flashToggle.style.display = hasFlash ? 'inline-block' : 'none';
            });
        };

        scanner.start().then(() => {
            updateFlashAvailability();
            // List cameras after the scanner started to avoid listCamera's stream and the scanner's stream being requested
            // at the same time which can result in listCamera's unconstrained stream also being offered to the scanner.
            // Note that we can also start the scanner after listCameras, we just have it this way around in the demo to
            // start the scanner earlier.
            QrScanner.listCameras(true).then(cameras => cameras.forEach(camera => {
                const option = document.createElement('option');
                option.value = camera.id;
                option.text = camera.label;
                camList.add(option);
            }));
        });

        QrScanner.hasCamera().then(hasCamera => camHasCamera.textContent = hasCamera);

        // for debugging
        window.scanner = scanner;

        document.getElementById('scan-region-highlight-style-select').addEventListener('change', (e) => {
            videoContainer.className = e.target.value;
            scanner._updateOverlay(); // reposition the highlight because style 2 sets position: relative
        });
        video.parentNode.insertBefore(scanner.$canvas, video.nextSibling);
        document.getElementById('inversion-mode-select').addEventListener('change', event => {
            scanner.setInversionMode(event.target.value);
        });

        camList.addEventListener('change', event => {
            scanner.setCamera(event.target.value).then(updateFlashAvailability);
        });

        flashToggle.addEventListener('click', () => {
            scanner.toggleFlash().then(() => flashState.textContent = scanner.isFlashOn() ? 'on' : 'off');
        });

        document.getElementById('start-button').addEventListener('click', () => {
            scanner.start();
            clearResult();
        });

        document.getElementById('stop-button').addEventListener('click', () => {
            scanner.stop();
        });
    </script>
@endsection

@section('css')

@endsection
