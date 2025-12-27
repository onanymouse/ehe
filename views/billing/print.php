<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk #<?php echo $inv['invoice_number']; ?></title>
    <style>
        /* Reset CSS agar pas di kertas thermal */
        body {
            font-family: 'Courier New', Courier, monospace; /* Font struk */
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 100%;
            max-width: 80mm; /* Lebar maksimal kertas 80mm */
            color: #000;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0; font-size: 16px; font-weight: bold; }
        .header p { margin: 2px 0; font-size: 10px; }
        
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        
        .info-table { width: 100%; font-size: 12px; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .label { width: 35%; }
        
        .total-box { 
            text-align: right; 
            font-size: 14px; 
            font-weight: bold; 
            margin-top: 10px; 
        }
        
        .footer { 
            text-align: center; 
            margin-top: 20px; 
            font-size: 10px; 
            font-style: italic;
        }

        /* Tombol Print (Hilang saat diprint) */
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #eee;
            border-bottom: 1px solid #ccc;
        }
        @media print {
            .no-print { display: none; }
            @page { margin: 0; }
            body { padding: 5px; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" style="padding:10px 20px; font-weight:bold; cursor:pointer;">🖨️ CETAK STRUK</button>
    </div>

    <div class="header">
        <h2>INTERNET WIFI</h2>
        <p>Jalan Raya Internet No. 1</p>
        <p>WA: 0812-3456-7890</p>
    </div>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td class="label">No. Inv</td>
            <td>: <?php echo $inv['invoice_number']; ?></td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td>: <?php echo date('d/m/Y H:i', strtotime($inv['created_at'])); ?></td>
        </tr>
        <tr>
            <td class="label">Pelanggan</td>
            <td>: <?php echo strtoupper($inv['customer_name']); ?></td>
        </tr>
        <tr>
            <td class="label">ID Pel</td>
            <td>: <?php echo $inv['customer_code']; ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td style="font-weight:bold;">Pembayaran Internet</td>
        </tr>
        <tr>
            <td><?php echo $inv['package_name']; ?> (<?php echo $inv['period_month']; ?>)</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="total-box">
        TOTAL: Rp <?php echo number_format($inv['amount'], 0, ',', '.'); ?>
    </div>
    
    <div style="text-align:right; font-size:10px; margin-top:5px;">
        Status: <?php echo ($inv['status'] == 'paid') ? 'LUNAS' : 'BELUM LUNAS'; ?><br>
        Admin: <?php echo $inv['admin_name'] ?? '-'; ?>
    </div>

    <div class="footer">
        <p>Terima Kasih atas kepercayaan Anda.</p>
        <p>Simpan struk ini sebagai bukti pembayaran yang sah.</p>
    </div>

</body>
</html>