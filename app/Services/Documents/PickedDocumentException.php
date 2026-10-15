<?php

namespace App\Services\Documents;

/**
 * A file chosen in SharePoint's picker that cannot become a document; the message is for the user.
 */
class PickedDocumentException extends \RuntimeException
{
    public static function outsideArchive(string $name): self
    {
        return new self(__('messages.document.pick_outside_archive', ['name' => $name]));
    }

    public static function notFound(): self
    {
        return new self(__('messages.document.pick_not_found'));
    }
}
