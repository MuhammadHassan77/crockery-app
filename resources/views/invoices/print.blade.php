<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>بل {{ $invoice->invoice_number }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Arial', 'Tahoma', sans-serif;
      font-size: 13px;
      color: #111;
      background: #fff;
      direction: rtl;
    }

    /* ── No-print toolbar ───────────────── */
    .toolbar {
      background: #f0f0f0;
      border-bottom: 1px solid #ccc;
      padding: 10px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      direction: ltr;
    }
    .toolbar button {
      padding: 7px 18px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
    }
    .btn-print { background: #0d6efd; color: #fff; }
    .btn-close { background: #6c757d; color: #fff; }

    /* ── Page ───────────────────────────── */
    .page {
      width: 100%;
      max-width: 780px;
      margin: 0 auto;
      padding: 18px 20px;
    }

    /* ── Header ─────────────────────────── */
    .bill-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 3px double #333;
      padding-bottom: 10px;
      margin-bottom: 10px;
    }
    .shop-info { text-align: center; flex: 1; }
    .shop-name {
      font-size: 22px;
      font-weight: 900;
      letter-spacing: 1px;
      text-decoration: underline;
      margin-bottom: 3px;
    }
    .shop-sub { font-size: 12px; color: #444; margin-bottom: 2px; }
    .shop-mob { font-size: 12px; font-weight: 600; direction: ltr; }

    .bill-date-box {
      border: 1px solid #333;
      padding: 5px 10px;
      font-size: 12px;
      text-align: center;
      min-width: 130px;
    }
    .bill-date-box .label { font-weight: 700; border-bottom: 1px solid #333; padding-bottom: 3px; margin-bottom: 3px; }

    /* ── Customer row ────────────────────── */
    .customer-row {
      border: 1px solid #aaa;
      padding: 5px 10px;
      margin-bottom: 6px;
      font-size: 13px;
    }
    .customer-row span { font-weight: 700; }

    /* ── Previous balance ────────────────── */
    .prev-balance-row {
      display: flex;
      justify-content: space-between;
      border: 1px solid #333;
      padding: 5px 10px;
      margin-bottom: 8px;
      background: #fff8e1;
      font-weight: 700;
    }
    .prev-balance-row .amount { color: #c0392b; font-size: 14px; direction: ltr; }

    /* ── Items table ─────────────────────── */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
    }
    .items-table th {
      background: #f0f0f0;
      border: 1px solid #555;
      padding: 7px 8px;
      font-size: 12px;
      font-weight: 700;
      text-align: center;
    }
    .items-table td {
      border: 1px solid #aaa;
      padding: 6px 8px;
      font-size: 13px;
      text-align: center;
    }
    .items-table tbody tr:nth-child(even) { background: #fafafa; }
    .items-table td.product-cell { text-align: right; padding-right: 10px; }
    .items-table td.amount-cell { text-align: left; direction: ltr; font-weight: 600; }
    .items-table td.number-cell { text-align: left; direction: ltr; }

    /* ── Totals block ────────────────────── */
    .totals-wrapper {
      display: flex;
      justify-content: flex-start;
      margin-top: 4px;
    }
    .totals-table {
      border-collapse: collapse;
      min-width: 300px;
    }
    .totals-table td {
      border: 1px solid #aaa;
      padding: 5px 12px;
      font-size: 13px;
    }
    .totals-table .label-cell { background: #f5f5f5; font-weight: 700; text-align: right; }
    .totals-table .value-cell { text-align: left; direction: ltr; font-weight: 600; min-width: 120px; }
    .totals-table .grand-row td { background: #333; color: #fff; font-size: 14px; font-weight: 900; }
    .totals-table .due-row td { background: #f8d7da; color: #721c24; font-weight: 700; }
    .totals-table .paid-row td { background: #d4edda; color: #155724; }

    /* ── Payment detail rows ─────────────── */
    .payment-detail-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
      font-size: 12px;
    }
    .payment-detail-table th {
      background: #e9ecef;
      border: 1px solid #aaa;
      padding: 5px 8px;
      text-align: center;
    }
    .payment-detail-table td {
      border: 1px solid #ddd;
      padding: 4px 8px;
      text-align: center;
    }

    /* ── Footer ──────────────────────────── */
    .bill-footer {
      margin-top: 18px;
      border-top: 2px solid #333;
      padding-top: 10px;
      text-align: center;
    }
    .urdu-thanks { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
    .footer-sub { font-size: 11px; color: #777; direction: ltr; }

    /* ── Status badge ────────────────────── */
    .status-badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
    }
    .status-paid    { background: #d4edda; color: #155724; }
    .status-partial { background: #fff3cd; color: #856404; }
    .status-pending { background: #f8d7da; color: #721c24; }

    /* ── Discount row ───────────────────── */
    .discount-cell { color: #c0392b; }

    /* ── Notes box ──────────────────────── */
    .notes-box {
      border: 1px dashed #aaa;
      padding: 6px 10px;
      margin-top: 8px;
      font-size: 12px;
      color: #555;
    }

    @media print {
      body  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .toolbar { display: none !important; }
      .page { padding: 8px; }
    }
  </style>
</head>
<body>

{{-- ── Toolbar (no-print) ── --}}
<div class="toolbar no-print">
  <button class="btn-print" onclick="window.print()">🖨️ Print</button>
  <button class="btn-close" onclick="window.close()">✕ Close</button>
  @if($invoice->customer)
  @php $waMsg = urlencode('Invoice '.$invoice->invoice_number.' - PKR '.number_format($invoice->total_amount,0).' - View: '.route('invoice.public', $invoice->share_token)); @endphp
  <a href="https://wa.me/?text={{ $waMsg }}" target="_blank"
     style="background:#25D366;color:#fff;padding:7px 16px;border-radius:4px;text-decoration:none;font-size:13px;font-weight:600;">
    📲 WhatsApp Share
  </a>
  @endif

  {{-- Language toggle --}}
  <div style="display:flex;align-items:center;gap:6px;margin-right:auto;">
    <span style="font-size:12px;color:#555;font-weight:600;">زبان / Language:</span>
    <button id="btnUrdu" onclick="setLang('ur')"
            style="padding:5px 14px;border:2px solid #0d6efd;border-radius:4px;background:#0d6efd;color:#fff;cursor:pointer;font-size:13px;font-weight:700;">
      اردو
    </button>
    <button id="btnEnglish" onclick="setLang('en')"
            style="padding:5px 14px;border:2px solid #6c757d;border-radius:4px;background:#fff;color:#6c757d;cursor:pointer;font-size:13px;font-weight:700;">
      English
    </button>
  </div>

  <span style="color:#666;font-size:12px;">{{ $invoice->invoice_number }}</span>
</div>

<div class="page">

  {{-- ── Bill Header ── --}}
  <div class="bill-header">
    {{-- Date box (right in RTL = appears on left visually) --}}
    <div class="bill-date-box">
      <div class="label">بل تاریخ</div>
      <div>{{ $invoice->invoice_date->format('d-m-Y') }}</div>
      @if($invoice->due_date)
        <div style="margin-top:4px;font-size:11px;color:#888;">واجب الادا: {{ $invoice->due_date->format('d-m-Y') }}</div>
      @endif
    </div>

    {{-- Shop info (center) --}}
    @php
      $shopName    = \App\Models\Setting::get('shop_name',    config('app.name', 'Bartan Shop'));
      $shopTagline = \App\Models\Setting::get('shop_tagline', 'Crockery & Household Items');
      $shopAddress = \App\Models\Setting::get('shop_address', '');
      $shopCity    = \App\Models\Setting::get('shop_city',    '');
      $shopPhone   = \App\Models\Setting::get('shop_phone',   '');
      $shopPhone2  = \App\Models\Setting::get('shop_phone2',  '');
      $footerNote  = \App\Models\Setting::get('bill_footer_note', 'شکریہ — خریداری کرنے کا شکریہ!');
      $fullAddress = collect([$shopAddress, $shopCity])->filter()->join('، ');
      $allPhones   = collect([$shopPhone, $shopPhone2])->filter()->join(' / ');
    @endphp
    <div class="shop-info">
      <div class="shop-name">{{ $shopName }}</div>
      @if($shopTagline)
        <div class="shop-sub">{{ $shopTagline }}</div>
      @endif
      @if($fullAddress)
        <div class="shop-sub">{{ $fullAddress }}</div>
      @endif
      @if($allPhones)
        <div class="shop-mob">MOB: {{ $allPhones }}</div>
      @endif
    </div>

    {{-- Invoice # box (left in RTL = appears on right visually) --}}
    <div class="bill-date-box">
      <div class="label">بل نمبر</div>
      <div style="font-weight:700;">{{ $invoice->invoice_number }}</div>
      @php $statusClass = match($invoice->status) { 'paid'=>'status-paid','partial'=>'status-partial', default=>'status-pending' }; @endphp
      <div style="margin-top:4px;">
        <span class="status-badge {{ $statusClass }}">{{ strtoupper($invoice->status) }}</span>
      </div>
    </div>
  </div>

  {{-- ── Customer Row ── --}}
  <div class="customer-row">
    <span>گاہک:&nbsp;</span>
    @if($invoice->customer)
      {{ $invoice->customer->name }}
      @if($invoice->customer->phone)
        &nbsp;&mdash;&nbsp;
        <span style="direction:ltr;display:inline-block;">{{ $invoice->customer->phone }}</span>
      @endif
      @if($invoice->customer->city)
        &nbsp;&mdash;&nbsp;{{ $invoice->customer->city }}
      @endif
    @else
      {{ $invoice->customer_name ?: 'Seedha Aaya Grahak (Walk-in)' }}
    @endif
  </div>

  {{-- ── Previous Balance ── --}}
  @if($previousBalance > 0)
  <div class="prev-balance-row">
    <span>سابقہ بقایا رقم:</span>
    <span class="amount">PKR {{ number_format($previousBalance, 0) }}</span>
  </div>
  @endif

  {{-- ── Items Table ── --}}
  <table class="items-table">
    <thead>
      <tr>
        <th style="width:38px">#</th>
        <th>مال / سائز</th>
        <th style="width:90px">ڈیزائن</th>
        <th style="width:70px">تعداد</th>
        <th style="width:110px">ریٹ</th>
        <th style="width:120px">کل رقم</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $i => $item)
      @php
        $productNameEn = $item->product_name;
        $productNameUr = $item->product_name_ur ?: $item->product_name;
        $sizeLabelEn   = $item->size_label   ?: '—';
        $sizeLabelUr   = $item->size_label_ur ?: $item->size_label ?: '—';
        $lineTotal     = $item->quantity * $item->unit_price;
      @endphp
      <tr>
        <td class="number-cell">{{ $i + 1 }}</td>
        <td class="product-cell item-name-cell"
            data-en="{{ $productNameEn }}"
            data-ur="{{ $productNameUr }}"
            data-size-en="{{ $sizeLabelEn }}"
            data-size-ur="{{ $sizeLabelUr }}">
          <strong class="item-name">{{ $productNameUr }}</strong>
          <br><small class="item-size" style="color:#666;">{{ $sizeLabelUr }}</small>
        </td>
        <td>—</td>
        <td class="number-cell">{{ $item->quantity }}</td>
        <td class="amount-cell">{{ number_format($item->unit_price, 0) }}</td>
        <td class="amount-cell"><strong>{{ number_format($lineTotal, 0) }}</strong></td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- ── Totals ── --}}
  @php
    $subtotal       = $invoice->items->sum(fn($it) => $it->quantity * $it->unit_price);
    $discount       = $invoice->discount_amount ?? 0;
    $grandTotal     = $invoice->total_amount;
    $paidAmount     = $invoice->paid_amount;
    $remaining      = $invoice->remaining_amount;
    $totalWithPrev  = $grandTotal + $previousBalance;
    $finalDue       = $remaining + $previousBalance;
  @endphp

  <div class="totals-wrapper">
    <table class="totals-table">
      @if($discount > 0)
      <tr>
        <td class="label-cell">جملہ رقم</td>
        <td class="value-cell">PKR {{ number_format($subtotal, 0) }}</td>
      </tr>
      <tr>
        <td class="label-cell discount-cell">چھوٹ / ڈسکاؤنٹ</td>
        <td class="value-cell discount-cell">- PKR {{ number_format($discount, 0) }}</td>
      </tr>
      @endif

      <tr class="grand-row">
        <td class="label-cell">ٹوٹل بل رقم</td>
        <td class="value-cell">PKR {{ number_format($grandTotal, 0) }}</td>
      </tr>

      @if($previousBalance > 0)
      <tr>
        <td class="label-cell">سابقہ بقایا</td>
        <td class="value-cell" style="color:#c0392b;">PKR {{ number_format($previousBalance, 0) }}</td>
      </tr>
      <tr>
        <td class="label-cell">کل واجب الادا</td>
        <td class="value-cell" style="color:#c0392b;font-weight:700;">PKR {{ number_format($totalWithPrev, 0) }}</td>
      </tr>
      @endif

      @if($paidAmount > 0)
      <tr class="paid-row">
        <td class="label-cell">ادا شدہ</td>
        <td class="value-cell">PKR {{ number_format($paidAmount, 0) }}</td>
      </tr>
      @endif

      <tr class="{{ $remaining > 0 ? 'due-row' : 'paid-row' }}">
        <td class="label-cell">باقی / ادھار</td>
        <td class="value-cell">PKR {{ number_format($finalDue, 0) }}</td>
      </tr>
    </table>
  </div>

  {{-- ── Payment History ── --}}
  @if($invoice->payments->count() > 0)
  <table class="payment-detail-table" style="margin-top:12px;">
    <thead>
      <tr>
        <th>ادائیگی کی تاریخ</th>
        <th>طریقہ</th>
        <th>رقم</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->payments as $payment)
      <tr>
        <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
        <td style="direction:ltr;color:#27ae60;font-weight:600;">PKR {{ number_format($payment->amount, 0) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  {{-- ── Notes ── --}}
  @if($invoice->notes)
  <div class="notes-box">
    <strong>نوٹ:</strong> {{ $invoice->notes }}
  </div>
  @endif

  {{-- ── Footer ── --}}
  <div class="bill-footer">
    <div class="urdu-thanks">{{ $footerNote ?? 'شکریہ — خریداری کرنے کا شکریہ!' }}</div>
    <div class="footer-sub">
      {{ $shopName ?? config('app.name') }} &bull; Generated: {{ now()->format('d M Y, h:i A') }}
    </div>
  </div>

</div>{{-- /page --}}

<script>
  // Language toggle: switch product/size names between Urdu and English
  var currentLang = 'ur';

  function setLang(lang) {
    currentLang = lang;

    document.querySelectorAll('.item-name-cell').forEach(function(cell) {
      var name     = lang === 'ur' ? cell.dataset.ur    : cell.dataset.en;
      var size     = lang === 'ur' ? cell.dataset.sizeUr : cell.dataset.sizeEn;
      cell.querySelector('.item-name').textContent = name;
      cell.querySelector('.item-size').textContent = size;
    });

    // Toggle button styles
    var activeStyle   = 'padding:5px 14px;border:2px solid #0d6efd;border-radius:4px;background:#0d6efd;color:#fff;cursor:pointer;font-size:13px;font-weight:700;';
    var inactiveStyle = 'padding:5px 14px;border:2px solid #6c757d;border-radius:4px;background:#fff;color:#6c757d;cursor:pointer;font-size:13px;font-weight:700;';
    document.getElementById('btnUrdu').style.cssText    = lang === 'ur' ? activeStyle : inactiveStyle;
    document.getElementById('btnEnglish').style.cssText = lang === 'en' ? activeStyle : inactiveStyle;
  }

  // Default: Urdu
  setLang('ur');
</script>

</body>
</html>
