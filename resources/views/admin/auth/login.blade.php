<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş — ShopEra Admin</title>
    @vite(['resources/admin/app.ts'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-900 p-4">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-2xl">
        <div class="mb-6 flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-xl font-black text-white">S</span>
            <div>
                <p class="text-lg font-bold text-gray-800">ShopEra Admin</p>
                <p class="text-xs text-gray-400">İdarə panelinə giriş</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Telefon və ya e-poçt</label>
                <input type="text" name="identifier" value="{{ old('identifier') }}" required autofocus
                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Şifrə</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-brand-500">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                Məni xatırla
            </label>
            <button type="submit" class="w-full rounded-lg bg-brand-600 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Daxil ol
            </button>
        </form>
    </div>
</body>
</html>
