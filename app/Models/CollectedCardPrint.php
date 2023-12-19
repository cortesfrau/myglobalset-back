<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectedCardPrint extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'collection_id',
        'scryfall_id',
    ];

}
