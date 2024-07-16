<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportService;

class ImportController extends Controller
{
    private $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function importBlogEntries()
    {
        // Call the importBlogEntries method of the ImportService
        $this->importService->importBlogEntries();
    }
    public function importJSONEntries()
    {
        // Call the importJSONEntries method of the ImportService
        $this->importService->importJSONEntries();
    }
}
