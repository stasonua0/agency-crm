<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayrollPayoutRequest;
use App\Http\Requests\UpdatePayrollPayoutRequest;
use App\Models\Payee;
use App\Models\PayrollPayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPayoutController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'type']);

        $payouts = PayrollPayout::query()
            ->with('employee:id,name')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->orderByDesc('payout_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Payroll/Index', [
            'payouts' => $payouts,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Payroll/Create', $this->formOptions());
    }

    public function store(StorePayrollPayoutRequest $request): RedirectResponse
    {
        $payout = PayrollPayout::create($this->payload($request->validated()));

        if ($payout->status === PayrollPayout::STATUS_PAID) {
            $payout->markPaid();
        }

        return redirect()->route('payroll.index')->with('success', 'Зарплатная выплата создана.');
    }

    public function edit(PayrollPayout $payroll): Response
    {
        return Inertia::render('Payroll/Edit', [
            ...$this->formOptions(),
            'payout' => $payroll,
        ]);
    }

    public function update(UpdatePayrollPayoutRequest $request, PayrollPayout $payroll): RedirectResponse
    {
        if ($payroll->status === PayrollPayout::STATUS_PAID) {
            throw ValidationException::withMessages([
                'payroll' => 'Оплаченную зарплатную выплату нельзя менять.',
            ]);
        }

        $payroll->update($this->payload($request->validated()));

        if ($payroll->status === PayrollPayout::STATUS_PAID) {
            $payroll->markPaid();
        }

        return redirect()->route('payroll.index')->with('success', 'Зарплатная выплата обновлена.');
    }

    public function destroy(PayrollPayout $payroll): RedirectResponse
    {
        if ($payroll->status === PayrollPayout::STATUS_PAID) {
            throw ValidationException::withMessages([
                'payroll' => 'Оплаченную зарплатную выплату нельзя удалить.',
            ]);
        }

        $payroll->delete();

        return redirect()->route('payroll.index')->with('success', 'Зарплатная выплата удалена.');
    }

    private function formOptions(): array
    {
        return [
            'employees' => Payee::query()
                ->where('type', Payee::TYPE_EMPLOYEE)
                ->where('status', Payee::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name', 'requisites']),
        ];
    }

    private function payload(array $validated): array
    {
        $employee = Payee::query()->findOrFail($validated['employee_id']);

        return [
            ...$validated,
            'employee_name_snapshot' => $employee->name,
            'requisites_snapshot' => $employee->requisites,
        ];
    }
}
