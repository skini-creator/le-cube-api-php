<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return AddressResource::collection($request->user()->addresses);
    }

    public function show(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        return new AddressResource($address);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:billing,shipping',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string',
            'address_line_2' => 'nullable|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'postal_code' => 'required|string',
            'country' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        // If this is set as default, unset others
        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()
                ->where('type', $validated['type'])
                ->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return new AddressResource($address);
    }

    public function update(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address_line_1' => 'sometimes|string',
            'address_line_2' => 'nullable|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'postal_code' => 'sometimes|string',
            'country' => 'sometimes|string',
        ]);

        $address->update($validated);

        return new AddressResource($address);
    }

    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ]);
    }

    public function setDefault(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        // Unset other default addresses of same type
        $request->user()->addresses()
            ->where('type', $address->type)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default address updated'
        ]);
    }
}
