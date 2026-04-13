<?php

use App\Livewire\Appliances;
use App\Livewire\DocumentDetail;
use App\Livewire\Documents;
use App\Livewire\Overview;
use App\Livewire\Utility;
use Illuminate\Support\Facades\Route;

Route::get('/', Overview::class);
Route::get('/documents', Documents::class);
Route::get('/documents/{document}', DocumentDetail::class);
Route::get('/appliances', Appliances::class);
Route::get('/utility', Utility::class);
