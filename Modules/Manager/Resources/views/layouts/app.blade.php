<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Snaker Manager')</title>
    <style>
        *{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#f1f5f9;color:#0f172a}
        header{display:flex;align-items:center;gap:16px;background:#0f172a;color:#fff;padding:14px 24px}
        header .brand{font-weight:700} header nav a{color:#cbd5e1;text-decoration:none;margin-right:14px;font-size:14px}
        header .spacer{flex:1} header form{margin:0}
        header button{background:#ef4444;color:#fff;border:0;border-radius:8px;padding:8px 14px;cursor:pointer;font-size:14px}
        main{max-width:1100px;margin:24px auto;padding:0 16px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px}
        .card .n{font-size:28px;font-weight:700;margin-top:6px}
        table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
        th,td{padding:12px 14px;text-align:left;border-bottom:1px solid #eef2f7;font-size:14px}
        th{background:#f8fafc;font-size:12px;text-transform:uppercase;color:#64748b}
        .muted{color:#64748b}
    </style>
</head>
<body>
<header>
    <span class="brand">Snaker Manager</span>
    <nav>
        <a href="{{ route('manager.dashboard') }}">Dashboard</a>
    </nav>
    <span class="spacer"></span>
    <span style="font-size:14px">{{ auth('owner')->user()?->email }}</span>
    <form method="POST" action="{{ route('manager.logout') }}">
        @csrf
        <button type="submit">Çıxış</button>
    </form>
</header>
<main>
    @yield('content')
</main>
</body>
</html>
