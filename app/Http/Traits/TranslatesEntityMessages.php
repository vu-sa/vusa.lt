<?php

namespace App\Http\Traits;

/**
 * Builds the localized "<entity> created/updated/deleted/restored" messages.
 *
 * Lithuanian participles agree with the gender of their subject ("naujiena sukurta", but
 * "puslapis sukurtas"), so lang/admin/{locale}/messages.php keeps an 'f' and an 'm' variant
 * of every action and entities.php declares the gender of each entity. Call sites name the
 * entity and never the gender:
 *
 * ```php
 * return $this->redirectBackWithSuccess($this->entityMessage('created', 'news'));
 * ```
 */
trait TranslatesEntityMessages
{
    /**
     * @param  'created'|'updated'|'deleted'|'restored'  $action
     * @param  string  $entity  A key in lang/admin/{locale}/entities.php, e.g. 'news'
     */
    protected function entityMessage(string $action, string $entity): string
    {
        /** @var string $gender */
        $gender = __("entities.{$entity}.gender");

        /** @var string $message */
        $message = __("messages.{$action}.{$gender}", [
            'model' => trans_choice("entities.{$entity}.model", 1),
        ]);

        return $message;
    }
}
