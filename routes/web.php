<?php

use App\Http\Controllers\SpreadsheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SpreadsheetController::class, 'index'])->name('home');

Route::get('/spreadsheets', [SpreadsheetController::class, 'index'])->name('spreadsheets.index');
Route::post('/spreadsheets', [SpreadsheetController::class, 'store'])->name('spreadsheets.store');
Route::get('/spreadsheets/{spreadsheet}', [SpreadsheetController::class, 'show'])->name('spreadsheets.show');
Route::delete('/spreadsheets/{spreadsheet}', [SpreadsheetController::class, 'destroy'])->name('spreadsheets.destroy');
Route::get('/spreadsheets/{spreadsheet}/export', [SpreadsheetController::class, 'export'])->name('spreadsheets.export');
Route::patch('/spreadsheets/{spreadsheet}/rows/{row}', [SpreadsheetController::class, 'updateRow'])->name('spreadsheets.rows.update');
Route::delete('/spreadsheets/{spreadsheet}/rows/{row}', [SpreadsheetController::class, 'destroyRow'])->name('spreadsheets.rows.destroy');
