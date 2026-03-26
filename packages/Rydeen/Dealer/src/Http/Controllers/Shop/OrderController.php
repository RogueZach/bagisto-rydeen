<?php

namespace Rydeen\Dealer\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Checkout\Facades\Cart;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Show order history.
     */
    public function index(Request $request)
    {
        $customer = auth('customer')->user();

        $query = $this->orderRepository->scopeQuery(function ($q) use ($customer, $request) {
            $q->where('customer_id', $customer->id);

            if ($request->search) {
                $q->where(function ($sq) use ($request) {
                    $sq->where('id', 'like', "%{$request->search}%")
                       ->orWhere('increment_id', 'like', "%{$request->search}%");
                });
            }

            return $q->orderByDesc('created_at');
        });

        $orders = $query->paginate(15);

        return view('rydeen-dealer::shop.orders.index', compact('orders'));
    }

    /**
     * Show order detail.
     */
    public function view(int $id)
    {
        $customer = auth('customer')->user();
        $order = $this->orderRepository->findOneWhere([
            'id'          => $id,
            'customer_id' => $customer->id,
        ]);

        if (! $order) {
            abort(404);
        }

        return view('rydeen-dealer::shop.orders.view', compact('order'));
    }

    /**
     * Print-friendly order view.
     */
    public function print(int $id)
    {
        $customer = auth('customer')->user();
        $order = $this->orderRepository->findOneWhere([
            'id'          => $id,
            'customer_id' => $customer->id,
        ]);

        if (! $order) {
            abort(404);
        }

        return view('rydeen-dealer::shop.orders.print', compact('order'));
    }

    /**
     * Reorder — copy items from a previous order to the cart.
     */
    public function reorder(int $id)
    {
        $customer = auth('customer')->user();
        $order = $this->orderRepository->findOneWhere([
            'id'          => $id,
            'customer_id' => $customer->id,
        ]);

        if (! $order) {
            abort(404);
        }

        $addedCount = 0;

        foreach ($order->items as $item) {
            try {
                $product = $this->productRepository->find($item->product_id);
                if ($product) {
                    Cart::addProduct($product, [
                        'product_id' => $product->id,
                        'quantity'   => $item->qty_ordered,
                    ]);
                    $addedCount++;
                }
            } catch (\Exception $e) {
                // Skip items that can't be added
                continue;
            }
        }

        if ($addedCount > 0) {
            return redirect()->route('dealer.order-review')
                ->with('success', trans('rydeen-dealer::app.shop.orders.reorder-success', ['count' => $addedCount]));
        }

        return redirect()->back()
            ->with('error', trans('rydeen-dealer::app.shop.orders.reorder-failed'));
    }

    /**
     * Show order review (cart contents before placing).
     */
    public function review()
    {
        $cart = Cart::getCart();

        return view('rydeen-dealer::shop.order-review.index', compact('cart'));
    }

    /**
     * Place the order.
     */
    public function placeOrder(Request $request)
    {
        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('dealer.catalog')
                ->with('error', trans('rydeen-dealer::app.shop.orders.cart-empty'));
        }

        $customer = auth('customer')->user();

        // Ensure billing address exists on the cart
        if (! $cart->billing_address) {
            $this->ensureCartAddresses($cart, $customer);
        }

        // Save shipping method
        Cart::saveShippingMethod('dealer_shipping_dealer_shipping');

        // Save payment method
        Cart::savePaymentMethod(['method' => 'dealer_order']);

        // Collect totals
        Cart::collectTotals();

        // Refresh cart
        $cart = Cart::getCart();

        // Create order from cart using Bagisto's OrderResource transformer
        $data = (new OrderResource($cart))->jsonSerialize();

        // Optionally add dealer notes
        if ($request->notes) {
            $data['notes'] = $request->notes;
        }

        $order = $this->orderRepository->create($data);

        // Deactivate the cart
        Cart::deActivateCart();

        return redirect()->route('dealer.order-confirmation', $order->id);
    }

    /**
     * Show order confirmation page.
     */
    public function confirmation(int $id)
    {
        $customer = auth('customer')->user();
        $order = $this->orderRepository->findOneWhere([
            'id'          => $id,
            'customer_id' => $customer->id,
        ]);

        if (! $order) {
            return redirect()->route('dealer.dashboard');
        }

        return view('rydeen-dealer::shop.order-confirmation.index', compact('order'));
    }

    /**
     * Update a cart item quantity.
     */
    public function updateItem(Request $request)
    {
        $cart = Cart::getCart();
        if (! $cart) {
            return redirect()->route('dealer.order-review');
        }

        $item = $cart->items->firstWhere('id', $request->item_id);
        if ($item) {
            Cart::updateItems(['qty' => [$request->item_id => max(1, (int) $request->quantity)]]);
        }

        return redirect()->route('dealer.order-review');
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request)
    {
        Cart::removeItem($request->item_id);
        return redirect()->route('dealer.order-review');
    }

    /**
     * Ensure the cart has billing (and shipping) addresses populated from the customer.
     */
    protected function ensureCartAddresses($cart, $customer): void
    {
        $address = $customer->addresses->first();

        $addressData = [
            'first_name'  => $customer->first_name,
            'last_name'   => $customer->last_name,
            'email'       => $customer->email,
            'phone'       => $customer->phone ?? '0000000000',
            'company_name' => $address?->company_name ?? '',
            'address'     => [$address?->address1 ?? 'N/A'],
            'city'        => $address?->city ?? 'N/A',
            'state'       => $address?->state ?? 'N/A',
            'postcode'    => $address?->postcode ?? '00000',
            'country'     => $address?->country ?? 'US',
        ];

        Cart::saveAddresses([
            'billing'  => array_merge($addressData, ['use_for_shipping' => true]),
            'shipping' => $addressData,
        ]);
    }
}
