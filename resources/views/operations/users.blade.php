@extends('layouts.app')
@section('title','Pengguna & Role')
@section('content')
@php($activeTab=request('tab','users'))
<div class="page-head">
    <div><span class="eyebrow">Administrasi Sistem</span><h1>Pengguna & Role</h1><p>Kelola akun, peran, dan izin akses aplikasi.</p></div>
    <div class="page-actions"><a class="button secondary" href="{{ route('roles.create') }}">＋ Tambah Role</a><a class="button" href="{{ route('users.create') }}">＋ Tambah Pengguna</a></div>
</div>
<div class="module-tabs admin-tabs">
    <a href="{{ route('users.index',['tab'=>'users']) }}" class="{{ $activeTab==='users'?'active':'' }}">Pengguna <span>{{ $users->total() }}</span></a>
    <a href="{{ route('users.index',['tab'=>'roles']) }}" class="{{ $activeTab==='roles'?'active':'' }}">Role <span>{{ $roles->total() }}</span></a>
</div>
@if($activeTab==='roles')
<section class="panel">
    <div class="panel-head"><div><h2>Daftar Role</h2><p>Role mengelompokkan permission yang diterapkan ke pengguna.</p></div></div>
    <div class="table-wrap"><table><thead><tr><th>Role</th><th>Lingkup</th><th>Permission</th><th>Pengguna</th><th>Aksi</th></tr></thead><tbody>
    @forelse($roles as $role)<tr>
        <td><b>{{ $role->name }}</b><small>{{ $role->slug }}</small></td>
        <td><span class="badge {{ $role->scope==='external'?'green':'blue' }}">{{ $role->scope==='external'?'Eksternal':'Internal' }}</span></td>
        <td><b>{{ $role->permissions->count() }} permission</b><small>{{ $role->permissions->pluck('name')->take(3)->join(', ') }}{{ $role->permissions->count()>3?' …':'' }}</small></td>
        <td>{{ $role->users_count }}</td>
        <td><div class="table-actions"><a class="mini-link" href="{{ route('roles.edit',$role) }}">Edit</a>@if($role->slug!=='super-admin')<form method="post" action="{{ route('roles.destroy',$role) }}" onsubmit="return confirm('Hapus role ini?')">@csrf @method('DELETE')<button class="mini-link danger" type="submit">Hapus</button></form>@endif</div></td>
    </tr>@empty<tr><td colspan="5" class="empty">Belum ada role.</td></tr>@endforelse
    </tbody></table></div><x-pagination :paginator="$roles" label="role" />
</section>
@else
<section class="panel">
    <div class="panel-head"><div><h2>Daftar Pengguna</h2><p>Cari dan kelola informasi akun beserta role-nya.</p></div></div>
    <form class="filters" method="get" action="{{ route('users.index') }}"><input type="hidden" name="tab" value="users"><input name="q" value="{{ $term }}" placeholder="Cari nama, email, atau NIK"><button class="button secondary">Cari</button>@if($term)<a class="mini-link" href="{{ route('users.index') }}">Reset</a>@endif</form>
    <div class="table-wrap"><table><thead><tr><th>Pengguna</th><th>Kontak</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($users as $user)<tr>
        <td><b>{{ $user->name }}</b><small>{{ $user->nik?:'NIK belum diisi' }} · {{ $user->position?:'Pengguna LENTERA' }}</small></td>
        <td>{{ $user->email }}<small>{{ $user->phone?:'-' }}</small></td>
        <td>@forelse($user->roles as $role)<span class="badge blue">{{ $role->name }}</span>@empty<small>Belum memiliki role</small>@endforelse</td>
        <td><x-status :status="$user->active?'active':'inactive'"/></td>
        <td><div class="table-actions"><a class="mini-link" href="{{ route('users.edit',$user) }}">Edit</a>@if(!auth()->user()->is($user))<form method="post" action="{{ route('users.status',$user) }}">@csrf @method('PATCH')<button class="mini-link" type="submit">{{ $user->active?'Nonaktifkan':'Aktifkan' }}</button></form><form method="post" action="{{ route('users.destroy',$user) }}" onsubmit="return confirm('Hapus pengguna ini?')">@csrf @method('DELETE')<button class="mini-link danger" type="submit">Hapus</button></form>@endif</div></td>
    </tr>@empty<tr><td colspan="5" class="empty">Tidak ada pengguna yang ditemukan.</td></tr>@endforelse
    </tbody></table></div><x-pagination :paginator="$users" label="pengguna" />
</section>
@endif
@endsection
