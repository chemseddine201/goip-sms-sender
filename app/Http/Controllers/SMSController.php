<?php

namespace App\Http\Controllers;

use App\Models\SMS;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SMSController extends Controller
{
    public function store(Request $request)
    {
        //validate request
        $validator = Validator::make($request->all(), [
            'user' => 'required|string|min:3|max:40',
            'phone' => 'required|string|regex:/^\d{10}$/',
            'message' => 'required|string',
            'operator' => 'required|min:3|string|in:mobilis,djezzy,ooredoo,all', //regex:(foo|bar|baz)
        ]);
        //check if request valid
        if ($validator->fails()) {
            return response("error", 401)->header('Content-Type', 'text/plain');
        }
        //get data
        $data = $request->all();
        //create new sms record
        SMS::create([
            'user' => $data['user'],
            'phone' => $data['phone'],
            'message' => $data['message'],
            'operator' => $data['operator'],
            'operator_id' => $this->getOperatorId($data['operator']),
            'message_id' => uniqid(),
        ]);
        //return json response
        return response("success", 200)->header('Content-Type', 'text/plain');
    }

    //only for testing
    public function storeMulti(Request $request)
    {
        //validate request
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^\d{10}$/',
            'operator' => 'required|min:3|string|in:mobilis,djezzy,ooredoo,all', //regex:(foo|bar|baz)
            'max' => 'required|string',//1 to 20 => |regex:/^(?:[1-9]|[1-2][0-9]|20)$/
        ]);
        //check if request valid
        if ($validator->fails()) {
            return response("error", 401)->header('Content-Type', 'text/plain');
        }
        //get data
        $data = $request->all();
        $data['user'] = $this->getRandomUsername();
        //create new sms record
        $messages = [];

        for ($i= 1; $i <= (int) $data['max']; $i++) { 
            $messages[] = [
                'user' => $data['user'],
                'phone' => $data['phone'],
                'message' => $this->getRandomMessage(),
                'operator' => $data['operator'],
                'operator_id' => $this->getOperatorId($data['operator']),
                'message_id' => uniqid(),
                'created_at' => Carbon::now()
            ];
            usleep(500);
        }
        //insert all messages
        SMS::insert($messages);
        //return json response
        return response("success", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * @param string $operator
     *
     * @return int
     */
    private function getOperatorId(string $operator): int
    {
        switch ($operator) {
            case "djezzy":
                return 2;
            case "ooredoo":
                return 3;
            case "mobilis":
            default:
                return 1;
        }
    }

    private function getRandomMessage () {
        $faker = \Faker\Factory::create();
        $wordCount = $faker->numberBetween(10, 15);
        $message = $faker->sentence($wordCount);
        return $message;
    }
    private function getRandomUsername () {
        $faker = \Faker\Factory::create();
        return $faker->userName();
    }
}
