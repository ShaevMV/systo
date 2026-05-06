<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width"/>
</head>
<body style="background: #f7f7f7;">
<table style="border: none; border-collapse: collapse; width: 100%; max-width: 815px; margin: auto;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding: 30px 0 30px;">
            <table style="background: white; box-shadow: 0 0 5px 0 hsla(231,3%,61%,.35); border-radius: 10px; width: 100%;" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding: 20px;">
                        <p style="font-family: Tahoma, sans-serif; font-size: 22px;">Возникли трудности со списком на {{ $festivalName }}.</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Комментарий организаторов:</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px; padding: 10px 15px; background: #f3f3f3; border-radius: 6px;">{{ $comment }}</p>
                        <p style="font-family: Tahoma, sans-serif; font-size: 18px;">Свяжитесь с организаторами для уточнения деталей.</p>
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
