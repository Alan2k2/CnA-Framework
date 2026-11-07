<?php

declare(strict_types=1);

namespace App\Models;

use CnA\Orm\Model;
use CnA\Orm\Relations\BelongsTo;
use CnA\Orm\Relations\HasMany;

class Order extends Model
{
    protected ?string $table = 'orders';

    protected array $fillable = [
        'user_id',
        'total_amount',
        'status',
    ];

    protected array $casts = [
        'user_id' => 'int',
        'total_amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}

