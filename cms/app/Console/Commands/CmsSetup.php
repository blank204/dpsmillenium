<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;

class CmsSetup extends Command
{
    protected $signature = 'cms:setup {--editor-password=} {--super-password=} {--force}';

    protected $description = 'Create the shared Editor login and the Super admin login';

    public function handle(): int
    {
        if (Account::count() > 0 && ! $this->option('force')) {
            $this->error('Accounts already exist. Use --force to reset both passwords.');
            return self::FAILURE;
        }

        $editor = $this->option('editor-password') ?? $this->secret('Password for the shared Editor login (min 12 characters)');
        $super = $this->option('super-password') ?? $this->secret('Password for the Super admin login (min 12 characters)');

        foreach (['Editor' => $editor, 'Super admin' => $super] as $label => $pw) {
            if (! is_string($pw) || mb_strlen($pw) < 12) {
                $this->error("{$label} password must be at least 12 characters.");
                return self::FAILURE;
            }
        }

        if ($editor === $super) {
            $this->error('Editor and Super admin passwords must be different.');
            return self::FAILURE;
        }

        foreach ([['editor', Account::EDITOR, $editor], ['superadmin', Account::SUPER_ADMIN, $super]] as [$username, $role, $pw]) {
            $account = Account::firstOrNew(['username' => $username]);
            $account->role = $role;
            $account->password = $pw;
            $account->password_changed_at = now();
            $account->save();
            if ($account->wasRecentlyCreated === false) {
                $account->bumpSessionVersion();
            }
        }

        $this->info('Accounts ready: username "editor" and username "superadmin".');
        return self::SUCCESS;
    }
}
