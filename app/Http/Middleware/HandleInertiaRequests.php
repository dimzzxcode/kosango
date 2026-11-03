<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Services\BookingSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settings = app(BookingSettings::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'canonical' => $request->url(),
            'auth' => [
                'user' => $request->user(),
            ],
            'bookingRules' => [
                'minDownPaymentPercent' => $settings->minDownPaymentPercent(),
                'lateGraceDays' => $settings->lateGraceDays(),
                'lateFee' => $settings->lateFee(),
                'refundWindowDays' => $settings->refundWindowDays(),
                'paymentExpiryHours' => $settings->paymentExpiryHours(),
                'maxRejectedUploads' => $settings->maxRejectedUploads(),
                'extensionOpenDaysBeforeEnd' => $settings->extensionOpenDaysBeforeEnd(),
                'maxGalleryImages' => $settings->maxGalleryImages(),
                'termsVersion' => $settings->termsVersion(),
                'invoiceReminderDays' => $settings->invoiceReminderDays(),
            ],
            'pages' => Page::query()->orderBy('title')->get(['slug', 'title']),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
