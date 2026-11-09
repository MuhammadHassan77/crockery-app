<?php

namespace Tests\Feature;

use App\Http\Controllers\BackupController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    public function test_destroy_deletes_existing_backup_and_redirects_with_success_message(): void
    {
        $filename = 'test-backup.sql';
        $path = storage_path('app/backups/' . $filename);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'dummy backup');

        $controller = new BackupController();
        $response = $controller->destroy($filename);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertFalse(File::exists($path));
        $this->assertSame('Backup deleted successfully.', $response->getSession()->get('success'));
    }
}
