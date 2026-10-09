<?php

namespace App\Http\Middleware;

use App\Models\Section;
use App\Models\StudentActivity;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'activity' => fn () => $this->activity($request),
            'adminSections' => fn () => $this->adminSections($request),
        ]);
    }

    private function adminSections(Request $request): array
    {
        if (! $request->user() || ! $request->is('paulo', 'paulo/*')) {
            return [];
        }

        try {
            return Section::orderBy('name')->get(['id', 'name'])->toArray();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    private function activity(Request $request): ?array
    {
        if (! $request->user() || ! $request->is('paulo', 'paulo/*')) {
            return null;
        }

        try {
            return [
                'unread' => StudentActivity::whereNull('read_at')->count(),
                'recent' => StudentActivity::latest('created_at')
                    ->latest('id')
                    ->limit(10)
                    ->get(['id', 'type', 'summary', 'read_at', 'created_at']),
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
