<?php

namespace Database\Seeders;

use App\Models\{Application, EthicsApplication, Institution, Permission, Role, Student, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['email' => 'admin@lentera.test', 'name' => 'Administrator LENTERA', 'role' => 'super-admin'],
            ['email' => 'pemohon@lentera.test', 'name' => 'Pemohon Demo', 'role' => 'applicant'],
            ['email' => 'mahasiswa@lentera.test', 'name' => 'Mahasiswa Demo', 'role' => 'student'],
            ['email' => 'diklat@lentera.test', 'name' => 'Admin Diklat', 'role' => 'diklat-admin'],
            ['email' => 'kamkordik@lentera.test', 'name' => 'Admin KAMKORDIK', 'role' => 'kamkordik-admin'],
            ['email' => 'etik@lentera.test', 'name' => 'Admin Komite Etik', 'role' => 'ethics-admin'],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => Hash::make('password'), 'active' => true]
            );

            if ($roleId = Role::where('slug', $account['role'])->value('id')) {
                $user->roles()->syncWithoutDetaching([$roleId]);
            }

            if ($account['role'] === 'student') {
                Student::whereNull('user_id')->oldest()->first()?->update(['user_id' => $user->id]);
            }
        }

        // Admin Diklat memproses pengajuan; hanya Pemohon yang membuatnya.
        $createPermission = Permission::where('slug', 'diklat.create')->value('id');
        if ($createPermission) {
            Role::where('slug', 'diklat-admin')->first()?->permissions()->detach($createPermission);
        }

        // Sebarkan data contoh ke beberapa pemohon internal agar akun Pemohon
        // Demo hanya menampilkan dua permohonan miliknya, sedangkan Admin
        // Diklat tetap dapat melihat keseluruhan antrean demo.
        $applicantRole = Role::where('slug', 'applicant')->first();
        $mainApplicant = User::where('email', 'pemohon@lentera.test')->first();
        $institutions = Institution::where('name', '!=', 'RSUD Embung Fatimah Kota Batam')
            ->orderBy('id')->limit(4)->get();

        if ($applicantRole && $mainApplicant && $institutions->isNotEmpty()) {
            $mainApplicant->update(['institution_id' => $institutions->first()->id]);

            $otherApplicants = $institutions->values()->map(function ($institution, $index) use ($applicantRole) {
                $user = User::updateOrCreate(
                    ['email' => 'pemohon-demo-'.($index + 2).'@internal.lentera'],
                    [
                        'name' => 'Pemohon '.$institution->name,
                        'institution_id' => $institution->id,
                        'password' => Hash::make(Str::random(40)),
                        'active' => false,
                    ]
                );
                $user->roles()->sync([$applicantRole->id]);

                return $user;
            });

            $distribute = function ($records) use ($mainApplicant, $otherApplicants, $institutions) {
                foreach ($records->values() as $index => $record) {
                    $owner = $index < 2
                        ? $mainApplicant
                        : $otherApplicants[($index - 2) % $otherApplicants->count()];
                    $institution = $index < 2
                        ? $institutions->first()
                        : $institutions[($index - 2) % $institutions->count()];
                    $record->update([
                        'applicant_id' => $owner->id,
                        'institution_id' => $institution->id,
                    ]);
                }
            };

            $demoApplications = Application::where('description', 'Kegiatan eksternal yang diajukan melalui portal Lentera.')
                ->where('person_in_charge', 'Andi Saputra')->orderBy('number')->get()
                ->sortBy(fn ($application) => in_array($application->status, ['revision_required', 'waiting_payment'], true) ? 0 : 1);
            $distribute($demoApplications);
            $distribute(EthicsApplication::where('principal_investigator', 'like', 'Dr. Peneliti %')
                ->orderBy('number')->get());
        }
    }
}
