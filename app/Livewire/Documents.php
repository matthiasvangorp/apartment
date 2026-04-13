<?php

namespace App\Livewire;

use App\Apartment\Ingest\ClaudeExtractor;
use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Documents · Apartment')]
class Documents extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public string $category = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Document::query();

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if (trim($this->search) !== '') {
            $term = trim($this->search);
            if (config('database.default') === 'mysql') {
                $query->whereRaw('MATCH(title_en, summary_en, raw_text) AGAINST (? IN NATURAL LANGUAGE MODE)', [$term])
                    ->orderByRaw('MATCH(title_en, summary_en, raw_text) AGAINST (? IN NATURAL LANGUAGE MODE) DESC', [$term]);
            } else {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                $query->where(fn ($w) => $w
                    ->where('title_en', 'like', $like)
                    ->orWhere('summary_en', 'like', $like)
                    ->orWhere('raw_text', 'like', $like)
                    ->orWhere('original_filename', 'like', $like));
            }
        } else {
            $query->orderByDesc('issued_on')->orderByDesc('id');
        }

        return view('livewire.documents', [
            'documents' => $query->paginate(20),
            'categories' => ClaudeExtractor::CATEGORIES,
        ]);
    }
}
