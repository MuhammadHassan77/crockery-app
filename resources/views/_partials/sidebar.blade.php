<aside class="sidebar">
    <div class="sidebar-shell">
        <button class="sidebar-close" type="button" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>

        <nav class="sidebar-nav">
            <ul class="nav-menu">

                {{-- Dashboard --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <span class="nav-icon"><i class="ph-light ph-squares-four"></i></span>
                        <span class="nav-text">{{ __('Dashboard') }}</span>
                    </a>
                </li>

                {{-- Products --}}
                <li class="nav-heading"><span>{{ app()->getLocale() === 'ur' ? 'Godam' : 'Inventory' }}</span></li>

                <li class="nav-item {{ request()->routeIs('products.*') ? 'has-submenu open' : 'has-submenu' }}">
                    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="#"
                        aria-expanded="{{ request()->routeIs('products.*') ? 'true' : 'false' }}">
                        <span class="nav-icon"><i class="ph-light ph-package"></i></span>
                        <span class="nav-text">{{ __('Products') }}</span>
                        <span class="nav-arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <ul class="nav-submenu">
                        <li>
                            <a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}"
                                href="{{ route('products.index') }}">
                                <span class="nav-dot"></span>{{ __('All Products') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('products.create') ? 'active' : '' }}"
                                href="{{ route('products.create') }}">
                                <span class="nav-dot"></span>{{ __('Add Product') }}
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}"
                        href="{{ route('reports.stock') }}">
                        <span class="nav-icon"><i class="ph-light ph-warehouse"></i></span>
                        <span class="nav-text">{{ __('Stock') }}</span>
                    </a>
                </li>

                {{-- Billing --}}
                <li class="nav-heading"><span>{{ app()->getLocale() === 'ur' ? 'Hisaab Kitaab' : 'Selling' }}</span>
                </li>

                <li class="nav-item {{ request()->routeIs('invoices.*') ? 'has-submenu open' : 'has-submenu' }}">
                    <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="#"
                        aria-expanded="{{ request()->routeIs('invoices.*') ? 'true' : 'false' }}">
                        <span class="nav-icon"><i class="ph-light ph-receipt"></i></span>
                        <span class="nav-text">{{ __('Invoices') }}</span>
                        <span class="nav-arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <ul class="nav-submenu">
                        <li>
                            <a class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}"
                                href="{{ route('invoices.index') }}">
                                <span class="nav-dot"></span>{{ __('All Invoices') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('invoices.create') ? 'active' : '' }}"
                                href="{{ route('invoices.create') }}">
                                <span class="nav-dot"></span>{{ __('New Invoice') }}
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('customers.*') ? 'has-submenu open' : 'has-submenu' }}">
                    <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="#"
                        aria-expanded="{{ request()->routeIs('customers.*') ? 'true' : 'false' }}">
                        <span class="nav-icon"><i class="ph-light ph-users-three"></i></span>
                        <span class="nav-text">{{ __('Customers') }}</span>
                        <span class="nav-arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <ul class="nav-submenu">
                        <li>
                            <a class="nav-link {{ request()->routeIs('customers.index') ? 'active' : '' }}"
                                href="{{ route('customers.index') }}">
                                <span class="nav-dot"></span>{{ __('All Customers') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('customers.create') ? 'active' : '' }}"
                                href="{{ route('customers.create') }}">
                                <span class="nav-dot"></span>{{ __('Add Customer') }}
                            </a>
                        </li>
                    </ul>
                </li>
                {{-- Purchasing --}}
                <li class="nav-heading"><span>{{ app()->getLocale() === 'ur' ? 'Kharedari' : 'Purchasing' }}</span>
                </li>

                <li class="nav-item {{ request()->routeIs('purchase_invoices.*') ? 'has-submenu open' : 'has-submenu' }}">
                    <a class="nav-link {{ request()->routeIs('purchase_invoices.*') ? 'active' : '' }}" href="#"
                        aria-expanded="{{ request()->routeIs('purchase_invoices.*') ? 'true' : 'false' }}">
                        <span class="nav-icon"><i class="ph-light ph-receipt"></i></span>
                        <span class="nav-text">{{ __('Purchase Invoices') }}</span>
                        <span class="nav-arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <ul class="nav-submenu">
                        <li>
                            <a class="nav-link {{ request()->routeIs('purchase_invoices.index') ? 'active' : '' }}"
                                href="{{ route('purchase_invoices.index') }}">
                                <span class="nav-dot"></span>{{ __('All Invoices') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('purchase_invoices.create') ? 'active' : '' }}"
                                href="{{ route('purchase_invoices.create') }}">
                                <span class="nav-dot"></span>{{ __('New Invoice') }}
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('vendors.*') ? 'has-submenu open' : 'has-submenu' }}">
                    <a class="nav-link {{ request()->routeIs('vendors.*') ? 'active' : '' }}" href="#"
                        aria-expanded="{{ request()->routeIs('vendors.*') ? 'true' : 'false' }}">
                        <span class="nav-icon"><i class="ph-light ph-factory"></i></span>
                        <span class="nav-text">{{ __('Vendors') }}</span>
                        <span class="nav-arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <ul class="nav-submenu">
                        <li>
                            <a class="nav-link {{ request()->routeIs('vendors.index') ? 'active' : '' }}"
                                href="{{ route('vendors.index') }}">
                                <span class="nav-dot"></span>{{ __('All Vendors') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('vendors.create') ? 'active' : '' }}"
                                href="{{ route('vendors.create') }}">
                                <span class="nav-dot"></span>{{ __('Add Vendor') }}
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Reports --}}
                <li class="nav-heading"><span>{{ __('Reports') }}</span></li>

                <li class="nav-item {{ request()->routeIs('reports.*') ? 'has-submenu open' : 'has-submenu' }}">
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="#"
                        aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
                        <span class="nav-icon"><i class="ph-light ph-chart-line-up"></i></span>
                        <span class="nav-text">{{ __('Reports') }}</span>
                        <span class="nav-arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <ul class="nav-submenu">
                        <li>
                            <a class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}"
                                href="{{ route('reports.sales') }}">
                                <span class="nav-dot"></span>{{ __('Sales Report') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('reports.purchases') ? 'active' : '' }}"
                                href="{{ route('reports.purchases') }}">
                                <span class="nav-dot"></span>{{ __('Purchases Report') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('reports.payments') ? 'active' : '' }}"
                                href="{{ route('reports.payments') }}">
                                <span class="nav-dot"></span>{{ __('Payments Report') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('reports.purchase_payments') ? 'active' : '' }}"
                                href="{{ route('reports.purchase_payments') }}">
                                <span class="nav-dot"></span>{{ __('Purchase Payments Report') }}
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}"
                                href="{{ route('reports.stock') }}">
                                <span class="nav-dot"></span>{{ __('Stock Report') }}
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Admin Only --}}
                @if (auth()->user()->isAdmin())
                    <li class="nav-heading">
                        <span>{{ app()->getLocale() === 'ur' ? 'Muhtamim' : 'Administration' }}</span></li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"
                            href="{{ route('settings.edit') }}">
                            <span class="nav-icon"><i class="ph-light ph-gear"></i></span>
                            <span
                                class="nav-text">{{ app()->getLocale() === 'ur' ? 'Settings' : 'Shop Settings' }}</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('backup.view') }}">
                            <span class="nav-icon"><i class="ph-light ph-database"></i></span>
                            <span class="nav-text">{{ __('Backup DB') }}</span>
                        </a>
                    </li>
                @endif

            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-account">
                <a href="#" class="sidebar-account-main">
                    <img src="{{ asset('assets/niceadmin/img/profile-img.webp') }}" alt="User"
                        class="sidebar-account-avatar">
                    <div class="sidebar-account-meta">
                        <div class="sidebar-account-name">{{ auth()->user()->name }}</div>
                        <div class="sidebar-account-role">{{ __(auth()->user()->isAdmin() ? 'Admin' : 'Staff') }}
                        </div>
                    </div>
                </a>
                <div class="sidebar-account-actions">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit"
                            class="sidebar-account-action sidebar-account-logout border-0 bg-transparent"
                            title="{{ __('Sign Out') }}">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</aside>
