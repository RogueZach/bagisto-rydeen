@extends('rydeen::shop.layouts.master')

@section('title', trans('rydeen-dealer::app.shop.orders.review-title'))

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">@lang('rydeen-dealer::app.shop.orders.review-title')</h1>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    @if ($cart && $cart->items->count())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Order Items --}}
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>

                @foreach ($cart->items as $item)
                    <div class="bg-white rounded-lg shadow p-4 flex items-start gap-4">
                        {{-- Product Thumbnail --}}
                        @php
                            $product = $item->product;
                            $imageUrl = $product?->images?->first()?->url ?? $product?->base_image_url ?? null;
                        @endphp
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $item->name }}" class="w-16 h-16 object-cover rounded flex-shrink-0">
                        @else
                            <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Item Details --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">SKU: {{ $item->sku }}</p>
                            <p class="text-sm text-gray-700 mt-1">${{ number_format($item->price, 2) }} each</p>
                        </div>

                        {{-- Quantity Controls --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <form action="{{ route('dealer.order-review.update-item') }}" method="POST" class="flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <input type="hidden" name="quantity" value="{{ max(1, (int) $item->quantity - 1) }}">
                                <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm font-medium"
                                        {{ (int) $item->quantity <= 1 ? 'disabled' : '' }}>
                                    &minus;
                                </button>
                            </form>

                            <span class="w-10 text-center text-sm font-medium text-gray-900">{{ (int) $item->quantity }}</span>

                            <form action="{{ route('dealer.order-review.update-item') }}" method="POST" class="flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <input type="hidden" name="quantity" value="{{ (int) $item->quantity + 1 }}">
                                <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm font-medium">
                                    +
                                </button>
                            </form>
                        </div>

                        {{-- Line Total --}}
                        <div class="text-right flex-shrink-0 w-24">
                            <p class="text-sm font-semibold text-gray-900">${{ number_format($item->total, 2) }}</p>
                        </div>

                        {{-- Remove Button --}}
                        <form action="{{ route('dealer.order-review.remove-item') }}" method="POST" class="flex-shrink-0">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                            <button type="submit"
                                    class="text-gray-400 hover:text-red-500 transition"
                                    title="Remove item">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach

                <form id="place-order-form" action="{{ route('dealer.order-review.place') }}" method="POST">
                    @csrf

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

                    {{-- Order Notes --}}
                    <div class="bg-white rounded-lg shadow p-4 mt-4">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Order Notes
                        </label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                  placeholder="Add any special instructions for your order..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Notes will be read by a Rydeen Specialist.</p>
                    </div>
                </form>
            </div>

            {{-- Right Column: Order Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium text-gray-900">${{ number_format($cart->sub_total, 2) }}</span>
                        </div>

                        @if ($cart->tax_total > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium text-gray-900">${{ number_format($cart->tax_total, 2) }}</span>
                            </div>
                        @endif

                        <hr class="my-3">

                        <div class="flex justify-between text-base font-bold">
                            <span class="text-gray-900">Total</span>
                            <span class="text-gray-900">${{ number_format($cart->grand_total, 2) }}</span>
                        </div>
                    </div>

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

                    <p class="text-xs text-gray-500 text-center mt-3">
                        Orders require admin review before processing.
                    </p>

                    <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-xs text-yellow-700">
                            <strong>Order Processing Hours:</strong> Mon-Fri, 9:30 AM - 4:30 PM PT. Orders received after hours or on weekends will be processed the next business day.
                        </p>
                    </div>

                    <a href="{{ route('dealer.catalog') }}" class="block text-center text-sm text-gray-900 hover:text-gray-700 mt-4">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            <p class="text-gray-500 mb-4">Your cart is empty</p>
            <a href="{{ route('dealer.catalog') }}"
               class="inline-block bg-gray-900 text-white px-6 py-2 rounded hover:bg-black text-sm">
                Browse Catalog
            </a>
        </div>
    @endif
@endsection

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
                const res = await fetch(`{{ route('dealer.contacts.search') }}?q=${encodeURIComponent(this.searchQuery)}`, {
                    headers: { 'Accept': 'application/json' },
                });
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
