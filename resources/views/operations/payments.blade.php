@extends('layouts.app')
@section('title','Pembayaran Diklat')
@section('content')
@php($canVerify=auth()->user()->hasRole('super-admin','diklat-admin'))
<div class="page-head">
    <div><span class="eyebrow blue-text">Diklat Eksternal</span><h1>Pembayaran Diklat</h1><p>Invoice, bukti bayar, dan hasil verifikasi pembayaran.</p></div>
</div>
<section class="panel">
    <div class="table-wrap"><table><thead><tr><th>Invoice</th><th>Pengajuan</th><th>Nilai</th><th>Pembayaran</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($invoices as $invoice)
        @php($payment=$invoice->payments->sortByDesc('paid_at')->first())
        <tr>
            <td><b>{{ $invoice->number }}</b><small>{{ $invoice->application->number }}</small></td>
            <td><a href="{{ route('applications.show',$invoice->application) }}"><b>{{ $invoice->application->title }}</b></a><small>{{ $invoice->application->applicant->name }}</small></td>
            <td><b>Rp{{ number_format($invoice->total,0,',','.') }}</b></td>
            <td>@if($payment)<b>{{ $payment->method ?: '-' }}</b><small>{{ $payment->reference ?: 'Tanpa referensi' }} · {{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</small>@else<span class="warning">Belum ada bukti</span>@endif</td>
            <td>{{ $invoice->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
            <td><x-status :status="$invoice->status"/></td>
            <td><div class="table-actions">
                <a class="mini-link" href="{{ route('applications.show',$invoice->application) }}">Detail</a>
                @if($payment?->proof_path)<button class="mini-link" type="button" data-payment-proof="{{ route('payments.proof',$payment) }}" data-proof-title="Bukti {{ $invoice->number }}">Lihat Bukti</button>@endif
                @if($canVerify && $payment?->status==='waiting_verification')
                    <form method="post" action="{{ route('payments.verify',$payment) }}">@csrf<button class="mini-link" name="status" value="verified" type="submit">Verifikasi</button></form>
                    <form method="post" action="{{ route('payments.verify',$payment) }}">@csrf<input name="review_note" maxlength="2000" required placeholder="Alasan penolakan"><button class="mini-link danger" name="status" value="rejected" type="submit">Tolak</button></form>
                @endif
            </div></td>
        </tr>
    @empty<tr><td colspan="7" class="empty">Belum ada invoice pembayaran.</td></tr>@endforelse
    </tbody></table></div>
    <x-pagination :paginator="$invoices" label="invoice" />
</section>

<x-payment-proof-modal />
@endsection
