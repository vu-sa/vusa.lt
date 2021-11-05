<?php
namespace App\Http\Controllers\Admin;

use App\Models\Users_group;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ContactController extends AdminBaseController {
    /**
     * Kontaktų valdymas
     */
    public function contactList($name, Request $request)
    {
        if ($request->User()->gid != 1) {
            $contacts = Contact::where('groupname', '=', $name)->where('contactGroup', '=', $request->User()->gid)->orderBy('contactOrder')->get();
            $name2 = $name;
            if ($name == 'padalinio-taryba') {
                $name = 'Padalinio taryba';
            }

            if ($name == 'padalinio-biuras') {
                $name = 'Koordinatoriai';
            }

            if ($name == 'padalinio-biuras-en') {
                $name = 'Koordinatoriai EN';
            }

            if ($name == 'padalinio-studentu-atstovai') {
                $name = 'Studentu atstovai';
            }

            if ($name == 'padalinio-kuratoriai') {
                $name = 'Kuratoriai';
            }

            if ($name == 'padalinio-kuratoriai-en') {
                $name = 'Kuratoriai EN';
            }
        } else {
            if ($name == 'parlamentas' || $name == 'parl-pirm') {
                $contacts = Contact::where('groupname', '=', 'parl-pirm')->orWhere('groupname', '=', 'parlamentas')->orderBy('contactOrder')->get();
            } elseif ($name == 'centrinis-biuras' || $name == 'central-office') {
                $contacts = Contact::where('groupname', '=', $name)->orderBy('contactOrder')->get();
            } elseif ($name == 'studentu-atstovai-lt') {
                $contacts = Contact::where('groupname', '=', 'studentu-atstovai')->where('lang', '=', 'lt')->orderBy('contactOrder')->get();
            } elseif ($name == 'studentu-atstovai-en') {
                $contacts = Contact::where('groupname', '=', 'studentu-atstovai')->where('lang', '=', 'en')->orderBy('contactOrder')->get();
            } else {
                $contacts = Contact::where('groupname', '=', $name)->get();
            }

            $name2 = $name;

            if ($name == 'parlamentas' || $name == 'parl-pirm') {
                $name = 'Parlamentas';
            }

            if ($name == 'parlamento-darbo-grupes') {
                $name = 'Parlamento darbo grupės';
            }

            if ($name == 'centrinis-biuras') {
                $name = 'Centrinis biuras';
            }

            if ($name == 'central-office') {
                $name = 'Centrinis biuras EN';
            }

            if ($name == 'padaliniai') {
                $name = 'Padaliniai';
            }

            if ($name == 'taryba') {
                $name = 'Taryba';
            }

            if ($name == 'programos-klubai-projektai') {
                $name = 'Programos, klubai ir projektai';
            }

            if ($name == 'revizija') {
                $name = 'Revizija';
            }

            if ($name == 'stiprinimas') {
                $name = 'Institucinio stiprinimo fondas';
            }

            if ($name == 'studentu-atstovai-lt') {
                $name = 'Studentų atstovai LT';
            }

            if ($name == 'studentu-atstovai-en') {
                $name = 'Studentų atstovai EN';
            }
        }

        return view('pages.admin.contactList', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => $name, 'contacts' => $contacts, 'name2' => $name2]);
    }

    public function getAddContact(Request $request)
    {
        $groupName = $request->input('group');
        $alias = Users_group::where('id', '=', $request->User()->gid)->first();

        return view('pages.admin.contactAdd', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'groupName' => $groupName, 'alias' => $alias]);
    }

    public function postAddContact(Request $request)
    {
        $rules = array();
        if ($request->groupname == 'centrinis-biuras') {
            $rules = array(
                'name' => 'required',
                'duties' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'infoText' => 'required',
                'image' => 'required',
                'groupname' => 'required'
            );
        }
        if ($request->groupname == 'padaliniai') {
            $rules = array(
                'name_full' => 'required|unique:contacts',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required',
                'address' => 'required',
            );
        }
        if ($request->groupname == 'taryba') {
            $rules = array(
                'name' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required',
                'name_short' => 'required'
            );
        }
        if ($request->groupname == 'padalinio-taryba') {
            $rules = array(
                'name' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required',
            );
        }
        if ($request->groupname == 'padalinio-biuras') {
            $rules = array(
                'name' => 'required',
                'duties' => 'required',
                'phone' => 'required',
                'infoText' => 'required',
                'image' => 'required',
                'groupname' => 'required',
            );
        }
        if ($request->groupname == 'padalinio-kuratoriai') {
            $rules = array(
                'name' => 'required',
                'duties' => 'required',
                'infoText' => 'required',
                'image' => 'required',
                'groupname' => 'required',
            );
        }
        if ($request->groupname == 'parl-pirm') {
            $rules = array(
                'name' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required',
            );
        }
        if ($request->groupname == 'parlamentas') {
            $rules = array(
                'name_short' => 'required',
                'members' => 'required',
            );
        }

        if ($request->groupname == 'parlamento-darbo-grupes') {
            $rules = array(
                'nameP' => 'required',
                'name_full' => 'required',
                'members' => 'required',
                'phone' => 'required',
                'email' => 'required',
            );
        }
        if ($request->groupname == 'programos-klubai-projektai') {
            $rules = array();
        }
        if ($request->groupname == 'revizija') {
            $rules = array(
                'nameRevizija' => 'required',
                'members' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required'
            );
        }

        $validator = Validator::make($request->all(), $rules);
        $file = null;
        $fileName = "";
        if ($request->image !== null) {
            if (strpos($request->image, '.vusa.') !== false) {
                $fileName = substr($request->image, 22);
            } else {
                $fileName = $request->image;
            }

            $manager = new ImageManager(array('driver' => 'imagick'));
            $frame_height = 0;
            $frame_width = 0;

            if ($request->frame_height != 0 && $request->frame_width != 0) {
                $frame_height = $request->frame_height;
                $frame_width = $manager->make($fileName)->width() / 1.1468;
                $scale_height = $manager->make($fileName)->height() / $frame_height;
                $scale_width = $manager->make($fileName)->width() / $frame_width;
                $manager->make($fileName)->crop(intval($scale_width * $request->width), intval($request->height * $scale_height),
                    intval($scale_width * $request->x), intval($scale_height * $request->y))->save($fileName);
            }
        }

        if ($validator->fails())
            return Redirect::to('/admin/kontaktai/prideti')->withInput()->withErrors(($validator));
        else {
            $contact = new Contact();

            $contact->duties = $request->duties;
            $contact->phone = $request->phone;
            $contact->email = $request->email;
            $contact->infoText = $request->infoText;
            $contact->image = $fileName;
            $contact->groupname = $request->groupname;
            $contact->name_short = $request->name_short;
            $contact->name_full = $request->name_full;
            $contact->address = $request->address;
            $contact->webpage = $request->webpage;
            $contact->members = $request->members;
            $contact->grouptitle = $request->grouptitle;

            if ($request->groupname == 'revizija') {
                $contact->name = $request->nameRevizija;
            }

            if ($request->groupname == 'padalinio-taryba' || $request->groupname == 'padalinio-biuras' || $request->groupname == 'padalinio-studentu-atstovai' || $request->groupname == 'padalinio-biuras-en') {
                $contact->name = $request->name;
            }

            if ($request->groupname == 'padalinio-kuratoriai' || $request->groupname == 'padalinio-kuratoriai-en') {
                $kuratoriaiContacts = Contact::where('groupname', '=', 'padalinio-kuratoriai')->where('contactGroup', '=', $request->User()->gid)->get();
                $contact->contactOrder = sizeof($kuratoriaiContacts) + 1;
            }

            if ($request->groupname == 'padalinio-biuras' || $request->groupname == 'padalinio-biuras-en') {
                $contact->name = $request->name;
                $padalinioBiurasContacts = Contact::where('groupname', '=', 'padalinio-biuras')->where('contactGroup', '=', $request->User()->gid)->get();
                $contact->contactOrder = sizeof($padalinioBiurasContacts) + 1;
            }

            if ($request->groupname == 'programos-klubai-projektai') {
                $contact->name_full = $request->name_full_programos;
            }

            if ($request->groupname == 'centrinis-biuras') {
                $contact->name = $request->name;
                $cbContacts = Contact::where('groupname', '=', 'centrinis-biuras')->get();
                $contact->contactOrder = sizeof($cbContacts) + 1;
            }

            if ($request->groupname == 'central-office') {
                $contact->name = $request->name;
                $cbContacts = Contact::where('groupname', '=', 'central-office')->get();
                $contact->contactOrder = sizeof($cbContacts) + 1;
            }

            if ($request->groupname == 'parlamentas') {
                $parlContacts = Contact::where('groupname', '=', 'parlamentas')->get();
                $contact->contactOrder = sizeof($parlContacts) + 1;
            }

            if ($request->groupname == 'parlamento-darbo-grupes') {
                $contact->name = $request->nameP;
            }

            if ($request->groupname == 'parl-pirm') {
                $contact->name = $request->name;
            }

            if ($request->groupname == 'aprasymas') {
                $contact->grouptitle = $request->grouptitleDescription;
            }

            $contact->contactGroup = $request->User()->gid;
            $contact->save();
        }

        return redirect('/admin/kontaktai/' . $request->groupname)->with('message', 'Kontaktas sėkmingai pridėtas.');
    }

    public function deleteContact(Request $request)
    {
        $itemId = $request->input('itemId');
        $contactInfo = Contact::where('id', '=', $itemId)->first();
        if ($contactInfo['image'] !== '') {
            Storage::delete('uploads/user/files/' . $contactInfo['image']);
        }

        if (Contact::where('id', '=', $contactInfo->id)->delete() == 1) {
            if ($contactInfo['groupname'] == 'parlamentas' || $contactInfo['groupname'] == 'centrinis-biuras' || $contactInfo['groupname'] == 'central-office') {
                $contacts = Contact::where('contactOrder', '>', $contactInfo['contactOrder'])->where('groupname', '=', $contactInfo['groupname'])->get();
                foreach ($contacts as $contact) {
                    Contact::where('id', '=', $contactInfo->id)->update(['contactOrder' => $contactInfo->contactOrder - 1]);
                }
            }
            return response()->json('DELETED', 200);
        } else {
            return response()->json('NOT DELETED', 200);
        }
    }

    public function getEditContact($id, Request $request)
    {
        $contats = Contact::where('id', '=', $id)->first();
        $alias = Users_group::where('id', '=', $request->User()->gid)->first();
        if ($contats['groupname'] == 'revizija') {
            $contats['nameRevizija'] = $contats['name'];
        }
        if ($contats['groupname'] == 'programos-klubai-projektai') {
            $contats['grouptitle'] = $contats['name'];
            $contats['name_full_programos'] = $contats['name_full'];
        }

        if ($contats['groupname'] == 'aprasymas') {
            $contats['grouptitleDescription'] = $contats['grouptitle'];
        }

        if ($contats['groupname'] == 'centrinis-biuras') {
            $contats['nameCB'] = $contats['name'];
        }

        return view('pages.admin.contactEdit', ['currentRoute' => $this->currentRoute, 'sessionInfo' => $request->User(), 'name' => null, 'contats' => $contats, 'alias' => $alias]);
    }

    public function postEditContact($id, Request $request)
    {
        $rules = array();
        if ($request->groupname == 'centrinis-biuras') {
            $rules = array(
                'name' => 'required',
                'duties' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'infoText' => 'required',
                'image' => 'required',
                'groupname' => 'required',
            );
        }
        if ($request->groupname == 'padaliniai') {
            $rules = array(
                'name_short' => 'required',
                'name_full' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required',
                'address' => 'required',
                'webpage' => 'required',
            );
        }
        if ($request->groupname == 'taryba') {
            $rules = array(
                'name' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required',
            );
        }
        if ($request->groupname == 'parl-pirm') {
            $rules = array(
                'name' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'groupname' => 'required',
            );
        }
        if ($request->groupname == 'parlamentas') {
            $rules = array(
                'name_short' => 'required',
                'members' => 'required',
            );
        }
        if ($request->groupname == 'programos-klubai-projektai') {
            $rules = array();
        }

        $validator = Validator::make($request->all(), $rules);
        $file = null;
        $fileName = "";
        if ($request->image !== null) {
            if (strpos($request->image, '.vusa.') !== false) {
                $fileName = substr($request->image, 22);
            } else {
                $fileName = $request->image;
            }

            $manager = new ImageManager(array('driver' => 'imagick'));
            $frame_height = 0;
            $frame_width = 0;

            if ($request->frame_height != 0 && $request->frame_width != 0) {
                $frame_height = $request->frame_height;
                $frame_width = $manager->make($fileName)->width() / 1.1468;
                $scale_height = $manager->make($fileName)->height() / $frame_height;
                $scale_width = $manager->make($fileName)->width() / $frame_width;
                $manager->make($fileName)->crop(intval($scale_width * $request->width), intval($request->height * $scale_height),
                    intval($scale_width * $request->x), intval($scale_height * $request->y))->save($fileName);
            }
        }
        if ($validator->fails())
            return Redirect::to('/admin/kontaktai/' . $id . '/redaguoti')->withErrors(($validator));
        else {
            if ($request->groupname == 'revizija') {
                $request->name = $request->nameRevizija;
            }

            if ($request->groupname == 'parlamento-darbo-grupes') {
                $request->name = $request->nameP;
            }

            if ($request->groupname == 'programos-klubai-projektai') {
                $request->name_full = $request->name_full_programos;
            }

            if ($request->groupname == 'aprasymas' || $request->groupname == 'aprasymas-padalinys') {
                $request->grouptitle = $request->grouptitleDescription;
            }
            
            $lang = Contact::where('id', '=', $id)->first()->lang;

            Contact::where('id', '=', $id)->update([
                'name' => $request->name,
                'duties' => $request->duties,
                'phone' => $request->phone,
                'email' => $request->email,
                'infoText' => $request->infoText,
                'groupname' => $request->groupname,
                'grouptitle' => $request->grouptitle,
                'name_short' => $request->name_short,
                'name_full' => $request->name_full,
                'address' => $request->address,
                'webpage' => $request->webpage,
                'members' => $request->members,
                'image' => $fileName
            ]);
        }
        if ($request->groupname == 'studentu-atstovai') {
            return redirect('/admin/kontaktai/' . ($lang == 'lt' ? 'studentu-atstovai-lt' : 'studentu-atstovai-en'))->with('message', 'Kontaktas sėkmingai atnaujintas.');
        } else {
            return redirect('/admin/kontaktai/' . $request->groupname)->with('message', 'Kontaktas sėkmingai atnaujintas.');
        }
    }

    public function getSwapContactUp($id, Request $request)
    {
        $contactGroup = $request->input('category');
        $selectedContactItem = Contact::where('id', '=', $id)->where('groupname', 'like', $contactGroup)->first();
        $upperContactItem = Contact::where('contactOrder', '<', $selectedContactItem['contactOrder'])->where('groupname', 'like', $contactGroup)->get()->sortByDesc('contactOrder')->first();

        Contact::where('id', '=', $selectedContactItem['id'])->update(['contactOrder' => $upperContactItem['contactOrder']]);
        Contact::where('id', '=', $upperContactItem['id'])->update(['contactOrder' => $selectedContactItem['contactOrder']]);

        return back();
    }

    public function getSwapContactDown($id, Request $request)
    {
        $contactGroup = $request->input('category');
        $selectedContactItem = Contact::where('id', '=', $id)->where('groupname', 'like', $contactGroup)->first();
        $upperContactItem = Contact::where('contactOrder', '>', $selectedContactItem['contactOrder'])->where('groupname', 'like', $contactGroup)->get()->sortBy('contactOrder')->first();

        Contact::where('id', '=', $selectedContactItem['id'])->update(['contactOrder' => $upperContactItem['contactOrder']]);
        Contact::where('id', '=', $upperContactItem['id'])->update(['contactOrder' => $selectedContactItem['contactOrder']]);

        return back();
    }
}