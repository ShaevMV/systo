<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width"/>
    <style>
        body {
            background: #f7f7f7;
        }
        .data-table {
            border-radius: 10px;
            -webkit-box-shadow: 0 0 5px 0 hsla(231, 3%, 61%, .35);
            box-shadow: 0 0 5px 0 hsla(231, 3%, 61%, .35);
        }

        @media all and (max-width: 600px) {
            .footer-td {
                display: block;
                margin: auto;
                text-align: center;
            }
        }
    </style>
</head>
<body style="background: #f7f7f7">
<table style="border: none; border-collapse: collapse; width: 100%; max-width: 815px; margin: auto;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding: 30px 0 30px;">
            <table class="data-table" style="border: none; background: white;  box-shadow: 0 0 5px 0 hsla(231,3%,61%,.35);border-radius: 10px; border-collapse: collapse; width: 100%; margin: auto;" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding: 20px;">
                        <table style="border: none; border-collapse: collapse;" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding-left: 20px; font-size: 30px; font-family: Tahoma, sans-serif;">Смена пароля в системе регистрации оргвзносов на Систо</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 20px 20px 20px; border-top: 1px solid #e6e6e6;  border-bottom: 1px solid #e6e6e6;">
                        <p style="text-align: center; font-family: Tahoma, sans-serif; font-size: 24px; font-weight: 500;">Привет, дорогой друг</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Для сменны пароля пожалуйста перейдите по
                            <a href="{{$link}}" style="font-family: Tahoma, sans-serif; font-size: 18px; color: black">ссылке</a>.</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Если Вы получили это письмо ошибочно, проигнорируйте данное письмо!</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">C любовью, команда организаторов Solar Systo Togathering</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 10px 10px 20px;">
                        <table style="border: none; width: 100%; border-collapse: collapse;" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="max-width: 250px;" class="footer-td">
                                    <p style="font-size: 14px; font-family: Tahoma, sans-serif;">© 2003—{{date('Y')}}, Solar Systo Togathering {{date('Y')}}.</p>
                                    <p style="font-size: 14px; font-family: Tahoma, sans-serif;">Все права защищены. Использование материалов сайта только с согласия правообладателей.
                                    <p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
