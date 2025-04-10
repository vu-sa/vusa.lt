<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeIndexPage extends GeneratorCommand
{
    protected $name = 'make:index-page';
    protected $description = 'Create a new index page for an entity';
    protected $type = 'Vue Component';

    protected function getStub()
    {
        return base_path('stubs/index-page.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\resources\\js\\Pages\\Admin';
    }

    protected function buildClass($name)
    {
        $replace = [
            '{{ modelName }}' => $this->argument('modelName'),
            '{{ entityName }}' => $this->argument('entityName'),
            '{{ modelNamePlural }}' => $this->argument('modelNamePlural'),
            '{{ icon }}' => $this->argument('icon'),
        ];

        return str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));
    }

    protected function getArguments()
    {
        return [
            ['modelName', InputArgument::REQUIRED, 'The model name in snake_case'],
            ['entityName', InputArgument::REQUIRED, 'The entity name in PascalCase'],
            ['modelNamePlural', InputArgument::REQUIRED, 'The plural form of the model name'],
            ['icon', InputArgument::REQUIRED, 'The icon name for the entity'],
        ];
    }
}