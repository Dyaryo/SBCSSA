<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Slip')</title>
    <link rel="icon" href="{{ asset_v('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset_v('icons/rooster-mascot.svg') }}" type="image/svg+xml">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            background: #eef1f6;
            color: #111;
            font-family: "Segoe UI", Tahoma, sans-serif;
            font-size: 13px;
        }
        .slip {
            background: #fff;
            margin: 0 auto;
            padding: 18px 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
        }
        .slip.receipt {
            width: 320px; max-width: 100%; font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 12.5px; line-height: 1.45; text-align: center; padding: 20px 18px;
        }
        body.receipt-print .brand { display: none; }
        body.receipt-print {
            padding: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        body.receipt-print .slip.receipt { width: 100%; max-width: 320px; }
        .slip.a4 { width: 760px; max-width: 100%; font-size: 12.5px; }
        h1 { font-size: 15px; margin: 0 0 2px; letter-spacing: .06em; }
        h2 { font-size: 12px; margin: 14px 0 6px; text-transform: uppercase; letter-spacing: .08em; border-bottom: 1px solid #bbb; padding-bottom: 4px; }
        .brand { text-align: center; border-bottom: 1px dashed #999; padding-bottom: 8px; margin-bottom: 10px; }
        .brand .sub { font-size: 10.5px; letter-spacing: .12em; color: #555; }
        .muted { color: #555; }
        .row { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; }
        .row .k { color: #444; }
        .row .v { font-weight: 600; text-align: right; overflow-wrap: anywhere; }
        table.compact th, table.compact td { padding: 3px 4px; font-size: 11px; }
        .big { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { text-align: left; padding: 4px 5px; border-bottom: 1px solid #e2e2e2; }
        th { background: #f3f4f7; font-size: 10.5px; text-transform: uppercase; letter-spacing: .05em; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: 700; border-top: 1.5px solid #333; border-bottom: none; }
        .cut { border-top: 1px dashed #999; margin: 12px 0 8px; }
        .note { font-size: 10.5px; color: #555; line-height: 1.45; }
        .center { text-align: center; }
        .sign { display: flex; gap: 22px; margin-top: 34px; }
        .sign div { flex: 1; border-top: 1px solid #333; padding-top: 5px; font-size: 10.5px; color: #444; text-align: center; }
        .flag { background: #fdecec; border: 1px solid #d33; color: #900; padding: 6px 8px; font-size: 11px; margin: 8px 0; }
        .qr { text-align: center; margin: 8px 0 2px; }
        .qr svg { width: 150px; height: 150px; display: block; margin: 0 auto; }
        .ticket-brand {
            text-align: center; font-size: 15.5px; font-weight: 800; line-height: 1.2;
            text-transform: uppercase; letter-spacing: .01em; margin-bottom: 3px;
            overflow-wrap: anywhere;
        }
        .ticket-title { text-align: center; font-size: 14px; font-weight: 800; letter-spacing: .16em; margin: 2px 0 9px; }
        .ticket-line { text-align: center; font-size: 12.5px; line-height: 1.45; overflow-wrap: anywhere; }
        .ticket-line.strong { font-weight: 800; text-transform: uppercase; letter-spacing: .01em; }
        .ticket-gap { height: 11px; }
        .ticket-amount { text-align: center; font-size: 16px; font-weight: 800; margin-top: 4px; }
        .ticket-side { text-align: center; font-size: 17px; font-weight: 800; letter-spacing: .1em; }
        .ticket-side.red { color: #b3261e; }
        .ticket-side.blue { color: #1d4f9c; }
        .barcode-wrap { text-align: center; margin: 4px 0 2px; }
        .barcode { width: 100%; max-width: 285px; height: 54px; display: block; margin: 0 auto; shape-rendering: crispEdges; }
        .barcode-text { font-family: Consolas, "Courier New", monospace; font-size: 11px; letter-spacing: .12em; margin-top: 4px; }
        .barcode-manual { font-size: 8px; letter-spacing: .18em; text-transform: uppercase; color: #666; margin-top: 3px; }
        .barcode-code { font-family: Consolas, "Courier New", monospace; font-size: 10px; font-weight: 700; letter-spacing: .04em; color: #111; }
        .ticket-qr { margin-top: 8px; }
        .ticket-qr svg { width: 64px; height: 64px; display: block; margin: 0 auto; }
        .qr-code { font-family: Consolas, "Courier New", monospace; font-size: 11px; letter-spacing: .04em; margin-top: 2px; }
        .qr-note { font-size: 9.5px; color: #555; }
        .actions { width: 320px; max-width: 100%; margin: 14px auto 0; display: flex; gap: 8px; justify-content: center; }
        .actions a, .actions button {
            font: inherit; cursor: pointer; padding: 7px 14px; border-radius: 5px;
            border: 1px solid #555; background: #fff; color: #111; text-decoration: none;
        }
        .actions .primary { background: #1f2937; color: #fff; border-color: #1f2937; }
        .badge { display: inline-block; padding: 1px 6px; border: 1px solid #666; border-radius: 3px; font-size: 10px; text-transform: uppercase; }
        .play-money-only {
            margin: 8px 0 2px; padding: 6px 8px; border: 1.5px solid #8a1f1f;
            background: #fff4f4; color: #5c1111; text-align: center;
            font-size: 9.5px; line-height: 1.4;
        }
        .play-money-only strong {
            display: block; font-size: 10px; letter-spacing: .14em;
            text-transform: uppercase; margin-bottom: 2px;
        }
        /* Receipts use a named page so 80mm roll paper gets its own width;
           closeout slips keep the plain A4 page. */
        @page { margin: 12mm; }
        /* Chrome ignores "80mm auto". A fixed 80mm width works; 200mm is
           enough for a bet or claim slip on a continuous 80mm roll. */
        @page receipt { size: 80mm 200mm; margin: 4mm; }
        body.receipt-print .slip { page: receipt; }

        @media print {
            body { background: #fff; padding: 0; }
            .slip { box-shadow: none; margin: 0; width: auto; max-width: none; padding: 0; }
            .slip.a4 { width: 100%; }
            .actions { display: none; }

            /* The screen rule is 320px wide. It must be overridden here or the
               slip is wider than the 72mm printable area and clips on 80mm rolls. */
            body.receipt-print .slip.receipt {
                width: 100%;
                max-width: 72mm;
                margin: 0 auto;
                padding: 0;
                font-size: 11.5px;
                page-break-inside: avoid;
            }
            body.receipt-print .row { padding: 2px 0; }
            body.receipt-print .cut { margin: 7px 0 5px; }
            body.receipt-print .note { font-size: 9px; }
            body.receipt-print .ticket-brand { font-size: 14px; }
            body.receipt-print .ticket-line { font-size: 11.5px; }
            body.receipt-print .ticket-line.strong { font-size: 12px; }
            body.receipt-print .ticket-gap { height: 8px; }
            body.receipt-print .barcode { width: 100%; max-width: 100%; height: 46px; }
            body.receipt-print .ticket-qr svg { width: 56px; height: 56px; }
            body.receipt-print h1 { font-size: 13px; }
        }
    </style>
</head>
<body class="@yield('body_class')">
    <div class="slip @yield('slip_class', 'a4')">
        <div class="brand">
            <h1>E-SABONG VIRTUAL CONSOLE</h1>
            <div class="sub">@yield('doc_type', 'DOCUMENT')</div>
        </div>

        @yield('body')

        <div class="play-money-only">
            <strong>Play money only — not a cash voucher</strong>
            Virtual credits have no cash value and cannot be bought, sold, redeemed or exchanged
            for physical cash. No cash-in, cash-out, deposit or cash payout.
        </div>

        <div class="cut"></div>
        <p class="note center">
            Keep this slip. Its code matches a record in the server ledger.
        </p>
    </div>

    <div class="actions">
        <button type="button" class="primary" data-print>Print</button>
        <a href="@yield('back_url', '/')">Back</a>
    </div>

    <script src="{{ asset_v('js/print.js') }}"></script>
</body>
</html>
