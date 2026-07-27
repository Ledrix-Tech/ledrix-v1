@php
    $pageTitle = trim($__env->yieldContent('title')) ?: ($title ?? config('app.name', 'Ledrix'));
    $contentAlign = $contentAlign ?? 'left';
    $signOff = $signOff ?? 'The Ledrix Team';
    $ledrixLogoUrl = $ledrixLogoUrl ?? asset(config('branding.logo', 'admin-assets/dpm-logos/logo-ic.png'));
@endphp
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $pageTitle }}</title>
    @include('emails.partials.styles')
</head>

<body style="margin:0;padding:0;background:linear-gradient(135deg,#db165b,#673187,#f7b63e);">
    <center>
        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"
            style="background:linear-gradient(135deg,#db165b,#673187,#f7b63e);margin:0;padding:24px 12px;">
            <tr>
                <td align="center" valign="top">
                    <table role="presentation" class="email-container" width="100%" border="0" cellpadding="0"
                        cellspacing="0" style="max-width:600px;background-color:#f7f7ff;border-radius:8px;overflow:hidden;">

                        {{-- Ledrix logo header --}}
                        <tr>
                            <td align="center" style="padding:24px 20px 16px;background-color:#ffffff;border-bottom:3px solid #673187;">
                                <img src="{{ $ledrixLogoUrl }}" width="160" alt="Ledrix"
                                    style="display:block;margin:0 auto;max-width:160px;height:auto;">
                            </td>
                        </tr>

                        @hasSection('hero')
                            <tr>
                                <td align="center" style="padding:0;background-color:#f7f7ff;">
                                    @yield('hero')
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td class="email-content-cell email-body"
                                style="padding:28px 24px 12px;text-align:{{ $contentAlign }};background-color:#f7f7ff;">
                                @yield('content')
                            </td>
                        </tr>

                        <tr>
                            <td class="email-content-cell"
                                style="padding:0 24px 28px;text-align:{{ $contentAlign }};background-color:#f7f7ff;font-family:'Asap',Helvetica,Arial,sans-serif;">
                                @hasSection('signoff')
                                    @yield('signoff')
                                @else
                                    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
                                        Thank you,<br>
                                        <strong style="color:#673187;font-style:normal;">{{ $signOff }}</strong>
                                    </p>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td align="center"
                                style="padding:14px 20px;background-color:#ede9fe;font-family:'Asap',Helvetica,Arial,sans-serif;font-size:12px;color:#666;">
                                &copy; {{ date('Y') }} Ledrix &mdash; Multi-tenant sales CRM
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </center>
</body>

</html>
