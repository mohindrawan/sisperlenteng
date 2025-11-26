<?php
require '../config/database.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) { echo json_encode([]); exit; }
$stmt = $pdo->prepare('SELECT bulan, tahun, harga FROM harga_komoditas WHERE id_komoditas=? ORDER BY tahun, bulan');
$stmt->execute([$id]); $rows = $stmt->fetchAll();
$data = [];
foreach($rows as $r){ $data[] = ['label'=> $r['tahun'].'-'.str_pad($r['bulan'],2,'0',STR_PAD_LEFT), 'harga'=> (float)$r['harga']]; }
header('Content-Type: application/json'); echo json_encode($data);


