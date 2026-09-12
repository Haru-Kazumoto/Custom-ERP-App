<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('make:module {name}')]
#[Description('Generating a module for directory structure')]
class MakeModuleCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = ucfirst($this->argument('name'));

        $modulePath = app_path("Modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("Module {$name} already exists.");
            return Command::FAILURE;
        }

        File::makeDirectory($modulePath . '/Controllers', 0755, true);
        File::makeDirectory($modulePath . '/Queries', 0755, true);
        File::makeDirectory($modulePath . '/Repositories', 0755, true);

        $this->createController($name);
        $this->createQuery($name);
        $this->createRepository($name);

        $this->info("Module {$name} created successfully.");

        return Command::SUCCESS;
    }

    protected function createController(string $name): void
    {
        $content = <<<PHP
<?php

namespace App\Modules\\{$name}\Controllers;

use App\Http\Controllers\Controller;

class {$name}Controller extends Controller
{
    //
}

PHP;

        File::put(
            app_path("Modules/{$name}/Controllers/{$name}Controller.php"),
            $content
        );
    }

    protected function createQuery(string $name): void
    {
        $content = <<<PHP
<?php

namespace App\Modules\\{$name}\Queries;

class Get{$name}Query
{
    //
}

PHP;

        File::put(
            app_path("Modules/{$name}/Queries/Get{$name}Query.php"),
            $content
        );
    }

    protected function createRepository(string $name): void
    {
        $content = <<<PHP
<?php

namespace App\Modules\\{$name}\Repositories;

class {$name}Repository
{
    //
}

PHP;

        File::put(
            app_path("Modules/{$name}/Repositories/{$name}Repository.php"),
            $content
        );
    }
}
