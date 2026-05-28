<?php

namespace Tests\Feature;

use App\Models\FailedImportRow;
use App\Models\ImportBatch;
use App\Models\InspectionCenter;
use App\Models\InspectionRecord;
use App\Models\NotificationSchedule;
use App\Models\User;
use App\Services\CsvImport\CsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    private InspectionCenter $center;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = InspectionCenter::create([
            'name' => 'Test Center',
            'code' => 'TST',
            'city' => 'Douala',
        ]);

        $this->user = User::factory()->create([
            'center_id' => $this->center->id,
        ]);
    }

    private function createCsvFile(string $content, string $filename = 'test.csv'): string
    {
        Storage::disk('local')->put("imports/{$filename}", $content);

        return "imports/{$filename}";
    }

    private function createBatch(string $filename): ImportBatch
    {
        return ImportBatch::create([
            'center_id' => $this->center->id,
            'uploaded_by' => $this->user->id,
            'filename' => $filename,
            'original_filename' => 'upload.csv',
            'status' => 'processing',
        ]);
    }

    public function test_imports_valid_rows(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;Jean Dupont;677123456;APTE\n";
        $csv .= "10/02/2026;15/02/2026;15/02/2027;B;Visite technique;CE 5678 CD;VU;Marie Kamga;699876543;APTE\n";

        $path = $this->createCsvFile($csv);
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);
        $batch->refresh();

        $this->assertEquals('completed', $batch->status);
        $this->assertEquals(2, $batch->total_rows);
        $this->assertEquals(2, $batch->imported_rows);
        $this->assertEquals(0, $batch->failed_rows);
        $this->assertEquals(0, $batch->duplicate_rows);
        $this->assertEquals(2, InspectionRecord::count());
    }

    public function test_rejects_rows_with_missing_required_fields(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;;VP;No Plate;677123456;APTE\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;;677123456;APTE\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;No Phone;;APTE\n";
        $csv .= "15/01/2026;20/01/2026;;A;Visite technique;LT 1234 AB;VP;No Expiry;677123456;APTE\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;No Status;677123456;\n";

        $path = $this->createCsvFile($csv, 'invalid.csv');
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);
        $batch->refresh();

        $this->assertEquals('completed', $batch->status);
        $this->assertEquals(0, $batch->imported_rows);
        $this->assertEquals(5, $batch->failed_rows);
        $this->assertEquals(5, FailedImportRow::count());
    }

    public function test_rejects_invalid_phone_numbers(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;Bad Phone;123;APTE\n";

        $path = $this->createCsvFile($csv, 'badphone.csv');
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);
        $batch->refresh();

        $this->assertEquals(0, $batch->imported_rows);
        $this->assertEquals(1, $batch->failed_rows);
        $this->assertStringContainsString('Invalid phone number', FailedImportRow::first()->error_message);
    }

    public function test_detects_duplicates_on_reimport(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;Jean Dupont;677123456;APTE\n";

        $path = $this->createCsvFile($csv, 'dup.csv');

        $batch1 = $this->createBatch($path);
        app(CsvImportService::class)->process($batch1);
        $batch1->refresh();
        $this->assertEquals(1, $batch1->imported_rows);

        $batch2 = $this->createBatch($path);
        app(CsvImportService::class)->process($batch2);
        $batch2->refresh();

        $this->assertEquals(0, $batch2->imported_rows);
        $this->assertEquals(1, $batch2->duplicate_rows);
        $this->assertEquals(1, InspectionRecord::count());
    }

    public function test_normalizes_phone_numbers_to_e164(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;Test;677123456;APTE\n";
        $csv .= "15/01/2026;20/01/2026;20/02/2027;B;Visite technique;CE 5678 CD;VP;Test2;+237699876543;APTE\n";

        $path = $this->createCsvFile($csv, 'phones.csv');
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);

        $records = InspectionRecord::all();
        $this->assertEquals('+237677123456', $records[0]->normalized_phone_number);
        $this->assertEquals('+237699876543', $records[1]->normalized_phone_number);
    }

    public function test_generates_notification_schedules(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;Visite technique;LT 1234 AB;VP;Jean;677123456;APTE\n";

        $path = $this->createCsvFile($csv, 'schedules.csv');
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);

        $this->assertGreaterThan(0, NotificationSchedule::count());
    }

    public function test_fails_with_invalid_headers(): void
    {
        $csv = "Wrong;Headers;Here\n";
        $csv .= "a;b;c\n";

        $path = $this->createCsvFile($csv, 'badheaders.csv');
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);
        $batch->refresh();

        $this->assertEquals('failed', $batch->status);
    }

    public function test_skips_cancelled_and_annule_status_rows(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;VT;LT 0001 AA;VP;Cancelled Row;677111222;Cancelled\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;VT;LT 0002 BB;VP;Annule Row;677222333;Annulé\n";
        $csv .= "15/01/2026;20/01/2026;20/01/2027;A;VT;LT 0003 CC;VP;Valid Row;677333444;APTE\n";

        $path = $this->createCsvFile($csv, 'skipped.csv');
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);
        $batch->refresh();

        $this->assertEquals(1, $batch->total_rows);
        $this->assertEquals(1, $batch->imported_rows);
        $this->assertEquals(1, InspectionRecord::count());
    }

    public function test_handles_various_date_formats(): void
    {
        $csv = "Regitration date;Inspection date;Expiration date;Cat.;Type;Licence plate;Category;Customer;Phone number;Status\n";
        $csv .= "2026-01-15;2026-01-20;2027-01-20;A;VT;LT 0001 AA;VP;Date Test;677111222;APTE\n";

        $path = $this->createCsvFile($csv, 'dates.csv');
        $batch = $this->createBatch($path);

        app(CsvImportService::class)->process($batch);
        $batch->refresh();

        $this->assertEquals(1, $batch->imported_rows);
        $record = InspectionRecord::first();
        $this->assertEquals('2027-01-20', $record->expiration_date->format('Y-m-d'));
    }
}
