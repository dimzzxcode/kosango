<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentPlanInterface;
use App\Contracts\PeriodCalculatorInterface;
use App\Contracts\RefundGatewayInterface;
use App\Contracts\RefundPolicyInterface;
use App\Enums\RefundInitiator;
use App\Events\InvoicePaid;
use App\Listeners\ConfirmBookingOnFirstInvoicePaid;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Refund;
use App\Models\Review;
use App\Observers\PaymentObserver;
use App\Observers\PropertyImageObserver;
use App\Observers\PropertyObserver;
use App\Observers\RefundObserver;
use App\Observers\ReviewObserver;
use App\Services\Payment\ManualTransferGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\PaymentPlan\FullPaymentPlan;
use App\Services\PaymentPlan\InstallmentPaymentPlan;
use App\Services\PaymentPlan\PaymentPlanFactory;
use App\Services\PaymentPlan\PerPeriodPaymentPlan;
use App\Services\Pricing\DailyPeriodCalculator;
use App\Services\Pricing\MonthlyPeriodCalculator;
use App\Services\Pricing\PeriodCalculatorFactory;
use App\Services\Pricing\YearlyPeriodCalculator;
use App\Services\Refund\AdminCancellationRefundPolicy;
use App\Services\Refund\CustomerCancellationRefundPolicy;
use App\Services\Refund\ManualRefundGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PeriodCalculatorFactory::class, fn (): PeriodCalculatorFactory => new PeriodCalculatorFactory([
            new DailyPeriodCalculator,
            new MonthlyPeriodCalculator,
            new YearlyPeriodCalculator,
        ]));

        $this->app->singleton(PaymentPlanFactory::class, fn ($app): PaymentPlanFactory => new PaymentPlanFactory([
            $app->make(FullPaymentPlan::class),
            $app->make(PerPeriodPaymentPlan::class),
            $app->make(InstallmentPaymentPlan::class),
        ]));

        $this->app->singleton(ManualTransferGateway::class);
        $this->app->singleton(PaymentGatewayManager::class, fn ($app): PaymentGatewayManager => new PaymentGatewayManager([
            'manual' => $app->make(ManualTransferGateway::class),
        ]));

        $this->app->bind(PeriodCalculatorInterface::class, DailyPeriodCalculator::class);
        $this->app->bind(PaymentPlanInterface::class, FullPaymentPlan::class);
        $this->app->bind(PaymentGatewayInterface::class, ManualTransferGateway::class);
        $this->app->bind(RefundGatewayInterface::class, ManualRefundGateway::class);

        $this->app->bind(CustomerCancellationRefundPolicy::class);
        $this->app->bind(AdminCancellationRefundPolicy::class);
        $this->app->bind(RefundPolicyInterface::class, function ($app, array $parameters): RefundPolicyInterface {
            $initiator = $parameters['initiator'] ?? RefundInitiator::Customer;

            return $initiator === RefundInitiator::Admin
                ? $app->make(AdminCancellationRefundPolicy::class)
                : $app->make(CustomerCancellationRefundPolicy::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(InvoicePaid::class, ConfirmBookingOnFirstInvoicePaid::class);

        Review::observe(ReviewObserver::class);

        Property::observe(PropertyObserver::class);
        PropertyImage::observe(PropertyImageObserver::class);
        Payment::observe(PaymentObserver::class);
        Refund::observe(RefundObserver::class);

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
