@extends('manager::layouts.app')

@section('title', 'Dashboard — Snaker Manager')
@section('heading', 'Dashboard')

@section('content')
    <div class="grid">
        <div class="card"><div class="muted">Site sahibləri</div><div class="n">{{ $counts['owners'] }}</div></div>
        <div class="card"><div class="muted">Planlar</div><div class="n">{{ $counts['plans'] }}</div></div>
        <div class="card"><div class="muted">Aktiv abunələr</div><div class="n">{{ $counts['active_subscriptions'] }}</div></div>
        <div class="card"><div class="muted">Temalar</div><div class="n">{{ $counts['themes'] }}</div></div>
    </div>

    <h2 style="font-size:18px">Son site sahibləri</h2>
    <table>
        <thead>
            <tr><th>Ad</th><th>E-poçt</th><th>Status</th><th>Domen</th><th>DB</th></tr>
        </thead>
        <tbody>
            @forelse ($owners as $owner)
                <tr>
                    <td>{{ $owner->name }}</td>
                    <td>{{ $owner->email }}</td>
                    <td>{{ $owner->status?->value ?? $owner->status }}</td>
                    <td>{{ $owner->domains_count }}</td>
                    <td class="muted">{{ $owner->db_name }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Hələ site sahibi yoxdur.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
