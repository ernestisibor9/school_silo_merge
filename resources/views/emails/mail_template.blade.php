<!DOCTYPE html>
<html>

<head>
    <title>{{ config('app.name', 'Stable Shield') }} Mail</title>
</head>

<body>
    <h1>Hello, {{ $data['name'] }}!</h1>
    <p>{{ $data['body'] }}</p>
    {{--  <a href="{{ $data['link'] }}" style="margin-top: 50px;">{{ $data['link'] }}</a>  --}}
    <a href="{{ $data['link'] }}"
        style="display:inline-block; padding:12px 24px; background-color:#007bff; color:#ffffff; text-decoration:none; border-radius:5px; margin-top:20px;">
        Verify Your Email
    </a>
    <div style="margin-top: 50px;width:100%;display:flex;align-items:center;justify-content:center">
        <p style="color: #cccccc;">POWERED BY <a href="https://stableshield.com">STABLE SHIELD SOLUTIONS</a></p>
    </div>
</body>

</html>
