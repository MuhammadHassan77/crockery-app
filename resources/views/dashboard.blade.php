@extends('layouts.app')
@section('title', __('Dashboard'))

@section('content')
<div class="page-dashboard">

  <section class="nd-topbar d-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="nd-title">{{ __('Dashboard') }}</h1>
      <p class="nd-subtitle mb-0">
        {{ app()->getLocale() === 'ur'
          ? 'Khush Aamdeed, ' . auth()->user()->name . '. Aaj ka Jaiza:'
          : 'Welcome back, ' . auth()->user()->name . '. Here\'s today\'s overview.' }}
      </p>
    </div>
    <div>
      <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> {{ __('New Invoice') }}
      </a>
    </div>
  </section>

  {{-- KPI Cards --}}
  <section class="row g-3 mb-4">
    <div class="col-xxl-3 col-md-6">
      <article class="nd-kpi-card nd-kpi-revenue">
        <span class="nd-kpi-icon"><i class="bi bi-cash-stack"></i></span>
        <span class="nd-kpi-label">{{ __("Today's Sales") }}</span>
        <strong class="nd-kpi-value">PKR {{ number_format($todaySales, 0) }}</strong>
        <span class="nd-kpi-trend up">
          <i class="bi bi-receipt"></i> {{ $todayInvoices }} {{ app()->getLocale() === 'ur' ? 'Raseed' : 'invoice' . ($todayInvoices != 1 ? 's' : '') }}
        </span>
      </article>
    </div>
    <div class="col-xxl-3 col-md-6">
      <article class="nd-kpi-card nd-kpi-leads">
        <span class="nd-kpi-icon"><i class="bi bi-hourglass-split"></i></span>
        <span class="nd-kpi-label">{{ __('Pending / Udhar') }}</span>
        <strong class="nd-kpi-value">PKR {{ number_format($totalPending, 0) }}</strong>
        <a href="{{ route('invoices.index', ['status' => 'pending']) }}" class="nd-kpi-trend">
          {{ app()->getLocale() === 'ur' ? 'Sab Dekho' : 'View all' }} <i class="bi bi-arrow-right"></i>
        </a>
      </article>
    </div>
    <div class="col-xxl-3 col-md-6">
      <article class="nd-kpi-card nd-kpi-cycle {{ $lowStockItems->count() > 0 ? 'nd-kpi-danger' : '' }}">
        <span class="nd-kpi-icon"><i class="bi bi-box-seam"></i></span>
        <span class="nd-kpi-label">{{ __('Low Stock Items') }}</span>
        <strong class="nd-kpi-value">{{ $lowStockItems->count() }}</strong>
        <a href="{{ route('reports.stock') }}" class="nd-kpi-trend {{ $lowStockItems->count() > 0 ? 'down' : 'up' }}">
          @if($lowStockItems->count() > 0)
            <i class="bi bi-exclamation-triangle"></i> {{ app()->getLocale() === 'ur' ? 'Dhyan Do' : 'Needs attention' }}
          @else
            <i class="bi bi-check-circle"></i> {{ app()->getLocale() === 'ur' ? 'Sab Theek' : 'All good' }}
          @endif
        </a>
      </article>
    </div>
    <div class="col-xxl-3 col-md-6">
      <article class="nd-kpi-card nd-kpi-retention">
        <span class="nd-kpi-icon"><i class="bi bi-people"></i></span>
        <span class="nd-kpi-label">{{ __("Today's Invoices") }}</span>
        <strong class="nd-kpi-value">{{ $todayInvoices }}</strong>
        <a href="{{ route('invoices.index') }}" class="nd-kpi-trend up">
          {{ app()->getLocale() === 'ur' ? 'Tamam Raseeden' : 'All invoices' }} <i class="bi bi-arrow-right"></i>
        </a>
      </article>
    </div>
  </section>

  <div class="row g-3">
    {{-- Recent Invoices --}}
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">{{ __('Recent Invoices') }}</h5>
          <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-primary">
            {{ app()->getLocale() === 'ur' ? 'Sab Dekho' : 'View All' }}
          </a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Invoice #') }}</th>
                  <th>{{ __('Customers') }}</th>
                  <th>{{ __('Total Amount') }}</th>
                  <th>{{ __('Status') }}</th>
                  <th>{{ __('Date') }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentInvoices as $invoice)
                <tr>
                  <td>
                    <a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold text-primary">
                      {{ $invoice->invoice_number }}
                    </a>
                  </td>
                  <td>{{ $invoice->customer_name }}</td>
                  <td>PKR {{ number_format($invoice->total_amount, 0) }}</td>
                  <td>
                    @php $badge = match($invoice->status) { 'paid' => 'success', 'partial' => 'warning', 'pending' => 'danger', default => 'secondary' }; @endphp
                    <span class="badge bg-{{ $badge }}">{{ __(ucfirst($invoice->status)) }}</span>
                  </td>
                  <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                  <td>
                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-light">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                    {{ __('No invoices yet.') }}
                    <a href="{{ route('invoices.create') }}">{{ app()->getLocale() === 'ur' ? 'Banao' : 'Create one' }}</a>
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Low Stock --}}
    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title mb-0">
            <i class="bi bi-exclamation-triangle text-warning me-1"></i> {{ __('Low Stock') }}
          </h5>
          <a href="{{ route('reports.stock') }}" class="btn btn-sm btn-outline-warning">
            {{ app()->getLocale() === 'ur' ? 'Sab Dekho' : 'View All' }}
          </a>
        </div>
        <div class="card-body p-0">
          @forelse($lowStockItems as $item)
          <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <div>
              <div class="fw-semibold small">{{ $item->product->name }}</div>
              <div class="text-muted" style="font-size:.78rem;">{{ $item->size_label }}</div>
            </div>
            <div class="text-end">
              <span class="badge {{ $item->stock_quantity == 0 ? 'bg-danger' : 'bg-warning text-dark' }}">
                {{ $item->stock_quantity }} {{ app()->getLocale() === 'ur' ? 'baqi' : 'left' }}
              </span>
            </div>
          </div>
          @empty
          <div class="text-center text-muted py-5">
            <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
            {{ app()->getLocale() === 'ur' ? 'Tamam Maal Theek Hai' : 'All stock levels are healthy' }}
          </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
