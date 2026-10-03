@extends('manager::layouts.app')
@section('content')
    @php $input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-brand-500 focus:ring-brand-500'; $label = 'mb-1 block text-sm font-medium text-gray-700'; @endphp
    <form method="POST" action="{{ route('manager.settings.update') }}"
          class="mx-auto max-w-2xl space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf @method('PUT')

        <div>
            <h2 class="text-base font-semibold text-gray-800">Dəstək əlaqələri</h2>
            <p class="mt-1 text-sm text-gray-500">
                Bu nömrə free-plan sahiblərinin admin panelindəki "Planı yüksəlt" səhifəsində WhatsApp düyməsi kimi göstərilir.
            </p>
        </div>

        <div>
            <label class="{{ $label }}">{{ $keys['support_whatsapp'] }}</label>
            <input name="support_whatsapp" value="{{ old('support_whatsapp', $values['support_whatsapp'] ?? '') }}"
                   placeholder="+994501234567" class="{{ $input }}">
            @error('support_whatsapp')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="{{ $label }}">{{ $keys['support_email'] }}</label>
            <input name="support_email" value="{{ old('support_email', $values['support_email'] ?? '') }}"
                   placeholder="support@snaker.store" class="{{ $input }}">
            @error('support_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end border-t border-gray-200 pt-4">
            <button class="rounded-lg bg-brand-600 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yadda saxla</button>
        </div>
    </form>
@endsection
