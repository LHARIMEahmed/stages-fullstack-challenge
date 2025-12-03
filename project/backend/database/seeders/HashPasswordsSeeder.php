<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HashPasswordsSeeder extends Seeder
{
    /**
     * Récupère tous les utilisateurs de la table 'users'
     * et on verifie si le mot de passe n'est pas déjà hashé.
     *
     * @return void
     */
    public function run()
    {
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            // Vérifie si le mot de passe n'est pas déjà hashé (les hash bcrypt commencent par '$2y$')
            if (!str_starts_with($user->password, '$2y$')) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['password' => Hash::make($user->password)]);
            }
        }

        $this->command->info('Tous les mots de passe existants ont été hashés.');
    }
}
