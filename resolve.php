<?php

$file = 'src/simrskhanza/DlgIGD.java';
$content = file_get_contents($file);
$content = str_replace("\r\n", "\n", $content); // Normalize newlines

$replacements = [
    1 => "import fungsi.WarnaTableRawatInap; //tambahan ichsan\nimport fungsi.catatanpasien;\nimport fungsi.closingkasir;",
    
    2 => "    private sekuel Sequel = new sekuel();\n    private validasi Valid = new validasi();\n    private Connection koneksi = koneksiDB.condb();\n    private DlgPasien pasien = new DlgPasien(null, false);\n    private PreparedStatement ps, ps3, pscaripiutang;\n    private DlgPeresepanDokter resepobat;",
    
    3 => "    private double biaya = 0, biayabaru = 0, biayalama = 0;\n    private String kdigd = \"\", nosisrute = \"\", aktifkanparsial = \"no\", URUTNOREG = \"\", terbitsep = \"\",\n            status = \"Baru\", alamatperujuk = \"-\", umur = \"0\", sttsumur = \"Th\", IPPRINTERTRACER = \"\",\n            norawatdipilih = \"\", normdipilih = \"\";",

    4 => "\n        if(closingkasir.getWajibClosingKasir().equals(\"\")){\n            closingkasir.SetClosingKasir();\n        }\n        \n        if(catatanpasien.getTampilkanCatatan().equals(\"\")){\n            catatanpasien.SetCatatanPasien();\n        }",
    
    5 => "            if (evt.getClickCount() == 1) {\n                if (norawatdipilih.equals(\"\")) {\n                    i = tbPetugas.getSelectedColumn();\n                    if (i == 8) {\n                        if (catatanpasien.getTampilkanCatatan().equals(\"Yes\")) {",

    6 => "                        dlgrwjl.SetPj(tbPetugas.getValueAt(tbPetugas.getSelectedRow(), 19).toString());\n                        dlgrwjl.setNoRm(TNoRw.getText(), DTPCari1.getDate(), DTPCari2.getDate());\n                        dlgrwjl.isCek();",
    
    8 => "        } else {\n            if (Sequel.cariInteger(\"select count(kamar_inap.no_rawat) from kamar_inap where kamar_inap.no_rawat=?\",\n                    TNoRw.getText()) > 0) {\n                JOptionPane.showMessageDialog(null, \"Maaf, Pasien sudah masuk Kamar Inap. Gunakan billing Ranap..!!!\");\n            } else {\n                if (resepobat == null || !resepobat.isDisplayable()) {\n                    resepobat = new DlgPeresepanDokter(null, false);\n                    resepobat.setDefaultCloseOperation(WindowConstants.DISPOSE_ON_CLOSE);\n                    resepobat.addWindowListener(new WindowAdapter() {\n                        @Override\n                        public void windowClosed(WindowEvent e) {\n                            resepobat = null;\n                        }\n                    }); \n\n                    resepobat.setSize(internalFrame1.getWidth() - 20, internalFrame1.getHeight() - 20);\n                    resepobat.setLocationRelativeTo(internalFrame1);\n                }\n                if (resepobat == null) return;\n                if (!resepobat.isVisible()) {\n                    resepobat.setNoRm(TNoRw.getText(), new Date(), CmbJam.getSelectedItem().toString(),\n                            CmbMenit.getSelectedItem().toString(), CmbDetik.getSelectedItem().toString(),\n                            KdDokter.getText(), TDokter.getText(), \"ralan\");\n                    resepobat.isCek();\n                    resepobat.tampilobat();\n                }\n\n                if (resepobat.isVisible()) {\n                    resepobat.toFront();\n                    return;\n                }\n                resepobat.setVisible(true);\n            }",
    
    9 => "    private void isPas() {\n        if (closingkasir.getWajibClosingKasir().equals(\"Yes\")) {\n            if (Sequel.cariInteger(\n                    \"select count(reg_periksa.no_rkm_medis) from reg_periksa where reg_periksa.no_rkm_medis=? and reg_periksa.status_bayar='Belum Bayar' and reg_periksa.stts<>'Batal'\",\n                    TNoRM.getText()) > 0) {\n                JOptionPane.showMessageDialog(rootPane,\n                        \"Maaf, pasien pada kunjungan sebelumnya memiliki tagihan yang belum di closing.\\nSilahkan konfirmasi dengan pihak kasir.. !!\");\n            } else {\n                if (catatanpasien.getTampilkanCatatan().equals(\"Yes\")) {\n                    if (Sequel.cariInteger(\n                            \"select count(catatan_pasien.no_rkm_medis) from catatan_pasien where catatan_pasien.no_rkm_medis=?\",\n                            TNoRM.getText()) > 0) {",
    
    10 => "        } else {\n            if (catatanpasien.getTampilkanCatatan().equals(\"Yes\")) {\n                if (Sequel.cariInteger(\n                        \"select count(catatan_pasien.no_rkm_medis) from catatan_pasien where catatan_pasien.no_rkm_medis=?\",\n                        TNoRM.getText()) > 0) {"
];

$count = 0;
$new_content = preg_replace_callback('/<<<<<<< HEAD\n(.*?)\n=======\n(.*?)\n>>>>>>> upstream\/master\n?/s', function($match) use (&$count, $replacements) {
    $count++;
    if (isset($replacements[$count])) {
        return $replacements[$count];
    }
    // Default to handling #7 (which was just keeping head)
    return $match[1];
}, $content);

file_put_contents($file, $new_content);
echo "Conflicts resolved: " . $count . "\n";

?>
