<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Str;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Routing\Route;

class FilePolicy
{
    protected $pluralModelName;

    protected $authorizer;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::FILE()->label);
    }

    protected function getDirectoryPadalinysAlias(string $directory, Authorizer $authorizer): string
    {
        // Check if path has padalinys in form of 'public/files/padaliniai/{padalinys}' and also handle the case of 'public/files/padaliniai'
        // {padalinys} directory example is 'vusapadalinys'

        $path = explode('/', $directory);

        if (in_array('padaliniai', $path)) {

            // Check if not last index
            if (array_search('padaliniai', $path) + 1 >= count($path)) {
                return '';
            }

            $padalinys = $path[array_search('padaliniai', $path) + 1];

            // If $padalinys starts with vusa, remove it and only then return
            if (Str::startsWith($padalinys, 'vusa')) {
                $padalinys = substr($padalinys, 4);
                return $padalinys;
            }
        }

        return '';
    }

    /**
     * Determine whether the user can view a directory.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, string $directory, Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        $check = $this->authorizer->forUser($user)->check($this->pluralModelName . '.read.padalinys');

        $padalinysDirectory = $this->getDirectoryPadalinysAlias($directory, $authorizer);
        $permissablePadaliniai = $this->authorizer->getPadaliniai()->pluck('alias')->toArray();

        if ($check) {

            if ($this->authorizer->isAllScope) {
                return true;
            }

            if (in_array($padalinysDirectory, $this->authorizer->getPadaliniai()->pluck('alias')->toArray())) {
                return true;
            }
        }

        return false;

    }

    /**
     * Determine whether the user can create models.
     */
    // public function create(User $user): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, string $path, Authorizer $authorizer): bool
    {
        $this->authorizer = $authorizer;
        $check = $this->authorizer->forUser($user)->check($this->pluralModelName . '.read.padalinys');

        $padalinysDirectory = $this->getDirectoryPadalinysAlias($path, $this->authorizer);

        if ($check) {

            if ($this->authorizer->isAllScope) {
                return true;
            }

            if (in_array($padalinysDirectory, $this->authorizer->getPadaliniai()->pluck('alias')->toArray())) {
                return true;
            }
        }

        return false;
    }
}
