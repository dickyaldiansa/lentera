<?php

namespace App\Http\Controllers;

use App\Services\DiklatContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiklatContactController extends Controller
{
    public function edit()
    {
        return view('operations.diklat-contact', ['number' => DiklatContact::number()]);
    }

    public function update(Request $request)
    {
        $request->validate(['whatsapp_number' => ['required', 'string', 'max:40']]);
        $request->merge(['whatsapp_number' => DiklatContact::normalize($request->input('whatsapp_number'))]);
        $data = $request->validate([
            'whatsapp_number' => ['required', 'regex:/^628[1-9][0-9]{7,11}$/'],
        ], ['whatsapp_number.regex' => 'Masukkan nomor WhatsApp Indonesia yang valid, diawali 08, 62, atau +62.']);

        DB::transaction(function () use ($request, $data) {
            $old = DB::table('settings')->where('key', DiklatContact::KEY)->first();
            DB::table('settings')->updateOrInsert(['key' => DiklatContact::KEY], [
                'group' => 'contact', 'value' => $data['whatsapp_number'], 'type' => 'string',
                'public' => true, 'created_at' => $old?->created_at ?? now(), 'updated_at' => now(),
            ]);
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id, 'ip_address' => $request->ip(),
                'module' => 'master.diklat_contact', 'action' => $old ? 'update' : 'create',
                'auditable_type' => 'settings',
                'auditable_id' => DB::table('settings')->where('key', DiklatContact::KEY)->value('id'),
                'old_values' => $old ? json_encode(['value' => $old->value]) : null,
                'new_values' => json_encode(['value' => $data['whatsapp_number']]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return redirect()->route('master.diklat-contact.edit')->with('success', 'Nomor WhatsApp Admin Diklat berhasil disimpan.');
    }
}
