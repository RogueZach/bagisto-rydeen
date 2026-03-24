<?php

namespace Rydeen\Core\Database\Seeders;

use Illuminate\Database\Seeder;
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
}
