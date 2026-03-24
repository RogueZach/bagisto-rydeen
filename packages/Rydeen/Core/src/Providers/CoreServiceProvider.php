<?php

namespace Rydeen\Core\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/rydeen.php', 'rydeen');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'rydeen');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'rydeen');

        // Ensure B2B Suite is activated
        $this->ensureB2BActive();
    }

    protected function ensureB2BActive(): void
    {
        try {
            if (Schema::hasTable('core_config')) {
                $active = \Webkul\Core\Models\CoreConfig::where('code', 'b2b_suite.general.settings.active')->first();
                if (! $active || $active->value !== '1') {
                    \Webkul\Core\Models\CoreConfig::updateOrCreate(
                        ['code' => 'b2b_suite.general.settings.active'],
                        ['value' => '1', 'channel_code' => 'default', 'locale_code' => 'en']
                    );
                }
            }
        } catch (\Exception $e) {
            // Silently skip if DB not available (e.g., during migrations)
        }
    }
}
