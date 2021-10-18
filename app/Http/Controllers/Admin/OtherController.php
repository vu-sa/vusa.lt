<?php
namespace App\Http\Controllers\Admin;

use App\Models\Padalinys;
use App\Models\User;
use App\Models\Users_group;
use Illuminate\Http\Request;

class OtherController extends AdminBaseController
{
    public function index(Request $request)
    {
        $padalinys = Padalinys::where('id', '=', $request->User()->id)->first();
        
        return view('pages.admin.main', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'padalinys' => $padalinys]);
    }

    public function getFileManager(Request $request)
    {
        $alias = Users_group::where('id', '=', $request->User()->gid)->first();

        return view('pages.admin.fileManager', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'alias' => $alias]);
    }

    /**
     * Administratoriaus profilis
     */
    // public function profile($username, Request $request)
    // {
    //     $userInfo = User::where('username', '=', $username)->get()->first();

    //     return view('pages.admin.profile', ['currentRoute' => $this->currentRoute, 'UsersInfo' => $userInfo, 'sessionInfo' => $request->User(), 'name' => null]);
    // }

    public function getChangelog(Request $request) {
        return view('pages.admin.changelog', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null]);
    }

    public function updateEN(Request $request) {

        Padalinys::where('id', '=', $request->User()->id)->update([
            'en' => $request->en ?? 0
        ]);

        return redirect('/admin')->with('message', 'Nustatymai atnaujinti.');
    }
}