<?php

namespace Database\Seeders;

use App\Models\Operator;
use Illuminate\Database\Seeder;

class OperatorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $operators = ["mobilis", "djezzy", "ooredoo", "all"];
        foreach ($operators as $operator) {
            Operator::create([
                'name' => $operator,
                'status' => 0
            ]);
        }
    }
}
