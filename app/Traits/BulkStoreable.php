<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait BulkStoreable
{
    /**
     * Generic bulk store
     * 
     * @param  Request   $request
     * @param  string    $modelClass
     * @param  array     $rules
     * @param  callable  $beforeCreate  fn($data) => array
     * @param  callable  $uniqueCheck   fn($data) => bool (true = duplicate)
     * @param  string    $redirectRoute
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function bulkStoreGeneric(
        Request $request,
        string $modelClass,
        array $rules,
        ?callable $beforeCreate = null,
        ?callable $uniqueCheck = null,
        ?string $redirectRoute = null
    ) {
        // Dynamic validation rules
        $validationRules = [
            'items' => 'required|array|min:1',
        ];
        foreach ($rules as $field => $rule) {
            $validationRules["items.*.{$field}"] = $rule;
        }

        $validated = $request->validate($validationRules);

        DB::beginTransaction();
        try {
            $count = 0;
            $skipped = 0;
            $errors = [];

            foreach ($validated['items'] as $idx => $item) {
                $rowNumber = $idx + 1;

                // Custom transform
                $data = $item;
                if ($beforeCreate) {
                    try {
                        $data = $beforeCreate($data, $request);
                    } catch (\Exception $e) {
                        $skipped++;
                        $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                        continue;
                    }
                }

                // Unique check
                if ($uniqueCheck) {
                    try {
                        if ($uniqueCheck($data, $request)) {
                            $skipped++;
                            $errors[] = "Baris {$rowNumber}: Duplikat data.";
                            continue;
                        }
                    } catch (\Exception $e) {
                        $skipped++;
                        $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                        continue;
                    }
                }

                try {
                    $modelClass::create($data);
                    $count++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "✅ Berhasil tambah {$count} data.";
            if ($skipped > 0) {
                $message .= " ⚠️ {$skipped} baris di-skip.";
            }

            return redirect()
                ->route($redirectRoute ?? $this->bulkRedirectRoute ?? 'dashboard')
                ->with('success', $message)
                ->with('bulk_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal bulk store: ' . $e->getMessage());
        }
    }

    /**
     * Parse bulk text (dari copy-paste Excel)
     * 
     * @param  string $text
     * @param  array  $columns  Daftar kolom: ['nama', 'capacity', 'notes']
     * @return array
     */
    protected function parseBulkText(string $text, array $columns): array
    {
        if (empty(trim($text))) {
            return [];
        }

        $lines = array_values(array_filter(
            array_map(fn($l) => trim(str_replace("\r", '', $l)), explode("\n", $text)),
            fn($l) => $l !== ''
        ));

        if (empty($lines)) {
            return [];
        }

        $separator = $this->detectSeparator($lines[0]);

        $result = [];
        foreach ($lines as $line) {
            $parts = array_map('trim', explode($separator, $line));

            $row = [];
            foreach ($columns as $i => $col) {
                $row[$col] = $parts[$i] ?? null;

                // Convert empty string to null
                if ($row[$col] === '') {
                    $row[$col] = null;
                }
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Auto-detect separator
     * Priority: Tab (Excel) > Pipe > Semicolon > Comma
     */
    protected function detectSeparator(string $line): string
    {
        if (str_contains($line, "\t")) return "\t";
        if (str_contains($line, '|')) return '|';
        if (str_contains($line, ';')) return ';';
        if (str_contains($line, ',')) return ',';
        return "\t"; // default
    }
}