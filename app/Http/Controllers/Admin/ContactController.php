<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ResourceController;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Contact::class, $this->authorizer]);

        $contacts = Contact::all()->paginate(20);

        return Inertia::render('Admin/People/IndexContact', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [Contact::class, $this->authorizer]);
        
        // TODO: implement duties later
        return Inertia::render('Admin/People/CreateContact', [
            // 'duties' => $this->getDutiesForForm()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Contact::class, $this->authorizer]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:contacts|unique:users',
            'phone' => 'required|string|max:255',
        ]);

        $contact = Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return redirect()->route('contacts.index')->with('success', 'Kontaktas sukurtas.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    // public function show(Contact $contact)
    // {
    //     $this->authorize('view', [Contact::class, $contact, $this->authorizer]);
    // }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        $this->authorize('update', [Contact::class, $contact, $this->authorizer]);
        
        return Inertia::render('Admin/People/EditContact', [
            'contact' => $contact,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', [Contact::class, $contact, $this->authorizer]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:contacts,email,' . $contact->id . ',id|unique:users,email,' . $contact->id . ',id',
            'phone' => 'required|string|max:255',
        ]);

        $contact->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return redirect()->back()->with('success', 'Kontaktas atnaujintas.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        $this->authorize('delete', [Contact::class, $contact, $this->authorizer]);

        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Kontaktas ištrintas.');
    }
}