<?php

namespace App\Http\Controllers;

use App\Models\SavedAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedAddressController extends Controller
{
    /**
     * Store a newly created saved address in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        SavedAddress::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));

        return redirect()->back();
    }

    /**
     * Update the specified saved address in storage.
     */
    public function update(Request $request, SavedAddress $savedAddress): RedirectResponse
    {
        abort_unless($savedAddress->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $savedAddress->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified saved address from storage.
     */
    public function destroy(Request $request, SavedAddress $savedAddress): RedirectResponse
    {
        abort_unless($savedAddress->user_id === $request->user()->id, 403);

        $savedAddress->delete();

        return redirect()->back();
    }
}
