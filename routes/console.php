<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('employees:sync-designations {--dry-run : List the changes without saving them}', function () {
    $changed = 0;

    foreach (\App\Models\Employee::orderBy('EmpPersonalCode')->get() as $employee) {
        $newId = $employee->resolveDesignationId();

        if ((string) $employee->Designation_id === (string) $newId) {
            continue;
        }

        $this->line(sprintf('%s  %s  Designation_id: %s -> %s',
            $employee->EmpPersonalCode, $employee->Designation,
            $employee->Designation_id ?? 'NULL', $newId ?? 'NULL (no match in DesignationTypeMaster)'));

        if (! $this->option('dry-run')) {
            $employee->forceFill(['Designation_id' => $newId])->saveQuietly();
        }
        $changed++;
    }

    $this->info(($this->option('dry-run') ? 'Would update ' : 'Updated ') . $changed . ' employee(s).');
})->purpose('Set each employee\'s Designation_id from their Designation name');
