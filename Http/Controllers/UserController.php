<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Authorization check
        if (Gate::denies('create', User::class)) {
            return json_response()->forbidden();
        }

        // Validate request data
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'username' => 'sometimes|nullable|required|string|max:50|unique:users',
            'email' => 'required|string|email|max:150|unique:users',
            'password' => 'required|string|min:6|max:255|confirmed',
            'access_level' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        // Create new resource
        $resource = new User;
        $resource->fill($data);

        // Encrypt user password
        $resource->password = bcrypt($data['password']);

        // Set user status & access level
        $resource->is_active = $request->input('is_active') ?: 0;
        $resource->access_level = $request->input('access_level') ?: 0;

        if ($resource->save()) {
            // Send welcome email
            // dispatch(new SendWelcomeEmail($resource));

            return json_response()->success([
                'messages' => [trans('messages.resource_created')],
                'resource' => User::find($resource->id),
            ], 201);
        }

        return json_response()->internalServerError();
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Get resource
        try {
            $resource = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return json_response()->notFound();
        }

        // Authorization check
        if (Gate::denies('update', $resource)) {
            return json_response()->forbidden();
        }

        if (($request->has('access_level') || $request->has('is_active')) && ! app('auth')->user()->is_admin) {
            return json_response()->forbidden();
        }

        // Validate request data
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'username' => [
                'sometimes',
                'nullable',
                'required',
                'string',
                'max:50',
                Rule::unique('users')->ignore($id),
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users')->ignore($id),
            ],
            'password' => 'sometimes|required|string|min:6|max:255|confirmed',
            'access_level' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        // Update resource
        $resource->fill($data);

        // Set new password if present
        if ($request->has('password')) {
            $resource->password = bcrypt($data['password']);
        }

        // Set user status & access level
        if ($request->has('is_active')) {
            $resource->is_active = $request->input('is_active');
        }
        if ($request->has('access_level')) {
            $resource->access_level = $request->input('access_level');
        }

        if ($resource->save()) {
            // Send password changed
            // if ($request->has('password')) {
            //     dispatch(new SendPasswordChangedEmail($resource));
            // }

            return json_response()->success([
                'messages' => [trans('messages.resource_updated')],
                'resource' => User::find($resource->id),
            ]);
        }

        return json_response()->internalServerError();
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        // Get data
        try {
            $data = User::withTrashed()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return json_response()->notFound();
        }

        if ($data->trashed() && ! (bool) $request->input('force')) {
            return json_response()->notFound();
        }

        // Authorization check
        if (Gate::denies('delete', $data)) {
            return json_response()->forbidden();
        }

        // Delete the model
        $deleted = (bool) $request->input('force')
            ? $data->forceDelete()
            : $data->delete();

        if ($deleted) {
            return json_response()->success(trans('messages.resource_deleted'));
        }

        return json_response()->internalServerError();
    }
}
