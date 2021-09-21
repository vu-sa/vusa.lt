<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;

class AdminBaseController extends BaseController {

    public $currentRoute;

    public function __construct()
    {
        $this->middleware('auth');
        $this->currentRoute = request()->path();
    }

}