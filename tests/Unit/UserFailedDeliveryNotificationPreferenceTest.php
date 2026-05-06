<?php

namespace Tests\Unit;

use App\Enums\FailedDeliveryNotificationPreference;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserFailedDeliveryNotificationPreferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function it_allows_both_quarantined_and_normal_notifications_for_all_preference()
    {
        $user = $this->createUser(userAttributes: [
            'failed_delivery_notification_preference' => FailedDeliveryNotificationPreference::All,
        ]);

        $this->assertTrue($user->shouldReceiveFailedDeliveryNotification(false));
        $this->assertTrue($user->shouldReceiveFailedDeliveryNotification(true));
    }

    #[Test]
    public function it_allows_only_normal_notifications_for_normal_only_preference()
    {
        $user = $this->createUser(userAttributes: [
            'failed_delivery_notification_preference' => FailedDeliveryNotificationPreference::NormalOnly,
        ]);

        $this->assertTrue($user->shouldReceiveFailedDeliveryNotification(false));
        $this->assertFalse($user->shouldReceiveFailedDeliveryNotification(true));
    }

    #[Test]
    public function it_allows_only_quarantined_notifications_for_quarantined_only_preference()
    {
        $user = $this->createUser(userAttributes: [
            'failed_delivery_notification_preference' => FailedDeliveryNotificationPreference::QuarantinedOnly,
        ]);

        $this->assertFalse($user->shouldReceiveFailedDeliveryNotification(false));
        $this->assertTrue($user->shouldReceiveFailedDeliveryNotification(true));
    }

    #[Test]
    public function it_disables_all_notifications_for_none_preference()
    {
        $user = $this->createUser(userAttributes: [
            'failed_delivery_notification_preference' => FailedDeliveryNotificationPreference::None,
        ]);

        $this->assertFalse($user->shouldReceiveFailedDeliveryNotification(false));
        $this->assertFalse($user->shouldReceiveFailedDeliveryNotification(true));
    }
}
