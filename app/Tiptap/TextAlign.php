<?php

namespace App\Tiptap;

use Tiptap\Core\Extension;

/**
 * PHP mirror of `resources/js/Components/TipTap/TextAlign.ts` — must produce byte-identical
 * class output to the frontend's live JSON→HTML preview (RichContentTiptapHTML.vue), since
 * this is what actually renders on public pages (ContentPart::getHtmlAttribute()).
 *
 * Not `Tiptap\Extensions\TextAlign` (the package ships one) — that one renders an inline
 * `style="text-align: …"`, which `HtmlSanitizerService` strips from every element on
 * write. A class survives sanitization the same way CustomHeading's size/accent do.
 */
class TextAlign extends Extension
{
    #[\Override]
    public static $name = 'textAlign';

    private const array ALIGN_CLASS = [
        'center' => 'rc-align-center',
        'end' => 'rc-align-end',
    ];

    #[\Override]
    public function addOptions()
    {
        return [
            'types' => ['heading', 'paragraph'],
        ];
    }

    #[\Override]
    public function addGlobalAttributes()
    {
        return [
            [
                'types' => $this->options['types'],
                'attributes' => [
                    'align' => [
                        'default' => null,
                        'parseHTML' => function ($DOMNode) {
                            $classes = explode(' ', $DOMNode->getAttribute('class') ?? '');
                            foreach (self::ALIGN_CLASS as $align => $class) {
                                if (in_array($class, $classes, true)) {
                                    return $align;
                                }
                            }

                            return null;
                        },
                        'renderHTML' => function ($attributes) {
                            $align = $attributes->align ?? null;
                            if (! $align || ! isset(self::ALIGN_CLASS[$align])) {
                                return null;
                            }

                            return ['class' => self::ALIGN_CLASS[$align]];
                        },
                    ],
                ],
            ],
        ];
    }
}
