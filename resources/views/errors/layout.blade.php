@php
    $status = $status ?? 500;
    $heading = $heading ?? 'Xəta';
    $message = $message ?? 'Gözlənilməz xəta baş verdi.';
@endphp
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} — {{ $heading }}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
             font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
             background:radial-gradient(circle at 20% 0%,#0e2233,transparent 40%),#0b1220;color:#e2e8f0;padding:24px}
        .card{width:100%;max-width:560px;text-align:center;
              background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);
              border-radius:22px;padding:44px 32px;box-shadow:0 30px 70px rgba(0,0,0,.4)}
        .code{font-size:84px;font-weight:900;line-height:1;letter-spacing:-2px;
              background:linear-gradient(120deg,#06b6d4,#6366f1);-webkit-background-clip:text;
              background-clip:text;color:transparent}
        h1{font-size:22px;margin:18px 0 8px}
        p{color:#94a3b8;font-size:15px;line-height:1.6;margin:0 0 26px}
        .actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:12px;
             font-size:14px;font-weight:700;text-decoration:none;border:1px solid rgba(255,255,255,.14);color:#e2e8f0}
        .btn:hover{background:rgba(255,255,255,.06)}
        .btn.primary{background:#06b6d4;border-color:#06b6d4;color:#04222b}
        .btn.primary:hover{background:#22c3dd}
        .brand{margin-top:26px;font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#475569}
    </style>
</head>
<body>
    <main class="card">
        <div class="code">{{ $status }}</div>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            <a class="btn primary" href="{{ url('/') }}">Ana səhifə</a>
            <a class="btn" href="javascript:history.back()">Geri qayıt</a>
        </div>
        <div class="brand">Snaker</div>
    </main>
</body>
</html>
