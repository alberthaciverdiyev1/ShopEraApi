<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <title>{{ __('Password reset code') }}</title>
</head>
<body style="margin:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;color:#101828;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6f8;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:520px;background:#ffffff;border-radius:16px;padding:28px;">
                <tr>
                    <td style="font-size:18px;font-weight:700;padding-bottom:6px;">
                        {{ config('app.name') }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size:15px;line-height:1.5;color:#475467;padding-bottom:18px;">
                        @if ($name)
                            {{ __('Hello :name,', ['name' => $name]) }}<br>
                        @endif
                        {{ __('Use the code below to set a new password.') }}
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:10px 0 18px;">
                        <div style="display:inline-block;font-size:30px;font-weight:700;letter-spacing:8px;
                                    background:#fff4ec;color:#ff6300;border-radius:12px;padding:14px 26px;">
                            {{ $code }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:13px;line-height:1.5;color:#667085;">
                        {{ __('The code expires in :minutes minutes.', ['minutes' => $minutes]) }}<br>
                        {{ __('If you did not ask for this, you can ignore this message — your password stays as it is.') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
