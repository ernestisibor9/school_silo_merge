<!DOCTYPE html>
<html>
<head>
    <title>{{ config('app.name', 'Stable Shield') }} Mail</title>
</head>
<body>
    <h1>Hello, {{ $data['name'] }}!</h1>
    <p>{{ $data['body'] }}</p>
    <a href="{{ $data['link'] }}" style="margin-top: 50px;">{{ $data['link'] }}</a>
    <div style="margin-top: 50px;width:100%;display:flex;align-items:center;justify-content:center">
        <p style="color: #cccccc;">POWERED BY <a href="https://stableshield.com">STABLE SHIELD SOLUTIONS</a></p>
    </div>
</body>
</html>