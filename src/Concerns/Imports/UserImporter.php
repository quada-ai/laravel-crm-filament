<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Imports;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Jobs\SendImportPasswordReset;
use VentureDrake\LaravelCrm\Models\Role;

class UserImporter extends Importer
{
    public function columns(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
        ];
    }

    public function dedupeField(): string
    {
        return 'email';
    }

    public function sampleRow(): array
    {
        return [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'role' => 'Editor',
        ];
    }

    public function importRow(array $row): bool
    {
        $name = trim((string) ($row['name'] ?? ''));
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $roleName = trim((string) ($row['role'] ?? ''));

        if ($email === '' || $name === '') {
            return false;
        }

        if (User::where('email', $email)->exists()) {
            return false;
        }

        try {
            $user = User::forceCreate([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::password(
                    length: 16,
                    letters: true,
                    numbers: true,
                    symbols: true,
                    spaces: false,
                )),
                'crm_access' => 1,
                'mailing_list' => 1,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        if ($roleName !== '') {
            $role = Role::crm()->where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        if (config('laravel-crm.teams')) {
            $team = optional(auth()->user())->currentTeam;
            if ($team) {
                DB::table('team_user')->insert([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'editor',
                ]);
                $user->forceFill(['current_team_id' => $team->id])->save();
            }
        }

        SendImportPasswordReset::dispatch($user->email);

        return true;
    }
}
