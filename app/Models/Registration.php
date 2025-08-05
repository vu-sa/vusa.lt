<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string|null $user_id
 * @property string $form_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FieldResponse> $fieldResponses
 * @property-read \App\Models\Form $form
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
    /** @use HasFactory<\Database\Factories\RegistrationFactory> */
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
