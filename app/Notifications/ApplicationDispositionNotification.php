<?php
namespace App\Notifications;
use App\Models\Application;
use App\Models\User;
use Illuminate\Notifications\Notification;
class ApplicationDispositionNotification extends Notification { public function __construct(private readonly Application $application,private readonly User $sender,private readonly ?string $note){} public function via(object $notifiable):array{return ['database'];} public function toDatabase(object $notifiable):array{return ['title'=>'Pengajuan menunggu persetujuan Direktur','message'=>$this->sender->name.' mendisposisikan '.$this->application->number.' — '.$this->application->title.'.'.($this->note?' Catatan: '.$this->note:''),'application_id'=>$this->application->id,'url'=>route('applications.show',$this->application)];} }
