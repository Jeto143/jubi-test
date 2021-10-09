<?php

declare(strict_types=1);

namespace App\Database;

use App\Exceptions\InvalidUserFileException;

interface UserImportationConverterInterface
{
    /**
     * Processes a file containing user data and produces a list of user IDs and their associated filter (attribute) IDs.
     *
     * @return ImportableUser[]
     * @throws InvalidUserFileException
     */
    public function convertToImportableFormat(string $userFilePath): array;
}
