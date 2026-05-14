<?php

namespace App\Http\Controllers;

use App\Models\PaymentOccurrence;
use App\Models\RecurringItem;
use App\Models\FinancialOperation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentOccurrenceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'operation_type', 'payment_method']);

        $occurrences = PaymentOccurrence::query()
            ->with(['client:id,short_name,legal_name', 'project:id,name', 'service:id,name'])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('operation_type'), fn ($query) => $query->where('operation_type', $request->string('operation_type')))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')))
            ->orderByDesc('due_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PaymentOccurrences/Index', [
            'occurrences' => $occurrences,
            'filters' => $filters,
        ]);
    }

    public function markPaid(Request $request, PaymentOccurrence $paymentOccurrence): RedirectResponse
    {
        if ($paymentOccurrence->payment_method !== RecurringItem::METHOD_CASH) {
            throw ValidationException::withMessages([
                'payment_occurrence' => 'Вручную можно оплачивать только наличные начисления.',
            ]);
        }

        if ($paymentOccurrence->status === PaymentOccurrence::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'payment_occurrence' => 'Отменённое начисление нельзя отметить оплаченным.',
            ]);
        }

        $validated = $request->validate([
            'paid_at' => ['required', 'date'],
        ]);

        if ($paymentOccurrence->status !== PaymentOccurrence::STATUS_PAID) {
            $paymentOccurrence->markPaid($validated['paid_at']);
        }

        return back()->with('success', 'Начисление отмечено оплаченным.');
    }

    public function correct(Request $request, PaymentOccurrence $paymentOccurrence): RedirectResponse
    {
        if ($paymentOccurrence->status !== PaymentOccurrence::STATUS_PAID) {
            throw ValidationException::withMessages([
                'payment_occurrence' => 'Корректировки доступны только для оплаченных начислений.',
            ]);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(FinancialOperation::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'paid_at' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $paymentOccurrence->createCorrection(
            $validated['type'],
            $validated['amount'],
            $validated['paid_at'],
            $validated['comment'] ?? null,
        );

        return back()->with('success', 'Корректировка создана.');
    }
}
