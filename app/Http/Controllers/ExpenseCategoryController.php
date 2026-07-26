<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $shop = Auth::user();
        $categories = ExpenseCategory::where('user_id', $shop->id)
            ->withCount('expenses')
            ->orderBy('name')
            ->get();

        return view('expense_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $shop = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        ExpenseCategory::create([
            'user_id' => $shop->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#008060',
        ]);

        return redirect()->route('expense-categories.index')->with('success', 'Category added!');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $shop = Auth::user();
        if ($expenseCategory->user_id !== $shop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $expenseCategory->update([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? $expenseCategory->color,
        ]);

        return redirect()->route('expense-categories.index')->with('success', 'Category updated!');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $shop = Auth::user();
        if ($expenseCategory->user_id !== $shop->id) {
            abort(403);
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')->with('success', 'Category deleted.');
    }
}
