<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\News;
use Inertia\Inertia;

class NewsController extends PublicController
{
    public function newsArchive() {
		
		$news = News::where('padalinys_id', $this->padalinys->id)
            ->select('id', 'title', 'short', 'image', 'permalink', 'publish_time', 'lang')
            ->orderBy('publish_time', 'desc')
            ->paginate(15);

		return Inertia::render('Public/NewsArchive', [
			'news' => $news
		])->withViewData([
			'title' => "{$this->padalinys->shortname} naujienų archyvas",
			'description' => "Naršyk per visas {$this->padalinys->shortname} naujienas",
		]);
	}
}