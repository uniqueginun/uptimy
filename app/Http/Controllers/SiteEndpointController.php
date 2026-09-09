<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SiteEndpointController extends Controller
{
    public function store(Request $request, string $domain): RedirectResponse
    {
        $site = Site::byDomain($domain)->whereBelongsTo($request->user())->firstOrFail();

        $validated = $request->validate([
            'uri' => ['required', 'string', 'max:2048', 'regex:/^\//'],
            'interval' => ['required', 'integer', Rule::in(Endpoint::INTERVAL_OPTIONS)],
        ]);

        $site->endpoints()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Endpoint created successfully.')]);

        return redirect()->route('sites.show', $domain);
    }

    public function destroy(Request $request, string $domain, int $endpoint): RedirectResponse
    {
        $site = Site::byDomain($domain)->whereBelongsTo($request->user())->firstOrFail();

        $endpoint = $site->endpoints()->firstWhere('id', $endpoint);

        $endpoint?->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Endpoint deleted successfully.')]);

        return redirect()->route('sites.show', $domain);
    }
}
