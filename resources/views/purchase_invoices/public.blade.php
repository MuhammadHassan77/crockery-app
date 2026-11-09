<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice {{ $invoice->invoice_number }} | {{ config('app.name') }}</title>
  <meta property="og:title" content="Invoice {{ $invoice->invoice_number }}">
  <meta property="og:description" content="Total: PKR {{ number_format($invoice->total_amount, 0) }} | Status: {{ ucfirst($invoice->status) }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f4f6f9; font-family: Arial, sans-serif; }
    .invoice-card { max-width: 680px; margin: 30px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
    .inv-top { background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%); color: #fff; padding: 24px 28px; }
    .inv-top .brand { font-size: 20px; font-weight: 700; }
    .inv-top .inv-number { font-size: 14px; opacity: .85; }
    .inv-top .inv-amount { font-size: 32px; font-weight: 700; }
    .inv-top .inv-status { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-paid { background: rgba(255,255,255,.25); color: #fff; }
    .status-partial { background: #fff3cd; color: #856404; }
    .status-pending { background: #f8d7da; color: #721c24; }
    .inv-body { padding: 24px 28px; }
    .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 8px; }
    .items-table th { background: #f8f9fa; font-size: 12px; font-weight: 600; padding: 8px; }
    .items-table td { font-size: 13px; padding: 8px; vertical-align: middle; }
    .due-box { background: #fff5f5; border: 1px solid #ffcccc; border-radius: 6px; padding: 14px; }
    .due-box .label { font-size: 12px; color: #888; }
    .due-box .amount { font-size: 22px; font-weight: 700; color: #c0392b; }
    .wa-btn { background: #25D366; color: #fff; border: none; border-radius: 6px; padding: 12px 24px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .wa-btn:hover { background: #1ebe5b; color: #fff; }
    footer { text-align: center; padding: 16px; background: #f8f9fa; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
  </style>
</head>
<body>

<div class="invoice-card">

  <div class="inv-top">
    <div class="d-flex justify-content-between align-items-start mb-2">
      <div>
        <div class="brand">{{ config('app.name', 'Bartan Shop') }}</div>
        <div class="inv-number">{{ $invoice->invoice_number }} &bull; {{ $invoice->invoice_date->format('d M Y') }}</div>
      </div>
      @php $statusClass = match($invoice->status) { 'paid' => 'status-paid', 'partial' => 'status-partial', default => 'status-pending' }; @endphp
      <span class="inv-status {{ $statusClass }}">{{ strtoupper($invoice->status) }}</span>
    </div>
    <div class="inv-amount">PKR {{ number_format($invoice->total_amount, 0) }}</div>
  </div>

  <div class="inv-body">

    <div class="row g-3 mb-4">
      <div class="col-sm-6">
        <p class="section-title">{{ app()->getLocale() === 'ur' ? 'Grahak' : 'Bill To' }}</p>
        @if($invoice->customer)
          <p class="fw-semibold mb-0">{{ $invoice->customer->name }}</p>
          @if($invoice->customer->phone)<p class="small text-muted mb-0">{{ $invoice->customer->phone }}</p>@endif
        @else
          <p class="fw-semibold mb-0">{{ $invoice->customer_name ?: (app()->getLocale() === 'ur' ? 'Seedha Aaya Grahak' : 'Walk-in Customer') }}</p>
        @endif
      </div>
      <div class="col-sm-6 text-sm-end">
        <p class="section-title">{{ app()->getLocale() === 'ur' ? 'Raseed ki Malumat' : 'Invoice Info' }}</p>
        <p class="mb-1 small">{{ app()->getLocale() === 'ur' ? 'Tarikh:' : 'Date:' }} <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong></p>
        @if($invoice->due_date)
        <p class="mb-0 small">{{ app()->getLocale() === 'ur' ? 'Maturity:' : 'Due:' }} <strong>{{ $invoice->due_date->format('d M Y') }}</strong></p>
        @endif
      </div>
    </div>

    <p class="section-title">{{ app()->getLocale() === 'ur' ? 'Ashtya' : 'Items' }}</p>
    <div class="table-responsive mb-4">
      <table class="table table-bordered items-table mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Size') }}</th>
            <th class="text-center">{{ app()->getLocale() === 'ur' ? 'Tadadd' : 'Qty' }}</th>
            <th class="text-end">{{ app()->getLocale() === 'ur' ? 'Qeemat' : 'Price' }}</th>
            <th class="text-end">{{ __('Total') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoice->items as $i => $item)
          <tr>
            <td class="text-muted small">{{ $i + 1 }}</td>
            <td><strong>{{ $item->product_name }}</strong></td>
            <td>{{ $item->size_label ?: '—' }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-end">PKR {{ number_format($item->unit_price, 0) }}</td>
            <td class="text-end"><strong>PKR {{ number_format($item->quantity * $item->unit_price, 0) }}</strong></td>
          </tr>
          @endforeach
        </tbody>
        <tfoot class="table-light">
          @if(($invoice->discount_amount ?? 0) > 0)
          <tr>
            <td colspan="5" class="text-end text-muted">{{ app()->getLocale() === 'ur' ? 'Upar Kul' : 'Subtotal' }}</td>
            <td class="text-end">PKR {{ number_format($invoice->items->sum(fn($it) => $it->quantity * $it->unit_price), 0) }}</td>
          </tr>
          <tr>
            <td colspan="5" class="text-end text-danger">{{ __('Discount') }}</td>
            <td class="text-end text-danger">- PKR {{ number_format($invoice->discount_amount, 0) }}</td>
          </tr>
          @endif
          <tr>
            <td colspan="5" class="text-end fw-bold">{{ app()->getLocale() === 'ur' ? 'Bara Kul' : 'Grand Total' }}</td>
            <td class="text-end fw-bold">PKR {{ number_format($invoice->total_amount, 0) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-sm-6">
        <p class="section-title">{{ app()->getLocale() === 'ur' ? 'Adaigian' : 'Payments Received' }}</p>
        @forelse($invoice->payments as $payment)
        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">{{ $payment->payment_date->format('d M Y') }} ({{ $payment->payment_method_label }})</span>
          <span class="text-success fw-semibold">PKR {{ number_format($payment->amount, 0) }}</span>
        </div>
        @empty
        <p class="small text-muted">{{ app()->getLocale() === 'ur' ? 'Abhi koi adaigi nahi mili.' : 'No payments received yet.' }}</p>
        @endforelse
      </div>
      <div class="col-sm-6">
        @if($invoice->remaining_amount > 0)
        <div class="due-box text-center">
          <div class="label">{{ app()->getLocale() === 'ur' ? 'Baqi (Udhar)' : 'Balance Due (Udhar)' }}</div>
          <div class="amount">PKR {{ number_format($invoice->remaining_amount, 0) }}</div>
        </div>
        @else
        <div class="text-center p-3 bg-success bg-opacity-10 border border-success rounded">
          <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
          <div class="fw-semibold text-success">{{ app()->getLocale() === 'ur' ? 'Puri Adaigi Ho Gayi' : 'Fully Paid' }}</div>
        </div>
        @endif
      </div>
    </div>

    @if($invoice->notes)
    <div class="alert alert-light border small mb-4">
      <i class="bi bi-sticky me-1"></i> {{ $invoice->notes }}
    </div>
    @endif

    <div class="text-center py-2">
      @php $waMsg = urlencode('Invoice ' . $invoice->invoice_number . ' - PKR ' . number_format($invoice->total_amount,0) . ' - View: ' . url()->current()); @endphp
      <a href="https://wa.me/?text={{ $waMsg }}" target="_blank" class="wa-btn">
        <i class="bi bi-whatsapp" style="font-size:18px"></i>
        {{ app()->getLocale() === 'ur' ? 'WhatsApp par Bhejo' : 'Share via WhatsApp' }}
      </a>
    </div>

  </div>

  <footer>
    {{ config('app.name') }} &bull; Invoice {{ $invoice->invoice_number }} &bull; {{ $invoice->invoice_date->format('d M Y') }}
    <br>{{ app()->getLocale() === 'ur' ? 'Yeh ek auto-generated raseed hai. Kisi sawal k liye rabta karein.' : 'This is an auto-generated invoice. Please contact us for any queries.' }}
  </footer>

</div>

</body>
</html>
