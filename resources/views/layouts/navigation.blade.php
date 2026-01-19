<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Левая часть (лого) -->
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>
            </div>

            <!-- Центральная навигация -->
            <div class="hidden sm:flex items-center flex-grow justify-center">
                <div class="flex space-x-4 mx-4 gap-4">
                    <x-nav-link :href="route('tables.index')" :active="request()->routeIs('tables.index')">
                        Столы
                    </x-nav-link>
                    <div class="relative" x-data="{ openProductsDropdown: false }">
                        <button @click="openProductsDropdown = !openProductsDropdown" 
                                @click.away="openProductsDropdown = false"
                                :class="{
                                    'text-gray-900 border-b-2 border-indigo-500': request()->routeIs('hookahs.*') || request()->routeIs('products.*'),
                                    'text-gray-500 hover:text-gray-700 hover:border-gray-300': !(request()->routeIs('hookahs.*') || request()->routeIs('products.*'))
                                }"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none focus:text-gray-700 focus:border-gray-300">
                            <span>Товары / Кальяны</span>
                            <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        
                        <div x-show="openProductsDropdown" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            class="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <a href="{{ route('hookahs.index') }}" 
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('hookahs.*') ? 'bg-gray-100 text-indigo-600 font-medium' : '' }}">
                                    Кальяны
                                </a>
                                <a href="{{ route('products.index') }}" 
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('products.*') ? 'bg-gray-100 text-indigo-600 font-medium' : '' }}">
                                    Товары
                                </a>
                            </div>
                        </div>
                    </div>
                    <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.index')">
                        Клиенты
                    </x-nav-link>
                    <x-nav-link :href="route('warehouses.index')" :active="request()->routeIs('warehouses.index')">
                        Склады 
                    </x-nav-link>
                    <x-nav-link :href="route('sales.index')" :active="request()->routeIs('sales.index')">
                        Продажи
                    </x-nav-link>
                    <x-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.index')">
                        Сотрудники
                    </x-nav-link>
                    <x-nav-link :href="route('shifts.index')" :active="request()->routeIs('shifts.index')">
                        Смены
                    </x-nav-link>
                </div>
                
                <!-- Выпадающее меню для остальных разделов -->
                <div class="relative ml-2" x-data="{ openDropdown: false }">
                    <button @click="openDropdown = !openDropdown" 
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none">
                        <span>Еще</span>
                        <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    
                    <div x-show="openDropdown" 
                         @click.away="openDropdown = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                        <div class="py-1">
                            <x-dropdown-link :href="route('inventories.index')">
                                Инвентаризация
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('fines.index')">
                                Штрафы
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('expenditures.index')">
                                Расходы
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('accounting.index')">
                                Бухгалтерия
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('bonus-history.index')">
                                История бонусов
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('payment-methods.index')">
                                Способы оплаты
                            </x-dropdown-link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Правая часть навигации -->
            <div class="flex items-center space-x-4">
                <!-- Индикатор текущей смены -->
                @php
                    use App\Models\Shift;
                    use Carbon\Carbon;

                    $currentShift = null;
                    $specificDate = Carbon::parse('2024-12-25');
                    $today = Carbon::today();
                    
                    if (Auth::check()) {
                        $currentShift = Shift::with(['employees'])
                            ->whereDate('date', $today)
                            ->first();
                    }
                    
                    // Определяем статус для отображения
                    $status = $currentShift ? $currentShift->status : 'no_shift';
                @endphp

                <!-- Кнопка смены -->
                <div class="hidden sm:block">
                    @if($status === 'open')
                    <a href="{{ route('shifts.index', ['focus' => $today->format('Y-m-d')]) }}" 
                    class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors duration-150
                            btn btn-success text-white">
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <div class="h-2 w-2 bg-white rounded-full animate-ping absolute"></div>
                                <div class="h-2 w-2 bg-white rounded-full relative"></div>
                            </div>
                            <span class="text-xs font-medium">
                                Открыта
                            </span>
                        </div>
                    </a>
                    @elseif($status === 'closed')
                    <a href="{{ route('shifts.index', ['focus' => $today->format('Y-m-d')]) }}" 
                    class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors duration-150
                            btn btn-secondary text-white">
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <div class="h-2 w-2 bg-white rounded-full relative"></div>
                            </div>
                            <span class="text-xs font-medium">
                                Закрыта
                            </span>
                        </div>
                    </a>
                    @else
                    <a href="{{ route('shifts.index', ['focus' => $today->format('Y-m-d')]) }}" 
                    class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors duration-150
                            btn btn-warning text-white">
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <div class="h-2 w-2 bg-white rounded-full relative"></div>
                            </div>
                            <span class="text-xs font-medium">
                                Открыть
                            </span>
                        </div>
                    </a>
                    @endif
                </div>

                <!-- Профиль пользователя -->
                <div class="hidden sm:flex sm:items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ml-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Кнопка меню для мобильных -->
                <div class="flex items-center sm:hidden">
                    <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu для мобильных -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('tables.index')" :active="request()->routeIs('tables.index')">
                Столы
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('hookahs.index')" :active="request()->routeIs('hookahs.index')">
                Кальяны
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')">
                Товары
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.index')">
                Клиенты
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('warehouses.index')" :active="request()->routeIs('warehouses.index')">
                Склады 
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('sales.index')" :active="request()->routeIs('sales.index')">
                Продажи
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.index')">
                Сотрудники
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('shifts.index')" :active="request()->routeIs('shifts.index')">
                Смены
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('inventories.index')" :active="request()->routeIs('inventories.index')">
                Инвентаризация
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('fines.index')" :active="request()->routeIs('fines.index')">
                Штрафы
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('expenditures.index')" :active="request()->routeIs('expenditures.index')">
                Расходы
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.index')" :active="request()->routeIs('accounting.index')">
                Бухгалтерия
            </x-responsive-nav-link>
            <x-dropdown-link :href="route('bonus-history.index')">
                История бонусов
            </x-dropdown-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>