<?php

namespace App\Http\Controllers;

use App\Services\ImportService;

class ImportController extends Controller
{
    private $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function importMonthlyEntries()
    {
        // Call the importBlogEntries method of the ImportService
        $this->importService->importMonthlyEntries();
    }

    public function importJSONEntries()
    {
        // Call the importJSONEntries method of the ImportService
        $this->importService->importJSONEntries();
    }

    public function importTitleEntries()
    {
        // Call the importJSONEntries method of the ImportService
        $this->importService->importTitleEntries();
    }
}
