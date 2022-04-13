<?php

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\Request;

class MainController extends Controller {
	public function home (Request $request) {
		return Inertia::render('Public/Home');
	}
}