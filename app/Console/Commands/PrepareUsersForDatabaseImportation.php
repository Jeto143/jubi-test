<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Database\UserImportationConverterInterface;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;

final class PrepareUsersForDatabaseImportation extends Command
{
    /** @var string */
    protected $signature = 'users:db-import-prepare {source} {target}';

    /** @var string */
    protected $description = 'Prepare users for database importation';

    /** @var string */
    protected $help = "Example usage:\nphp artisan users:db-import-prepare /path/to/users.csv /path/to/prepared_users.json";

    public function handle(UserImportationConverterInterface $userImportationConverter, LoggerInterface $logger): int
    {
        $sourcePath = $this->argument('source');
        $targetPath = $this->argument('target');

        if (!is_string($sourcePath) || !is_string($targetPath)) {
            $this->error('Invalid source and/or target file path(s).');
            return 1;
        }

        if (!file_exists($sourcePath)) {
            $this->error('Source file does not exist.');
            return 1;
        }

        if (file_exists($targetPath)
            && $this->ask('Target file already exists. Do you want to overwrite it? (yes/no) [no]') !== 'yes') {
            return 1;
        }

        try {
            $this->line('Converting users to importable format...');
            $importableUsers = $userImportationConverter->convertToImportableFormat($sourcePath);

            $this->line('Saving importable file...');
            $importableUsersJson = json_encode($importableUsers, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            file_put_contents($targetPath, $importableUsersJson);
        } catch (\Exception $e) {
            $logger->error($e->__toString());
            $this->error($e->getMessage());
            return 1;
        }

        $this->info('Operation was successful.');
        return 0;
    }
}
