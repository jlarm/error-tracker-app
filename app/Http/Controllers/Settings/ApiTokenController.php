<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
        ]);

        $token = $request->user()->createToken($data['name']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token created.')]);

        return back()->with('flash.api_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token revoked.')]);

        return back();
    }
}
