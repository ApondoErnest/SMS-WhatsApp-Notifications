<?php

namespace App\Livewire;

use App\Models\InspectionRecord;
use Livewire\Component;
use Livewire\WithPagination;

class InspectionRecordsTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $expiryFilter = '';

    public string $sortField = 'expiration_date';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $centerId = auth()->user()->center_id;

        $records = InspectionRecord::query()
            ->where('center_id', $centerId)
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('customer_name', 'like', "%{$this->search}%")
                        ->orWhere('licence_plate', 'like', "%{$this->search}%")
                        ->orWhere('normalized_phone_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->expiryFilter, function ($q) {
                match ($this->expiryFilter) {
                    'this_week' => $q->whereBetween('expiration_date', [now(), now()->addDays(7)]),
                    'this_month' => $q->whereBetween('expiration_date', [now(), now()->addDays(30)]),
                    'expired' => $q->where('expiration_date', '<', now()),
                    default => $q,
                };
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);

        return view('livewire.inspection-records-table', compact('records'));
    }
}
