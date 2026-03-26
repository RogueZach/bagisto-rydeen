<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OrderApprovalController extends Controller
{
    /**
     * List all orders with optional status and search filters.
     */
    public function index(Request $request)
    {
        $query = DB::table('orders')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'orders.id',
                'orders.increment_id',
                'orders.status',
                'orders.grand_total',
                'orders.total_qty_ordered',
                'orders.created_at',
                'customers.first_name',
                'customers.last_name',
                'customers.email'
            )
            ->orderByDesc('orders.created_at');

        if ($status = $request->get('status')) {
            $query->where('orders.status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('orders.increment_id', 'like', "%{$search}%")
                  ->orWhere('customers.first_name', 'like', "%{$search}%")
                  ->orWhere('customers.last_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        return view('rydeen-dealer::admin.orders.index', compact('orders'));
    }

    /**
     * Show a single order with items.
     */
    public function view(int $id)
    {
        $order = DB::table('orders')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select(
                'orders.id',
                'orders.increment_id',
                'orders.status',
                'orders.grand_total',
                'orders.sub_total',
                'orders.shipping_amount',
                'orders.tax_amount',
                'orders.discount_amount',
                'orders.total_qty_ordered',
                'orders.created_at',
                'orders.customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.email',
                'customers.phone',
                'orders.dealer_contact_id'
            )
            ->where('orders.id', $id)
            ->first();

        if (! $order) {
            abort(404);
        }

        $items = DB::table('order_items')
            ->where('order_id', $id)
            ->select('id', 'name', 'sku', 'qty_ordered', 'price', 'total', 'type')
            ->get();

        $contact = null;
        if ($order->dealer_contact_id) {
            $contact = DB::table('rydeen_dealer_contacts')->where('id', $order->dealer_contact_id)->first();
        }

        return view('rydeen-dealer::admin.orders.view', compact('order', 'items', 'contact'));
    }

    /**
     * Approve an order — set status to processing.
     */
    public function approve(int $id)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'processing',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order has been approved and set to processing.');
    }

    /**
     * Hold an order — set status to pending.
     */
    public function hold(int $id)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'pending',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order has been placed on hold (pending).');
    }

    /**
     * Cancel an order.
     */
    public function cancel(int $id)
    {
        DB::table('orders')->where('id', $id)->update([
            'status'     => 'canceled',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order has been canceled.');
    }
}
