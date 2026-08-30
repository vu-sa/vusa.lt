<?php

use App\Models\AgendaItemNote;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Opening an agenda item used to autosave the editor's empty document, so most
     * notes hold `<p></p>` and every item read as annotated. Re-running each value
     * through the model's mutator normalises that markup to null.
     */
    public function up(): void
    {
        AgendaItemNote::query()
            ->whereNotNull('notes_html')
            ->chunkById(200, function ($notes): void {
                foreach ($notes as $note) {
                    $note->notes_html = $note->notes_html;

                    if ($note->isDirty('notes_html')) {
                        $note->timestamps = false;
                        $note->saveQuietly();
                    }
                }
            });
    }

    public function down(): void
    {
        // Blank markup carries no information worth restoring.
    }
};
