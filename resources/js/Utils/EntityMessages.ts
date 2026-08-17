import { trans, transChoice } from 'laravel-vue-i18n';

/**
 * Lithuanian adjectives agree with the gender of the noun they describe, so `forms.new_model`
 * and `forms.edit_model` keep an `f` and an `m` variant and every entity declares its gender in
 * `entities.php`. These helpers are the frontend counterpart of
 * `App\Http\Traits\TranslatesEntityMessages::entityMessage()` — call sites name the entity, and
 * never a gender (which used to be smuggled in as `$tChoice(..., 0 | 1)`).
 *
 * @param entity a key in lang/admin/{locale}/entities.php, e.g. 'reservation'
 */
function entityTitle(key: 'new_model' | 'edit_model', entity: string): string {
  return trans(`forms.${key}.${trans(`entities.${entity}.gender`)}`, {
    model: transChoice(`entities.${entity}.model`, 1),
  });
}

/** "Nauja rezervacija" / "New reservation" — the heading of a create page. */
export const newEntityTitle = (entity: string): string => entityTitle('new_model', entity);

/** "Rezervacijos redagavimas" / "Edit reservation" — the heading of an edit page. */
export const editEntityTitle = (entity: string): string => entityTitle('edit_model', entity);
