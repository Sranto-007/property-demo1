<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    // 1. Show all properties
    public function index()
    {
        return response()->json(Property::all(), 200);
    }

    // 2. Create a property
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'rent' => 'required|numeric|min:0',
            'bedrooms' => 'required|integer|min:1|max:255',
        ]);

        $property = Property::create($validated);

        return response()->json([
            'message' => 'Property created successfully',
            'data' => $property,
        ], 201);
    }

    // 3. Show one property
    public function show(string $id)
    {
        $property = Property::findOrFail($id);

        return response()->json($property, 200);
    }

    // 4. Update a property
    public function update(Request $request, string $id)
    {
        $property = Property::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'rent' => 'sometimes|required|numeric|min:0',
            'bedrooms' => 'sometimes|required|integer|min:1|max:255',
        ]);

        $property->update($validated);

        return response()->json([
            'message' => 'Property updated successfully',
            'data' => $property,
        ], 200);
    }

    // 5. Delete a property
    public function destroy(string $id)
    {
        $property = Property::findOrFail($id);
        $property->delete();

        return response()->json([
            'message' => 'Property deleted successfully',
        ], 200);
    }
}
