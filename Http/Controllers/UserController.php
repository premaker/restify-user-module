<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\User\Entities\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Authorization check
        if (Gate::denies('listAll', User::class)) {
            return json_response()->forbidden();
        }

        // Get data
        $data = request_with('trashed')
            ? User::withTrashed()->orderBy('id', 'desc')
            : User::orderBy('id', 'desc');

        return json_response()->paginate($data->paginate(per_page())->toArray());
    }

    /**
     * Display the specified resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        // Get data
        if ($id == 'me') {
            $data = $request->user();
        } else {
            try {
                $data = request_with('trashed') ? User::withTrashed()->findOrFail($id) : User::findOrFail($id);
            } catch (ModelNotFoundException $e) {
                return json_response()->notFound();
            }
        }

        // Authorization check
        if (Gate::denies('view', $data)) {
            return json_response()->forbidden();
        }

        return json_response()->success($data);
    }
}
