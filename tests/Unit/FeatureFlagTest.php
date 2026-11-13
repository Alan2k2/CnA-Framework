<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Features\FeatureManager;
use CnA\Testing\TestCase;

class FeatureFlagTest extends TestCase
{
    public function testBooleanFlags(): void
    {
        $manager = new FeatureManager([
            'dark_mode' => true,
            'legacy_billing' => false,
        ]);

        $this->assertTrue($manager->isEnabled('dark_mode'));
        $this->assertFalse($manager->isEnabled('legacy_billing'));
        $this->assertFalse($manager->isEnabled('non_existent_flag'));

        $manager->set('beta_dashboard', true);
        $this->assertTrue($manager->isEnabled('beta_dashboard'));
    }

    public function testClosureTargeting(): void
    {
        $manager = new FeatureManager();

        $manager->set('vip_perks', function (array $context) {
            return ($context['tier'] ?? 'standard') === 'vip';
        });

        $this->assertTrue($manager->isEnabled('vip_perks', ['tier' => 'vip']));
        $this->assertFalse($manager->isEnabled('vip_perks', ['tier' => 'standard']));
        $this->assertFalse($manager->isEnabled('vip_perks', []));
    }

    public function testPercentageRollout(): void
    {
        $manager = new FeatureManager([
            'always_on' => 100,
            'always_off' => 0,
        ]);

        $this->assertTrue($manager->isEnabled('always_on', ['user_id' => 123]));
        $this->assertFalse($manager->isEnabled('always_off', ['user_id' => 123]));
    }
}
