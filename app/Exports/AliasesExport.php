<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AliasesExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
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
