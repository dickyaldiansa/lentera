@if($application->invoice)
@php($invoice = $application->invoice)
@php($canVerifyPayment = auth()->user()->hasRole('super-admin','diklat-admin'))
<section class="panel">
    <h2>Pembayaran</h2>
    <p>Invoice {{ $invoice->number }} — <b>Rp{{ number_format($invoice->total,0,',','.') }}</b></p>
    <x-status :status="$invoice->status"/>
    @if(auth()->user()->hasRole('applicant'))
        @foreach($paymentMethods as $method)
            <div class="invoice-box"><b>{{ $method->name }}</b><p>{{ $method->bank_name }} {{ $method->account_number ? '— '.$method->account_number : '' }}</p>
            @if($method->account_holder)<p>Atas nama: {{ $method->account_holder }}</p>@endif
            @if($method->instructions)<p>{{ $method->instructions }}</p>@endif</div>
        @endforeach
        @if($paymentMethods->isEmpty())<p>Hubungi Diklat untuk informasi rekening pembayaran.</p>@endif
        @if($invoice->status === 'unpaid')
        <form method="post" enctype="multipart/form-data" action="{{ route('payments.store',$invoice) }}" class="stack">
            @csrf
            <label>Jumlah pembayaran (Rp)<input name="amount" type="number" value="{{ $invoice->total }}" readonly required></label>
            <label>Metode pembayaran<select name="method" required><option value="">Pilih metode</option>@foreach($paymentMethods as $method)<option value="{{ $method->name }}">{{ $method->name }}</option>@endforeach</select></label>
            <label>Nomor referensi<input name="reference" maxlength="100"></label>
            <label>Bukti pembayaran<input type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required></label>
            <small>PDF, JPG, JPEG, atau PNG; maksimal 5 MB.</small>
            <button class="button">Unggah Bukti Pembayaran</button>
        </form>
        @elseif($invoice->status === 'waiting_verification')<p>Bukti telah dikirim. Menunggu verifikasi Diklat.</p>
        @elseif($invoice->status === 'paid')<p>Pembayaran telah diverifikasi Diklat.</p>@endif
    @endif
    @foreach($invoice->payments->sortByDesc('id') as $payment)
        <div class="invoice-box"><b>{{ $payment->method }}</b> <x-status :status="$payment->status"/>
        @if($payment->review_note)<p>Catatan Diklat: {{ $payment->review_note }}</p>@endif
        @if($canVerifyPayment && $payment->proof_path)<button class="mini-link" type="button" data-payment-proof="{{ route('payments.proof',$payment) }}">Lihat Bukti {{ $payment->method }}</button>@endif
        @if($canVerifyPayment && $payment->status === 'waiting_verification')
        <form method="post" action="{{ route('payments.verify',$payment) }}" class="stack">@csrf
            <label>Catatan pemeriksaan<textarea name="review_note" maxlength="2000" placeholder="Wajib diisi jika bukti ditolak"></textarea></label>
            <button class="button" name="status" value="verified">Verifikasi</button>
            <button class="button secondary" name="status" value="rejected">Tolak</button>
        </form>
        @endif</div>
    @endforeach
</section>
@if($canVerifyPayment)<x-payment-proof-modal />@endif
@endif
