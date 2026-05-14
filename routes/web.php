<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('clients', ClientController::class)->except('show');
    Route::resource('services', ServiceController::class)->except('show');
    Route::resource('projects', ProjectController::class)->except('show');

    $sections = [
        'recurring-items' => 'Регулярные операции',
        'payment-occurrences' => 'Начисления',
        'financial-operations' => 'Финансовые операции',
        'invoices' => 'Счета',
        'acts' => 'Акты',
        'payees' => 'Получатели выплат',
        'payouts' => 'Выплаты',
        'payroll' => 'Зарплаты',
        'pf' => 'ПФ',
        'reports' => 'Отчёты',
        'settings' => 'Настройки',
        'audit-log' => 'Журнал аудита',
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
