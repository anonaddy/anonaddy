<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AliasesExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return user()->aliases()->withTrashed()->get();
    }

    public function headings(): array
    {
        if (user()->aliases->first()) {
            return array_keys(
                user()->aliases->first()->toArray()
            );
        }

        return [];
    }
}
