<?php

namespace App\Http\Controllers;

use App\Models\SMS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    public function index() {
        $messages = SMS::orderBy('id', 'DESC')->get();
        return view('messages', compact('messages'));
    }

    public function fetchData(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');

        $query = DB::table('sms');

        if ($search) {
            $query->where('user', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('message', 'like', '%' . $search . '%');
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        if ($limit != -1) {
            $query->offset($start)
                ->limit($limit);
        }
        
        $query->orderBy('id', 'DESC');

        $data = $query->select(['id', 'line', 'sent_status', 'phone', 'message', 'user'])->get();

        $jsonResponse = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ];

        return response()->json($jsonResponse);
    }

    public function deleteAll() {
        SMS::truncate();
        return response()->json(['status' => 'success'], 200);
    }

    public function destroy(Request $request) {
        $id = $request->input('id');
        SMS::destroy($id);

        return response()->json(['status' => 'success'], 200);
    }


}
