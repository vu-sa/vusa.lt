<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Support\Str;

class FilePolicy
{
    protected $pluralModelName;

    public function __construct(public Authorizer $authorizer)
    {
        $this->pluralModelName = Str::plural(ModelEnum::FILE()->label);
    }

    protected function getDirectoryPadalinysAlias(string $directory): string
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
    public function viewAny(User $user, string $directory)
    {
        $check = $this->authorizer->forUser($user)->check($this->pluralModelName.'.read.padalinys');

        $padalinysDirectory = $this->getDirectoryPadalinysAlias($directory, $this->authorizer);

        if ($check) {

            if ($this->authorizer->isAllScope) {
                return true;
            }

            if (in_array($padalinysDirectory, $this->authorizer->getTenants()->pluck('alias')->toArray())) {
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
    public function delete(User $user, string $path): bool
    {
        $check = $this->authorizer->forUser($user)->check($this->pluralModelName.'.read.padalinys');

        $padalinysDirectory = $this->getDirectoryPadalinysAlias($path, $this->authorizer);

        if ($check) {

            if ($this->authorizer->isAllScope) {
                return true;
            }

            if (in_array($padalinysDirectory, $this->authorizer->getTenants()->pluck('alias')->toArray())) {
                return true;
            }
        }

        return false;
    }
}
