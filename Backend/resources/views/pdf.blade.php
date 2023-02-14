<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
    <style>
        body {
            font-family: "dejavu sans", serif;
            font-size: 20px;
        }

        #wrapper{
            width: 990px;
            margin: 0 auto;
            background: url('http://193.106.175.59/images/top-img.png') no-repeat center;
            background-size: cover;
        }

        .container{
            position: relative;
            box-sizing: border-box;
            margin-left: 45px;
            margin-right: 45px;
        }

        #top{
            border-bottom: 4px solid #86201c;
            background-color: #fff;
            box-sizing: border-box;
            height: 80px;

        }

        #top img{
            width: 70%;
            position: relative;
            top: 10px;
            left: -7px;
        }

        #main{
            margin-top: 40px;
        }

        #in-main{
            display: flex;
            flex-wrap: nowrap;
            justify-content: space-between;
            margin-top: 45px;
        }

        .lefter{
            width: 400px;
        }

        .lefter img{
            width: 100%;
            height: auto;
        }

        .righter{
            width: 450px;
            padding-top: 10px;
        }

        .item{
            margin-bottom: 50px;
        }

        .item h5{
            margin-top: 0;
            margin-bottom: 17px;
            font-size: 25px;
        }

        .item span{
            color: #86201c;
            font-size: 23px;
        }

        #sub{
            margin-top: 150px;
            padding-bottom: 150px;
        }

        #sub h3{
            margin-top: 0;
            color: #86201c;
            font-size: 28px;
            margin-bottom: 25px;
        }

        #sub p{
            line-height: 1.5;
            margin-top: 25px;
            margin-bottom: 25px;
        }

        #footer{
            height: 60px;
            background: #86201c;
        }

    </style>
</head>
<body>
<div id="wrapper">
    <div id="top">
        <div class="container">
            <img src="http://193.106.175.59/images/main-bg.jpg" alt="top-img">
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
            <p>На входе на фестиваль каждому гостю необходимо будет показать свой qr-код <br>на экране телефона или в распечатанном виде!<br>
                Позаботься об этом заранее!</p>

            <p>До встречи, на фестивале!</p>
        </div>
    </div>
    <div id="footer">

    </div>
</div>
</body>
</html>
