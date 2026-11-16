<?php

namespace Tests\Unit;

use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Services\Notification\MessagePersonalizer;
use Tests\TestCase;

class MessagePersonalizerTest extends TestCase
{
    public function test_compact_licence_plate_removes_spaces_and_dashes(): void
    {
        $this->assertSame('LT003MM', MessagePersonalizer::compactLicencePlate('LT 003 MM'));
        $this->assertSame('LT164HP', MessagePersonalizer::compactLicencePlate('lt-164-hp'));
        $this->assertSame('CE123AB', MessagePersonalizer::compactLicencePlate("CE\u{00A0}123 AB"));
    }

    public function test_render_uses_compact_licence_plate_for_sms_and_whatsapp(): void
    {
        $center = new InspectionCenter(['name' => 'NOVETESCO Mendong-Yaounde']);
        $record = new InspectionRecord([
            'customer_name' => 'NZEUGA ROLAND',
            'licence_plate' => 'LT 003 MM',
            'expiration_date' => '2026-12-16',
        ]);
        $record->setRelation('center', $center);

        $message = app(MessagePersonalizer::class)->render(
            'Véhicule {licence_plate} expire le {expiration_date} — {center_name}',
            $record,
        );

        $this->assertSame(
            'Véhicule LT003MM expire le 16/12/2026 — NOVETESCO Mendong-Yaounde',
            $message,
        );
        $this->assertStringNotContainsString('LT 003 MM', $message);
    }
}
