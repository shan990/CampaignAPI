<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as faker;

class ReservationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        foreach (range(1,50) as $index) {

            DB::table('reservations')->insert([
                'voucher_id' => $faker->unique()->numberBetween(111111, 999999),
                'status' => 0,
                'transaction_at' => now()->toDateTimeString()
            ]);
            
        }
    }
}
