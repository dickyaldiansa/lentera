<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowStep extends Model {
    protected $guarded=[]; protected $casts=['allow_revision'=>'boolean','allow_reject'=>'boolean','conditions'=>'array'];
    public function version(){return $this->belongsTo(WorkflowVersion::class,'workflow_version_id');}
    public function behavior(): string {
        return $this->conditions['behavior'] ?? match(true) {
            (bool)($this->conditions['creates_invoice']??false) => 'invoice',
            (bool)($this->conditions['payment_gate']??false) => 'payment',
            (bool)($this->conditions['completes_activity']??false) => 'finish',
            $this->name === 'KAMKORDIK: Persamaan Persepsi' => 'coordination',
            $this->name === 'KAMKORDIK: Serah Terima' => 'handover',
            $this->assignee_value === 'arsiparis' => 'disposition',
            default => 'approval',
        };
    }
}
