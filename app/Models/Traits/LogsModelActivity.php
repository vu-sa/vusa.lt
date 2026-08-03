<?php

namespace App\Models\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * The single entry point every logged model should use instead of wiring up
 * Spatie's LogsActivity directly. It picks the correct attribute-discovery
 * strategy automatically and applies the noise guards every model needs, so
 * authors can't silently end up logging nothing (see defaultActivitylogOptions).
 *
 * Models with bespoke needs should override getActivitylogOptions() and chain
 * onto defaultActivitylogOptions() rather than rebuilding from
 * LogOptions::defaults() — that keeps the noise guards in place.
 */
trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions();
    }

    protected function defaultActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly(config('activitylog.default_except_attributes', []));

        // v5's unguardedAttributes() returns [] when $guarded contains '*' — the
        // default a model gets when it declares #[Fillable] instead of
        // $guarded = []. logUnguarded() would then silently log nothing, so pick
        // the strategy that actually matches how the model declares mass
        // assignment instead of relying on every author remembering this.
        return in_array('*', $this->getGuarded(), true)
            ? $options->logFillable()
            : $options->logUnguarded();
    }
}
