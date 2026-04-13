<?php

namespace App\Livewire;

use App\Models\Appliance;
use App\Models\Document;
use App\Models\UtilityReading;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Document · Apartment')]
class DocumentDetail extends Component
{
    public Document $document;

    public function mount(Document $document): void
    {
        $this->document = $document;
    }

    public function render()
    {
        $downloadUrl = URL::temporarySignedRoute(
            'apartment.documents.download',
            now()->addMinutes(30),
            ['document' => $this->document->id],
        );

        $reading = UtilityReading::where('document_id', $this->document->id)->first();
        $linkedAppliance = Appliance::where('manual_document_id', $this->document->id)->first();

        return view('livewire.document-detail', [
            'downloadUrl' => $downloadUrl,
            'reading' => $reading,
            'linkedAppliance' => $linkedAppliance,
        ]);
    }
}
