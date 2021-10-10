<?php

declare(strict_types=1);

namespace App\Database;

use App\Exceptions\InvalidUserFileException;
use Illuminate\Support\Arr;

/**
 * An implementation that takes a CSV file as input.
 */
final class CsvUserImportationConverter implements UserImportationConverterInterface
{
    private const EMAIL_MAPPING_JSON_PATH = 'app/mapping/emails.json';
    private const FILTER_MAPPING_JSON_PATH = 'app/mapping/filters.json';
    private const SOURCE_FILE_EMAIL_COLUMN = 'email';

    /**
     * @throws InvalidUserFileException
     * @throws \JsonException
     */
    public function convertToImportableFormat(string $userFilePath): array
    {
        $userFile = fopen($userFilePath, 'rb');

        try {
            if (($headerRow = fgetcsv($userFile, 0, ';')) === false) {
                throw new InvalidUserFileException('Source file is empty.');
            }
            if (!in_array(self::SOURCE_FILE_EMAIL_COLUMN, $headerRow, true)) {
                throw new InvalidUserFileException(sprintf('Source file is missing a "%s" column.', self::SOURCE_FILE_EMAIL_COLUMN));
            }
            $emailToIdMapping = $this->buildEmailToIdMapping();
            $valueToIdMappingByFilter = $this->buildValueToIdMappingByFilter();
            $preparedUsers = [];
            while (($row = fgetcsv($userFile, 0, ';')) !== false) {
                if (count($row) !== count($headerRow)) {
                    throw new InvalidUserFileException(sprintf('All lines from the source file must have %d values.', count($headerRow)));
                }
                $userValues = array_combine($headerRow, $row);
                $preparedUsers[] = $this->convertUser($userValues, $emailToIdMapping, $valueToIdMappingByFilter);
            }

            return $preparedUsers;
        } finally {
            fclose($userFile);
        }
    }

    /**
     * @param array<string, ?string> $userValues
     * @param array<string, string> $emailToIdMapping
     * @param array<string, array<string, string>> $valueToIdMappingByFilter
     * @throws InvalidUserFileException
     */
    private function convertUser(array $userValues, array $emailToIdMapping, array $valueToIdMappingByFilter): ImportableUser
    {
        $email = $userValues[self::SOURCE_FILE_EMAIL_COLUMN];
        if (!isset($emailToIdMapping[$email])) {
            throw new InvalidUserFileException("No ID found for email {$email}.");
        }

        $filterIds = [];
        foreach (Arr::except($userValues, self::SOURCE_FILE_EMAIL_COLUMN) as $filter => $value) {
            if (!isset($valueToIdMappingByFilter[$filter][$value])) {
                throw new InvalidUserFileException("No ID found for value {$value} of filter {$filter}.");
            }
            $filterIds[] = $valueToIdMappingByFilter[$filter][$value];
        }

        return new ImportableUser($emailToIdMapping[$email], array_values($filterIds));
    }

    /**
     * @return array<string, string> E.g. ['john.doe@mail.com' => '123abc456', ...]
     * @throws \JsonException
     */
    private function buildEmailToIdMapping(): array
    {
        $emailMappingJson = file_get_contents(storage_path(self::EMAIL_MAPPING_JSON_PATH));
        $emailMapping = json_decode($emailMappingJson, true, 512, JSON_THROW_ON_ERROR);

        return array_column($emailMapping, '_id', 'email');
    }

    /**
     * @return array<string, array<string, string>> E.g. ['Tranche de salaire' => ['26 - 30' => 'abc123def', ...], ...]
     * @throws \JsonException
     */
    private function buildValueToIdMappingByFilter(): array
    {
        $filterMappingJson = file_get_contents(storage_path(self::FILTER_MAPPING_JSON_PATH));
        $filterMapping = json_decode($filterMappingJson, true, 512, JSON_THROW_ON_ERROR);

        return array_reduce($filterMapping, static function (array $valueMappingsByFilter, array $filter): array {
            $valueMappingsByFilter[$filter['name']['fr']] = array_column($filter['values'], '_id', 'fr');
            return $valueMappingsByFilter;
        }, []);
    }
}
