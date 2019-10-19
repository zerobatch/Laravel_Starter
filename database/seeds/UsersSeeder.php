<?php

use Illuminate\Database\Seeder;
use App\{User, Role, RoleUser};

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $superadmin_role = factory(Role::class)->create(['name' => 'superadmin']);
        $admin_role = factory(Role::class)->create(['name' => 'admin']);
        $superadmin_user = factory(User::class)->create(['email' => "superadmin@superadmin.com", 'password' => 'superadmin']);
        $admin_user = factory(User::class)->create(['email' => "admin@admin.com", 'password' => 'admin']);

        factory(RoleUser::class)->create(['user_id' => $superadmin_user->id, 'role_id' => $superadmin_role->id]);
        factory(RoleUser::class)->create(['user_id' => $admin_user->id, 'role_id' => $admin_role->id]);
    }
}
