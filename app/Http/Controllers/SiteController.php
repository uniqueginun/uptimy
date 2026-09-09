<?php

namespace App\Http\Controllers;

use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('sites/index', [
            'sites' => SiteResource::collection($request->user()->sites),
        ]);
    }

    public function show(Request $request, string $domain): Response
    {
        $site = Site::byDomain($domain)
            ->whereBelongsTo($request->user())
            ->with(['endpoints' => function ($query) {
                $query->withCount([
                    'checks',
                    'successChecks',
                    'checks as checks_24h_count' => fn (Builder $query) => $query->where('checked_at', '>=', now()->subDay()),
                    'successChecks as success_checks_24h_count' => fn (Builder $query) => $query->where('checked_at', '>=', now()->subDay()),
                ]);
            }])
            ->firstOrFail();

        return Inertia::render('sites/show', [
            'site' => SiteResource::make($site)->resolve(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|unique:sites,url',
        ]);

        $site = $request->user()->sites()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site created successfully.')]);

        return to_route('sites.show', $site->domain);
    }
}
