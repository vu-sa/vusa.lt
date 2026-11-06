<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A Microsoft account (tenant + object id) that signs in a user. The object id survives VU
 * renaming the account, e.g. from a student to a staff address, which the email cannot.
 *
 * @property int $id
 * @property string $user_id
 * @property string $microsoft_tenant_id
 * @property string $microsoft_object_id
 * @property string $principal_name
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 *
 * @method static \Database\Factories\UserMicrosoftIdentityFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMicrosoftIdentity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMicrosoftIdentity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMicrosoftIdentity query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['user_id', 'microsoft_tenant_id', 'microsoft_object_id', 'principal_name', 'last_login_at'])]
class UserMicrosoftIdentity extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
