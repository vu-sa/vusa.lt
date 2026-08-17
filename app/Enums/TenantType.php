<?php

namespace App\Enums;

/**
 * The kind of organisational unit a tenant represents.
 *
 * These three strings drove roughly forty comparisons across PHP and Vue with no definition
 * anywhere — the closest thing to a source of truth was a hand-written option list in
 * TenantForm.vue. Backing values match the `tenants.type` column exactly, so the enum can be
 * cast on the model and compared against existing rows without a data migration.
 */
enum TenantType: string
{
    /** VU SA central office — exactly one row. */
    case Pagrindinis = 'pagrindinis';

    /** A faculty-level representation unit. */
    case Padalinys = 'padalinys';

    /** Programos, klubai, projektai — student initiatives rather than representation units. */
    case Pkp = 'pkp';

    /**
     * The translation key used for this type's label in the admin UI.
     */
    public function labelKey(): string
    {
        return 'forms.options.tenant_type_'.$this->value;
    }

    /**
     * Types that take part in student representation — i.e. everything except PKP.
     *
     * Several queries exclude PKP tenants because student initiatives have no formal
     * representation duties, meetings or dashboards.
     *
     * @return list<self>
     */
    public static function representational(): array
    {
        return [self::Pagrindinis, self::Padalinys];
    }

    /**
     * @return list<string>
     */
    public static function representationalValues(): array
    {
        return array_map(fn (self $type) => $type->value, self::representational());
    }
}
