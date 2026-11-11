<?php

namespace App\Livewire;

use App\Jobs\ProcessCsvImportJob;
use App\Models\ImportBatch;
use App\Support\CurrentCenter;
use Livewire\Component;
use Livewire\WithFileUploads;

class CsvImportUpload extends Component
{
    use WithFileUploads;

    public $file;

    public ?int $batchId = null;

    public ?string $status = null;

    public ?string $message = null;

    public array $summary = [];

    public function startImport(): void
    {
        $maxKb = max(1024, (int) config('import.max_file_size_mb', 64) * 1024);

        $this->validate([
            'file' => ['required', 'file', 'max:'.$maxKb],
        ]);

        $originalName = $this->file->getClientOriginalName();
        $extension = strtolower($this->file->getClientOriginalExtension());

        if ($extension !== 'csv') {
            $this->addError('file', __('The file must be in .csv format'));

            return;
        }

        $user = auth()->user();
        $storedPath = $this->file->store('imports', 'local');

        $batch = ImportBatch::create([
            'center_id' => app(CurrentCenter::class)->id(),
            'uploaded_by' => $user->id,
            'filename' => $storedPath,
            'original_filename' => $originalName,
            'status' => 'pending',
            'processed_rows' => 0,
        ]);

        $this->batchId = $batch->id;
        $this->status = 'processing';
        $this->message = __('Processing…');
        $this->summary = [
            'total_rows' => 0,
            'imported_rows' => 0,
            'duplicate_rows' => 0,
            'failed_rows' => 0,
            'processed_rows' => 0,
        ];

        ProcessCsvImportJob::dispatch($batch->id);
    }

    public function checkProgress(): void
    {
        if (! $this->batchId) {
            return;
        }

        $batch = ImportBatch::find($this->batchId);

        if (! $batch) {
            return;
        }

        $this->status = $batch->status;
        $this->summary = [
            'total_rows' => $batch->total_rows,
            'imported_rows' => $batch->imported_rows,
            'duplicate_rows' => $batch->duplicate_rows,
            'failed_rows' => $batch->failed_rows,
            'processed_rows' => $batch->processed_rows,
        ];

        if ($batch->status === 'completed') {
            $this->message = $batch->error_message
                ?: __('Import completed successfully.');
        } elseif ($batch->status === 'failed') {
            $this->message = $batch->error_message
                ?: __('Import failed. Check the file and try again.');
        } elseif ($batch->status === 'processing') {
            $this->message = __('Processing… :count rows so far', [
                'count' => number_format($batch->processed_rows),
            ]);
        }
    }

    public function resetUpload(): void
    {
        $this->reset(['file', 'batchId', 'status', 'message', 'summary']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.csv-import-upload', [
            'maxFileSizeMb' => (int) config('import.max_file_size_mb', 64),
            'maxRows' => (int) config('import.max_rows', 250000),
        ]);
    }
}
