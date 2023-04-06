<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
    <style>
        body {
            font-family: "dejavu sans", serif;
            font-size: 12px;
            background-size: cover;
            position: relative;
            padding: 0;
            margin: 0;
            height: 1019px;
            width: 720px;
        }

        #fon {
            width: 720px;
            height: 1019px;
            position: fixed;
            box-sizing: border-box;
            z-index: 1;
        }

        #top{
            height: 60px;
            left: 0;
            width: 1019px;
            background-color: #fff;
            z-index: 3;
            position: absolute;
            border-bottom: 2px solid #86201c;
            z-index: 5;
        }

        #top-img{
            width: 410px;
            height: auto;
            position: absolute;
            top: 11px;
            left: 20px;
            z-index: 10;
        }

        #top-class-title{
            position: absolute;
            top: 85px;
            left: 15px;
            font-size: 17px;
            position: absolute;
            margin: 0;
            width: 600px;
            z-index: 5;
        }

        #qr-code{
            width: 320px;
            height: 320px;
            position: absolute;
            left: 16px;
            top: 150px;
            z-index: 5;
        }

        .name{
            position: absolute;
            left: 380px;
            top: 162px;
            font-size: 18px;
            z-index: 5;
        }

        .email{
            position: absolute;
            left: 380px;
            top: 240px;
            font-size: 18px;
            z-index: 5;
        }

        .id{
            position: absolute;
            left: 380px;
            top: 320px;
            font-size: 18px;
            z-index: 5;
        }

        #sub{
            position: absolute;
            top: 533px;
            left: 15px;
            width: 610px;
            font-size: 19px;
            line-height: 1.5;
            margin: 0;
            z-index: 5;
        }


        footer{
            position: absolute;
            width: 720px;
            bottom: 0;
            height: 40px;
            background-color: #86201c;
            z-index: 5;
        }



    </style>
</head>
<body>
<img src="https://api.solarsysto.ru/images/main-bg.jpg" alt="fon" id="fon">
<div id="top"></div>
<img src="https://api.solarsysto.ru/images/top-img.png" alt="top" id="top-img">
<img src="{{$url}}" width="420" height="420" id="qr-code">

<p id="top-class-title">Привет дорогой друг, это твой электронный билет на Solar Systo Togathering 2023!</p>

<div class="name">
    Имя: {{$name}}
</div>

<div class="email">
   Email: {{$email}}
</div>

<div class="id">
    ID: {{$kilter}}
</div>

<p id="sub"><strong style="    color: #86201c;
    font-weight: bold;
    font-size: 23px;
    display: inline-block;
    margin-bottom: 20px;">Внимание!</strong><br>
    На входе на фестиваль каждому гостю необходимо будет показать свой qr-код на экране телефона или в распечатанном виде!
    Позаботься об этом заранее!<br><br>
    До встречи, на фестивале!</p>
<footer></footer>
</body>
</html>

