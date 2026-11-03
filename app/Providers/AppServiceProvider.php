<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureGates();
    }

    /**
     * Configure authorization gates.
     */
    protected function configureGates(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
    }

    /**
     * Configure rate limiting for public-facing endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('job-requests', fn (Request $request): array => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
            // Each request emails its contact address, so stop one address being flooded from many IPs.
            Limit::perDay(10)->by('email:'.(is_string($request->input('contact_email')) ? mb_strtolower(trim($request->input('contact_email'))) : $request->ip())),
        ]);

        RateLimiter::for('job-request-photos', fn (Request $request): Limit => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Inertia serialises nested resources as responses, which would wrap every nested
        // collection in a "data" key. Paginated collections still keep data/links/meta.
        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Generate every URL (canonicals, sitemap, emails) from APP_URL rather than the
        // request's Host header, so a spoofed host can't leak into cached pages or the sitemap.
        // Console commands already use APP_URL, and forcing it there would make Wayfinder
        // write absolute URLs into the front-end build, tying it to one domain.
        if (app()->isProduction() && ! app()->runningInConsole()) {
            URL::forceRootUrl(config('app.url'));

            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

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
