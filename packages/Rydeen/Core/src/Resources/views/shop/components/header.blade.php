<header class="bg-white shadow-sm" x-data="{ mobileOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ auth('customer')->check() ? '/dealer/dashboard' : '/dealer/login' }}" class="text-2xl font-bold text-gray-900 tracking-tight">RYDEEN</a>

            {{-- Desktop Navigation --}}
            <nav class="hidden md:flex items-center space-x-6">
                @auth('customer')
                    <a href="/dealer/dashboard"
                       class="text-sm font-medium {{ request()->is('dealer/dashboard*') ? 'text-gray-900 border-b-2 border-yellow-400 pb-1' : 'text-gray-600 hover:text-gray-900' }}">
                        Dashboard
                    </a>
                    <a href="/dealer/catalog"
                       class="text-sm font-medium {{ request()->is('dealer/catalog*') ? 'text-gray-900 border-b-2 border-yellow-400 pb-1' : 'text-gray-600 hover:text-gray-900' }}">
                        Catalog
                    </a>
                    <a href="/dealer/orders"
                       class="text-sm font-medium {{ request()->is('dealer/orders*') ? 'text-gray-900 border-b-2 border-yellow-400 pb-1' : 'text-gray-600 hover:text-gray-900' }}">
                        Orders
                    </a>
                    <a href="/dealer/resources"
                       class="text-sm font-medium {{ request()->is('dealer/resources*') ? 'text-gray-900 border-b-2 border-yellow-400 pb-1' : 'text-gray-600 hover:text-gray-900' }}">
                        Resources
                    </a>

                    {{-- Cart Icon --}}
                    <a href="{{ route('dealer.cart') }}" class="relative text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                        <span id="cart-badge"
                              class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full w-5 h-5 items-center justify-center font-bold"
                              style="display: none;">
                            0
                        </span>
                    </a>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('shop.customer.session.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="/dealer/login" class="text-sm font-medium text-gray-600 hover:text-gray-900">Login</a>
                @endauth
            </nav>

            {{-- Mobile Menu Button --}}
            <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-gray-600 hover:text-gray-900">
                <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile Navigation --}}
    <div x-show="mobileOpen" x-cloak x-transition class="md:hidden bg-white border-t">
        <div class="px-4 py-3 space-y-2">
            @auth('customer')
                <a href="/dealer/dashboard"
                   class="block px-3 py-2 rounded text-sm font-medium {{ request()->is('dealer/dashboard*') ? 'bg-yellow-50 text-gray-900' : 'text-gray-600 hover:bg-gray-100' }}">
                    Dashboard
                </a>
                <a href="/dealer/catalog"
                   class="block px-3 py-2 rounded text-sm font-medium {{ request()->is('dealer/catalog*') ? 'bg-yellow-50 text-gray-900' : 'text-gray-600 hover:bg-gray-100' }}">
                    Catalog
                </a>
                <a href="/dealer/orders"
                   class="block px-3 py-2 rounded text-sm font-medium {{ request()->is('dealer/orders*') ? 'bg-yellow-50 text-gray-900' : 'text-gray-600 hover:bg-gray-100' }}">
                    Orders
                </a>
                <a href="/dealer/resources"
                   class="block px-3 py-2 rounded text-sm font-medium {{ request()->is('dealer/resources*') ? 'bg-yellow-50 text-gray-900' : 'text-gray-600 hover:bg-gray-100' }}">
                    Resources
                </a>
                <a href="{{ route('dealer.cart') }}"
                   class="block px-3 py-2 rounded text-sm font-medium text-gray-600 hover:bg-gray-100">
                    Cart
                </a>
                <form method="POST" action="{{ route('shop.customer.session.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="block w-full text-left px-3 py-2 rounded text-sm font-medium text-gray-600 hover:bg-gray-100">
                        Logout
                    </button>
                </form>
            @else
                <a href="/dealer/login"
                   class="block px-3 py-2 rounded text-sm font-medium text-gray-600 hover:bg-gray-100">
                    Login
                </a>
            @endauth
        </div>
    </div>
</header>
