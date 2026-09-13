<?php
// export_excel_dashboard.php — Export Laporan Dashboard Admin ke Excel (.xlsx / SpreadsheetML)
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once 'db.php';

function tabelAdaEx($conn, $table) {
    $t = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    return $r && mysqli_num_rows($r) > 0;
}
function kolomTersediaEx($conn, $table, $kandidat) {
    static $cache = [];
    if (!isset($cache[$table])) {
        $cols = [];
        $t = mysqli_real_escape_string($conn, $table);
        $r = mysqli_query($conn, "SHOW COLUMNS FROM `$t`");
        if ($r) { while ($row = mysqli_fetch_assoc($r)) $cols[] = $row['Field']; }
        $cache[$table] = $cols;
    }
    foreach ($kandidat as $c) {
        if (in_array($c, $cache[$table], true)) return $c;
    }
    return null;
}

$ada_peminjaman   = tabelAdaEx($conn, 'peminjaman');
$col_user         = $ada_peminjaman ? kolomTersediaEx($conn,'peminjaman',['user_id','anggota_id','id_user','id_anggota','member_id']) : null;
$col_buku         = $ada_peminjaman ? kolomTersediaEx($conn,'peminjaman',['buku_id','id_buku']) : null;
$col_pinjam       = $ada_peminjaman ? kolomTersediaEx($conn,'peminjaman',['waktu_pinjam','tgl_pinjam','tanggal_pinjam','created_at','tgl_peminjaman']) : null;
$col_jatuh_tempo  = $ada_peminjaman ? kolomTersediaEx($conn,'peminjaman',['batas_kembali','tgl_kembali','tanggal_kembali','due_date']) : null;
$col_dikembalikan = $ada_peminjaman ? kolomTersediaEx($conn,'peminjaman',['waktu_kembali','tgl_dikembalikan','tanggal_dikembalikan','returned_at','tgl_pengembalian']) : null;
$col_status       = $ada_peminjaman ? kolomTersediaEx($conn,'peminjaman',['status']) : null;
$skema_lengkap    = $ada_peminjaman && $col_user && $col_buku && $col_pinjam;

$tahun_sekarang = (int)date('Y');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : $tahun_sekarang;
if ($tahun < 2000 || $tahun > 2100) $tahun = $tahun_sekarang;

$nama_bulan = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
    5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
    9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
];

// ── Data ringkasan ──
$buku_res = mysqli_query($conn,"SELECT COUNT(*) AS c, COALESCE(SUM(stok),0) AS s FROM buku");
$buku_row = $buku_res ? mysqli_fetch_assoc($buku_res) : ['c'=>0,'s'=>0];
$total_buku = (int)$buku_row['c'];
$total_stok = (int)$buku_row['s'];

$anggota_res = mysqli_query($conn,"SELECT status, COUNT(*) AS c FROM users WHERE role='member' GROUP BY status");
$anggota_counts = ['pending'=>0,'approved'=>0,'rejected'=>0];
if ($anggota_res) { while ($row=mysqli_fetch_assoc($anggota_res)) $anggota_counts[$row['status']]=(int)$row['c']; }

$sedang_dipinjam=null; $terlambat=null; $peminjaman_bulan_ini=0; $anggota_pinjam_bulan_ini=0;
if ($skema_lengkap) {
    if ($col_status) {
        $q=mysqli_query($conn,"SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER($col_status) NOT IN ('dikembalikan','selesai','returned','kembali','ditolak','rejected')");
        $sedang_dipinjam=(int)(mysqli_fetch_assoc($q)['c']??0);
    } elseif ($col_dikembalikan) {
        $q=mysqli_query($conn,"SELECT COUNT(*) AS c FROM peminjaman WHERE $col_dikembalikan IS NULL");
        $sedang_dipinjam=(int)(mysqli_fetch_assoc($q)['c']??0);
    }
    if ($col_status && $col_jatuh_tempo) {
        $q=mysqli_query($conn,"SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER($col_status) NOT IN ('dikembalikan','selesai','returned','kembali','ditolak','rejected') AND $col_jatuh_tempo < NOW()");
        $terlambat=(int)(mysqli_fetch_assoc($q)['c']??0);
    } elseif ($col_status) {
        $q=mysqli_query($conn,"SELECT COUNT(*) AS c FROM peminjaman WHERE LOWER($col_status)='terlambat'");
        $terlambat=(int)(mysqli_fetch_assoc($q)['c']??0);
    }
    $q=mysqli_query($conn,"SELECT COUNT(*) AS c, COUNT(DISTINCT $col_user) AS a FROM peminjaman WHERE YEAR($col_pinjam)=YEAR(CURDATE()) AND MONTH($col_pinjam)=MONTH(CURDATE())");
    $row=$q?mysqli_fetch_assoc($q):null;
    $peminjaman_bulan_ini=(int)($row['c']??0);
    $anggota_pinjam_bulan_ini=(int)($row['a']??0);
}

// ── Rekap bulanan ──
$rekap_per_bulan=[];
if ($skema_lengkap) {
    $sql="SELECT MONTH($col_pinjam) AS bulan_num, COUNT(*) AS total_pinjam, COUNT(DISTINCT $col_user) AS anggota_pinjam";
    if ($col_dikembalikan) $sql.=", SUM(CASE WHEN $col_dikembalikan IS NOT NULL THEN 1 ELSE 0 END) AS total_kembali";
    $sql.=" FROM peminjaman WHERE YEAR($col_pinjam)=$tahun GROUP BY bulan_num";
    $res=mysqli_query($conn,$sql);
    if ($res) { while($row=mysqli_fetch_assoc($res)) $rekap_per_bulan[(int)$row['bulan_num']]=$row; }
}

// ── Denda ──
$denda_tersedia=$ada_peminjaman && kolomTersediaEx($conn,'peminjaman',['status_denda']) && kolomTersediaEx($conn,'peminjaman',['denda']);
$denda_belum_count=0; $denda_lunas_count=0; $denda_belum_rp=0; $denda_lunas_rp=0;
if ($denda_tersedia) {
    $q=mysqli_query($conn,"SELECT status_denda, COUNT(*) AS c, COALESCE(SUM(denda),0) AS t FROM peminjaman WHERE denda>0 GROUP BY status_denda");
    if ($q) { while($row=mysqli_fetch_assoc($q)) { if($row['status_denda']==='lunas'){$denda_lunas_count=(int)$row['c'];$denda_lunas_rp=(int)$row['t'];}else{$denda_belum_count+=(int)$row['c'];$denda_belum_rp+=(int)$row['t'];} } }
}

$anggota_unik_tahun=0;
if ($skema_lengkap) {
    $q=mysqli_query($conn,"SELECT COUNT(DISTINCT $col_user) AS c FROM peminjaman WHERE YEAR($col_pinjam)=$tahun");
    $anggota_unik_tahun=(int)(mysqli_fetch_assoc($q)['c']??0);
}

// ── Buku terpopuler ──
$buku_terpopuler=[];
if ($skema_lengkap) {
    $q=mysqli_query($conn,"SELECT b.judul, COUNT(*) AS jml FROM peminjaman p JOIN buku b ON b.id=p.$col_buku GROUP BY p.$col_buku,b.judul ORDER BY jml DESC LIMIT 10");
    if ($q) { while($row=mysqli_fetch_assoc($q)) $buku_terpopuler[]=$row; }
}

// ─── Helper functions ───
function xlsCell($val,$type='String'){
    $v=htmlspecialchars((string)$val,ENT_XML1,'UTF-8');
    if($type==='Number') return "<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">$v</Data></Cell>";
    return "<Cell ss:StyleID=\"s_wrap\"><Data ss:Type=\"String\">$v</Data></Cell>";
}
function xlsHeader($val){
    $v=htmlspecialchars((string)$val,ENT_XML1,'UTF-8');
    return "<Cell ss:StyleID=\"s_head\"><Data ss:Type=\"String\">$v</Data></Cell>";
}
function xlsTitle($val,$span=0){
    $v=htmlspecialchars((string)$val,ENT_XML1,'UTF-8');
    $m=$span>0?" ss:MergeAcross=\"$span\"":'';
    return "<Cell ss:StyleID=\"s_title\"$m><Data ss:Type=\"String\">$v</Data></Cell>";
}
function xlsHL($val,$type='String'){
    $v=htmlspecialchars((string)$val,ENT_XML1,'UTF-8');
    return "<Cell ss:StyleID=\"s_hl\"><Data ss:Type=\"$type\">$v</Data></Cell>";
}
function xlsSec($val,$span=1){
    $v=htmlspecialchars((string)$val,ENT_XML1,'UTF-8');
    return "<Cell ss:StyleID=\"s_sec\" ss:MergeAcross=\"$span\"><Data ss:Type=\"String\">$v</Data></Cell>";
}
function xlsFoot($val,$type='String'){
    $v=htmlspecialchars((string)$val,ENT_XML1,'UTF-8');
    return "<Cell ss:StyleID=\"s_foot\"><Data ss:Type=\"$type\">$v</Data></Cell>";
}
function emptyRow(){return "<Row ss:Height=\"8\"></Row>";}

$tgl_export=date('d F Y, H:i');
$filename="Laporan_AKSANOVA_{$tahun}_".date('Ymd_His').".xls";

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');
header('Pragma: public');
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Title>Laporan AKSA NOVA <?php echo $tahun; ?></Title>
  <Author>AKSA NOVA Library</Author>
</DocumentProperties>
<Styles>
  <Style ss:ID="s_title">
    <Font ss:Bold="1" ss:Size="13" ss:Color="#D8B878" ss:FontName="Calibri"/>
    <Interior ss:Color="#0D1117" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s_head">
    <Font ss:Bold="1" ss:Size="10" ss:Color="#1A1205" ss:FontName="Calibri"/>
    <Interior ss:Color="#D8B878" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
    <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C8A060"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C8A060"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C8A060"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C8A060"/></Borders>
  </Style>
  <Style ss:ID="s_wrap">
    <Font ss:Size="10" ss:FontName="Calibri"/>
    <Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/>
    <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DDDDDD"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DDDDDD"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DDDDDD"/></Borders>
  </Style>
  <Style ss:ID="s_num">
    <Font ss:Size="10" ss:FontName="Calibri"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DDDDDD"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DDDDDD"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#DDDDDD"/></Borders>
  </Style>
  <Style ss:ID="s_hl">
    <Font ss:Size="10" ss:Bold="1" ss:Color="#7B0000" ss:FontName="Calibri"/>
    <Interior ss:Color="#FFE5E5" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
    <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFAAAA"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFAAAA"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFAAAA"/></Borders>
  </Style>
  <Style ss:ID="s_sec">
    <Font ss:Bold="1" ss:Size="11" ss:Color="#D8B878" ss:FontName="Calibri"/>
    <Interior ss:Color="#1A1A2E" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="s_foot">
    <Font ss:Bold="1" ss:Size="10" ss:Color="#1A1205" ss:FontName="Calibri"/>
    <Interior ss:Color="#C8A060" ss:Pattern="Solid"/>
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    <Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#C8A060"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C8A060"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C8A060"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C8A060"/></Borders>
  </Style>
  <Style ss:ID="s_sub">
    <Font ss:Size="9" ss:Color="#888888" ss:FontName="Calibri" ss:Italic="1"/>
  </Style>
</Styles>

<!-- ════════ SHEET 1: Rekap Bulanan ════════ -->
<Worksheet ss:Name="Rekap Bulanan <?php echo $tahun; ?>">
<Table ss:DefaultColumnWidth="80">
  <Column ss:Width="160"/>
  <Column ss:Width="120"/>
  <Column ss:Width="120"/>
  <Column ss:Width="120"/>
  <Row ss:Height="32"><?php echo xlsTitle("LAPORAN PERPUSTAKAAN AKSA NOVA  \u2014  REKAP BULANAN $tahun",3); ?></Row>
  <Row ss:Height="16"><Cell ss:StyleID="s_sub" ss:MergeAcross="3"><Data ss:Type="String">Diekspor: <?php echo $tgl_export; ?></Data></Cell></Row>
  <?php echo emptyRow(); ?>
  <Row ss:Height="24">
    <?php echo xlsHeader('Bulan').xlsHeader('Total Peminjaman').xlsHeader('Anggota Meminjam').xlsHeader('Buku Dikembalikan'); ?>
  </Row>
  <?php
  $grand_pinjam=0; $grand_kembali=0;
  for($m=1;$m<=12;$m++):
      $data=$rekap_per_bulan[$m]??null;
      $tp=(int)($data['total_pinjam']??0);
      $ap=(int)($data['anggota_pinjam']??0);
      $tk=$col_dikembalikan?(int)($data['total_kembali']??0):null;
      $grand_pinjam+=$tp; $grand_kembali+=($tk??0);
      $is_now=($tahun===$tahun_sekarang&&$m===(int)date('n'));
  ?>
  <Row ss:Height="20">
    <?php echo xlsCell($nama_bulan[$m].' '.$tahun.($is_now?' \u2190 Bulan ini':'')); ?>
    <Cell ss:StyleID="s_num"><Data ss:Type="Number"><?php echo $tp; ?></Data></Cell>
    <Cell ss:StyleID="s_num"><Data ss:Type="Number"><?php echo $ap; ?></Data></Cell>
    <?php if($tk!==null): ?>
    <Cell ss:StyleID="s_num"><Data ss:Type="Number"><?php echo $tk; ?></Data></Cell>
    <?php else: ?>
    <Cell ss:StyleID="s_num"><Data ss:Type="String">-</Data></Cell>
    <?php endif; ?>
  </Row>
  <?php endfor; ?>
  <Row ss:Height="24">
    <?php echo xlsFoot("TOTAL $tahun"); ?>
    <?php echo xlsFoot($grand_pinjam,'Number'); ?>
    <?php echo xlsFoot($anggota_unik_tahun,'Number'); ?>
    <?php echo $col_dikembalikan?xlsFoot($grand_kembali,'Number'):xlsFoot('-'); ?>
  </Row>
</Table>
</Worksheet>

<!-- ════════ SHEET 2: Ringkasan & Denda ════════ -->
<Worksheet ss:Name="Ringkasan Perpustakaan">
<Table ss:DefaultColumnWidth="80">
  <Column ss:Width="220"/>
  <Column ss:Width="160"/>
  <Row ss:Height="32"><?php echo xlsTitle("RINGKASAN PERPUSTAKAAN AKSA NOVA",1); ?></Row>
  <Row ss:Height="16"><Cell ss:StyleID="s_sub" ss:MergeAcross="1"><Data ss:Type="String">Per tanggal: <?php echo $tgl_export; ?></Data></Cell></Row>
  <?php echo emptyRow(); ?>

  <Row ss:Height="22"><?php echo xlsSec("  KOLEKSI BUKU"); ?></Row>
  <Row ss:Height="20"><?php echo xlsHeader('Keterangan').xlsHeader('Jumlah'); ?></Row>
  <Row ss:Height="20"><?php echo xlsCell('Total Judul Buku')."<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">$total_buku</Data></Cell>"; ?></Row>
  <Row ss:Height="20"><?php echo xlsCell('Total Stok Fisik')."<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">$total_stok</Data></Cell>"; ?></Row>
  <Row ss:Height="20"><?php
    echo xlsCell('Sedang Dipinjam');
    echo $sedang_dipinjam!==null?"<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">$sedang_dipinjam</Data></Cell>":"<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"String\">-</Data></Cell>";
  ?></Row>
  <?php echo emptyRow(); ?>

  <Row ss:Height="22"><?php echo xlsSec("  KEANGGOTAAN"); ?></Row>
  <Row ss:Height="20"><?php echo xlsHeader('Keterangan').xlsHeader('Jumlah'); ?></Row>
  <Row ss:Height="20"><?php echo xlsCell('Anggota Aktif (Approved)')."<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">{$anggota_counts['approved']}</Data></Cell>"; ?></Row>
  <Row ss:Height="20"><?php echo xlsCell('Menunggu Persetujuan (Pending)')."<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">{$anggota_counts['pending']}</Data></Cell>"; ?></Row>
  <Row ss:Height="20"><?php echo xlsCell('Ditolak (Rejected)')."<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">{$anggota_counts['rejected']}</Data></Cell>"; ?></Row>
  <?php echo emptyRow(); ?>

  <Row ss:Height="22"><?php echo xlsSec("  AKTIVITAS PEMINJAMAN"); ?></Row>
  <Row ss:Height="20"><?php echo xlsHeader('Keterangan').xlsHeader('Jumlah'); ?></Row>
  <Row ss:Height="20"><?php echo xlsCell('Peminjaman Bulan Ini')."<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">$peminjaman_bulan_ini</Data></Cell>"; ?></Row>
  <Row ss:Height="20"><?php echo xlsCell("Total Peminjaman Tahun $tahun")."<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">$total_tahun_pinjam</Data></Cell>"; ?></Row>
  <Row ss:Height="20"><?php
    if($terlambat!==null&&$terlambat>0){echo xlsHL('Terlambat Kembali').xlsHL($terlambat,'Number');}
    else{echo xlsCell('Terlambat Kembali');echo $terlambat!==null?"<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"Number\">$terlambat</Data></Cell>":"<Cell ss:StyleID=\"s_num\"><Data ss:Type=\"String\">-</Data></Cell>";}
  ?></Row>
  <?php echo emptyRow(); ?>

  <Row ss:Height="22"><?php echo xlsSec("  DENDA"); ?></Row>
  <?php if(!$denda_tersedia): ?>
  <Row ss:Height="20"><Cell ss:MergeAcross="1"><Data ss:Type="String">Data denda belum tersedia.</Data></Cell></Row>
  <?php else: ?>
  <Row ss:Height="20"><?php echo xlsHeader('Keterangan').xlsHeader('Nominal'); ?></Row>
  <Row ss:Height="20"><?php
    if($denda_belum_count>0){echo xlsHL("Belum Dibayar ($denda_belum_count transaksi)").xlsHL('Rp '.number_format($denda_belum_rp,0,',','.'));}
    else{echo xlsCell("Belum Dibayar ($denda_belum_count transaksi)").xlsCell('Rp '.number_format($denda_belum_rp,0,',','.'));}
  ?></Row>
  <Row ss:Height="20"><?php echo xlsCell("Lunas ($denda_lunas_count transaksi)").xlsCell('Rp '.number_format($denda_lunas_rp,0,',','.')); ?></Row>
  <?php endif; ?>
</Table>
</Worksheet>

<!-- ════════ SHEET 3: Buku Terpopuler ════════ -->
<Worksheet ss:Name="Buku Terpopuler">
<Table ss:DefaultColumnWidth="80">
  <Column ss:Width="50"/>
  <Column ss:Width="300"/>
  <Column ss:Width="110"/>
  <Row ss:Height="32"><?php echo xlsTitle("BUKU PALING SERING DIPINJAM \u2014 AKSA NOVA",2); ?></Row>
  <Row ss:Height="16"><Cell ss:StyleID="s_sub" ss:MergeAcross="2"><Data ss:Type="String">Diekspor: <?php echo $tgl_export; ?></Data></Cell></Row>
  <?php echo emptyRow(); ?>
  <Row ss:Height="24"><?php echo xlsHeader('No').xlsHeader('Judul Buku').xlsHeader('Total Dipinjam'); ?></Row>
  <?php if(empty($buku_terpopuler)): ?>
  <Row ss:Height="20"><Cell ss:MergeAcross="2"><Data ss:Type="String">Belum ada data peminjaman.</Data></Cell></Row>
  <?php else: ?>
  <?php foreach($buku_terpopuler as $i=>$b): ?>
  <Row ss:Height="20">
    <Cell ss:StyleID="s_num"><Data ss:Type="Number"><?php echo $i+1; ?></Data></Cell>
    <?php echo xlsCell($b['judul']); ?>
    <Cell ss:StyleID="s_num"><Data ss:Type="Number"><?php echo (int)$b['jml']; ?></Data></Cell>
  </Row>
  <?php endforeach; ?>
  <?php endif; ?>
</Table>
</Worksheet>

</Workbook>