<?php

namespace App\Models;

use App\Tiptap\TiptapEditor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPart extends Model
{
    use HasFactory;

    protected $casts = [
        'json_content' => 'array',
        'options' => 'array',
    ];

    protected $fillable = [
        'type',
        'json_content',
        'options',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    // TODO: Maybe use later
    public function parseTiptapElements(): ContentPart
    {
        $editor = new TiptapEditor();

        if ($this->type === 'tiptap' || $this->type === 'shadcn-card') {
            $this->html = $editor->setContent($this->json_content)->getHTML();

            return $this;
        }

        if ($this->type === 'shadcn-accordion') {

            $json_content = $this->json_content;

            foreach ($json_content as $key => $value) {
                $json_content[$key]['content'] = $editor->setContent($value['content'])->getHTML();
            }

            $this->json_content = $json_content;

            return $this;
        }

        return $this;
    }
}
