@extends('admin.layouts.app')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 p-4">
            <p class="font-semibold text-gray-700">Rollar</p>
            <a href="{{ route('admin.roles.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Yeni rol</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase text-gray-400">
                    <th class="px-4 py-3 font-semibold">Ad</th>
                    <th class="px-4 py-3 font-semibold">İcazələr</th>
                    <th class="px-4 py-3 font-semibold">İstifadəçilər</th>
                    <th class="px-4 py-3 text-right font-semibold">Əməliyyat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($roles as $role)
                    <tr class="hover:bg-gray-50/60">
                        <td class="px-4 py-3 font-medium text-gray-700">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $role->users_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.roles.edit', $role->id) }}" class="rounded-lg p-2 text-gray-500 hover:bg-brand-50 hover:text-brand-600">@include('admin.partials.icon', ['name' => 'pencil', 'class' => 'h-4 w-4'])</a>
                                @unless (in_array($role->name, ['admin', 'developer', 'manager']))
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-600" onclick="return confirm('Silinsin?')">@include('admin.partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

    </div>
@endsection
