<dialog class="proof-modal" data-proof-modal aria-labelledby="proof-modal-title">
    <div class="proof-modal-head">
        <div><span class="eyebrow blue-text">Bukti Pembayaran</span><h2 id="proof-modal-title" data-proof-modal-title>Pratinjau bukti pembayaran</h2></div>
        <button class="proof-modal-close" type="button" data-proof-close aria-label="Tutup popup">&times;</button>
    </div>
    <div class="proof-modal-body"><iframe data-proof-frame title="Pratinjau bukti pembayaran"></iframe></div>
    <div class="proof-modal-actions"><a class="button secondary" data-proof-open target="_blank" rel="noopener">Buka di Tab Baru</a><button class="button" type="button" data-proof-close>Tutup</button></div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const modal=document.querySelector('[data-proof-modal]');
    if(!modal)return;
    const frame=modal.querySelector('[data-proof-frame]');
    const title=modal.querySelector('[data-proof-modal-title]');
    const openLink=modal.querySelector('[data-proof-open]');
    document.querySelectorAll('[data-payment-proof]').forEach(button=>button.addEventListener('click',()=>{
        frame.src=button.dataset.paymentProof;
        title.textContent=button.dataset.proofTitle;
        openLink.href=button.dataset.paymentProof;
        modal.showModal();
    }));
    modal.querySelectorAll('[data-proof-close]').forEach(button=>button.addEventListener('click',()=>modal.close()));
    modal.addEventListener('click',event=>{if(event.target===modal)modal.close()});
    modal.addEventListener('close',()=>{frame.src='about:blank'});
});
</script>
