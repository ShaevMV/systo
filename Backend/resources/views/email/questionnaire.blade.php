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
                                <td style="padding-left: 20px; font-size: 30px; font-family: Tahoma, sans-serif;">Анкета участника Solar Systo Togathering </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 20px 20px 20px; border-top: 1px solid #e6e6e6;  border-bottom: 1px solid #e6e6e6;">
                        <p style="text-align: center; font-family: Tahoma, sans-serif; font-size: 24px; font-weight: 500;">Привет, дорогой друг</p>
                        <p style="text-align: center; font-family: Tahoma, sans-serif; font-size: 24px; font-weight: 500;">Поздравляем тебя с зарегистрированным членством в нашем приватном клубе. Благодарим за поддержку нашего любимого Систо.</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Просим тебя так же заполнить небольшую анкету, это поможет нам улучшить совместное взаимодействие.
                            <br/>При введении данных твоего телеграмм аккаунта ты получишь доступ в новый приватный чат Solar Systo Togathering.  Если не планируешь участвовать в общем чате, можно отписаться от него, это не повлияет на твои права и возможности.
                            </p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Ссылка на анкету: <a href="{{ $link }}" target="_blank"> ссылка на анкету </a>  </p>

                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Если у тебя остались вопрос - пиши в ТГ <a href="https://t.me/systo_club">@systo_club</a></p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">C любовью, команда организаторов Solar Systo Togathering</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 20px 10px 10px 20px;">
                        <table style="border: none; width: 100%; border-collapse: collapse;" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="max-width: 250px;" class="footer-td">
                                    <p style="font-size: 14px; font-family: Tahoma, sans-serif;">© 2003—{{date('Y')}}, Solar Systo Togathering {{date('Y')}}.</p>
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
