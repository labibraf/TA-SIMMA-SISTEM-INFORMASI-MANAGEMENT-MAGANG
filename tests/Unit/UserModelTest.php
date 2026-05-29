<?php

namespace Tests\Unit;

use App\Models\Bagian;
use App\Models\Mentor;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_role_helpers_are_case_insensitive(): void
    {
        $user = new User(['name' => 'Demo']);

        $roleAdmin = new Role();
        $roleAdmin->setAttribute('role_name', 'AdMiN');
        $user->setRelation('role', $roleAdmin);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isMentor());
        $this->assertFalse($user->isPeserta());

        $roleMentor = new Role();
        $roleMentor->setAttribute('role_name', 'mentor');
        $user->setRelation('role', $roleMentor);
        $this->assertTrue($user->isMentor());

        $roleIntern = new Role();
        $roleIntern->setAttribute('role_name', 'INTERN');
        $user->setRelation('role', $roleIntern);
        $this->assertTrue($user->isPeserta());
    }

    public function test_actual_name_prioritizes_peserta_then_mentor_then_user_name(): void
    {
        $user = new User(['name' => 'Nama User']);

        $peserta = new Peserta();
        $peserta->setAttribute('nama_lengkap', 'Nama Peserta');
        $user->setRelation('peserta', $peserta);
        $user->unsetRelation('mentor');
        $this->assertSame('Nama Peserta', $user->actual_name);

        $user->unsetRelation('peserta');
        $mentor = new Mentor();
        $mentor->setAttribute('nama_mentor', 'Nama Mentor');
        $user->setRelation('mentor', $mentor);
        $this->assertSame('Nama Mentor', $user->actual_name);

        $user->unsetRelation('mentor');
        $this->assertSame('Nama User', $user->actual_name);
    }

    public function test_profile_status_returns_expected_structure(): void
    {
        $user = new User([
            'name' => 'Fallback User',
            'email' => 'fallback@example.test',
        ]);

        $peserta = new Peserta();
        $peserta->setAttribute('nama_lengkap', 'Peserta A');
        $peserta->setAttribute('email', 'peserta@example.test');
        $user->setRelation('peserta', $peserta);

        $this->assertSame([
            'type' => 'peserta',
            'complete' => true,
            'name' => 'Peserta A',
            'email' => 'peserta@example.test',
        ], $user->profile_status);

        $user->unsetRelation('peserta');
        $mentor = new Mentor();
        $mentor->setAttribute('nama_mentor', 'Mentor A');
        $mentor->setAttribute('email', 'mentor@example.test');
        $user->setRelation('mentor', $mentor);

        $this->assertSame([
            'type' => 'mentor',
            'complete' => true,
            'name' => 'Mentor A',
            'email' => 'mentor@example.test',
        ], $user->profile_status);

        $user->unsetRelation('mentor');
        $this->assertSame([
            'type' => 'user',
            'complete' => false,
            'name' => 'Fallback User',
            'email' => 'fallback@example.test',
        ], $user->profile_status);
    }

    public function test_departemen_info_uses_profile_and_admin_fallbacks(): void
    {
        $user = new User(['name' => 'User']);

        $bagian = new Bagian(['nama_bagian' => 'IT Support']);
        $peserta = new Peserta();
        $peserta->setRelation('bagian', $bagian);
        $user->setRelation('peserta', $peserta);

        $this->assertSame('IT Support', $user->departemen_name);
        $this->assertSame([
            'bagian' => 'IT Support',
            'type' => 'peserta',
            'icon' => 'ti ti-school',
            'color' => 'blue-400',
        ], $user->departemen_info);

        $user->unsetRelation('peserta');
        $roleAdmin = new Role();
        $roleAdmin->setAttribute('role_name', 'admin');
        $user->setRelation('role', $roleAdmin);

        $this->assertSame([
            'bagian' => 'Administrator',
            'type' => 'admin',
            'icon' => 'ti ti-shield',
            'color' => 'orange-400',
        ], $user->departemen_info);
    }
}
