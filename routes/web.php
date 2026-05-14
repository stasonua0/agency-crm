<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\FinancialOperationController;
use App\Http\Controllers\PayeeController;
use App\Http\Controllers\PaymentOccurrenceController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RecurringItemController;
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
    Route::resource('payees', PayeeController::class)->except('show');
    Route::resource('recurring-items', RecurringItemController::class)->except('show');
    Route::get('payment-occurrences', [PaymentOccurrenceController::class, 'index'])->name('payment.occurrences.index');
    Route::patch('payment-occurrences/{paymentOccurrence}/mark-paid', [PaymentOccurrenceController::class, 'markPaid'])->name('payment.occurrences.mark-paid');
    Route::post('payment-occurrences/{paymentOccurrence}/corrections', [PaymentOccurrenceController::class, 'correct'])->name('payment.occurrences.corrections.store');
    Route::get('financial-operations', [FinancialOperationController::class, 'index'])->name('financial.operations.index');
    Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::post('payouts', [PayoutController::class, 'store'])->name('payouts.store');
    Route::patch('payouts/{payoutBatch}/mark-paid', [PayoutController::class, 'markPaid'])->name('payouts.mark-paid');

    $sections = [
        'invoices' => 'Счета',
        'acts' => 'Акты',
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
