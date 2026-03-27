<?php

namespace Rydeen\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\CoreConfig;
use Webkul\Customer\Models\CustomerGroup;

class RydeenSeeder extends Seeder
{
    /**
     * Seed the Rydeen dealer groups and B2B config.
     */
    public function run(): void
    {
        $this->seedCustomerGroups();
        $this->seedB2BConfig();
        $this->seedAdminBranding();
        $this->seedSalesRepRole();
    }

    protected function seedCustomerGroups(): void
    {
        $groups = [
            ['name' => 'MESA Dealers',          'code' => 'mesa-dealers'],
            ['name' => 'New Dealers',            'code' => 'new-dealers'],
            ['name' => 'Dealers',                'code' => 'dealers'],
            ['name' => 'International Dealers',  'code' => 'international-dealers'],
        ];

        foreach ($groups as $group) {
            CustomerGroup::firstOrCreate(
                ['code' => $group['code']],
                ['name' => $group['name'], 'is_user_defined' => 1]
            );
        }
    }

    protected function seedB2BConfig(): void
    {
        CoreConfig::updateOrCreate(
            ['code' => 'b2b_suite.general.settings.active'],
            ['value' => '1', 'channel_code' => 'default', 'locale_code' => 'en']
        );
    }

    protected function seedAdminBranding(): void
    {
        $assetsDir = dirname(__DIR__, 2).'/Resources/assets/images';

        Storage::disk('public')->makeDirectory('rydeen');

        foreach (['logo.png', 'dark-logo.png'] as $file) {
            $source = $assetsDir.'/'.$file;

            if (file_exists($source)) {
                Storage::disk('public')->put('rydeen/'.$file, file_get_contents($source));
            }
        }

        CoreConfig::updateOrCreate(
            ['code' => 'general.design.admin_logo.logo_image'],
            ['value' => 'rydeen/logo.png', 'channel_code' => null, 'locale_code' => null]
        );
    }

    protected function seedSalesRepRole(): void
    {
        \Webkul\User\Models\Role::firstOrCreate(
            ['name' => 'Sales Rep'],
            [
                'description'     => 'Sales representative with access to assigned dealers and their orders only.',
                'permission_type' => 'custom',
                'permissions'     => [
                    'dashboard',
                    'rydeen',
                    'rydeen.dealers',
                    'rydeen.dealers.view',
                    'rydeen.orders',
                    'rydeen.orders.view',
                    'rydeen.orders.approve',
                    'rydeen.orders.hold',
                ],
            ]
        );
    }
}
