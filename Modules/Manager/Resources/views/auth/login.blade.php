<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş — Snaker Manager</title>
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#0f172a;color:#e2e8f0;display:flex;min-height:100vh;align-items:center;justify-content:center}
        .box{background:#1e293b;padding:32px;border-radius:16px;width:360px}
        h1{font-size:20px;margin:0 0 20px}
        label{display:block;font-size:13px;margin:12px 0 6px;color:#94a3b8}
        input{width:100%;padding:10px 12px;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#fff;font-size:14px}
        button{width:100%;margin-top:20px;padding:11px;border:0;border-radius:8px;background:#06b6d4;color:#04212b;font-weight:700;cursor:pointer}
        .err{color:#fca5a5;font-size:13px;margin-top:10px}
    </style>
</head>
<body>
<div class="box">
    <h1>Snaker Manager</h1>
    <form method="POST" action="{{ route('manager.login.attempt') }}">
        @csrf
        <label>E-poçt</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        <label>Şifrə</label>
        <input type="password" name="password" required>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <button type="submit">Daxil ol</button>
    </form>
</div>
</body>
</html>
