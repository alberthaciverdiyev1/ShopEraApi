@extends('admin.layouts.app')

@php $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white'; @endphp

@section('content')
    <div class="mx-auto max-w-md">
        <form method="POST" action="{{ route('admin.profile.password.update') }}"
              class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @csrf @method('PUT')

            <div>
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">Şifrəni dəyiş</h2>
                <p class="mt-1 text-sm text-gray-500">Hesabınızın şifrəsini buradan yeniləyə bilərsiniz.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Cari şifrə</label>
                <input type="password" name="current_password" required autocomplete="current-password" class="{{ $input }}">
                @error('current_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Yeni şifrə</label>
                <input type="password" name="password" required autocomplete="new-password" class="{{ $input }}">
                @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Yeni şifrə (təkrar)</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $input }}">
            </div>

            <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                <button class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
            </div>
        </form>
    </div>
@endsection
