<?php

namespace App\Livewire;

use App\Models\NotificationLog;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationLogsTable extends Component
{
    use WithPagination;

    public string $channelFilter = '';

    public string $statusFilter = '';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $centerId = auth()->user()->center_id;

        $logs = NotificationLog::query()
            ->where('center_id', $centerId)
            ->when($this->channelFilter, fn ($q) => $q->where('channel', $this->channelFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('delivery_status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('phone_number', 'like', "%{$this->search}%")
                        ->orWhereHas('inspectionRecord', function ($r) {
                            $r->where('customer_name', 'like', "%{$this->search}%")
                              ->orWhere('licence_plate', 'like', "%{$this->search}%");
                        });
                });
            })
            ->with('inspectionRecord')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.notification-logs-table', compact('logs'));
    }
}
