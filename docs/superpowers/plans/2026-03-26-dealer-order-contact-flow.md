# Dealer Order Contact Flow & Notification Fan-Out — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add customer contact association to dealer orders with a 4-way email notification fan-out and admin management UI.

**Architecture:** New `rydeen_dealer_contacts` table with a `DealerContact` model scoped to dealers. The order-review page gets an Alpine.js contact search/create widget. On order placement, the `OrderListener` fans out emails to admin(s), rep, dealer, and customer. Admin gets a "Dealer Contacts" section and a settings page for configuring notification recipients.

**Tech Stack:** PHP 8.2, Laravel 11, Bagisto v2.3.16, Alpine.js, Tailwind CSS, Blade, Pest

---

### Task 1: Migration — `rydeen_dealer_contacts` table and `dealer_contact_id` on orders

**Files:**
- Create: `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_000001_create_rydeen_dealer_contacts_table.php`
- Create: `packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_000002_add_dealer_contact_id_to_orders_table.php`

- [ ] **Step 1: Create the dealer contacts table migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rydeen_dealer_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            $table->index(['customer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rydeen_dealer_contacts');
    }
};
```

- [ ] **Step 2: Create the orders FK migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('dealer_contact_id')->nullable()->after('customer_id');

            $table->foreign('dealer_contact_id')
                ->references('id')
                ->on('rydeen_dealer_contacts')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dealer_contact_id']);
            $table->dropColumn('dealer_contact_id');
        });
    }
};
```

- [ ] **Step 3: Run migrations**

Run: `php artisan migrate`
Expected: Both migrations run successfully, `rydeen_dealer_contacts` table created, `dealer_contact_id` column added to `orders`.

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_000001_create_rydeen_dealer_contacts_table.php packages/Rydeen/Dealer/src/Database/Migrations/2026_03_26_000002_add_dealer_contact_id_to_orders_table.php
git commit -m "feat: add dealer contacts table and FK on orders"
```

---

### Task 2: DealerContact Model

**Files:**
- Create: `packages/Rydeen/Dealer/src/Models/DealerContact.php`

- [ ] **Step 1: Create the DealerContact model**

```php
<?php

namespace Rydeen\Dealer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

class DealerContact extends Model
{
    protected $table = 'rydeen_dealer_contacts';

    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'dealer_contact_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDealer($query, int $dealerId)
    {
        return $query->where('customer_id', $dealerId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add packages/Rydeen/Dealer/src/Models/DealerContact.php
git commit -m "feat: add DealerContact model"
```

---

### Task 3: Shop-Side Contact Controller (search + create API)

**Files:**
- Create: `packages/Rydeen/Dealer/src/Http/Controllers/Shop/ContactController.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/shop.php`

- [ ] **Step 1: Create the ContactController**

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rydeen\Dealer\Models\DealerContact;

class ContactController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();
        $query = $request->get('q', '');

        $contacts = DealerContact::forDealer($customer->id)
            ->active()
            ->when($query, fn ($q) => $q->search($query))
            ->orderBy('first_name')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        return response()->json($contacts);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $contact = DealerContact::create([
            ...$validated,
            'customer_id' => $customer->id,
        ]);

        return response()->json($contact, 201);
    }
}
```

- [ ] **Step 2: Add routes to shop.php**

Add the following inside the existing `Route::middleware(['web', 'customer'])->prefix('dealer')->group(...)` block in `packages/Rydeen/Dealer/src/Routes/shop.php`, after the "Resources / FAQ" route and before the closing `});`:

```php
    // Contacts (JSON API for order-review widget)
    Route::get('contacts/search', [ContactController::class, 'search'])->name('dealer.contacts.search');
    Route::post('contacts', [ContactController::class, 'store'])->name('dealer.contacts.store');
```

Also add the import at the top of the file:

```php
use Rydeen\Dealer\Http\Controllers\Shop\ContactController;
```

- [ ] **Step 3: Verify routes register**

Run: `php artisan route:list --path=dealer/contacts`
Expected: Two routes listed — `GET dealer/contacts/search` and `POST dealer/contacts`.

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Shop/ContactController.php packages/Rydeen/Dealer/src/Routes/shop.php
git commit -m "feat: add dealer contact search and create endpoints"
```

---

### Task 4: Order Review Page — Contact Widget

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php`

- [ ] **Step 1: Add the contact widget to the order-review page**

In `packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php`, find the `{{-- Order Notes --}}` comment (line ~91). Insert the contact widget **before** it (between the `@endforeach` of cart items at line ~89 and the `{{-- Order Notes --}}` at line ~91):

```blade
                {{-- Customer Contact --}}
                <div class="bg-white rounded-lg shadow p-4 mt-4" x-data="contactWidget()">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Customer Contact <span class="text-red-500">*</span></h2>

                    {{-- Selected contact display --}}
                    <template x-if="selectedContact">
                        <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900" x-text="selectedContact.first_name + ' ' + selectedContact.last_name"></p>
                                <p class="text-xs text-gray-500" x-text="selectedContact.email"></p>
                                <p class="text-xs text-gray-500" x-show="selectedContact.phone" x-text="selectedContact.phone"></p>
                            </div>
                            <button type="button" @click="clearSelection()" class="text-sm text-gray-600 hover:text-gray-900 underline">Change</button>
                        </div>
                    </template>

                    {{-- Search / Add toggle --}}
                    <template x-if="!selectedContact">
                        <div>
                            {{-- Search box --}}
                            <div class="relative">
                                <input type="text"
                                       x-model="searchQuery"
                                       @input.debounce.300ms="doSearch()"
                                       @focus="showDropdown = true"
                                       placeholder="Search contacts by name, email, or phone..."
                                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">

                                {{-- Dropdown results --}}
                                <div x-show="showDropdown && results.length > 0"
                                     @click.outside="showDropdown = false"
                                     class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto">
                                    <template x-for="contact in results" :key="contact.id">
                                        <button type="button"
                                                @click="selectContact(contact)"
                                                class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                            <p class="text-sm font-medium text-gray-900" x-text="contact.first_name + ' ' + contact.last_name"></p>
                                            <p class="text-xs text-gray-500" x-text="contact.email"></p>
                                        </button>
                                    </template>
                                </div>

                                {{-- No results --}}
                                <div x-show="showDropdown && searchQuery.length >= 2 && results.length === 0 && !searching"
                                     class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded shadow-lg p-3">
                                    <p class="text-sm text-gray-500">No contacts found.</p>
                                </div>
                            </div>

                            {{-- Add new toggle --}}
                            <button type="button" @click="showAddForm = !showAddForm"
                                    class="mt-2 text-sm text-gray-700 hover:text-gray-900 underline">
                                <span x-text="showAddForm ? 'Cancel' : '+ Add New Contact'"></span>
                            </button>

                            {{-- Add new form --}}
                            <div x-show="showAddForm" x-transition class="mt-3 space-y-2 border-t border-gray-200 pt-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" x-model="newContact.first_name" placeholder="First Name *"
                                           class="border border-gray-300 rounded px-3 py-2 text-sm">
                                    <input type="text" x-model="newContact.last_name" placeholder="Last Name *"
                                           class="border border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <input type="email" x-model="newContact.email" placeholder="Email *"
                                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <input type="text" x-model="newContact.phone" placeholder="Phone (optional)"
                                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <textarea x-model="newContact.notes" placeholder="Notes (optional)" rows="2"
                                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm"></textarea>

                                <p x-show="addError" x-text="addError" class="text-xs text-red-600"></p>

                                <button type="button" @click="createContact()"
                                        :disabled="saving"
                                        class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-black disabled:opacity-50">
                                    <span x-show="!saving">Save & Select</span>
                                    <span x-show="saving">Saving...</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Hidden input for form submission --}}
                    <input type="hidden" name="dealer_contact_id" :value="selectedContact ? selectedContact.id : ''">
                </div>
```

- [ ] **Step 2: Move the form tag to wrap the contact widget**

Currently the `<form id="place-order-form">` wraps only the notes textarea (lines 93-104). We need it to also wrap the contact widget so the hidden `dealer_contact_id` input submits with the form.

Replace the existing order notes section (lines ~92-105) with this — the `<form>` now starts before the contact widget:

Find the existing form block:
```blade
                {{-- Order Notes --}}
                <div class="bg-white rounded-lg shadow p-4 mt-4">
                    <form id="place-order-form" action="{{ route('dealer.order-review.place') }}" method="POST">
                        @csrf
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Order Notes
                        </label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                  placeholder="Add any special instructions for your order..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Notes will be read by a Rydeen Specialist.</p>
                    </form>
                </div>
```

Replace with (move `<form>` to wrap the contact widget's hidden input):

```blade
                {{-- Order Notes --}}
                <form id="place-order-form" action="{{ route('dealer.order-review.place') }}" method="POST">
                    @csrf
                    <div class="bg-white rounded-lg shadow p-4 mt-4">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Order Notes
                        </label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                  placeholder="Add any special instructions for your order..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Notes will be read by a Rydeen Specialist.</p>
                    </div>
                </form>
```

Then move the contact widget's hidden input into this form by removing it from the contact widget `<div>` and instead adding it inside the form, right before the closing `</form>`:

Actually, a simpler approach: wrap both the contact widget and notes section in a single `<form>`. Move `<form id="place-order-form" ...>` and `@csrf` to appear just before the contact widget div, and `</form>` to appear after the notes div. The contact widget's hidden `<input type="hidden" name="dealer_contact_id" ...>` stays inside the contact widget — it'll be inside the form because the form wraps everything.

The restructured left column content (between `<div class="lg:col-span-2 space-y-4">` and its closing `</div>`) should be:

```blade
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>

                @foreach ($cart->items as $item)
                    {{-- ... existing item cards unchanged ... --}}
                @endforeach

                <form id="place-order-form" action="{{ route('dealer.order-review.place') }}" method="POST">
                    @csrf

                    {{-- Customer Contact widget (from Step 1) goes here --}}

                    {{-- Order Notes --}}
                    <div class="bg-white rounded-lg shadow p-4 mt-4">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Order Notes
                        </label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                  placeholder="Add any special instructions for your order..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Notes will be read by a Rydeen Specialist.</p>
                    </div>
                </form>
            </div>
```

- [ ] **Step 3: Disable "Place Order" button until contact is selected**

In the right column order summary, update the "Place Order" button to use Alpine.js state. Wrap the right column `<div class="lg:col-span-1">` with `x-data` that reads from the contact widget. Since the contact widget's Alpine component is in a different DOM subtree, use a custom event approach.

Replace the Place Order button:

```blade
                    <button type="submit"
                            form="place-order-form"
                            class="mt-6 w-full bg-yellow-400 text-gray-900 font-semibold py-3 px-4 rounded hover:bg-yellow-500 transition text-sm">
                        Place Order
                    </button>
```

With:

```blade
                    <button type="submit"
                            form="place-order-form"
                            x-data="{ contactSelected: false }"
                            @contact-selected.window="contactSelected = true"
                            @contact-cleared.window="contactSelected = false"
                            :disabled="!contactSelected"
                            :class="contactSelected ? 'bg-yellow-400 hover:bg-yellow-500' : 'bg-gray-300 cursor-not-allowed'"
                            class="mt-6 w-full text-gray-900 font-semibold py-3 px-4 rounded transition text-sm">
                        Place Order
                    </button>
```

- [ ] **Step 4: Add the Alpine.js component script**

Add the following `@push('scripts')` block at the very end of the file, before `@endsection`:

```blade
@push('scripts')
<script>
function contactWidget() {
    return {
        searchQuery: '',
        results: [],
        selectedContact: null,
        showDropdown: false,
        showAddForm: false,
        searching: false,
        saving: false,
        addError: '',
        newContact: { first_name: '', last_name: '', email: '', phone: '', notes: '' },

        async doSearch() {
            if (this.searchQuery.length < 2) {
                this.results = [];
                return;
            }
            this.searching = true;
            try {
                const res = await fetch(`{{ route('dealer.contacts.search') }}?q=${encodeURIComponent(this.searchQuery)}`);
                this.results = await res.json();
            } catch (e) {
                this.results = [];
            }
            this.searching = false;
            this.showDropdown = true;
        },

        selectContact(contact) {
            this.selectedContact = contact;
            this.showDropdown = false;
            this.searchQuery = '';
            this.results = [];
            this.$dispatch('contact-selected');
        },

        clearSelection() {
            this.selectedContact = null;
            this.$dispatch('contact-cleared');
        },

        async createContact() {
            this.addError = '';
            if (!this.newContact.first_name || !this.newContact.last_name || !this.newContact.email) {
                this.addError = 'First name, last name, and email are required.';
                return;
            }
            this.saving = true;
            try {
                const res = await fetch('{{ route("dealer.contacts.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.newContact),
                });
                if (!res.ok) {
                    const err = await res.json();
                    this.addError = err.message || 'Failed to create contact.';
                    this.saving = false;
                    return;
                }
                const contact = await res.json();
                this.selectContact(contact);
                this.showAddForm = false;
                this.newContact = { first_name: '', last_name: '', email: '', phone: '', notes: '' };
            } catch (e) {
                this.addError = 'Network error. Please try again.';
            }
            this.saving = false;
        },
    };
}
</script>
@endpush
```

- [ ] **Step 5: Manually test in browser**

1. Navigate to `https://reform9.com/dealer/catalog` and add a product.
2. Go to `https://reform9.com/dealer/order-review`.
3. Verify the contact widget appears between the cart items and notes.
4. Verify the "Place Order" button is disabled (gray).
5. Click "+ Add New Contact", fill in the form, click "Save & Select".
6. Verify the contact card appears and the button becomes yellow/active.
7. Click "Change" and verify you can re-search.

- [ ] **Step 6: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/order-review/index.blade.php
git commit -m "feat: add contact search/create widget to order review page"
```

---

### Task 5: OrderController — Validate and attach contact on order placement

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php`

- [ ] **Step 1: Add contact validation and FK attachment to placeOrder()**

In `packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php`, add `use Rydeen\Dealer\Models\DealerContact;` to the imports at the top.

Then replace the `placeOrder` method (lines 135-177) with:

```php
    public function placeOrder(Request $request)
    {
        $request->validate([
            'dealer_contact_id' => 'required|integer',
        ]);

        $cart = Cart::getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('dealer.catalog')
                ->with('error', trans('rydeen-dealer::app.shop.orders.cart-empty'));
        }

        $customer = auth('customer')->user();

        // Verify the contact belongs to this dealer
        $contact = DealerContact::where('id', $request->dealer_contact_id)
            ->where('customer_id', $customer->id)
            ->where('is_active', true)
            ->first();

        if (! $contact) {
            return redirect()->route('dealer.order-review')
                ->with('error', 'Please select a valid customer contact.');
        }

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

        // Attach the dealer contact to the order
        \Illuminate\Support\Facades\DB::table('orders')
            ->where('id', $order->id)
            ->update(['dealer_contact_id' => $contact->id]);

        // Deactivate the cart
        Cart::deActivateCart();

        return redirect()->route('dealer.order-confirmation', $order->id);
    }
```

- [ ] **Step 2: Verify the place-order flow rejects without a contact**

Manually test: try submitting the form without a contact selected. Should redirect back with validation error.

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Shop/OrderController.php
git commit -m "feat: validate and attach dealer contact on order placement"
```

---

### Task 6: New Mail Classes — Rep and Customer notifications

**Files:**
- Create: `packages/Rydeen/Dealer/src/Mail/OrderRepNotificationMail.php`
- Create: `packages/Rydeen/Dealer/src/Mail/OrderCustomerNotificationMail.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-rep-notification.blade.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-customer-notification.blade.php`

- [ ] **Step 1: Create OrderRepNotificationMail**

```php
<?php

namespace Rydeen\Dealer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderRepNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public $order,
        public $contact,
        public string $repName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dealer Order #' . ($this->order->increment_id ?? $this->order->id) . ' — New Order from Your Dealer',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'rydeen-dealer::shop.emails.order-rep-notification',
        );
    }
}
```

- [ ] **Step 2: Create the rep email template**

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="background-color: #FFD200; display: inline-block; padding: 12px 24px; border-radius: 4px;">
                <h1 style="color: #000000; font-size: 24px; margin: 0; letter-spacing: 2px;">RYDEEN</h1>
            </div>
        </div>
        <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 24px;">Dealer Order Notification</p>

        <p style="color: #333; font-size: 16px;">Hi {{ $repName }},</p>

        <p style="color: #333; font-size: 16px;">One of your dealers has placed a new order.</p>

        <div style="background: #f9f9f9; border-left: 3px solid #FFD200; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #333;">
                <strong>Dealer:</strong> {{ $order->customer_first_name }} {{ $order->customer_last_name }} ({{ $order->customer_email }})<br>
                <strong>Order #:</strong> {{ $order->increment_id ?? $order->id }}<br>
                <strong>Date:</strong> {{ $order->created_at->format('M d, Y h:i A') }}
            </p>
        </div>

        <div style="background: #f9f9f9; border-left: 3px solid #000000; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #333;">
                <strong>Customer Contact:</strong><br>
                {{ $contact->first_name }} {{ $contact->last_name }}<br>
                {{ $contact->email }}
                @if ($contact->phone)<br>{{ $contact->phone }}@endif
            </p>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <thead>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Product</th>
                    <th style="padding: 8px 12px; text-align: left; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">SKU</th>
                    <th style="padding: 8px 12px; text-align: center; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Qty</th>
                    <th style="padding: 8px 12px; text-align: right; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333;">{{ $item->name }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #666;">{{ $item->sku }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; text-align: center;">{{ (int) $item->qty_ordered }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; text-align: right;">${{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-weight: bold; font-size: 16px; color: #1a1a1a; text-align: right; margin-top: 16px;">
            Grand Total: ${{ number_format($order->grand_total, 2) }}
        </p>

        @if ($order->notes)
            <p style="color: #333; font-size: 14px; margin-top: 16px; padding: 12px; background: #FFF9E0; border-left: 3px solid #FFD200; border-radius: 4px;">
                <strong>Dealer Notes:</strong><br>{{ $order->notes }}
            </p>
        @endif

        <p style="color: #999; font-size: 12px; text-align: center; margin-top: 32px;">
            &mdash; Rydeen Dealer Portal
        </p>
    </div>
</body>
</html>
```

- [ ] **Step 3: Create OrderCustomerNotificationMail**

```php
<?php

namespace Rydeen\Dealer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCustomerNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public $order,
        public $contact,
        public string $dealerName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Submitted on Your Behalf — #' . ($this->order->increment_id ?? $this->order->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'rydeen-dealer::shop.emails.order-customer-notification',
        );
    }
}
```

- [ ] **Step 4: Create the customer email template**

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="background-color: #FFD200; display: inline-block; padding: 12px 24px; border-radius: 4px;">
                <h1 style="color: #000000; font-size: 24px; margin: 0; letter-spacing: 2px;">RYDEEN</h1>
            </div>
        </div>
        <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 24px;">Order Notification</p>

        <p style="color: #333; font-size: 16px;">Hi {{ $contact->first_name }},</p>

        <p style="color: #333; font-size: 16px;">Your dealer <strong>{{ $dealerName }}</strong> has submitted an order on your behalf. Below is a summary of the items requested.</p>

        <p style="color: #333; font-size: 14px;">
            <strong>Order #:</strong> {{ $order->increment_id ?? $order->id }}<br>
            <strong>Date:</strong> {{ $order->created_at->format('M d, Y h:i A') }}
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <thead>
                <tr>
                    <th style="padding: 8px 12px; text-align: left; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Product</th>
                    <th style="padding: 8px 12px; text-align: center; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Qty</th>
                    <th style="padding: 8px 12px; text-align: right; border-bottom: 2px solid #e5e5e5; font-size: 13px; color: #666; text-transform: uppercase;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333;">{{ $item->name }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; text-align: center;">{{ (int) $item->qty_ordered }}</td>
                        <td style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333; text-align: right;">${{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-weight: bold; font-size: 16px; color: #1a1a1a; text-align: right; margin-top: 16px;">
            Grand Total: ${{ number_format($order->grand_total, 2) }}
        </p>

        <p style="color: #333; font-size: 14px; margin-top: 24px;">
            Your dealer will be in touch regarding next steps. If you have any questions, please reach out to them directly.
        </p>

        <p style="color: #999; font-size: 12px; text-align: center; margin-top: 32px;">
            &mdash; Rydeen
        </p>
    </div>
</body>
</html>
```

- [ ] **Step 5: Commit**

```bash
git add packages/Rydeen/Dealer/src/Mail/OrderRepNotificationMail.php packages/Rydeen/Dealer/src/Mail/OrderCustomerNotificationMail.php packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-rep-notification.blade.php packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-customer-notification.blade.php
git commit -m "feat: add rep and customer order notification mail classes and templates"
```

---

### Task 7: Update Existing Emails — Add contact info block

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Mail/OrderConfirmationMail.php`
- Modify: `packages/Rydeen/Dealer/src/Mail/OrderSubmittedMail.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-confirmation.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-submitted.blade.php`

- [ ] **Step 1: Update OrderConfirmationMail to accept contact**

Replace the constructor in `packages/Rydeen/Dealer/src/Mail/OrderConfirmationMail.php`:

```php
    public function __construct(public $order, public $contact = null) {}
```

Remove `implements ShouldQueue` and the `Queueable` trait usage — dealer confirmation email fires synchronously per existing pattern:

```php
class OrderConfirmationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public $order, public $contact = null) {}
```

- [ ] **Step 2: Update OrderSubmittedMail to accept contact**

Replace the constructor in `packages/Rydeen/Dealer/src/Mail/OrderSubmittedMail.php`:

```php
    public function __construct(public $order, public $contact = null) {}
```

- [ ] **Step 3: Add contact info block to order-confirmation.blade.php**

In `packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-confirmation.blade.php`, after the date/order# paragraph (after line 18) and before the items table, add:

```html
        @if ($contact)
            <div style="background: #f9f9f9; border-left: 3px solid #000000; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
                <p style="margin: 0; font-size: 14px; color: #333;">
                    <strong>Customer:</strong><br>
                    {{ $contact->first_name }} {{ $contact->last_name }}<br>
                    {{ $contact->email }}
                    @if ($contact->phone)<br>{{ $contact->phone }}@endif
                </p>
            </div>
        @endif
```

- [ ] **Step 4: Add contact info block to order-submitted.blade.php**

In `packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-submitted.blade.php`, after the date/order# paragraph (after line 16) and before the items table, add:

```html
        @if ($contact)
            <div style="background: #f9f9f9; border-left: 3px solid #000000; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
                <p style="margin: 0; font-size: 14px; color: #333;">
                    <strong>Customer Contact:</strong><br>
                    {{ $contact->first_name }} {{ $contact->last_name }}<br>
                    {{ $contact->email }}
                    @if ($contact->phone)<br>{{ $contact->phone }}@endif
                </p>
            </div>
        @endif
```

- [ ] **Step 5: Commit**

```bash
git add packages/Rydeen/Dealer/src/Mail/OrderConfirmationMail.php packages/Rydeen/Dealer/src/Mail/OrderSubmittedMail.php packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-confirmation.blade.php packages/Rydeen/Dealer/src/Resources/views/shop/emails/order-submitted.blade.php
git commit -m "feat: update existing order emails to include customer contact info"
```

---

### Task 8: OrderListener — 4-Way Email Fan-Out

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Listeners/OrderListener.php`

- [ ] **Step 1: Rewrite OrderListener to fan out 4 emails**

Replace the entire contents of `packages/Rydeen/Dealer/src/Listeners/OrderListener.php`:

```php
<?php

namespace Rydeen\Dealer\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Rydeen\Dealer\Mail\OrderConfirmationMail;
use Rydeen\Dealer\Mail\OrderCustomerNotificationMail;
use Rydeen\Dealer\Mail\OrderRepNotificationMail;
use Rydeen\Dealer\Mail\OrderSubmittedMail;
use Rydeen\Dealer\Models\DealerContact;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Admin;

class OrderListener
{
    public function afterOrderCreated($order): void
    {
        $contact = $this->getContact($order);
        $dealer = Customer::find($order->customer_id);

        // 1. Send to admin(s)
        $this->sendToAdmins($order, $contact);

        // 2. Send to assigned rep
        $this->sendToRep($order, $contact, $dealer);

        // 3. Send confirmation to dealer (synchronous)
        $this->sendToDealer($order, $contact);

        // 4. Send notification to customer
        $this->sendToCustomer($order, $contact, $dealer);
    }

    protected function getContact($order): ?DealerContact
    {
        $contactId = DB::table('orders')
            ->where('id', $order->id)
            ->value('dealer_contact_id');

        return $contactId ? DealerContact::find($contactId) : null;
    }

    protected function sendToAdmins($order, ?DealerContact $contact): void
    {
        try {
            $recipients = $this->getAdminRecipients();

            foreach ($recipients as $email) {
                Mail::to($email)->queue(new OrderSubmittedMail($order, $contact));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function sendToRep($order, ?DealerContact $contact, ?Customer $dealer): void
    {
        if (! $dealer?->assigned_rep_id) {
            return;
        }

        try {
            $rep = Admin::find($dealer->assigned_rep_id);

            if ($rep?->email) {
                Mail::to($rep->email)->queue(
                    new OrderRepNotificationMail($order, $contact, $rep->name)
                );
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function sendToDealer($order, ?DealerContact $contact): void
    {
        try {
            if ($order->customer_email) {
                Mail::to($order->customer_email)->send(
                    new OrderConfirmationMail($order, $contact)
                );
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function sendToCustomer($order, ?DealerContact $contact, ?Customer $dealer): void
    {
        if (! $contact?->email) {
            return;
        }

        try {
            $dealerName = trim(($dealer?->first_name ?? '') . ' ' . ($dealer?->last_name ?? ''));

            Mail::to($contact->email)->queue(
                new OrderCustomerNotificationMail($order, $contact, $dealerName ?: 'Your Dealer')
            );
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function getAdminRecipients(): array
    {
        // Check for configured admin recipients in core_config
        $configValue = DB::table('core_config')
            ->where('code', 'rydeen.order_notification_admin_ids')
            ->value('value');

        if ($configValue) {
            $adminIds = json_decode($configValue, true);

            if (is_array($adminIds) && count($adminIds) > 0) {
                return Admin::whereIn('id', $adminIds)
                    ->pluck('email')
                    ->toArray();
            }
        }

        // Fallback to env var
        return [config('rydeen.admin_order_email', 'orders@test.reform9.com')];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add packages/Rydeen/Dealer/src/Listeners/OrderListener.php
git commit -m "feat: rewrite order listener for 4-way email fan-out"
```

---

### Task 9: Admin — Dealer Contacts Section

**Files:**
- Create: `packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerContactController.php`
- Create: `packages/Rydeen/Dealer/src/DataGrids/DealerContactDataGrid.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/admin/contacts/index.blade.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/admin/contacts/view.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/admin.php`

- [ ] **Step 1: Create DealerContactDataGrid**

```php
<?php

namespace Rydeen\Dealer\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DealerContactDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('rydeen_dealer_contacts')
            ->leftJoin('customers', 'rydeen_dealer_contacts.customer_id', '=', 'customers.id')
            ->leftJoin('orders', 'orders.dealer_contact_id', '=', 'rydeen_dealer_contacts.id')
            ->select(
                'rydeen_dealer_contacts.id',
                DB::raw("CONCAT(rydeen_dealer_contacts.first_name, ' ', rydeen_dealer_contacts.last_name) as contact_name"),
                'rydeen_dealer_contacts.email as contact_email',
                'rydeen_dealer_contacts.phone as contact_phone',
                DB::raw("CONCAT(customers.first_name, ' ', customers.last_name) as dealer_name"),
                'rydeen_dealer_contacts.is_active',
                'rydeen_dealer_contacts.created_at',
                DB::raw('COUNT(orders.id) as order_count')
            )
            ->groupBy(
                'rydeen_dealer_contacts.id',
                'rydeen_dealer_contacts.first_name',
                'rydeen_dealer_contacts.last_name',
                'rydeen_dealer_contacts.email',
                'rydeen_dealer_contacts.phone',
                'rydeen_dealer_contacts.is_active',
                'rydeen_dealer_contacts.created_at',
                'customers.first_name',
                'customers.last_name'
            );

        $this->setQueryBuilder($queryBuilder);
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'contact_name',
            'label'      => 'Contact Name',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'contact_email',
            'label'      => 'Email',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'contact_phone',
            'label'      => 'Phone',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'dealer_name',
            'label'      => 'Dealer',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'order_count',
            'label'      => 'Orders',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => 'Active',
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->is_active
                ? '<span class="badge badge-md badge-success">Active</span>'
                : '<span class="badge badge-md badge-danger">Inactive</span>',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => 'Created',
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-eye',
            'title'  => 'View',
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.rydeen.contacts.view', $row->id),
        ]);
    }
}
```

- [ ] **Step 2: Create DealerContactController**

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rydeen\Dealer\DataGrids\DealerContactDataGrid;
use Rydeen\Dealer\Models\DealerContact;

class DealerContactController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return app(DealerContactDataGrid::class)->toJson();
        }

        return view('rydeen-dealer::admin.contacts.index');
    }

    public function view(int $id)
    {
        $contact = DealerContact::with(['dealer', 'orders'])->findOrFail($id);

        return view('rydeen-dealer::admin.contacts.view', compact('contact'));
    }

    public function update(Request $request, int $id)
    {
        $contact = DealerContact::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'notes'      => 'nullable|string|max:1000',
            'is_active'  => 'boolean',
        ]);

        $contact->update($validated);

        return redirect()->back()->with('success', 'Contact updated successfully.');
    }
}
```

- [ ] **Step 3: Create admin contacts index view**

```blade
<x-admin::layouts>
    <x-slot:title>
        Dealer Contacts
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Dealer Contacts
        </p>
    </div>

    <x-admin::datagrid :src="route('admin.rydeen.contacts.index')" />
</x-admin::layouts>
```

- [ ] **Step 4: Create admin contacts view page**

```blade
<x-admin::layouts>
    <x-slot:title>
        Contact: {{ $contact->full_name }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Contact: {{ $contact->full_name }}
        </p>

        <a href="{{ route('admin.rydeen.contacts.index') }}"
           class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
            Back to Contacts
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Contact Details --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Contact Information</h2>

        <form action="{{ route('admin.rydeen.contacts.update', $contact->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $contact->first_name) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                    @error('first_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $contact->last_name) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                    @error('last_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $contact->email) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $contact->phone) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white">{{ old('notes', $contact->notes) }}</textarea>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $contact->is_active ? 'checked' : '' }}
                               class="rounded border-gray-300">
                        <span class="text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Dealer Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Dealer</h2>
        <div class="text-sm">
            <p><strong>{{ $contact->dealer->first_name }} {{ $contact->dealer->last_name }}</strong></p>
            <p class="text-gray-500">{{ $contact->dealer->email }}</p>
        </div>
    </div>

    {{-- Associated Orders --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Associated Orders</h2>

        @if ($contact->orders->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-600 bg-gray-50 dark:bg-gray-900 dark:text-gray-300 border-b">
                        <tr>
                            <th class="px-4 py-3">Order #</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contact->orders as $order)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3">{{ $order->increment_id ?? $order->id }}</td>
                                <td class="px-4 py-3">{{ ucfirst($order->status) }}</td>
                                <td class="px-4 py-3">${{ number_format($order->grand_total, 2) }}</td>
                                <td class="px-4 py-3">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.rydeen.orders.view', $order->id) }}" class="text-blue-600 hover:underline text-xs">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No orders associated with this contact yet.</p>
        @endif
    </div>
</x-admin::layouts>
```

- [ ] **Step 5: Add admin routes for contacts**

In `packages/Rydeen/Dealer/src/Routes/admin.php`, add the import at the top:

```php
use Rydeen\Dealer\Http\Controllers\Admin\DealerContactController;
```

Add a new route group after the existing orders group:

```php
Route::middleware(['web', 'admin'])->prefix('admin/rydeen/contacts')->group(function () {
    Route::get('/', [DealerContactController::class, 'index'])->name('admin.rydeen.contacts.index');
    Route::get('{id}', [DealerContactController::class, 'view'])->name('admin.rydeen.contacts.view');
    Route::put('{id}', [DealerContactController::class, 'update'])->name('admin.rydeen.contacts.update');
});
```

- [ ] **Step 6: Verify routes register**

Run: `php artisan route:list --path=admin/rydeen/contacts`
Expected: Three routes listed — GET index, GET view, PUT update.

- [ ] **Step 7: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Admin/DealerContactController.php packages/Rydeen/Dealer/src/DataGrids/DealerContactDataGrid.php packages/Rydeen/Dealer/src/Resources/views/admin/contacts/index.blade.php packages/Rydeen/Dealer/src/Resources/views/admin/contacts/view.blade.php packages/Rydeen/Dealer/src/Routes/admin.php
git commit -m "feat: add admin dealer contacts section with DataGrid"
```

---

### Task 10: Admin — Notification Recipients Settings Page

**Files:**
- Create: `packages/Rydeen/Dealer/src/Http/Controllers/Admin/SettingsController.php`
- Create: `packages/Rydeen/Dealer/src/Resources/views/admin/settings/index.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Routes/admin.php`

- [ ] **Step 1: Create SettingsController**

```php
<?php

namespace Rydeen\Dealer\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;

class SettingsController extends Controller
{
    public function index()
    {
        $admins = Admin::orderBy('name')->get();

        $selectedIds = [];
        $configValue = DB::table('core_config')
            ->where('code', 'rydeen.order_notification_admin_ids')
            ->value('value');

        if ($configValue) {
            $selectedIds = json_decode($configValue, true) ?? [];
        }

        return view('rydeen-dealer::admin.settings.index', compact('admins', 'selectedIds'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'admin_ids'   => 'nullable|array',
            'admin_ids.*' => 'integer|exists:admins,id',
        ]);

        $adminIds = $validated['admin_ids'] ?? [];
        $value = json_encode(array_map('intval', $adminIds));

        DB::table('core_config')->updateOrInsert(
            ['code' => 'rydeen.order_notification_admin_ids'],
            [
                'value'      => $value,
                'channel_code' => 'default',
                'locale_code'  => 'en',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Notification settings saved.');
    }
}
```

- [ ] **Step 2: Create settings view**

```blade
<x-admin::layouts>
    <x-slot:title>
        Rydeen Settings
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Rydeen Settings
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Order Notification Recipients</h2>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Select which admin users should receive email notifications when a dealer places an order.
            If none are selected, notifications will be sent to <code>{{ config('rydeen.admin_order_email') }}</code>.
        </p>

        <form action="{{ route('admin.rydeen.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded p-3">
                @foreach ($admins as $admin)
                    <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-1 rounded">
                        <input type="checkbox"
                               name="admin_ids[]"
                               value="{{ $admin->id }}"
                               {{ in_array($admin->id, $selectedIds) ? 'checked' : '' }}
                               class="rounded border-gray-300">
                        <span class="text-gray-700 dark:text-gray-300">{{ $admin->name }}</span>
                        <span class="text-gray-400 text-xs">({{ $admin->email }})</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
```

- [ ] **Step 3: Add settings routes**

In `packages/Rydeen/Dealer/src/Routes/admin.php`, add the import:

```php
use Rydeen\Dealer\Http\Controllers\Admin\SettingsController;
```

Add the route group:

```php
Route::middleware(['web', 'admin'])->prefix('admin/rydeen/settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('admin.rydeen.settings.index');
    Route::put('/', [SettingsController::class, 'update'])->name('admin.rydeen.settings.update');
});
```

- [ ] **Step 4: Commit**

```bash
git add packages/Rydeen/Dealer/src/Http/Controllers/Admin/SettingsController.php packages/Rydeen/Dealer/src/Resources/views/admin/settings/index.blade.php packages/Rydeen/Dealer/src/Routes/admin.php
git commit -m "feat: add admin settings page for order notification recipients"
```

---

### Task 11: Display Contact on Order View Pages

**Files:**
- Modify: `packages/Rydeen/Dealer/src/Resources/views/shop/orders/view.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php`
- Modify: `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php`

- [ ] **Step 1: Add contact info to dealer-side order view**

In `packages/Rydeen/Dealer/src/Resources/views/shop/orders/view.blade.php`, after the order summary grid (after the closing `</div>` of the `bg-white rounded-lg shadow p-6 mb-6` block around line 36) and before the order items table, add:

```blade
    {{-- Customer Contact --}}
    @php
        $contact = \Rydeen\Dealer\Models\DealerContact::find(
            \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->value('dealer_contact_id')
        );
    @endphp
    @if ($contact)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase mb-2">Customer Contact</h2>
            <p class="font-medium text-gray-900">{{ $contact->first_name }} {{ $contact->last_name }}</p>
            <p class="text-sm text-gray-600">{{ $contact->email }}</p>
            @if ($contact->phone)
                <p class="text-sm text-gray-600">{{ $contact->phone }}</p>
            @endif
        </div>
    @endif
```

- [ ] **Step 2: Add contact info to admin-side order view**

In `packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php`, update the `view()` method to also load the contact. After the `$items` query, add:

```php
        $contact = null;
        $contactId = DB::table('orders')->where('id', $id)->value('dealer_contact_id');
        if ($contactId) {
            $contact = DB::table('rydeen_dealer_contacts')->where('id', $contactId)->first();
        }

        return view('rydeen-dealer::admin.orders.view', compact('order', 'items', 'contact'));
```

Replace the existing `return view(...)` line with the above.

In `packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php`, after the Order Information `</div>` block and before the Order Items block, add:

```blade
    {{-- Customer Contact --}}
    @if (isset($contact) && $contact)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                Customer Contact
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Name:</span>
                    <span class="ml-2 font-medium">{{ $contact->first_name }} {{ $contact->last_name }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Email:</span>
                    <span class="ml-2 font-medium">{{ $contact->email }}</span>
                </div>
                @if ($contact->phone)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Phone:</span>
                        <span class="ml-2 font-medium">{{ $contact->phone }}</span>
                    </div>
                @endif
                @if ($contact->notes)
                    <div class="md:col-span-2">
                        <span class="text-gray-500 dark:text-gray-400">Notes:</span>
                        <span class="ml-2">{{ $contact->notes }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
```

- [ ] **Step 3: Commit**

```bash
git add packages/Rydeen/Dealer/src/Resources/views/shop/orders/view.blade.php packages/Rydeen/Dealer/src/Resources/views/admin/orders/view.blade.php packages/Rydeen/Dealer/src/Http/Controllers/Admin/OrderApprovalController.php
git commit -m "feat: display customer contact on order detail pages"
```

---

### Task 12: Smoke Test — End-to-End Order Flow

**Files:** None (testing only)

- [ ] **Step 1: Clear caches**

Run: `php artisan optimize:clear`

- [ ] **Step 2: Run migrations**

Run: `php artisan migrate`
Expected: Both new migrations run (or "Nothing to migrate" if already run in Task 1).

- [ ] **Step 3: Verify routes**

Run: `php artisan route:list --path=dealer/contacts && php artisan route:list --path=admin/rydeen/contacts && php artisan route:list --path=admin/rydeen/settings`
Expected: All 7 new routes listed.

- [ ] **Step 4: Browser test — full order flow**

1. Log in as a dealer at `/dealer/login`.
2. Browse catalog at `/dealer/catalog`, add a product.
3. Go to `/dealer/order-review`.
4. Verify "Place Order" button is disabled.
5. Click "+ Add New Contact", fill in test data, click "Save & Select".
6. Verify contact card shows, button activates.
7. Click "Place Order".
8. Verify redirect to confirmation page.
9. Check email inbox — verify 4 emails sent (admin, rep if assigned, dealer, customer).
10. Go to `/dealer/orders` and click the new order — verify contact info appears.

- [ ] **Step 5: Browser test — admin side**

1. Log in to admin.
2. Navigate to `/admin/rydeen/contacts` — verify DataGrid shows the new contact.
3. Click through to view — verify details and associated order.
4. Navigate to `/admin/rydeen/settings` — verify admin list, check/uncheck, save.
5. Navigate to `/admin/rydeen/orders/{id}` — verify contact info block shows.

- [ ] **Step 6: Final commit (if any fixes needed)**

```bash
git add -A
git commit -m "fix: smoke test fixes for dealer order contact flow"
```
