<?php

namespace App\Support;

use App\Models\InspectionCenter;
use App\Models\User;

class CurrentCenter
{
    private bool $resolved = false;

    private ?InspectionCenter $center = null;

    public function center(): ?InspectionCenter
    {
        if ($this->resolved) {
            return $this->center;
        }

        $this->resolved = true;
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $id = session('current_center_id');
            $this->center = $id ? InspectionCenter::find($id) : null;

            return $this->center;
        }

        $this->center = $user->center_id
            ? InspectionCenter::find($user->center_id)
            : null;

        return $this->center;
    }

    public function id(): int
    {
        $id = $this->center()?->id;
        abort_unless($id, 403);

        return $id;
    }

    public function forget(): void
    {
        $this->resolved = false;
        $this->center = null;
    }
}
