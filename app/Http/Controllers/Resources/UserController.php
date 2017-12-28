<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\Controller;
use App\User;
use App\Util\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\QueryException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return [
            'users' => User::all(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validator($request);

        $request['password'] = bcrypt($request->password);

        try {
            $user = User::create($request->all());

            if ($user) {
                return [
                    'user' => $user,
                ];
            }

            return $this->makeError('errors.creating_user', 'database');
        } catch (\Exception $e) {
            return $this->makeError();
        }
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $User
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $password = $request['password'];

        $checkPass = !empty($password);

        $this->validator($request, $checkPass, $user->id);

        // Unset password field if the user didn't change it.
        if (!$checkPass) {
            unset($request['password']);
        } else {
            $request['password'] = bcrypt($request['password']);
        }

        try {
            if ($user->update($request->all())) {
                return [
                    'user' => $user,
                ];
            }

            return $this->makeError(
                'errors.error_updating_user',
                'database'
            );
        } catch (\Exception $e) {
            return $this->makeError(
                'errors.fatal_error',
                'exception'
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $User
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if ($user->delete()) {
            return [];
        }

        return $this->makeError();
    }

    private function validator(Request $request, $checkPass = true, $id = '')
    {
        $emailValidation = 'required|max:191|email|unique:users';

        if ($id) {
            $emailValidation .= ',email,'.$id;
        }

        $request->validate([
            'name' => 'required|max:191',
            'email' => $emailValidation,
            'type_id' => 'required|integer|between:1,2',
            'password' => $checkPass ? 'required|min:6' : '',
        ], [
            'type_id.*' => __('users.invalid_user_type'),
        ]);
    }
}
