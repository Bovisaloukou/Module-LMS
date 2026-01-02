<?php

namespace App\Livewire;

use App\Models\Certificate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('My Certificates')]
class MyCertificates extends Component
{
    public function render()
    {
        return view('livewire.my-certificates', [
            'certificates' => Certificate::where('student_id', auth()->id())
                ->with('course')
                ->latest('issued_at')
                ->get(),
        ]);
    }
}
