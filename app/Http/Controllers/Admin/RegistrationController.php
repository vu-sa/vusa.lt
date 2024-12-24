<?php

namespace App\Http\Controllers\Admin;

use App\Events\MemberRegistrationCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Registration;
use App\Services\ModelAuthorizer as Authorizer;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Collection;

class RegistrationController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Registration $registration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Registration $registration)
    {
        //
    }
}
