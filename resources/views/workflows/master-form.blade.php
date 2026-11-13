@extends('layouts.app')
@section('title','Pengaturan Alur Layanan')
@section('content')
@php
$existingSteps=old('steps',$version?->steps?->map(fn($step)=>[
    'source_step_id'=>$step->id,
    'name'=>$step->name,'action_type'=>$step->action_type,
    'assignee'=>$step->assignee_type.':'.$step->assignee_value,
    'sla_hours'=>$step->sla_hours,'condition'=>$step->conditions['type']??'always',
    'behavior'=>$step->behavior(),'allow_revision'=>(int)$step->allow_revision,'allow_reject'=>(int)$step->allow_reject,
    'allow_pending'=>(int)($step->conditions['allow_pending']??($step->assignee_value==='executive-approver')),
    'revision_target'=>$step->conditions['revision_target']??$step->position,'reject_behavior'=>$step->conditions['reject_behavior']??'reject',
    'outcome'=>$step->conditions['outcome']??(($step->conditions['completes_activity']??false)?'certificate':'information'),
    'letter_content'=>$step->conditions['letter_content']??'',
])->values()->all()?:[
    ['name'=>'Verifikasi Administrasi','behavior'=>'administration','action_type'=>'verification','assignee'=>'role:diklat-admin','sla_hours'=>24,'condition'=>'always'],
    ['name'=>'Selesai','behavior'=>'finish','action_type'=>'approval','assignee'=>'role:diklat-admin','sla_hours'=>24,'condition'=>'always','outcome'=>'information'],
]);
@endphp
<div class="page-head"><div><span class="eyebrow">Master → Pengaturan Alur Layanan</span><h1>{{ $template->exists?'Atur Alur Layanan':'Susun Alur Layanan' }}</h1><p>Pilih proses yang dijalankan pada setiap tahap. Setuju melanjutkan ke tahap berikutnya; perbaikan dan penolakan mengikuti aturan yang Anda tetapkan.</p></div>@if($version)<span class="pill">Versi terbaru: v{{ $version->version_number }} ({{ $version->status==='draft'?'Draf':'Terbit' }})</span>@endif</div>
<form class="panel workflow-form" method="post" action="{{ $template->exists?route('workflow-master.update',$template):route('workflow-master.store') }}">@csrf @if($template->exists)@method('PUT')@endif
    <div class="form-grid">
        <label>Nama workflow *<input name="name" value="{{ old('name',$version?->steps->first()?->conditions['editor']['name']??$template->name) }}" required maxlength="255" placeholder="Contoh: Persetujuan Penelitian"></label>
        <label>Modul *<select name="module" required>@foreach(['diklat'=>'Diklat','ethics'=>'Komite Etik','kamkordik'=>'KAMKORDIK'] as $value=>$label)<option value="{{ $value }}" @selected(old('module',$template->module?:'diklat')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label>Jenis layanan<select name="activity_type_id"><option value="">Semua jenis dalam modul</option>@foreach($activityTypes as $type)<option value="{{ $type->id }}" @selected((string)old('activity_type_id',$template->activity_type_id)===(string)$type->id)>{{ $type->name }}</option>@endforeach</select></label>
        <label>Status saat diterbitkan<select name="active"><option value="1" @selected(old('active',$version?->steps->first()?->conditions['editor']['active']??($template->exists?$template->active:1)))>Aktif</option><option value="0" @selected(!old('active',$version?->steps->first()?->conditions['editor']['active']??($template->exists?$template->active:1)))>Nonaktif</option></select></label>
        <label class="full">Keterangan<textarea name="description" maxlength="5000" placeholder="Tujuan dan ruang lingkup workflow">{{ old('description',$version?->steps->first()?->conditions['editor']['description']??$template->description) }}</textarea></label>
        <label class="full">Persyaratan dokumen kategori ini<textarea name="document_requirements" maxlength="10000" rows="5" placeholder="Satu persyaratan per baris. Kosongkan untuk memakai persyaratan bawaan kategori.">{{ old('document_requirements',$version?->steps->first()?->conditions['document_requirements']??'') }}</textarea></label>
    </div>
    <div class="workflow-builder-head"><div><h2>Tahapan Persetujuan</h2><p>Seret tidak diperlukan; gunakan tombol naik/turun untuk mengatur urutan.</p></div><button class="button secondary" type="button" data-add-step>＋ Tambah Tahap</button></div>
    <div class="workflow-step-list" data-step-list>
        @foreach($existingSteps as $index=>$step)
        <div class="workflow-step-row" data-step>
            <span class="workflow-step-number">{{ $index+1 }}</span>
            <div class="workflow-step-fields">
                <input type="hidden" data-field="source_step_id" name="steps[{{ $index }}][source_step_id]" value="{{ $step['source_step_id'] ?? '' }}">
                <label>Nama tahap<input data-field="name" name="steps[{{ $index }}][name]" value="{{ $step['name'] }}" required maxlength="255"></label>
                <label>Jenis tindakan<select data-field="action_type" name="steps[{{ $index }}][action_type]">@foreach(['verification'=>'Verifikasi','review'=>'Review','approval'=>'Persetujuan','finance'=>'Keuangan','signature'=>'Tanda tangan'] as $value=>$label)<option value="{{ $value }}" @selected($step['action_type']===$value)>{{ $label }}</option>@endforeach</select></label>
                <label>Pejabat / pelaksana<select data-field="assignee" name="steps[{{ $index }}][assignee]" required><optgroup label="Berdasarkan role">@foreach($roles as $role)<option value="role:{{ $role->slug }}" @selected($step['assignee']==='role:'.$role->slug)>{{ $role->name }}</option>@endforeach</optgroup><optgroup label="Pengguna tertentu">@foreach($users as $user)<option value="user:{{ $user->id }}" @selected($step['assignee']==='user:'.$user->id)>{{ $user->name }}</option>@endforeach</optgroup></select></label>
                <label>SLA (jam)<input data-field="sla_hours" type="number" min="1" name="steps[{{ $index }}][sla_hours]" value="{{ $step['sla_hours'] }}" placeholder="24"></label>
                <label>Kondisi<select data-field="condition" name="steps[{{ $index }}][condition]"><option value="always" @selected($step['condition']==='always')>Selalu dijalankan</option><option value="paid" @selected($step['condition']==='paid')>Hanya layanan berbayar</option><option value="requires_ethics" @selected($step['condition']==='requires_ethics')>Memerlukan telaah etik</option><option value="requires_kamkordik" @selected($step['condition']==='requires_kamkordik')>Memerlukan KAMKORDIK</option></select></label>
                @include('workflows.step-settings',['step'=>$step])
            </div>
            <div class="workflow-step-controls"><button type="button" data-up title="Naik">↑</button><button type="button" data-down title="Turun">↓</button><button type="button" data-remove title="Hapus">×</button></div>
        </div>
        @endforeach
    </div>
    @error('steps')<div class="alert error">{{ $message }}</div>@enderror
    <p class="form-hint">Pembayaran dituntaskan melalui unggah bukti dan verifikasi Diklat; telaah etik melalui penerbitan EC; koordinasi melalui halaman KAMKORDIK. Tahap penyelesaian berjalan otomatis setelah seluruh tahap sebelumnya selesai.</p>
    <section class="panel" id="workflow-preview" hidden><h2>Ringkasan Alur Layanan</h2><ol data-preview-list></ol><p>Setuju meneruskan ke tahap berikutnya. Perbaikan menunggu pemohon melengkapi berkas. Penolakan mengikuti aturan tiap tahap.</p></section>
    <div class="form-actions"><a class="button secondary" href="{{ route('workflow-master.index') }}">Batal</a><button class="button secondary" type="button" data-preview>Pratinjau Alur</button><button class="button secondary" name="intent" value="draft">Simpan Draf</button><button class="button" name="intent" value="publish">Terbitkan Alur</button></div>
</form>
<template id="workflow-step-template"><div class="workflow-step-row" data-step><span class="workflow-step-number"></span><div class="workflow-step-fields"><label>Nama tahap<input data-field="name" required></label><label>Jenis tindakan<select data-field="action_type"><option value="verification">Verifikasi</option><option value="review">Review</option><option value="approval">Persetujuan</option><option value="finance">Keuangan</option><option value="signature">Tanda tangan</option></select></label><label>Pejabat / pelaksana<select data-field="assignee"><optgroup label="Berdasarkan role">@foreach($roles as $role)<option value="role:{{ $role->slug }}">{{ $role->name }}</option>@endforeach</optgroup><optgroup label="Pengguna tertentu">@foreach($users as $user)<option value="user:{{ $user->id }}">{{ $user->name }}</option>@endforeach</optgroup></select></label><label>SLA (jam)<input data-field="sla_hours" type="number" min="1" value="24"></label><label>Kondisi<select data-field="condition"><option value="always">Selalu dijalankan</option><option value="paid">Hanya layanan berbayar</option><option value="requires_ethics">Memerlukan telaah etik</option><option value="requires_kamkordik">Memerlukan KAMKORDIK</option></select></label></div><div class="workflow-step-controls"><button type="button" data-up>↑</button><button type="button" data-down>↓</button><button type="button" data-remove>×</button></div></div></template>
@endsection
<template id="workflow-settings-template">@include('workflows.step-settings',['step'=>[]])</template>
@push('scripts')<script>
(()=>{
    const list=document.querySelector('[data-step-list]'),template=document.querySelector('#workflow-step-template');
    if(!list)return;
    template.content.querySelector('.workflow-step-fields').append(document.querySelector('#workflow-settings-template').content.cloneNode(true));
    const preview=()=>{
        const target=document.querySelector('[data-preview-list]');target.replaceChildren();
        list.querySelectorAll('[data-step]').forEach(row=>{
            const item=document.createElement('li');
            const field=key=>row.querySelector(`[data-field="${key}"]`);
            item.textContent=`${field('name').value || 'Tahap tanpa nama'} — ${field('behavior').selectedOptions[0].textContent} — ${field('assignee').selectedOptions[0]?.textContent || 'Pilih petugas'}`;
            if(field('allow_revision').value==='1')item.textContent+=`; perbaikan kembali ke tahap ${field('revision_target').value || 'ini'}`;
            if(field('behavior').value==='finish')item.textContent+=`; hasil: ${field('outcome').selectedOptions[0].textContent}`;
            target.append(item);
        });
        document.querySelector('#workflow-preview').hidden=false;
    };
    document.querySelector('[data-preview]').addEventListener('click',preview);
    list.addEventListener('change',event=>{
        if(event.target.matches('[data-field="behavior"]')){
            const row=event.target.closest('[data-step]'),behavior=event.target.value;
            const role={invoice:'diklat-admin',payment:'diklat-admin',ethics:'ethics-chair',coordination:'kamkordik-admin',handover:'kamkordik-admin'}[behavior];
            if(role)row.querySelector('[data-field="assignee"]').value=`role:${role}`;
            row.querySelector('[data-field="action_type"]').value={invoice:'verification',payment:'finance',ethics:'review',coordination:'review',handover:'approval'}[behavior]||'approval';
            if(['payment','ethics','finish','coordination','handover','invoice'].includes(behavior))row.querySelector('[data-field="condition"]').value='always';
            if(behavior==='payment'){row.querySelector('[data-field="allow_revision"]').value='0';row.querySelector('[data-field="allow_reject"]').value='0';}
        }
    });

    const showCharacterStatus=(field,limitAttempted=false)=>{
        const feedback=field.parentElement.querySelector('[data-character-limit]');
        if(!feedback)return;
        const length=field.value.length,max=field.maxLength,remaining=max-length;
        feedback.classList.toggle('warning',remaining<=Math.min(50,Math.ceil(max*.1))&&remaining>0);
        feedback.classList.toggle('error',remaining<=0||limitAttempted);
        feedback.textContent=limitAttempted
            ? `Batas maksimal ${max.toLocaleString('id-ID')} karakter. Teks tambahan tidak dimasukkan.`
            : remaining<=0
                ? `Batas maksimal ${max.toLocaleString('id-ID')} karakter sudah tercapai.`
                : `${length.toLocaleString('id-ID')} / ${max.toLocaleString('id-ID')} karakter`;
    };

    const bindCharacterLimit=field=>{
        if(field.dataset.limitBound)return;
        field.dataset.limitBound='true';
        const feedback=document.createElement('small');
        feedback.dataset.characterLimit='';
        feedback.className='character-limit';
        feedback.setAttribute('aria-live','polite');
        field.insertAdjacentElement('afterend',feedback);
        field.addEventListener('input',()=>showCharacterStatus(field));
        field.addEventListener('keydown',event=>{
            const addsText=event.key.length===1&&!event.ctrlKey&&!event.metaKey&&!event.altKey;
            const hasSelection=field.selectionStart!==field.selectionEnd;
            if(addsText&&!hasSelection&&field.value.length>=field.maxLength)showCharacterStatus(field,true);
        });
        field.addEventListener('paste',event=>{
            const pasted=event.clipboardData?.getData('text')??'';
            const selected=Math.abs((field.selectionEnd??0)-(field.selectionStart??0));
            if(field.value.length-selected+pasted.length>field.maxLength)setTimeout(()=>showCharacterStatus(field,true));
        });
        showCharacterStatus(field);
    };

    const refresh=()=>{
        [...list.querySelectorAll('[data-step]')].forEach((row,index)=>{
            row.querySelector('.workflow-step-number').textContent=index+1;
            row.querySelectorAll('[data-field]').forEach(field=>{
                field.name=`steps[${index}][${field.dataset.field}]`;
                if(field.dataset.field==='name')field.maxLength=255;
            });
        });
        document.querySelectorAll('.workflow-form [maxlength]').forEach(bindCharacterLimit);
    };

    document.querySelector('[data-add-step]').addEventListener('click',()=>{list.append(template.content.cloneNode(true));refresh();});
    list.addEventListener('click',event=>{
        const row=event.target.closest('[data-step]');if(!row)return;
        const previousRows=[...list.querySelectorAll('[data-step]')];
        const revisionTargets=previousRows.map(item=>{
            const input=item.querySelector('[data-field="revision_target"]');
            return {input,target:input.value?previousRows[Number(input.value)-1]:null};
        });
        if(event.target.matches('[data-remove]')&&list.children.length>1)row.remove();
        if(event.target.matches('[data-up]')&&row.previousElementSibling)list.insertBefore(row,row.previousElementSibling);
        if(event.target.matches('[data-down]')&&row.nextElementSibling)list.insertBefore(row.nextElementSibling,row);
        refresh();
        const nextRows=[...list.querySelectorAll('[data-step]')];
        revisionTargets.forEach(({input,target})=>{if(target)input.value=nextRows.includes(target)?String(nextRows.indexOf(target)+1):'';});
    });
    refresh();
})();
</script>@endpush
