<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    protected function paymentTypes($shop)
    {
        $fromOrders = Order::where('user_id', $shop->id)
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method');

        $defaults = collect(['Cash', 'Bank Transfer']);

        return $fromOrders->merge($defaults)->unique()->sort()->values();
    }

    public function index(Request $request)
    {
        $shop = Auth::user();

        $query = Expense::with('category')->where('user_id', $shop->id);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('expense_category_id', $request->category_id);
        }

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest('expense_date');
                break;
            case 'amount_high':
                $query->orderByDesc('amount');
                break;
            case 'amount_low':
                $query->orderBy('amount');
                break;
            default:
                $query->latest('expense_date');
        }

        $expenses = $query->paginate(20)->withQueryString();
        $categories = ExpenseCategory::where('user_id', $shop->id)->orderBy('name')->get();
        $totalAmount = Expense::where('user_id', $shop->id)->sum('amount');

        return view('expenses.index', compact('expenses', 'categories', 'totalAmount'));
    }

    public function create()
    {
        $shop = Auth::user();
        $categories = ExpenseCategory::where('user_id', $shop->id)->orderBy('name')->get();
        $paymentTypes = $this->paymentTypes($shop);

        return view('expenses.create', compact('categories', 'paymentTypes'));
    }

    public function store(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Expense::create([
            'user_id' => $shop->id,
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'payment_type' => $validated['payment_type'] ?? null,
            'expense_date' => $validated['expense_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense added!');
    }

    public function edit(Expense $expense)
    {
        $shop = Auth::user();
        if ($expense->user_id !== $shop->id) {
            abort(403);
        }

        $categories = ExpenseCategory::where('user_id', $shop->id)->orderBy('name')->get();
        $paymentTypes = $this->paymentTypes($shop);

        return view('expenses.edit', compact('expense', 'categories', 'paymentTypes'));
    }

    public function update(Request $request, Expense $expense)
    {
        $shop = Auth::user();
        if ($expense->user_id !== $shop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $expense->update([
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'payment_type' => $validated['payment_type'] ?? null,
            'expense_date' => $validated['expense_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense updated!');
    }

    public function destroy(Expense $expense)
    {
        $shop = Auth::user();
        if ($expense->user_id !== $shop->id) {
            abort(403);
        }

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}
