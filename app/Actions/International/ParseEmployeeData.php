<?php

namespace App\Actions\International;

/**
 * Parses the "code:dress,code:dress" string built by the add/edit form JavaScript.
 */
class ParseEmployeeData
{
    /**
     * @return array{entries: array<int, array{code:string,dress:bool}>, errors: array<int,string>}
     */
    public function __invoke(string $data, string $invalidPrefix = 'Invalid employee data format: '): array
    {
        $entries = [];
        $errors = [];

        foreach (explode(',', $data) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            $parts = explode(':', $entry);
            if (count($parts) !== 2) {
                $errors[] = $invalidPrefix.$entry;

                continue;
            }

            $entries[] = ['code' => trim($parts[0]), 'dress' => (int) $parts[1] === 1];
        }

        return ['entries' => $entries, 'errors' => $errors];
    }
}
