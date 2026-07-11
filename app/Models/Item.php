<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'price_in_thb',
        'weight_in_grams',
        'cargo_price_in_thb',
        'cargo_price_in_mmk',
        'selling_price_in_mmk',
        'stock_in',
        'stock_out',
        'has_gem',
        'remark',
        'created_by',
        'updated_by',
    ];

    //relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('document_type', 'ITEM_IMAGE');
    }

    // user ralations
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
