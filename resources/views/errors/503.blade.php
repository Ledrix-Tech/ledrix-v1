<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <title>503 Service Unavailable</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --gray-100: #f3f4f6;
            --gray-300: #d1d5db;
            --gray-700: #374151;
            --red-500: #ef4444;
        }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-700);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            max-width: 600px;
            background: #fff;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px;
            text-align: center;
        }

        h1 {
            font-size: 48px;
            font-weight: bold;
            color: var(--red-500);
            margin: 0 0 10px 0;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            line-height: 1.5;
            color: #6b7280;
        }

        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #9ca3af;
        }
    </style>
</head>

<body>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
        integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>

    <script>
        $(document).ready(function() {
            toastr.options.timeOut = 10000;

            @if (Session::has('success'))
                toastr.success('{{ Session::get('success') }}');
            @endif

            @if (Session::has('info'))
                toastr.info('{{ Session::get('info') }}');
            @endif

            @if (Session::has('warning'))
                toastr.warning('{{ Session::get('warning') }}');
            @endif

            @if (Session::has('error'))
                toastr.error('{{ Session::get('error') }}');
            @endif

            @if (Session::has('status'))
                toastr.info('{{ Session::get('status') }}');
            @endif
        });
    </script>

    <div class="container">
        <h1>503</h1>
        <h2>System Alert</h2>

        <p>An unexpected system issue has occurred. Please contact the development team immediately.</p>

        <div class="reason">
            <strong>Detected Issue:</strong>
            <p>Sorry, we are down for maintenance or an unexpected issue occurred.</p>
        </div>

        <p><em>Further actions may be required to restore full functionality.</em></p>

        <div class="footer">— {{ config('app.name') }}</div>
    </div>
</body>

</html>
