<?php

namespace App\Models;

use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $user_id
 * @property string $form_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, FieldResponse> $fieldResponses
 * @property-read Form|null $form
 *
 * @method static \Database\Factories\RegistrationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration query()
 *
 * @mixin \Eloquent
 */
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function fieldResponses()
    {
        return $this->hasMany(FieldResponse::class);
    }
}
