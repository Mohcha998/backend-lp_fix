<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class StudentsSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 150; $i++) {
            DB::table('students')->insert([
                'id_user_fthr' => rand(49, 65),
                'id_user_mthr' => rand(49, 65),
                'id_program' => 1, // Sesuaikan jika perlu
                'user_id' => rand(68, 77), // Sesuaikan dengan ID yang relevan
                'id_course' => 1, // Nilai yang diinginkan untuk id_course
                'id_branch' => rand(7, 29),
                'id_kelas' => null, // Sesuaikan jika perlu
                'name' => 'Child ' . rand(1, 100) . ' of User ' . rand(1, 200),
                'phone' => $faker->phoneNumber,
                'email' => $faker->email,
                'tgl_lahir' => $faker->date('Y-m-d', 'now'),
                'asal_sekolah' => $faker->company,
                'perubahan' => null,
                'kelebihan' => null,
                'dirawat' => null,
                'kondisi' => null,
                'tindakan' => null,
                'emergency_contact' => $faker->phoneNumber,
                'hubungan_eme' => $faker->randomElement(['Father', 'Mother']),
                'emergency_call' => $faker->phoneNumber,
                'status' => 1,
            ]);
        }
    }
}
