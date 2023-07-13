<?php

namespace App\Http\Controllers;

use App\Models\Line;
use App\Models\Operator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LinesController extends Controller
{
    public function index() {
        $lines = Line::with(['operator'])->orderBy('id')->get();
        return view('lines', compact('lines'));
    }
    public function switch(Request $request) {
        $id = $request->input('id');
        $line = Line::where('id', $id)->first();
        $line->status = $line->status ? 0 : 1;
        $line->busy = 0;
        $line->save();
        return response()->json(['status' => 'success'], 200);
    }
    public function reset() {
        Line::query()->update(['busy' => 0]);
        return response()->json(['status' => 'success'], 200);
    }

    //TODO: free old used lines
    public function freeLongBusy() {
        Line::where('busy', 1)
        ->where('status', 1)
        ->where('updated_at', '<=', Carbon::now()->subMinutes(5)->toDateTimeString())
        ->update(['busy' => 0]);
        
        return response()->json(['status' => 'success'], 200);
    }
}
