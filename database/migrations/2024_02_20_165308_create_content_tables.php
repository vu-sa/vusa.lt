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
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('content_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->json('json_content');
            $table->json('options')->nullable();
            $table->integer('order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('content_id')->after('other_lang_id')->constrained()->onDelete('cascade');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('content_id')->after('other_lang_id')->constrained()->onDelete('cascade');
        });

        $pages = Page::all();

        foreach ($pages as $key => $page) {
            $json = (new Tiptap\Editor)->setContent($page->text)->getDocument();

            $content = new Content();

            $content->parts()->create([
                'type' => 'tiptap',
                'json_content' => $json,
            ]);

            $page->content()->associate($content);

            echo "Page {$page->id} has been migrated\n";
        }

        $news = News::all();

        foreach ($news as $key => $new) {
            $json = (new Tiptap\Editor)->setContent($new->text)->getDocument();

            $content = new Content();

            $content->parts()->create([
                'type' => 'tiptap',
                'json_content' => $json,
            ]);

            $new->content()->associate($content);

            echo "News {$new->id} has been migrated\n";
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('text');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('text');
        });

        // long text for session payload
        Schema::table('sessions', function (Blueprint $table) {
            $table->longText('payload')->change();
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
