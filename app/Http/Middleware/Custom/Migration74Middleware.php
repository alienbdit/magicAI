<?php

namespace App\Http\Middleware\Custom;

use App\Helpers\Classes\InstallationHelper;
use App\Helpers\Classes\TableSchema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class Migration74Middleware
{
    public function __construct(
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $updatedFileExist = Cache::rememberForever('updated_file_check', static function () {
            return file_exists(base_path('updated'));
        });
        if (! $updatedFileExist) {
            Artisan::call('migrate', ['--force' => true]);

            InstallationHelper::runInstallation();
            file_put_contents(base_path('updated'), 'updated');
        }

        return $next($request);
    }
}
