<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
    <style>
        body {
            font-family: "dejavu sans", serif;
            font-size: 18px;
        }

        #wrapper {
            width: 210mm;
            height: 250mm;
            background: url('http://193.106.175.59/images/top-img.png');
            background-size: cover;
            position: relative;
        }

        .container {
            position: relative;
            box-sizing: border-box;
            margin-left: 30px;
            margin-right: 30px;
        }

        #top {
            border-bottom: 4px solid #86201c;
            background-color: #fff;
            box-sizing: border-box;
            height: 60px;

        }

        #top img {
            width: 465px;
            position: relative;
            top: 7px;
            left: -5px;
        }

        #main {
            margin-top: 28px;
        }

        #in-main {
            display: flex;
            flex-wrap: nowrap;
            justify-content: space-between;
            margin-top: 28px;
        }

        .lefter {
            width: 290px;
        }

        .lefter img {
            width: 100%;
            height: auto;
        }

        .righter {
            width: 421px;
            padding-top: 2px;
        }

        .item {
            margin-bottom: 35px;
        }

        .item h5 {
            margin-top: 0;
            margin-bottom: 14px;
            font-size: 22px;
        }

        .item span {
            color: #86201c;
            font-size: 22px;
        }

        #sub {
            margin-top: 60px;
            padding-bottom: 52px;
        }

        #sub h3 {
            margin-top: 0;
            color: #86201c;
            font-size: 26px;
            margin-bottom: 24px;
        }

        #sub p {
            line-height: 1.5;
            margin-top: 0;
            margin-bottom: 27px;
        }

        #footer {
            height: 60px;
            background: #86201c;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

    </style>
</head>
<body>
<div id="wrapper">
    <div id="top">
        <div class="container">
            <img src="http://193.106.175.59/images/main-bg.jpg" alt="top-img" width="210">
        </div>
    </div>

    <div id="main">
        <div class="container">
            <p>Привет дорогой друг, это твой электронный билет на Солар Систо 2023!</p>

            <div id="in-main">
                <div class="lefter">
                    <img src="{{$url}}" alt="qr-code">
                </div>

                <div class="righter">
                    <div class="item">
                        <h5>Имя:</h5>
                        <span style="font-weight: bold; text-transform: uppercase;">{{$name}}</span>
                    </div>

                    <div class="item">
                        <h5>Email:</h5>
                        <span>{{$email}}</span>
                    </div>

                    <div class="item">
                        <h5>Id Билета:</h5>
                        <span style="font-weight: bold; text-transform: uppercase;">E-{{$kilter}}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="sub">
        <div class="container">
            <h3>Внимание!</h3>
            <p>На входе на фестиваль каждому гостю необходимо будет показать свой qr-код на экране телефона или в
                распечатанном виде!<br>
                Позаботься об этом заранее!</p>

            <p>До встречи, на фестивале!</p>
        </div>
    </div>
    <div id="footer">

    </div>
</div>
</body>
</html>

