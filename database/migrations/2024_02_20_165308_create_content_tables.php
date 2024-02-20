<?php

use App\Models\Content;
use App\Models\News;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('json_content');
            $table->json('options')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('contentables', function (Blueprint $table) {
            $table->foreignId('content_id');
            $table->morphs('contentable');
            $table->integer('order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['content_id', 'contentable_id', 'contentable_type'], 'contentables_primary');
        });

        $pages = Page::all();

        foreach ($pages as $key => $page) {
            $json = (new Tiptap\Editor)->setContent($page->text)->getJSON();

            $content = new Content();

            $content->type = 'tiptap';
            $content->json_content = $json;

            $content->save();

            $page->contents()->attach($content, ['order' => 0]);

            $page->save();

            echo "Page {$page->id} has been migrated\n";
        }

        $news = News::all();

        foreach ($news as $key => $new) {
            $json = (new Tiptap\Editor)->setContent($new->text)->getJSON();

            $content = new Content();

            $content->type = 'tiptap';
            $content->json_content = $json;

            $content->save();

            $new->contents()->attach($content, ['order' => 0]);

            $new->save();

            echo "News {$new->id} has been migrated\n";
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('text');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
        Schema::dropIfExists('contentables');
    }
};
