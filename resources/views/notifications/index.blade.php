@extends('layouts.app')

@section('title','Notifikasi')

@section('content')
<div class="page-head"><div><span class="eyebrow blue-text">Pemberitahuan</span><h1>Notifikasi</h1><p>Informasi terbaru untuk akun Anda.</p></div></div>
<section class="panel">
    <div class="notification-list">
        @forelse($notifications as $notification)
            <a href="{{ $notification->data['url'] ?? route('dashboard') }}" class="notification-item {{ is_null($notification->read_at) ? 'unread' : '' }}">
                <div><b>{{ $notification->data['title'] ?? 'Notifikasi' }}</b><p>{{ $notification->data['message'] ?? '' }}</p><small>{{ $notification->created_at->diffForHumans() }}</small></div><span>›</span>
            </a>
        @empty
            <div class="empty">Belum ada notifikasi.</div>
        @endforelse
    </div>
    {{ $notifications->links() }}
</section>
@endsection
