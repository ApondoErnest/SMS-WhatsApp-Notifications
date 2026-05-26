<?php

namespace App\Livewire;

use App\Models\ImportBatch;
use App\Jobs\ProcessCsvImportJob;
use Illuminate\Support\Facades\Storage;
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

    public function upload(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:10240'],
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
            'center_id' => $user->center_id,
            'uploaded_by' => $user->id,
            'filename' => $storedPath,
            'original_filename' => $originalName,
            'status' => 'processing',
        ]);

        $this->batchId = $batch->id;
        $this->status = 'processing';
        $this->message = __('Processing…');

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
        ];

        if ($batch->status === 'completed') {
            $this->message = __('Import completed successfully.');
        } elseif ($batch->status === 'failed') {
            $this->message = __('Import failed. Check the file and try again.');
        }
    }

    public function resetUpload(): void
    {
        $this->reset(['file', 'batchId', 'status', 'message', 'summary']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.csv-import-upload');
    }
}
