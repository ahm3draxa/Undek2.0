<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $senderName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;font-family:Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#0c0c12;padding:20px 32px;">
                            <span style="color:#f5f5fa;font-size:18px;font-weight:700;letter-spacing:0.02em;">Undek</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;color:#333333;font-size:15px;line-height:1.6;">
                            {!! nl2br(e($body)) !!}
                            <p style="margin-top:32px;">
                                Thanks,<br>
                                {{ $senderName }}<br>
                                Undek
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background-color:#f4f4f7;color:#8a8a99;font-size:12px;">
                            You're receiving this email because you're a valued Undek client. If you'd rather not receive occasional updates like this, just reply and let us know.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
