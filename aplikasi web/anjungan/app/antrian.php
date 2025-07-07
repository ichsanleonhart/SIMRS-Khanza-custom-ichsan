<?php
require_once('../conf/conf.php');
date_default_timezone_set('Asia/Jakarta');


if (isset($_GET['p'])) {
    //jam reset antrian

    //kode poli yang ingin ditampilkan
    $jamreset = '23:00:00';

    switch ($_GET['p']) {
        case 'pengaturan':
            $_sql = "select nama_instansi,email from setting";
            $hasil = bukaquery($_sql);
            $data = array();
            while ($r = mysqli_fetch_array($hasil)) {
                $r['text'] = "Selamat Datang di Klinik Pratama Aster || Kepuasan Anda adalah Kebanggaan Kami";
                $data = $r;
            }
            echo json_encode($data);
            break;

        case 'panggil':
            $_sql = "SELECT nomor,loket FROM antripendaftaran_nomor WHERE status='1' ORDER BY jam ASC LIMIT 1";

            $hasil = bukaquery($_sql);
            $data = array();
            while ($r = mysqli_fetch_array($hasil)) {
                $data[] = $r;
                bukaquery2("UPDATE antripendaftaran_nomor SET status = '3' WHERE status='2'");
                bukaquery2("UPDATE antripendaftaran_nomor SET status = '2' WHERE nomor = '$r[nomor]'");
            }
            echo json_encode($data);
            break;


        case 'nomor':
            $_sql = "SELECT nomor,loket FROM antripendaftaran_nomor WHERE status < '3' AND status > '0'  ORDER BY jam ASC LIMIT 1";
            $hasil = bukaquery($_sql);
            $data = array();

            if (mysqli_num_rows($hasil) > 0) {
                while ($row = mysqli_fetch_array($hasil)) {
                    $data[] = $row;
                }
            } else {
                $row['nomor'] = '0';
                $row['loket'] = '-';
                $data[] = $row;
            }
            echo json_encode($data);
            break;

        case 'loket1':
            // Menginisialisasi array data di luar loop
            $data = array();

            $_sql = "select ifnull(MAX(CONVERT(antripendaftaran_nomor.nomor,signed)),0) as nomor, loket from antripendaftaran_nomor WHERE loket<>'' GROUP BY loket";
            $h = bukaquery($_sql);

            if (mysqli_num_rows($h) > 0) {
                while ($row = mysqli_fetch_array($h)) {
                    $data[] = $row;
                }
            } else {
                $row['nomor'] = '-';
                $row['loket'] = '-';
                $data[] = $row;
            }

            // Menampilkan hasil sebagai JSON
            echo json_encode($data);
            break;

        case 'getnomor':
            bukaquery2("delete from antripendaftaran_nomor where jam not like '" . date("Y-m-d") . "%' ");
            $_sql = "select ifnull(MAX(CONVERT(antripendaftaran_nomor.nomor,signed)),0) as nomor from antripendaftaran_nomor WHERE jam LIKE '" . date("Y-m-d") . " %' ";
            $hasil = bukaquery($_sql);
            $data = array();

            if (mysqli_num_rows($hasil) > 0) {
                while ($row = mysqli_fetch_array($hasil)) {
                    $_sql = "INSERT INTO antripendaftaran_nomor (nomor, status, jam) VALUES ('" . ($row['nomor'] + 1) . "','0','" . date("Y-m-d H:i:s") . "')";
                    $hasil = bukaquery($_sql);
                    $data[] = $row;
                }
            } else {
                $_sql = "INSERT INTO antripendaftaran_nomor (nomor, status, jam) VALUES ('1','0','" . date("Y-m-d H:i:s") . "')";
                $hasil = bukaquery($_sql);
                $data[] = 1;
            }

            echo json_encode($data);
            break;

        case 'ceknomorterakhir':
            $_sql = "select ifnull(MAX(CONVERT(antripendaftaran_nomor.nomor,signed)),0) as nomor from antripendaftaran_nomor WHERE jam LIKE '" . date("Y-m-d") . " %' ";
            $hasil = bukaquery($_sql);
            $data = array();

            while ($row = mysqli_fetch_array($hasil)) {
                $data[] = $row;
            }

            echo json_encode($data);
            break;
    }
}
