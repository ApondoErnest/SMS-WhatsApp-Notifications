<?php

namespace App\Livewire;

use App\Models\NotificationSchedule;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationSchedulesTable extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $channelFilter = '';

    public string $dateFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
        $this->syncDatesFromFilter();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->syncDatesFromFilter();
    }

    public function render()
    {
        $centerId = auth()->user()->center_id;

        $schedules = NotificationSchedule::query()
            ->where('center_id', $centerId)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->channelFilter, fn ($q) => $q->where('channel', $this->channelFilter))
            ->when($this->dateFilter !== '', function ($q) {
                $this->applyDateFilter($q);
            })
            ->with('inspectionRecord')
            ->orderBy('scheduled_date')
            ->paginate(20);

        $period = $this->resolvePeriodDisplay();

        return view('livewire.notification-schedules-table', compact('schedules', 'period'));
    }

    private function syncDatesFromFilter(): void
    {
        $today = Carbon::today();

        match ($this->dateFilter) {
            'today' => $this->setPeriod($today, $today),
            'week' => $this->setPeriod($today->copy()->startOfWeek(), $today->copy()->endOfWeek()),
            'month' => $this->setPeriod($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            'custom' => null,
            default => $this->setPeriod(null, null),
        };
    }

    private function setPeriod(?Carbon $from, ?Carbon $to): void
    {
        $this->dateFrom = $from?->toDateString();
        $this->dateTo = $to?->toDateString();
    }

    /**
     * @return array{label: string, from: ?string, to: ?string, from_display: string, to_display: string}
     */
    private function resolvePeriodDisplay(): array
    {
        $today = Carbon::today();

        if ($this->dateFilter === '') {
            return [
                'label' => __('All dates'),
                'from' => null,
                'to' => null,
                'from_display' => '—',
                'to_display' => '—',
            ];
        }

        [$from, $to] = match ($this->dateFilter) {
            'today' => [$today, $today],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'custom' => [
                $this->dateFrom ? Carbon::parse($this->dateFrom) : null,
                $this->dateTo ? Carbon::parse($this->dateTo) : null,
            ],
            default => [null, null],
        };

        return [
            'label' => match ($this->dateFilter) {
                'today' => __('Today'),
                'week' => __('This week'),
                'month' => __('This month'),
                'custom' => __('Custom range'),
                default => __('All dates'),
            },
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'from_display' => $from ? $from->format('d/m/Y') : '—',
            'to_display' => $to ? $to->format('d/m/Y') : '—',
        ];
    }

    private function applyDateFilter($query): void
    {
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('scheduled_date', [$this->dateFrom, $this->dateTo]);

            return;
        }

        if ($this->dateFrom) {
            $query->whereDate('scheduled_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('scheduled_date', '<=', $this->dateTo);
        }
    }
}
