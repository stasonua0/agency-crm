<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    $sections = [
        'clients' => 'Clients',
        'projects' => 'Projects',
        'services' => 'Services',
        'recurring-items' => 'Recurring operations',
        'payment-occurrences' => 'Payment accruals',
        'financial-operations' => 'Financial operations',
        'invoices' => 'Invoices',
        'acts' => 'Acts',
        'payees' => 'Payees',
        'payouts' => 'Payouts',
        'payroll' => 'Payroll',
        'pf' => 'PF',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'audit-log' => 'Audit Log',
    ];

    foreach ($sections as $slug => $title) {
        Route::get("/{$slug}", fn () => Inertia::render('SectionPlaceholder', [
            'title' => $title,
        ]))->name(str_replace('-', '.', $slug).'.index');
    }

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
