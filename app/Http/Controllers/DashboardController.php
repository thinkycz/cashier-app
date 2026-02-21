<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Customer;

class DashboardController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)->get();
        $openTransactions = Transaction::where('status', 'open')->with('customer')->get();
        $customers = Customer::all();

        return Inertia::render('Dashboard', [
            'products' => $products,
            'openTransactions' => $openTransactions,
            'customers' => $customers,
        ]);
    }
}
