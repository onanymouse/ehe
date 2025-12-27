<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk - <?php echo $inv['invoice_number']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; /* Font struk jadul */
            font-size: 12px;
            margin: 0;
            padding: 10px;
            color: #000;
        }
        .container {
            width: 100%;
            max-width: 58mm; /* Ukuran kertas thermal 58mm */
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        .flex { display: flex; justify-content: space-between; }
        
        /* Hilangkan elemen browser saat print */
        @media print {
            @page { margin: 0; size: auto; }
            body { margin: 0; padding: 5px; }
            .no-print { display: none; }
        }
        
        .btn-back {
            background: #ccc; padding: 5px 10px; text-decoration: none; color: #000; border: 1px solid #999; display: block; margin-bottom: 10px; text-align: center;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <a href="javascript:window.history.back()" class="btn-back">&laquo; Kembali</a>
    </div>

    <div class="container">
        <div class="text-center">
            <h3 style="margin:0;"><?php echo $company_name; ?></h3>
            <span style="font-size: 10px;"><?php echo $company_address; ?></span><br>
            <span style="font-size: 10px;">WA: <?php echo $company_wa; ?></span>
        </div>
        
        <div class="line"></div>

        <div>
            Tgl Bayar: <?php echo date('d/m/Y H:i', strtotime($inv['paid_at'])); ?><br>
            No. Ref: <?php echo $inv['invoice_number']; ?><br>
            Pelanggan: <?php echo $inv['customer_name']; ?> (<?php echo $inv['customer_code']; ?>)
        </div>

        <div class="line"></div>

        <div class="flex">
            <span><?php echo $inv['package_name']; ?></span>
        </div>
        <div class="flex">
            <span>Periode: <?php echo $inv['period_month']; ?></span>
            <span class="bold"><?php echo number_format($inv['amount'], 0, ',', '.'); ?></span>
        </div>

        <div class="line"></div>

        <div class="flex bold" style="font-size: 14px;">
            <span>TOTAL BAYAR:</span>
            <span>Rp <?php echo number_format($inv['amount'], 0, ',', '.'); ?></span>
        </div>
        
        <div class="line"></div>

        <div class="text-center" style="margin-top: 10px;">
            <p>-- LUNAS --</p>
            <p style="font-size: 10px;">Terima kasih atas pembayaran Anda.<br>Simpan struk ini sebagai bukti sah.</p>
        </div>
    </div>

</body>
</html>
