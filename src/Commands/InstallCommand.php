<?php

namespace Varbox\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Varbox\Seed\AnalyticsSeeder;
use Varbox\Seed\CountriesSeeder;
use Varbox\Seed\LanguagesSeeder;
use Varbox\Seed\PermissionsSeeder;
use Varbox\Seed\RolesSeeder;
use Varbox\Seed\UsersSeeder;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'varbox:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the VarBox platform.';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $this->brand();

        $this->line('<fg=cyan>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=cyan>Installing the VarBox platform.</>');
        $this->line('<fg=cyan>-------------------------------------------------------------------------------------------------------</>');

        $this->publishFiles();
        $this->writeEnvVariables();
        $this->registerRoutes();
        $this->modifyUserModel();
        $this->modifyExceptionHandler();
        $this->generateAdminMenu();
        $this->generatePages();
        $this->manageUploads();
        $this->manageBackups();
        $this->manageFroala();
        $this->migrateTables();
        $this->seedDatabase();
    }

    /**
     * @return void
     */
    protected function publishFiles()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>PUBLISHING FILES</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $this->callSilent('vendor:publish', ['--tag' => 'config']);
        $this->line('<fg=green>SUCCESS |</> Published all config files inside the "config/varbox/" directory.');

        $this->callSilent('vendor:publish', ['--tag' => 'varbox-migrations']);
        $this->line('<fg=green>SUCCESS |</> Published all migration files inside the "database/migrations/" directory.');

        $this->callSilent('vendor:publish', ['--tag' => 'varbox-views']);
        $this->line('<fg=green>SUCCESS |</> Published all view files inside the "resources/views/vendor/varbox/" directory.');

        $this->callSilent('vendor:publish', ['--tag' => 'varbox-public']);
        $this->line('<fg=green>SUCCESS |</> Published all asset files inside the "public/vendor/varbox/" directory.');
    }

    /**
     * @return void
     */
    protected function writeEnvVariables()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>UPDATING .ENV FILE</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        try {
            $env = $this->files->get($this->laravel->environmentFilePath());

            if (false === strpos($env, 'CACHE_ALL_QUERIES')) {
                $this->files->append($this->laravel->environmentFilePath(), "\nCACHE_ALL_QUERIES=false");
                $this->line('<fg=green>SUCCESS |</> Appended "CACHE_ALL_QUERIES" configuration to the ".env" file!');
            } else {
                $this->line('<fg=green>SUCCESS |</> The ".env" file already contains the "CACHE_ALL_QUERIES" configuration.');
            }

            if (false === strpos($env, 'CACHE_DUPLICATE_QUERIES')) {
                $this->files->append($this->laravel->environmentFilePath(), "\nCACHE_DUPLICATE_QUERIES=false\n");
                $this->line('<fg=green>SUCCESS |</> Appended "CACHE_DUPLICATE_QUERIES" configuration to the ".env" file!');
            } else {
                $this->line('<fg=green>SUCCESS |</> The ".env" file already contains the "CACHE_DUPLICATE_QUERIES" configuration.');
            }

            if (false === strpos($env, 'LOG_ACTIVITY')) {
                $this->files->append($this->laravel->environmentFilePath(), "\nLOG_ACTIVITY=true\n");
                $this->line('<fg=green>SUCCESS |</> Appended "LOG_ACTIVITY" configuration to the ".env" file!');
            } else {
                $this->line('<fg=green>SUCCESS |</> The ".env" file already contains the "LOG_ACTIVITY" configuration.');
            }

            if (false === strpos($env, 'SAVE_ERRORS')) {
                $this->files->append($this->laravel->environmentFilePath(), "\nSAVE_ERRORS=true\n");
                $this->line('<fg=green>SUCCESS |</> Appended "SAVE_ERRORS" configuration to the ".env" file!');
            } else {
                $this->line('<fg=green>SUCCESS |</> The ".env" file already contains the "SAVE_ERRORS" configuration.');
            }

            if (false === strpos($env, 'ANALYTICS_VIEW_ID')) {
                $this->files->append($this->laravel->environmentFilePath(), "\nANALYTICS_VIEW_ID=\n");
                $this->line('<fg=green>SUCCESS |</> Appended "ANALYTICS_VIEW_ID" configuration to the ".env" file!');
            } else {
                $this->line('<fg=green>SUCCESS |</> The ".env" file already contains the "ANALYTICS_VIEW_ID" configuration.');
            }

            if (false === strpos($env, 'FFMPEG_PATH')) {
                $this->files->append($this->laravel->environmentFilePath(), "\nFFMPEG_PATH=ffmpeg");
                $this->line('<fg=green>SUCCESS |</> Appended "FFMPEG_PATH" configuration to the ".env" file!');
            } else {
                $this->line('<fg=green>SUCCESS |</> The ".env" file already contains the "FFMPEG_PATH" configuration.');
            }

            if (false === strpos($env, 'FFPROBE_PATH')) {
                $this->files->append($this->laravel->environmentFilePath(), "\nFFPROBE_PATH=ffprobe\n");
                $this->line('<fg=green>SUCCESS |</> Appended "FFPROBE_PATH" configuration to the ".env" file!');
            } else {
                $this->line('<fg=green>SUCCESS |</> The ".env" file already contains the "FFPROBE_PATH" configuration.');
            }
        } catch (FileNotFoundException $e) {
            $this->line('<fg=red>ERROR   |</> Unable to append the env variables! The file ".env" was not found.');
        }
    }

    /**
     * @return void
     */
    protected function registerRoutes()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>REGISTERING ROUTE</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        try {
            $routes = $this->files->get(base_path('routes/web.php'));

            if (false === strpos($routes, 'Route::url()')) {
                $this->files->append(base_path('routes/web.php'), "\n// This should be the last line\nRoute::url();\n");
                $this->line('<fg=green>SUCCESS |</> Registered the routes inside the "routes/web.php" file.');
            } else {
                $this->line('<fg=green>SUCCESS |</> Route already registered inside the "routes/web.php" file.');
            }
        } catch (FileNotFoundException $e) {
            $this->line('<fg=red>ERROR   |</> Unable to register the route`! The file "routes/web.php" was not found.');
        }
    }

    /**
     * @return void
     * @throws FileNotFoundException
     */
    protected function modifyUserModel()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>MODIFYING USER MODEL</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $authGuardsStub = __DIR__ . '/../../resources/stubs/config/auth/guards.stub';
        $userModelFile = $this->laravel['path'] . '/User.php';
        $authConfig = $this->laravel['path.config'] . '/auth.php';

        if ($this->files->exists($authConfig)) {
            $content = $this->files->get($authConfig);

            if (strpos($content, "'admin' => [") === false) {
                $content = str_replace(
                    "'guards' => [",
                    "'guards' => [\n\n" . file_get_contents($authGuardsStub)
                    , $content
                );

                $this->files->put($authConfig, $content);

                $this->line('<fg=green>SUCCESS |</> Added the "admin" guard inside "config/auth.php" => "guards".');
            } else {
                $this->line('<fg=green>SUCCESS |</> The "admin" guard already exists inside "config/auth.php" => "guards".');
            }
        } else {
            $this->line('<fg=red>ERROR   |</> The "config/auth.php" file does not exist!');
            $this->line('<fg=red>ERROR   |</> You will have to manually add the "admin" guard (same as "users").');
        }

        if ($this->files->exists($userModelFile)) {
            $content = $this->files->get($userModelFile);

            if ($content !== false) {
                $content = str_replace(
                    'extends Authenticatable',
                    "extends \Varbox\Models\User",
                    $content
                );

                $content = str_replace(
                    "'name', 'email', 'password'",
                    "'email', 'password', 'first_name', 'last_name', 'active'",
                    $content
                );

                if (strpos($content, "protected \$casts = [\n        'active' => 'boolean',") === false) {
                    $content = str_replace(
                        "protected \$casts = [",
                        "protected \$casts = [\n        'active' => 'boolean',",
                        $content
                    );
                }

                $this->files->put($userModelFile, $content);

                $this->line('<fg=green>SUCCESS |</> Extended the "app/User.php" with the VarBox user model.');
                $this->line('<fg=green>SUCCESS |</> Modified the "fillable" property of the "app/User.php".');
                $this->line('<fg=green>SUCCESS |</> Modified the "casts" property of the "app/User.php".');
            } else {
                $this->line('<fg=red>ERROR   |</> Could not get the contents of "app/User.php"! You will need to update this manually.');
                $this->line('<fg=red>ERROR   |</> Change "extends Authenticatable" to "extends \Varbox\Models\User" in your user model.');
                $this->line('<fg=red>ERROR   |</> Append to the "fillable" property the following fields: first_name, last_name, active');
                $this->line('<fg=red>ERROR   |</> Append to the "casts" property the following: "active" => "boolean"');
            }
        } else {
            $this->line('<fg=red>ERROR   |</> Unable to locate "app/User.php"! You will need to update this manually.');
            $this->line('<fg=red>ERROR   |</> Change "extends Authenticatable" to "extends \Varbox\Models\User" in your user model.');
            $this->line('<fg=red>ERROR   |</> Append to the "fillable" property the following fields: first_name, last_name, active');
            $this->line('<fg=red>ERROR   |</> Append to the "casts" property the following: "active" => "boolean"');
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function modifyExceptionHandler()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>MODIFYING THE EXCEPTION HANDLER</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $exceptionHandlerFile = $this->laravel['path'] . '/Exceptions/Handler.php';

        if ($this->files->exists($exceptionHandlerFile)) {
            $content = $this->files->get($exceptionHandlerFile);

            if ($content !== false) {
                $content = str_replace(
                    'extends ExceptionHandler',
                    "extends \Varbox\Exceptions\Handler",
                    $content
                );

                $this->files->put($exceptionHandlerFile, $content);

                $this->line('<fg=green>SUCCESS |</> Extended the "app/Exceptions/Handler.php" with the VarBox exception handler.');
            } else {
                $this->line('<fg=red>ERROR   |</> Could not get the contents of "app/Exceptions/Handler.php"! You will need to update this manually.');
                $this->line('<fg=red>ERROR   |</> Change "extends ExceptionHandler" to "extends \Varbox\Exceptions\Handler" in your exception handler.');
            }
        } else {
            $this->line('<fg=red>ERROR   |</> Unable to locate "app/Exceptions/Handler.php"! You will need to update this manually.');
            $this->line('<fg=red>ERROR   |</> Change "extends ExceptionHandler" to "extends \Varbox\Exceptions\Handler" in your exception handler.');
        }
    }

    /**
     * @return bool
     * @throws FileNotFoundException
     */
    protected function generateAdminMenu()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>GENERATING ADMIN MENU</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $stub = __DIR__ . '/../../resources/stubs/composers/admin_menu.stub';
        $path = "{$this->laravel['path']}/Http/Composers";
        $file = "{$path}/AdminMenuComposer.php";
        $contents = str_replace('DummyNamespace', $this->laravel->getNamespace() . 'Http\\Composers', $this->files->get($stub));

        if ($this->files->exists($file)) {
            $this->line('<fg=green>SUCCESS |</> The file "AdminMenuComposer.php" already exists inside the "app/Http/Composers/" directory!');

            return false;
        }

        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true, true);
        }

        $this->files->put($file, $contents);
        $this->line('<fg=green>SUCCESS |</> The "AdminMenuComposer.php" file has been copied over to "app/Http/Composers/" directory!');
    }

    /**
     * @return bool
     * @throws FileNotFoundException
     */
    protected function generatePages()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>GENERATING PAGES ROUTER</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $stub = __DIR__ . '/../../resources/stubs/controllers/pages.stub';
        $path = "{$this->laravel['path']}/Http/Controllers";
        $file = "{$path}/PagesController.php";
        $contents = str_replace('DummyNamespace', $this->laravel->getNamespace() . 'Http\\Controllers', $this->files->get($stub));

        if ($this->files->exists($file)) {
            $this->line('<fg=green>SUCCESS |</> The file "PagesController.php" already exists inside the "app/Http/Controllers/" directory!');

            return false;
        }

        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true, true);
        }

        $this->files->put($file, $contents);
        $this->line('<fg=green>SUCCESS |</> The "PagesController.php" file has been copied over to "app/Http/Controllers/" directory!');
    }

    /**
     * @return void
     * @throws FileNotFoundException
     */
    protected function manageUploads()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>MANAGING UPLOADS</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $uploadsDiskStub = __DIR__ . '/../../resources/stubs/config/upload/disks.stub';
        $filesystemsConfig = $this->laravel['path.config'] . '/filesystems.php';
        $uploadsPath = $this->laravel['path.storage'] . '/uploads';
        $gitignoreFile = $uploadsPath . '/.gitignore';

        if ($this->files->exists($filesystemsConfig)) {
            $content = $this->files->get($filesystemsConfig);

            if (strpos($content, "'uploads' => [") === false) {
                $content = str_replace(
                    "'disks' => [",
                    "'disks' => [\n\n" . file_get_contents($uploadsDiskStub)
                    , $content
                );

                $this->files->put($filesystemsConfig, $content);
                $this->line('<fg=green>SUCCESS |</> Setup the "uploads" storage disk inside "config/filesystems.php" => "disks".');
            } else {
                $this->line('<fg=green>SUCCESS |</> The "uploads" storage disk already exists inside "config/filesystems.php" => "disks".');
            }
        } else {
            $this->line('<fg=red>ERROR   |</> The "config/filesystems.php" file does not exist!');
            $this->line('<fg=red>ERROR   |</> You will have to manually add the "uploads" storage disk.');
        }

        if ($this->files->exists($gitignoreFile)) {
            $this->line('<fg=green>SUCCESS |</> The "storage/uploads/" directory already exists.');
            $this->line('<fg=green>SUCCESS |</> The ".gitignore" file inside the "storage/uploads/" directory already exists.');

            return;
        } else {
            $this->files->makeDirectory($uploadsPath);
            $this->line('<fg=green>SUCCESS |</> Created the "storage/uploads/" directory!');

            $this->files->put($gitignoreFile, "*\n!.gitignore\n");
            $this->line('<fg=green>SUCCESS |</> Created the ".gitignore" file inside "storage/uploads/" directory!');

            $this->callSilent('varbox:uploads-link');
            $this->line('<fg=green>SUCCESS |</> The "public/uploads/" directory has been linked!');
        }
    }

    /**
     * @return void
     * @throws FileNotFoundException
     */
    protected function manageBackups()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>MANAGING BACKUPS</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $backupsDiskStub = __DIR__ . '/../../resources/stubs/config/backup/disks.stub';
        $filesystemsConfig = $this->laravel['path.config'] . '/filesystems.php';
        $backupsPath = $this->laravel['path.storage'] . '/backups';
        $gitignoreFile = $backupsPath . '/.gitignore';

        if ($this->files->exists($filesystemsConfig)) {
            $content = $this->files->get($filesystemsConfig);

            if (strpos($content, "'backups' => [") === false) {
                $content = str_replace(
                    "'disks' => [",
                    "'disks' => [\n\n" . file_get_contents($backupsDiskStub)
                    , $content
                );

                $this->files->put($filesystemsConfig, $content);
                $this->line('<fg=green>SUCCESS |</> Setup the "backups" storage disk inside "config/filesystems.php" => "disks".');
            } else {
                $this->line('<fg=green>SUCCESS |</> The "backups" storage disk already exists inside "config/filesystems.php" => "disks".');
            }
        } else {
            $this->line('<fg=red>ERROR   |</> The "config/filesystems.php" file does not exist!');
            $this->line('<fg=red>ERROR   |</> You will have to manually add the "backups" storage disk.');
        }

        if ($this->files->exists($gitignoreFile)) {
            $this->line('<fg=green>SUCCESS |</> The "storage/backups/" directory already exists.');
            $this->line('<fg=green>SUCCESS |</> The ".gitignore" file inside the "storage/backups/" directory already exists.');

            return;
        } else {
            $this->files->makeDirectory($backupsPath);
            $this->line('<fg=green>SUCCESS |</> Created the "storage/backups/" directory!');

            $this->files->put($gitignoreFile, "*\n!.gitignore\n");
            $this->line('<fg=green>SUCCESS |</> Created the ".gitignore" file inside "storage/backups/" directory!');
        }
    }

    /**
     * @return void
     * @throws FileNotFoundException
     */
    protected function manageFroala()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>SETTING UP FROALA</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $froalaDiskStub = __DIR__ . '/../../resources/stubs/config/froala/disks.stub';
        $filesystemsConfig = $this->laravel['path.config'] . '/filesystems.php';
        $froalaPath = $this->laravel['path.storage'] . '/froala';
        $gitignoreFile = $froalaPath . '/.gitignore';

        if ($this->files->exists($filesystemsConfig)) {
            $content = $this->files->get($filesystemsConfig);

            if (strpos($content, "'froala' => [") === false) {
                $content = str_replace(
                    "'disks' => [",
                    "'disks' => [\n\n" . file_get_contents($froalaDiskStub)
                    , $content
                );

                $this->files->put($filesystemsConfig, $content);
                $this->line('<fg=green>SUCCESS |</> Setup the "froala" storage disk inside "config/filesystems.php" => "disks".');
            } else {
                $this->line('<fg=green>SUCCESS |</> The "froala" storage disk already exists inside "config/filesystems.php" => "disks".');
            }
        } else {
            $this->line('<fg=red>ERROR   |</> The "config/filesystems.php" file does not exist!');
            $this->line('<fg=red>ERROR   |</> You will have to manually add the "froala" storage disk.');
        }

        if ($this->files->exists($gitignoreFile)) {
            $this->line('<fg=green>SUCCESS |</> The "storage/froala/" directory already exists.');
            $this->line('<fg=green>SUCCESS |</> The ".gitignore" file inside the "storage/froala/" directory already exists.');

            return;
        } else {
            $this->files->makeDirectory($froalaPath);
            $this->line('<fg=green>SUCCESS |</> Created the "storage/froala/" directory!');

            $this->files->put($gitignoreFile, "*\n!.gitignore\n");
            $this->line('<fg=green>SUCCESS |</> Created the ".gitignore" file inside "storage/froala/" directory!');

            $this->callSilent('varbox:froala-link');
            $this->line('<fg=green>SUCCESS |</> The "public/froala/" directory has been linked!');
        }
    }

    /**
     * @return void
     */
    protected function migrateTables()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>MIGRATING TABLES</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $this->call('migrate');
    }

    /**
     * @return void
     */
    protected function seedDatabase()
    {
        $this->line(PHP_EOL . PHP_EOL);
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');
        $this->line('<fg=yellow>SEEDING DATABASE</>');
        $this->line('<fg=yellow>-------------------------------------------------------------------------------------------------------</>');

        $this->callSilent('db:seed', ['--class' => PermissionsSeeder::class]);
        $this->line('<fg=green>SUCCESS |</> Seeded permissions!');

        $this->callSilent('db:seed', ['--class' => RolesSeeder::class]);
        $this->line('<fg=green>SUCCESS |</> Seeded roles!');

        $this->callSilent('db:seed', ['--class' => UsersSeeder::class]);
        $this->line('<fg=green>SUCCESS |</> Seeded users!');

        $this->callSilent('db:seed', ['--class' => CountriesSeeder::class]);
        $this->line('<fg=green>SUCCESS |</> Seeded countries!');

        $this->callSilent('db:seed', ['--class' => LanguagesSeeder::class]);
        $this->line('<fg=green>SUCCESS |</> Seeded languages!');

        $this->callSilent('db:seed', ['--class' => AnalyticsSeeder::class]);
        $this->line('<fg=green>SUCCESS |</> Seeded Analytics code!');
    }

    /**
     * http://patorjk.com/software/taag/#p=display&f=Small&t=varbox%20base
     *
     * @return void
     */
    protected function brand()
    {
        $this->line(
            "<fg=cyan>               _
 __ ____ _ _ _| |__  _____ __
 \ V / _` | '_| '_ \/ _ \ \ /
  \_/\__,_|_| |_.__/\___/_\_\
</>"
        );
    }
}
