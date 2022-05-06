<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class PageView extends Model
{
    protected $table = 'page_views';

    public static function createViewLog() {
        $view = new PageView();
        $view->host = Request::getHost();
        $view->url = Request::url();
        $view->session_id = Request::getSession()->getId();
        $view->user_id = (Auth::check()) ? Auth::id() : null; //this check will either put the user id or null, no need to use \Auth()->user()->id as we have an inbuild function to get auth id
        $view->ip = Request::getClientIp();
        $view->agent = Request::header('User-Agent');
        $view->save();//please note to save it at lease, very important
    }
}
