<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as faker;

class TransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        foreach (range(1,150) as $index) {

            DB::table('transactions')->insert([
                'customer_id' => $faker->numberBetween(1, 50),
                'total_spent' => $faker->numberBetween(1, 70),
                'total_saving' => $faker->numberBetween(1, 10),
                'transaction_at' => $faker->dateTimeBetween($startDate = '-2 months', $endDate = 'now')
            ]);
            
        }
        
    }
}
