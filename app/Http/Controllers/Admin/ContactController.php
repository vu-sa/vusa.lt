<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ResourceController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:contacts|unique:users',
            'phone' => 'nullable|string|max:255',
            'extra_attributes' => 'array'
        ]);

        $contact = Contact::create($validated);

        return redirect()->route('contacts.index')->with('success', 'Kontaktas sukurtas.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact)
    {
        $this->authorize('view', [Contact::class, $contact, $this->authorizer]);

        return Inertia::render('Admin/People/ShowContact', [
            'contact' => $contact->load('activities.causer', 'comments'),
        ]);
    }

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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('contacts')->ignore($contact->id)],
            'phone' => 'nullable|string|max:255',
            'extra_attributes' => 'array'
        ]);

        $contact->update($validated);

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
