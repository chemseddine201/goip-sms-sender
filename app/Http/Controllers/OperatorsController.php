<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;

class OperatorsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $operators = Operator::orderBy('id')->get();
        return view('operators', compact('operators'));
    }

    public function switch(Request $request)
    {
        $id = $request->input('id');
        $line = Operator::where('id', $id)->first();
        $line->status = $line->status ? 0 : 1;
        $line->save();
        
        return response()->json(['status' => 'success'], 200);
    }
    

}
