import re

with open('src/simrskhanza/DlgIGD.java', 'r', encoding='utf-8') as f:
    text = f.read()

def replacer(match):
    replacer.count += 1
    idx = replacer.count
    head = match.group(1)
    
    if idx == 1:
        return "import fungsi.WarnaTableRawatInap; //tambahan ichsan\nimport fungsi.catatanpasien;\nimport fungsi.closingkasir;"
    
    elif idx == 2:
        return """    private sekuel Sequel = new sekuel();
    private validasi Valid = new validasi();
    private Connection koneksi = koneksiDB.condb();
    private DlgPasien pasien = new DlgPasien(null, false);
    private PreparedStatement ps, ps3, pscaripiutang;
    private DlgPeresepanDokter resepobat;"""

    elif idx == 3:
        return """    private double biaya = 0, biayabaru = 0, biayalama = 0;
    private String kdigd = "", nosisrute = "", aktifkanparsial = "no", URUTNOREG = "", terbitsep = "",
            status = "Baru", alamatperujuk = "-", umur = "0", sttsumur = "Th", IPPRINTERTRACER = "",
            norawatdipilih = "", normdipilih = "";"""

    elif idx == 4:
        return """
        if(closingkasir.getWajibClosingKasir().equals("")){
            closingkasir.SetClosingKasir();
        }
        
        if(catatanpasien.getTampilkanCatatan().equals("")){
            catatanpasien.SetCatatanPasien();
        }"""
        
    elif idx == 5:
        return """            if (evt.getClickCount() == 1) {
                if (norawatdipilih.equals("")) {
                    i = tbPetugas.getSelectedColumn();
                    if (i == 8) {
                        if (catatanpasien.getTampilkanCatatan().equals("Yes")) {"""

    elif idx == 6:
        return """                        dlgrwjl.SetPj(tbPetugas.getValueAt(tbPetugas.getSelectedRow(), 19).toString());
                        dlgrwjl.setNoRm(TNoRw.getText(), DTPCari1.getDate(), DTPCari2.getDate());
                        dlgrwjl.isCek();"""

    elif idx == 7:
        return head

    elif idx == 8:
        return """        } else {
            if (Sequel.cariInteger("select count(kamar_inap.no_rawat) from kamar_inap where kamar_inap.no_rawat=?",
                    TNoRw.getText()) > 0) {
                JOptionPane.showMessageDialog(null, "Maaf, Pasien sudah masuk Kamar Inap. Gunakan billing Ranap..!!!");
            } else {
                if (resepobat == null || !resepobat.isDisplayable()) {
                    resepobat = new DlgPeresepanDokter(null, false);
                    resepobat.setDefaultCloseOperation(WindowConstants.DISPOSE_ON_CLOSE);
                    resepobat.addWindowListener(new WindowAdapter() {
                        @Override
                        public void windowClosed(WindowEvent e) {
                            resepobat = null;
                        }
                    }); 

                    resepobat.setSize(internalFrame1.getWidth() - 20, internalFrame1.getHeight() - 20);
                    resepobat.setLocationRelativeTo(internalFrame1);
                }
                if (resepobat == null) return;
                if (!resepobat.isVisible()) {
                    resepobat.setNoRm(TNoRw.getText(), new Date(), CmbJam.getSelectedItem().toString(),
                            CmbMenit.getSelectedItem().toString(), CmbDetik.getSelectedItem().toString(),
                            KdDokter.getText(), TDokter.getText(), "ralan");
                    resepobat.isCek();
                    resepobat.tampilobat();
                }

                if (resepobat.isVisible()) {
                    resepobat.toFront();
                    return;
                }
                resepobat.setVisible(true);
            }"""

    elif idx == 9:
        return """    private void isPas() {
        if (closingkasir.getWajibClosingKasir().equals("Yes")) {
            if (Sequel.cariInteger(
                    "select count(reg_periksa.no_rkm_medis) from reg_periksa where reg_periksa.no_rkm_medis=? and reg_periksa.status_bayar='Belum Bayar' and reg_periksa.stts<>'Batal'",
                    TNoRM.getText()) > 0) {
                JOptionPane.showMessageDialog(rootPane,
                        "Maaf, pasien pada kunjungan sebelumnya memiliki tagihan yang belum di closing.\\nSilahkan konfirmasi dengan pihak kasir.. !!");
            } else {
                if (catatanpasien.getTampilkanCatatan().equals("Yes")) {
                    if (Sequel.cariInteger(
                            "select count(catatan_pasien.no_rkm_medis) from catatan_pasien where catatan_pasien.no_rkm_medis=?",
                            TNoRM.getText()) > 0) {"""

    elif idx == 10:
        return """        } else {
            if (catatanpasien.getTampilkanCatatan().equals("Yes")) {
                if (Sequel.cariInteger(
                        "select count(catatan_pasien.no_rkm_medis) from catatan_pasien where catatan_pasien.no_rkm_medis=?",
                        TNoRM.getText()) > 0) {"""
    
    return match.group(0)

replacer.count = 0

pattern = r'<<<<<<< HEAD\n(.*?)\n=======\n(.*?)\n>>>>>>> upstream/master\n?'
new_text = re.sub(pattern, replacer, text, flags=re.DOTALL)

with open('src/simrskhanza/DlgIGD.java', 'w', encoding='utf-8') as f:
    f.write(new_text)

print(f"Conflicts resolved: {replacer.count}")
