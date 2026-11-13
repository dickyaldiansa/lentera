<?php

namespace App\Services;

use App\Models\{Role, User, WorkflowTemplate};
use Illuminate\Validation\ValidationException;

class WorkflowConfiguration
{
    public const BEHAVIORS = [
        'approval' => 'Persetujuan / rekomendasi', 'administration' => 'Verifikasi administrasi',
        'disposition' => 'Disposisi ke penerima', 'invoice' => 'Administrasi & penerbitan tagihan',
        'payment' => 'Pembayaran pemohon & verifikasi Diklat', 'ethics' => 'Telaah etik & penerbitan EC',
        'coordination' => 'KAMKORDIK: persamaan persepsi', 'handover' => 'KAMKORDIK: serah terima',
        'finish' => 'Penyelesaian otomatis & hasil layanan',
    ];

    public function prepare(array $data, ?WorkflowTemplate $template, bool $publishing): array
    {
        $originals = $template?->versions()->latest('version_number')->first()?->steps->keyBy('id') ?? collect();
        $modern = collect($data['steps'])->contains(fn($s) => isset($s['behavior']));
        $steps = [];
        foreach (array_values($data['steps']) as $i => $step) {
            $original = $originals->get($step['source_step_id'] ?? null);
            $behavior = $step['behavior'] ?? $original?->behavior() ?? 'approval';
            $conditions = array_merge($original?->conditions ?? [], ['type' => $step['condition']]);
            if ($modern) {
                $conditions = ['type' => $step['condition'], 'managed' => true, 'behavior' => $behavior,
                    'creates_invoice' => $behavior === 'invoice', 'payment_gate' => $behavior === 'payment',
                    'completes_activity' => $behavior === 'finish', 'outcome' => $step['outcome'] ?? 'information',
                    'revision_target' => (int)($step['revision_target'] ?? ($i+1)),
                    'reject_behavior' => $step['reject_behavior'] ?? 'reject',
                    'allow_pending' => (bool)($step['allow_pending'] ?? false),
                    'letter_content' => $step['letter_content'] ?? null,
                ];
                if ($i === 0) $conditions['document_requirements'] = $data['document_requirements'] ?? '';
            }
            $steps[] = $step + ['behavior' => $behavior];
            $steps[$i]['settings'] = $conditions;
            $steps[$i]['allow_revision'] = isset($step['allow_revision']) ? (bool)$step['allow_revision'] : ($original?->allow_revision ?? true);
            $steps[$i]['allow_reject'] = isset($step['allow_reject']) ? (bool)$step['allow_reject'] : ($original?->allow_reject ?? true);
            if (!$publishing) continue;
            [$kind,$value] = explode(':',$step['assignee'],2);
            $exists = $kind === 'user'
                ? User::whereKey($value)->where('active',true)->whereHas('roles',fn($q)=>$q->where('scope','internal'))->exists()
                : Role::where('slug',$value)->where('scope','internal')->whereHas('users',fn($q)=>$q->where('active',true))->exists();
            if (!$exists) $this->fail("steps.$i.assignee", 'Pilih petugas internal aktif atau jabatan yang memiliki petugas aktif.');
            if (!$modern) continue;
            if($behavior!=='finish'){
                $canAccess=$kind==='user'
                    ? (bool)User::find($value)?->canDo('workflow.approve')
                    : Role::where('slug',$value)->where(fn($q)=>$q->where('slug','super-admin')->orWhereHas('permissions',fn($p)=>$p->where('slug','workflow.approve')))->exists();
                if(!$canAccess)$this->fail("steps.$i.assignee",'Petugas atau jabatan harus memiliki hak akses Persetujuan Saya.');
            }
            if ($data['module'] !== 'diklat') $this->fail('module','Tahap layanan terpadu menggunakan modul Diklat.');
            if (in_array($behavior,['invoice','payment']) && $step['assignee'] !== 'role:diklat-admin') $this->fail("steps.$i.assignee",'Administrasi tagihan dan verifikasi pembayaran ditangani Admin Diklat.');
            if (in_array($behavior,['coordination','handover']) && $step['assignee'] !== 'role:kamkordik-admin') $this->fail("steps.$i.assignee",'Koordinasi ditangani Admin KAMKORDIK.');
            if ($behavior === 'ethics' && $step['assignee'] !== 'role:ethics-chair') $this->fail("steps.$i.assignee",'Tahap telaah etik dituntaskan Ketua Komite Etik melalui penerbitan EC.');
            if (in_array($behavior,['invoice','payment','ethics','coordination','handover','finish']) && $step['condition'] !== 'always') $this->fail("steps.$i.condition",'Tahap proses layanan wajib selalu dijalankan.');
            if($behavior==='payment'){
                $steps[$i]['allow_revision']=false;
                $steps[$i]['allow_reject']=false;
                $steps[$i]['settings']['allow_pending']=false;
            }
            if ($conditions['revision_target'] < 1 || $conditions['revision_target'] > $i+1) $this->fail("steps.$i.revision_target",'Perbaikan hanya dapat kembali ke tahap ini atau tahap sebelumnya.');
            if ($behavior === 'disposition' && !isset($data['steps'][$i+1])) $this->fail('steps','Disposisi harus memiliki tahap penerima berikutnya.');
        }
        if ($publishing && $modern) {
            $behaviors = array_column($steps,'behavior');
            if (end($behaviors) !== 'finish' || count(array_keys($behaviors,'finish')) !== 1) $this->fail('steps','Alur harus memiliki tepat satu tahap penyelesaian di posisi terakhir.');
            foreach (['invoice','payment','ethics','coordination','handover'] as $name) if (count(array_keys($behaviors,$name))>1) $this->fail('steps','Tahap '.$name.' hanya boleh satu kali dalam satu alur.');
            $invoice = array_search('invoice',$behaviors,true); $payment = array_search('payment',$behaviors,true);
            if (($invoice === false) !== ($payment === false) || ($invoice !== false && $payment !== $invoice+1)) $this->fail('steps','Penerbitan tagihan harus langsung diikuti tahap pembayaran dan verifikasi.');
            $coordination=array_search('coordination',$behaviors,true); $handover=array_search('handover',$behaviors,true);
            if ($handover!==false && ($coordination===false || $handover!==$coordination+1)) $this->fail('steps','Serah terima harus langsung mengikuti persamaan persepsi.');
            if ($payment!==false && $coordination!==false && $payment>$coordination) $this->fail('steps','Pembayaran harus selesai sebelum koordinasi KAMKORDIK.');
            foreach($steps as $i=>$step) {
                $target=$step['settings']['revision_target']-1;
                if (($step['allow_revision'] || $step['settings']['reject_behavior']==='revision') && $target < $i) {
                    foreach(array_slice($behaviors,$target,$i-$target) as $crossed) if(in_array($crossed,['invoice','payment','ethics','coordination','handover'])) $this->fail("steps.$i.revision_target",'Perbaikan tidak dapat mengulang tahap pembayaran, etik, atau koordinasi yang sudah selesai.');
                }
            }
            $last=end($steps);
            if ($last['settings']['outcome']==='permit' && blank($last['settings']['letter_content'])) $this->fail('steps','Isi surat izin wajib diisi untuk hasil layanan surat izin.');
        }
        // Older clients must retain the already-published payment gates.
        if ($publishing && !$modern) foreach($originals as $original) {
            if (($original->conditions['payment_gate']??false)||($original->conditions['creates_invoice']??false)||($original->conditions['completes_activity']??false)) {
                if (!collect($steps)->contains(fn($s)=>(int)($s['source_step_id']??0)===$original->id)) $this->fail('steps','Tahap administrasi, pembayaran, dan selesai wajib dipertahankan.');
            }
        }
        return $steps;
    }

    private function fail(string $key,string $message): never { throw ValidationException::withMessages([$key=>$message]); }
}
