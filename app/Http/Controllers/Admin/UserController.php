<?php
namespace App\Http\Controllers\Admin;

use App\Models\Users_group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class UserController extends AdminBaseController {

    /**
     * Vartotojų valdymas
     */
    public function Users(Request $request)
    {
        $users = User::orderBy('gid')->simplePaginate(30);
        $userGroups = Users_group::orderBy('id')->get();

        return view('pages.admin.users', ['currentRoute' => $this->currentRoute, 'users' => $users, 'sessionInfo' => $request->User(), 'name' => null, 'userGroups' => $userGroups]);
    }

    public function getCreateUser(Request $request)
    {
        $userGroups = Users_group::orderBy('descr')->get();

        $groups = array();
        foreach ($userGroups as $userGroup) {
            $groups[$userGroup['id']] = $userGroup['descr'];
        }

        return view('pages.admin.userAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'userGroups' => $groups]);
    }

    public function postCreateUser(Request $request)
    {
        $rules = array(
            'username' => 'required|unique:users',
            'password' => 'required|min:7',
            'password_repeat' => 'required|same:password',
            'realname' => 'required',
            'gid' => 'required'
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/vartotojai/prideti')->withInput()->withErrors(($validator));
        } else {
            $user = new User;
            $user->username = $request->username;
            $user->realname = $request->realname;
            $user->password = bcrypt($request->password);
            $user->gid = $request->gid;
            $user->disabled = $request->disabled;
            $user->lastlogin = $request->lastlogin;
            $user->disabled = '0';
            $user->lastlogin_ip = "";
            $user->created = date("Y-m-d H:i:s", time());
            $user->save();
        }

        return redirect('/admin/vartotojai')->with('message', 'Naudotojas sukurtas.');
    }

    public function deleteUser(Request $request)
    {
        $userID = $request->input('userID');
        if (User::where('id', '=', $userID)->delete() == 1) {
            return redirect('/admin/vartotojai')->with('message', 'Naudotojas pašalintas.');
        } else {
            return back()->with('message', 'Naudotojo pašalinti nepavyko');
        }

    }

    public function getUpdateUser($username, Request $request)
    {
        $userInfo = User::where('username', '=', $username)->first();
        $userGroups = Users_group::orderBy('id')->get();

        $groups = array();
        foreach ($userGroups as $userGroup) {
            $groups[$userGroup['id']] = $userGroup['descr'];
        }

        return view('pages.admin.userEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'userInfo' => $userInfo, 'name' => null, 'userGroups' => $groups]);
    }

    public function postUpdateUser($username, Request $request)
    {
        $rules = array(
            'username' => 'required',
            'realname' => 'required',
            'password' => 'empty',
            'password_repeat' => 'empty',
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/vartotojai/' . $username . '/redaguoti')->withInput()->withErrors(($validator));
        } else {
            User::where('username', '=', $username)->update([
                'username' => $request->username,
                'realname' => $request->realname,
                'gid' => $request->gid
            ]);

            return redirect('/admin/vartotojai')->with('message', 'Naudotojas informacija atnaujinta.');
        }
    }

    public function getChangeUserPassword($username, Request $request)
    {

        $userInfo = User::where('username', '=', $username)->first();

        return view('pages.admin.userChangePassword', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'userInfo' => $userInfo, 'name' => null]);
    }

    public function postChangeUserPassword($username, Request $request)
    {
        $rules = array(
            'password' => 'required|min:7',
            'password_repeat' => 'required|same:password'
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('/admin/vartotojai/prideti')->withInput()->withErrors(($validator));
        } else {
            User::where('username', '=', $username)->update([
                'password' => bcrypt($request->password)
            ]);
        }

        return redirect('/admin/vartotojai')->with('message', 'Naudotojo ' . $username . ' slaptažodis atnaujintas.');
    }

    /**
     * Vartotojų grupių valdymas
     */

    public function groups(Request $request)
    {
        $groups = Users_group::simplePaginate(10);

        return view('pages.admin.groups', ['currentRoute' => $this->currentRoute, 'groups' => $groups, 'sessionInfo' => $request->User(), 'name' => null]);
    }
}