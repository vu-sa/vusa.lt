<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Services\AdminNavigation\AdminNavigationCatalog;
use Inertia\Response;

class TypeController extends AdminController
{
    public function index(AdminNavigationCatalog $catalog): Response
    {
        $destinations = $catalog->taxonomyDestinations(auth()->user());
        abort_if($destinations === [], 403);

        return $this->inertiaResponse('Admin/ModelMeta/IndexTypes', [
            'destinations' => $destinations,
        ]);
    }
}
