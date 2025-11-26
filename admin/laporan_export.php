<?php
require '../config/database.php'; require '../config/auth.php'; if(!is_admin()) exit;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0; $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y'); $desa_id = isset($_GET['desa_id']) ? (int)$_GET['desa_id'] : 0;
$where=[]; $params=[]; if($bulan){$where[]='h.bulan=?'; $params[]=$bulan;} if($tahun){$where[]='h.tahun=?'; $params[]=$tahun;} if($desa_id){$where[]='k.id_desa=?'; $params[]=$desa_id;}
$q='SELECT h.*, k.nama_komoditas, d.nama as desa FROM harga_komoditas h LEFT JOIN komoditas k ON h.id_komoditas=k.id_komoditas LEFT JOIN desa d ON k.id_desa=d.id_desa'; if(count($where)) $q.=' WHERE '.implode(' AND ', $where); $q.=' ORDER BY h.tahun DESC, h.bulan DESC';
$stmt=$pdo->prepare($q); $stmt->execute($params); $rows=$stmt->fetchAll();
header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="laporan_harga.csv"');
$out = fopen('php://output','w'); fputcsv($out, ['Komoditas','Desa','Bulan','Tahun','Harga','Tanggal Update']);
foreach($rows as $r){ fputcsv($out, [$r['nama_komoditas'],$r['desa'],$r['bulan'],$r['tahun'],$r['harga'],$r['tanggal_update']]); }
fclose($out);


