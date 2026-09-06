<?php

use App\Models\ContentPart;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pages = [
            'lt' => Page::query()->where('permalink', 'programos-klubai-projektai')->first(),
            'en' => Page::query()->where('permalink', 'programs-clubs-and-projects')->first(),
        ];

        $ltIntro = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Jeigu nori įkurti iniciatyvą, pasižiūrėk '],
                        ['type' => 'text', 'text' => 'šį puslapį', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://vusa.lt/lt/nauju-stud-org-ikurimas', 'target' => '_blank', 'rel' => 'noopener noreferrer nofollow']]]],
                        ['type' => 'text', 'text' => '!'],
                    ],
                ],
            ],
        ];

        $enIntro = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'If you want to create new initiatives, please read '],
                        ['type' => 'text', 'text' => 'this page.', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://vusa.lt/en/procedure-for-the-establishmen', 'target' => '_blank', 'rel' => 'noopener noreferrer nofollow', 'class' => null]]]],
                    ],
                ],
            ],
        ];

        foreach ($pages as $lang => $page) {
            if (! $page || ! $page->content_id) {
                continue;
            }

            // Shorten legacy hardcoded tiptap part to just the intro text
            $tiptapPart = ContentPart::query()
                ->where('content_id', $page->content_id)
                ->where('type', 'tiptap')
                ->first();

            if ($tiptapPart) {
                $tiptapPart->update([
                    'json_content' => $lang === 'en' ? $enIntro : $ltIntro,
                ]);
            }

            // Create institution-list part if not already present
            $hasInstitutionList = ContentPart::query()
                ->where('content_id', $page->content_id)
                ->where('type', 'institution-list')
                ->exists();

            if (! $hasInstitutionList) {
                $part = new ContentPart;
                $part->content_id = $page->content_id;
                $part->type = 'institution-list';
                $part->json_content = [
                    'title' => $lang === 'en' ? 'Programs, clubs and projects' : 'Programos, klubai ir projektai',
                    'eyebrow' => 'VU SA',
                ];
                $part->options = [
                    'typeSlug' => 'pkp',
                    'tenantScope' => 'all',
                ];
                $part->order = 1;
                $part->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $pageContentIds = Page::query()
            ->whereIn('permalink', ['programos-klubai-projektai', 'programs-clubs-and-projects'])
            ->whereNotNull('content_id')
            ->pluck('content_id');

        ContentPart::query()
            ->whereIn('content_id', $pageContentIds)
            ->where('type', 'institution-list')
            ->delete();
    }
};
