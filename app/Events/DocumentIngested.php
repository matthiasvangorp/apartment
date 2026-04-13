<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentIngested
{
    use Dispatchable;

    public function __construct(public readonly Document $document) {}
}
