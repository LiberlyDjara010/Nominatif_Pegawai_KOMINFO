<?php
require 'auth.php';
include "koneksi.php";

requireCanManageData();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids'])) {

    $ids = explode(',', $_POST['ids']);

    $ids = array_map('intval', $ids);

    $ids = array_filter($ids);

    if (count($ids) > 0) {

        $idList = implode(',', $ids);

        mysqli_query(
            $conn,
            "DELETE FROM pegawai WHERE id IN ($idList)"

        );

        $jumlah = mysqli_affected_rows($conn);

        header(
            "Location: pegawai.php?flash=hapus_massal&jumlah=" . $jumlah
        );
        exit;
    }

    header("Location: pegawai.php");
    exit;
}
?>