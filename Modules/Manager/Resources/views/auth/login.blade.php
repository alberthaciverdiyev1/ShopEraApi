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
        .pw{position:relative}
        .pw button{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;background:none;border:0;color:#94a3b8;cursor:pointer;line-height:0}
        .pw button:hover{color:#e2e8f0}
        .pw svg{width:18px;height:18px}
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
        <div class="pw" data-password-field>
            <input type="password" name="password" required style="padding-right:36px">
            <button type="button" data-password-toggle aria-label="Şifrəni göstər">
                <svg data-eye fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                <svg data-eye-off fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
            </button>
        </div>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <button type="submit">Daxil ol</button>
    </form>
</div>
<script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-password-toggle]');
        if (!button) return;
        event.preventDefault();
        var field = button.closest('[data-password-field]');
        var input = field.querySelector('input');
        var reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        button.setAttribute('aria-label', reveal ? 'Şifrəni gizlət' : 'Şifrəni göstər');
        button.querySelector('[data-eye]').style.display = reveal ? 'none' : 'block';
        button.querySelector('[data-eye-off]').style.display = reveal ? 'block' : 'none';
    });
</script>
</body>
</html>
