<?php

namespace App\Livewire;

use App\Models\ImportBatch;
use Livewire\Component;
use Livewire\WithPagination;

class ImportHistoryTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $centerId = auth()->user()->center_id;

        $batches = ImportBatch::query()
            ->where('center_id', $centerId)
            ->when($this->search, fn ($q) => $q->where('original_filename', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->with('uploader')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.import-history-table', compact('batches'));
    }
}
