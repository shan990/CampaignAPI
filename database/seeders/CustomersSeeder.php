<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as faker;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $gender = $faker->randomElement(['male', 'female']);

        foreach (range(1,50) as $index) {

            DB::table('customers')->insert([
                'first_name' => $faker->firstName($gender), 
                'last_name' => $faker->lastName($gender),
                'gender' => $gender,
                'date_of_birth' => $faker->date($format = 'Y-m-d', $max = 'now'),
                'contact_number' => $faker->unique()->numberBetween(11111111, 99999999), 
                'email' => $faker->safeEmail,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ]);
            
        }
    }
}
