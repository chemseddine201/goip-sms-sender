<?php

namespace Database\Seeders;

use App\Models\Line;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LinesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $lines = [];
        $operators = [1, 2, 3, 4]; // Operator IDs in the desired order
        for ($i = 1; $i <= 32; $i++) {
            $operator_id = $operators[(int)(($i - 1) / 8)]; // Determine operator ID based on modulus
        
            $lines[] = [
                'operator_id' => $operator_id,
                'status' => 0,
                'created_at' => Carbon::now()
            ];
        }
        Line::insert($lines);
    }
}
