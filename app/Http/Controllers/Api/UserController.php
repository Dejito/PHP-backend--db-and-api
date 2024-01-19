<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


/**
 * Create User
 * @param Request $request
 * @return User
 */


class UserController extends Controller
{
    public function createUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'avatar' => 'required',
                    'type' => 'required',
                    'open_id' => 'required',
                    'name' => 'required',
                    'email' => 'required',
                    'password' => 'required',
                
                ]
            );
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'meessage' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }
            $validated = $validateUser -> validated();
            $map = [];
            //type = mail, google, facebook, phone, apple

            $map['type'] = $validated['type'];
            $map['open_id'] = $validated['open_id'];

            $user = User::where($map) -> first();
            //whether user has logged in or not
            //empty means does not exist
            //then save te user in the database for the first time

            if (empty($user->id)){
                //this certain user has nver been in our database
                //our job is to assign user in the database
                //this token is userid
                $validated["token"] = md5(uniqid().rand(10000, 99999));
                //user first time created
                $validated['created_at'] = Carbon::now();
                //returns the id of the row after saving
                $userID= User::insertGetId($validated);
                //user's all information
                $userInfo = User::where('id', '=', $userID)-> first();

                $accessToken = $userInfo -> createToken(uniqid())-> plainTextToken;

                $userInfo->acess_token = $accessToken;

                return response()->json([
                    'status' => true,
                    'message' => 'User created successfully',
                    'data' => $userInfo,
                ], 200);
           
            }

            // $user = User::create([
            //     'name' => $request->name,
            //     'email' => $request->email,
            //     'password' => $request->password
            // ]);
            $userID= User::insertGetId($validated);
            //user's all information
            $userInfo = User::where('id', '=', $userID)-> first();
            $accessToken = $userInfo-> createToken(uniqid())-> plainTextToken;
            $userInfo->acess_token = $accessToken;

            return response()->json([
                'status' => true,
                'message' => 'User logged in  successfully',
                'token' => $userInfo
            ], 200);

        } catch (\Throwable $th) {  
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    /**
     * Login The User
     * @param Request $request
     * @return User
     */

    public function loginUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }


            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status' => true,
                'message' => 'User Logged in Successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
