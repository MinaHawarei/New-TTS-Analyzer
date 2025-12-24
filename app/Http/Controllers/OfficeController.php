<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OfficeController extends Controller
{
    public function index()
    {
        return view('office');
    }

   public function mergeAndDownload(Request $request): BinaryFileResponse
{
    $request->validate([
        'files.*' => 'required|file|mimes:xlsx,xls'
    ]);

    $mergedData = collect();
    $isFirstFile = true;

    foreach ($request->file('files') as $file) {
        $importer = new class implements \Maatwebsite\Excel\Concerns\ToCollection {
            public \Illuminate\Support\Collection $rows;

            public function collection(\Illuminate\Support\Collection $collection)
            {
                $this->rows = $collection;
            }
        };

        \Maatwebsite\Excel\Facades\Excel::import($importer, $file);

        $rows = $importer->rows;

        if (! $isFirstFile) {
            $rows = $rows->slice(1);
        }

        $mergedData = $mergedData->merge($rows);
        $isFirstFile = false;
    }

    $mergedArray = $mergedData->toArray();

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\ArrayExport($mergedArray),
        'merged_file.xlsx',
        \Maatwebsite\Excel\Excel::XLSX
    );
}

}
