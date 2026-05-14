<?php

use App\Http\Controllers\ActController;
use App\Http\Controllers\AuditLogController;
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
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::post('clients/lookup-company', CompanyLookupController::class)->name('clients.lookup-company');

    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::get('services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');

    Route::get('payees', [PayeeController::class, 'index'])->name('payees.index');
    Route::get('payees/create', [PayeeController::class, 'create'])->name('payees.create');
    Route::get('payees/{payee}/edit', [PayeeController::class, 'edit'])->name('payees.edit');

    Route::get('recurring-items', [RecurringItemController::class, 'index'])->name('recurring-items.index');
    Route::get('recurring-items/create', [RecurringItemController::class, 'create'])->name('recurring-items.create');
    Route::get('recurring-items/{recurringItem}/edit', [RecurringItemController::class, 'edit'])->name('recurring-items.edit');

    Route::get('payment-occurrences', [PaymentOccurrenceController::class, 'index'])->name('payment.occurrences.index');
    Route::get('financial-operations', [FinancialOperationController::class, 'index'])->name('financial.operations.index');
    Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::get('payroll', [PayrollPayoutController::class, 'index'])->name('payroll.index');
    Route::get('payroll/create', [PayrollPayoutController::class, 'create'])->name('payroll.create');
    Route::get('payroll/{payroll}/edit', [PayrollPayoutController::class, 'edit'])->name('payroll.edit');
    Route::get('pf', [PfController::class, 'index'])->name('pf.index');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('acts', [ActController::class, 'index'])->name('acts.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.log.index');

    Route::middleware('role:'.User::ROLE_OWNER.','.User::ROLE_FINANCE_MANAGER)->group(function () {
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::patch('clients/{client}', [ClientController::class, 'update']);
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

        Route::post('services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::patch('services/{service}', [ServiceController::class, 'update']);
        Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::patch('projects/{project}', [ProjectController::class, 'update']);
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

        Route::post('payees', [PayeeController::class, 'store'])->name('payees.store');
        Route::put('payees/{payee}', [PayeeController::class, 'update'])->name('payees.update');
        Route::patch('payees/{payee}', [PayeeController::class, 'update']);
        Route::delete('payees/{payee}', [PayeeController::class, 'destroy'])->name('payees.destroy');

        Route::post('recurring-items', [RecurringItemController::class, 'store'])->name('recurring-items.store');
        Route::put('recurring-items/{recurringItem}', [RecurringItemController::class, 'update'])->name('recurring-items.update');
        Route::patch('recurring-items/{recurringItem}', [RecurringItemController::class, 'update']);
        Route::delete('recurring-items/{recurringItem}', [RecurringItemController::class, 'destroy'])->name('recurring-items.destroy');

        Route::patch('payment-occurrences/{paymentOccurrence}/mark-paid', [PaymentOccurrenceController::class, 'markPaid'])->name('payment.occurrences.mark-paid');
        Route::post('payment-occurrences/{paymentOccurrence}/corrections', [PaymentOccurrenceController::class, 'correct'])->name('payment.occurrences.corrections.store');
        Route::post('payouts', [PayoutController::class, 'store'])->name('payouts.store');
        Route::patch('payouts/{payoutBatch}/mark-paid', [PayoutController::class, 'markPaid'])->name('payouts.mark-paid');
        Route::post('payroll', [PayrollPayoutController::class, 'store'])->name('payroll.store');
        Route::put('payroll/{payroll}', [PayrollPayoutController::class, 'update'])->name('payroll.update');
        Route::patch('payroll/{payroll}', [PayrollPayoutController::class, 'update']);
        Route::delete('payroll/{payroll}', [PayrollPayoutController::class, 'destroy'])->name('payroll.destroy');
        Route::post('pf', [PfController::class, 'store'])->name('pf.store');
        Route::patch('pf/{pfPayoutBatch}/mark-paid', [PfController::class, 'markPaid'])->name('pf.mark-paid');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])->name('invoices.email.store');
        Route::post('invoices/{invoice}/tochka', [InvoiceController::class, 'sendTochka'])->name('invoices.tochka.store');
        Route::post('acts', [ActController::class, 'store'])->name('acts.store');
    });

    Route::middleware('role:'.User::ROLE_OWNER)->group(function () {
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/webhooks/tochka', TochkaWebhookController::class)->name('webhooks.tochka');

require __DIR__.'/auth.php';
