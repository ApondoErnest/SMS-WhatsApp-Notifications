<?php

namespace App\Livewire;

use App\Models\NotificationSchedule;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationSchedulesTable extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $channelFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $centerId = auth()->user()->center_id;

        $schedules = NotificationSchedule::query()
            ->where('center_id', $centerId)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->channelFilter, fn ($q) => $q->where('channel', $this->channelFilter))
            ->with('inspectionRecord')
            ->orderBy('scheduled_date')
            ->paginate(20);

        return view('livewire.notification-schedules-table', compact('schedules'));
    }
}
