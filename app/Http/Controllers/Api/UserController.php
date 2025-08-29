<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = auth()->user();

        return response()->json(['status' => 'success', 'data' => $user], 200);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $user = User::find(auth()->user()->id);
        $user->password = bcrypt($request->password);
        $user->first_login = 0;
        $user->save();

        return $this->return_success(true, 'Password successfully updated!');
    }

    /** Send Password Reset Link Action
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request): JsonResponse
    {

        $users = User::getUsers([],
            $request->get('per_page'), $request->get('sortType'),
            $request->get('sortBy'));

        return $this->return_success($users);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if (!User::where('id', $id)->exists()) {
                throw new Exception('User not found');
            }
            $authUser = Auth::id();
            if ($authUser == $id) {
                throw new Exception('You can\'t delete yourself');
            }
            $user = User::find($id);
            $user->forceDelete();
            return $this->return_success(true, 'User successfully deleted');
        } catch (Exception $e) {
            return $this->return_error($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function changeRole(int $id): JsonResponse
    {
        try {
            if (!User::where('id', $id)->exists()) {
                throw new Exception('User not found');
            }
            $authUser = Auth::id();
            if ($authUser == $id) {
                throw new Exception('You can\'t change role yourself');
            }
            $user = User::find($id);
            if ($user->role == 1) {
                $user->update(['role' => 2]);
            } else {
                $user->update(['role' => 1]);
            }
            return $this->return_success(true, 'User role successfully updated');
        } catch (Exception $e) {
            return $this->return_error($e->getMessage());
        }
    }
}
