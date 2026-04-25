<?php

namespace App\Http\Controllers\Api;

use App\Exports\CatalogExport;
use App\Exports\ProductsExport;
use App\Exports\StockInitialExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Requests\SpreadsheetExportRequest;
use App\Http\Requests\SpreadsheetImportRequest;
use App\Imports\CatalogImport;
use App\Imports\ProductsImport;
use App\Imports\StockInitialImport;
use App\Services\Inventory\StockService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExportController extends Controller
{
    use ApiResponse;

    protected array $catalogs = [
        'categories',
        'brands',
        'units',
        'warehouses',
        'suppliers',
        'customers',
    ];

    public function exportProducts(SpreadsheetExportRequest $request): BinaryFileResponse
    {
        return $this->download(
            new ProductsExport(),
            'products',
            $request->validated('format', 'xlsx')
        );
    }

    public function exportCatalog(SpreadsheetExportRequest $request, string $catalog): BinaryFileResponse
    {
        $this->assertCatalog($catalog);

        return $this->download(
            new CatalogExport($catalog),
            $catalog,
            $request->validated('format', 'xlsx')
        );
    }

    public function exportStockInitial(SpreadsheetExportRequest $request): BinaryFileResponse
    {
        return $this->download(
            new StockInitialExport(),
            'stock-initial',
            $request->validated('format', 'xlsx')
        );
    }

    public function importProducts(SpreadsheetImportRequest $request): JsonResponse
    {
        $import = new ProductsImport();
        ExcelFacade::import($import, $request->file('file'));

        return $this->success(
            $import->result(),
            $this->importMessage($import->result(), 'Productos importados correctamente.')
        );
    }

    public function importCatalog(SpreadsheetImportRequest $request, string $catalog): JsonResponse
    {
        $this->assertCatalog($catalog);

        $import = new CatalogImport($catalog);
        ExcelFacade::import($import, $request->file('file'));

        return $this->success(
            $import->result(),
            $this->importMessage($import->result(), 'Catálogo importado correctamente.')
        );
    }

    public function importStockInitial(SpreadsheetImportRequest $request, StockService $stockService): JsonResponse
    {
        $import = new StockInitialImport($stockService, $request->user());
        ExcelFacade::import($import, $request->file('file'));

        return $this->success(
            $import->result(),
            $this->importMessage($import->result(), 'Stock inicial importado correctamente.')
        );
    }

    protected function download(object $export, string $baseName, string $format): BinaryFileResponse
    {
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = sprintf('%s-%s.%s', $baseName, now()->format('Ymd_His'), $extension);

        return ExcelFacade::download($export, $filename, $writerType);
    }

    protected function assertCatalog(string $catalog): void
    {
        if (! in_array($catalog, $this->catalogs, true)) {
            abort(404, 'Catálogo inválido.');
        }
    }

    protected function importMessage(array $result, string $successMessage): string
    {
        return empty($result['errors'])
            ? $successMessage
            : $successMessage.' Se procesaron algunos registros con observaciones.';
    }
}
