<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ModelAuthorizer as Authorizer;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Contact::class);

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
        $this->authorize('create', Contact::class);

        // TODO: implement duties later
        return Inertia::render('Admin/People/CreateContact', [
            // 'duties' => $this->getDutiesForForm()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Contact::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:contacts|unique:users',
            'phone' => 'nullable|string|max:255',
            'extra_attributes' => 'array',
        ]);

        $contact = Contact::create($validated);

        return redirect()->route('contacts.index')->with('success', 'Kontaktas sukurtas.');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact)
    {
        $this->authorize('view', Contact::class);

        return Inertia::render('Admin/People/ShowContact', [
            'contact' => $contact->load('activities.causer', 'comments'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        $this->authorize('update', $contact);

        return Inertia::render('Admin/People/EditContact', [
            'contact' => $contact,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('contacts')->ignore($contact->id)],
            'phone' => 'nullable|string|max:255',
            'extra_attributes' => 'array',
        ]);

        $contact->update($validated);

        return redirect()->back()->with('success', 'Kontaktas atnaujintas.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Kontaktas ištrintas.');
    }
}
