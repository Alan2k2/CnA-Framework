<?php

declare(strict_types=1);

namespace App\Models;

use CnA\Orm\Model;
use CnA\Orm\Relations\HasMany;

class User extends Model
{
    protected ?string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'api_token',
    ];

    protected array $hidden = [
        'password',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}

