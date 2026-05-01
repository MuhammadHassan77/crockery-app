@extends('layouts.app')
@section('title', $customer->name)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ $customer->name }}</h4>
    <small class="text-muted">{{ __('Customer Profile') }}</small>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">
      <i class="bi bi-receipt me-1"></i> {{ __('New Invoice') }}
    </a>
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm">
      <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
    </a>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
    </a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body">
        <div class="text-center mb-3">
          <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2"
               style="width:64px;height:64px;">
            <i class="bi bi-person fs-2 text-primary"></i>
          </div>
          <h5 class="mb-0">{{ $customer->name }}</h5>
          <span class="badge {{ $customer->is_active ? 'bg-success' : 'bg-secondary' }}">
            {{ $customer->is_active ? __('Active') : __('Inactive') }}
          </span>
        </div>
        <hr>
        @if($customer->phone)
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi bi-telephone text-muted"></i>
          <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
          <a href="https://wa.me/{{ preg_replace('/\D/','',$customer->phone) }}"
             class="ms-auto text-success" target="_blank" title="WhatsApp">
            <i class="bi bi-whatsapp"></i>
          </a>
        </div>
        @endif
        @if($customer->email)
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi bi-envelope text-muted"></i>
          <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
        </div>
        @endif
        @if($customer->city)
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi bi-geo-alt text-muted"></i>
          <span>{{ $customer->city }}</span>
        </div>
        @endif
        @if($customer->address)
        <div class="d-flex align-items-start gap-2 mb-2">
          <i class="bi bi-map text-muted mt-1"></i>
          <span class="small">{{ $customer->address }}</span>
        </div>
        @endif
        @if($customer->notes)
        <div class="alert alert-light border small mt-2 mb-0">
          <i class="bi bi-sticky me-1"></i>{{ $customer->notes }}
        </div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        @php
          $totalInvoiced = $customer->invoices->sum('total_amount');
          $totalPaid     = $customer->invoices->sum('paid_amount');
          $totalDue      = $totalInvoiced - $totalPaid;
        @endphp
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">{{ __('Total Invoiced') }}</span>
          <strong>PKR {{ number_format($totalInvoiced, 0) }}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">{{ __('Total Paid') }}</span>
          <strong class="text-success">PKR {{ number_format($totalPaid, 0) }}</strong>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-muted small">{{ __('Udhar / Due') }}</span>
          <strong class="{{ $totalDue > 0 ? 'text-danger' : 'text-success' }}">
            PKR {{ number_format($totalDue, 0) }}
          </strong>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">{{ __('Invoice History') }}</h6>
        <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-plus-lg me-1"></i> {{ __('New Invoice') }}
        </a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Invoice #') }}</th>
                <th>{{ __('Date') }}</th>
                <th class="text-end">{{ __('Total') }}</th>
                <th class="text-end">{{ __('Paid') }}</th>
                <th class="text-end">{{ __('Due') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($customer->invoices as $inv)
              <tr>
                <td>
                  <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-primary">
                    {{ $inv->invoice_number }}
                  </a>
                </td>
                <td>{{ $inv->invoice_date->format('d M Y') }}</td>
                <td class="text-end">PKR {{ number_format($inv->total_amount, 0) }}</td>
                <td class="text-end text-success">PKR {{ number_format($inv->paid_amount, 0) }}</td>
                <td class="text-end {{ $inv->remaining_amount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                  PKR {{ number_format($inv->remaining_amount, 0) }}
                </td>
                <td>
                  @php $badge = match($inv->status) { 'paid' => 'success', 'partial' => 'warning text-dark', 'pending' => 'danger', default => 'secondary' }; @endphp
                  <span class="badge bg-{{ $badge }}">{{ __(ucfirst($inv->status)) }}</span>
                </td>
                <td>
                  <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-light">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="bi bi-receipt fs-2 d-block mb-2"></i>{{ __('No invoices yet.') }}
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
