<?php

declare(strict_types=1);

namespace App\Models;

use CnA\Orm\Model;
use CnA\Orm\Relations\BelongsTo;

class OrderItem extends Model
{
    protected ?string $table = 'order_items';

    protected array $fillable = [
        'order_id',
        'product_name',
        'quantity',
        'unit_price',
    ];

    protected array $casts = [
        'order_id' => 'int',
        'quantity' => 'int',
        'unit_price' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

