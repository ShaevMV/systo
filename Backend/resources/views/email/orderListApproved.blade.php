<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width"/>
    <style>
        body { background: #f7f7f7; }
        .data-table {
            border-radius: 10px;
            box-shadow: 0 0 5px 0 hsla(231, 3%, 61%, .35);
        }
        @media all and (max-width: 600px) {
            .footer-td { display: block; margin: auto; text-align: center; }
        }
    </style>
</head>
<body style="background: #f7f7f7">
<table style="border: none; border-collapse: collapse; width: 100%; max-width: 815px; margin: auto;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding: 30px 0 30px;">
            <table class="data-table" style="border: none; background: white; box-shadow: 0 0 5px 0 hsla(231,3%,61%,.35); border-radius: 10px; border-collapse: collapse; width: 100%; margin: auto;" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding: 20px;">
                        <p style="padding-left: 20px; font-size: 30px; font-family: Tahoma, sans-serif; margin: 0;">Билеты на {{ $festivalName }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 20px 20px 20px; border-top: 1px solid #e6e6e6; border-bottom: 1px solid #e6e6e6;">
                        <p style="text-align: center; font-family: Tahoma, sans-serif; font-size: 24px; font-weight: 500;">Привет, дорогой друг!</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Твое участие на {{ $festivalName }} одобрено.</p>
                        @if(!empty($locationName))
                            <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Локация: <strong>{{ $locationName }}</strong></p>
                        @endif
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Электронные билеты с QR-кодом на каждого гостя прикреплены к этому письму.</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 22px;">ВНИМАНИЕ! Все ваши гости должны заполнить небольшие анкеты для активации QR-кода. Пожалуйста, сообщите своим гостям о том, что им на почту отправлена ссылка на анкету.</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">QR-код участника является персональным и предоставляется исключительно лицу, чьи ФИО и адрес электронной почты указаны в файле. Не подлежит перепродаже, передаче и использованию третьими лицами. В случае выявления факта нарушения билет будет незамедлительно аннулирован без права восстановления, а в посещении Систо будет отказано! На входе на фестиваль каждому гостю необходимо будет показать свой QR-код на экране телефона или в распечатанном виде.</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">До встречи на фестивале!</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">С любовью, команда организаторов Solar Systo Togathering</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 10px 10px 20px;">
                        <p style="font-size: 14px; font-family: Tahoma, sans-serif;">© 2003—{{ date('Y') }}, Solar Systo Togathering.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
