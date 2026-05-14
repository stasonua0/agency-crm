<?php

use App\Http\Controllers\ActController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyLookupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialOperationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PayeeController;
use App\Http\Controllers\PaymentOccurrenceController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\PayrollPayoutController;
use App\Http\Controllers\PfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RecurringItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TochkaWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('clients', ClientController::class)->except('show');
    Route::post('clients/lookup-company', CompanyLookupController::class)->name('clients.lookup-company');
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
    Route::resource('payroll', PayrollPayoutController::class)->parameters(['payroll' => 'payroll'])->except('show');
    Route::get('pf', [PfController::class, 'index'])->name('pf.index');
    Route::post('pf', [PfController::class, 'store'])->name('pf.store');
    Route::patch('pf/{pfPayoutBatch}/mark-paid', [PfController::class, 'markPaid'])->name('pf.mark-paid');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email.store');
    Route::post('invoices/{invoice}/tochka', [InvoiceController::class, 'sendTochka'])->name('invoices.tochka.store');
    Route::get('acts', [ActController::class, 'index'])->name('acts.index');
    Route::post('acts', [ActController::class, 'store'])->name('acts.store');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/audit-log', fn () => Inertia::render('SectionPlaceholder', [
        'title' => 'Журнал аудита',
    ]))->name('audit.log.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/webhooks/tochka', TochkaWebhookController::class)->name('webhooks.tochka');

require __DIR__.'/auth.php';
