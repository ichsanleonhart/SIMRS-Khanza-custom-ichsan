/*
 Kontribusi dari mas Haris, RS Bhayangkara Nganjuk
 */

/*
 * DlgRujuk.java
 *
 * Created on 31 Mei 10, 20:19:56
 */

package kepegawaian;

import fungsi.WarnaTable;
import fungsi.batasInput;
import fungsi.koneksiDB;
import fungsi.koneksiDBWa_mgm;
import fungsi.sekuel;
import fungsi.validasi;
import fungsi.akses;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.event.KeyEvent;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.io.File;
import java.io.FileInputStream;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.Date;
import java.util.HashMap;
import java.util.Map;
import javax.swing.JOptionPane;
import javax.swing.JTable;
import javax.swing.event.DocumentEvent;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableColumn;
import org.apache.http.HttpResponse;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.entity.mime.HttpMultipartMode;
import org.apache.http.entity.mime.MultipartEntity;
import org.apache.http.entity.mime.content.InputStreamBody;
import org.apache.http.impl.client.DefaultHttpClient;


/**
 *
 * @author perpustakaan
 */
public final class PengajuanCutiAdmin extends javax.swing.JDialog {
    private final DefaultTableModel tabMode;
    private Connection koneksi=koneksiDB.condb();
    private  Connection koneksiwa;
    private sekuel Sequel=new sekuel();
    private validasi Valid=new validasi();
    private PreparedStatement ps;
    private ResultSet rs;
    private int i=0,pilihan=0,reply=0;  //tambahan [reply=0] by ichsan untuk layar tampilan yes / no;
    private double total=0;
    private String FileName ="", lokasifilepdf="", SQLException=""; //tambahan ichsan FileName ="",kodeberkas="", lokasifile="" SQLException=""
    private boolean isHRD = false; // <-- TAMBAHAN UNTUK MENGECEK USER LOGIN SEBAGAI HRD ATAU BUKAN
    /** Creates new form DlgRujuk
     * @param parent
     * @param modal */
    public PengajuanCutiAdmin(java.awt.Frame parent, boolean modal) {
        super(parent, modal);
        initComponents();
        this.setLocation(8,1);
        setSize(628,674);
        isHRD = cekApakahHRD(); //cek apakah user login sebagai HRD? - Ichsan

        tabMode=new DefaultTableModel(null,new Object[]{
                "No.Pengajuan","Tanggal","Tgl Awal","Tgl Akhir","NIK","Diajukan Oleh","Bidang","Departemen","Jenis Cuti","Alamat Tujuan",
                "Jml Cuti","Kepentingan Cuti","NIK P.J.","P.J. Terkait", "Status Atasan", "Waktu Setuju Atasan", "Status HRD", "Waktu Setuju HRD "  //tambahan "P.J. Terkait", "Status Atasan", "Status HRD" - ichsan
            }){
              @Override public boolean isCellEditable(int rowIndex, int colIndex){return false;}
        };
        tbObat.setModel(tabMode);

        //tbObat.setDefaultRenderer(Object.class, new WarnaTable(panelJudul.getBackground(),tbObat.getBackground()));
        tbObat.setPreferredScrollableViewportSize(new Dimension(500,500));
        tbObat.setAutoResizeMode(JTable.AUTO_RESIZE_OFF);

        for (i = 0; i < 18; i++) {
            TableColumn column = tbObat.getColumnModel().getColumn(i);
            if(i==0){
                column.setPreferredWidth(85);
            }else if(i==1){
                column.setPreferredWidth(65);
            }else if(i==2){
                column.setPreferredWidth(65);
            }else if(i==3){
                column.setPreferredWidth(65);
            }else if(i==4){
                column.setPreferredWidth(85);
            }else if(i==5){
                column.setPreferredWidth(160);
            }else if(i==6){
                column.setPreferredWidth(70);
            }else if(i==7){
                column.setPreferredWidth(70);
            }else if(i==8){
                column.setPreferredWidth(110);
            }else if(i==9){
                column.setPreferredWidth(170);
            }else if(i==10){
                column.setPreferredWidth(80);
            }else if(i==11){
                column.setPreferredWidth(170);
            }else if(i==12){
                column.setPreferredWidth(85);
            }else if(i==13){
                column.setPreferredWidth(160);
            }else if(i==14){ // Status Atasan
                column.setPreferredWidth(95);
            }else if(i==15){ // Waktu Setuju Atasan
                column.setPreferredWidth(130);
            }else if(i==16){ // Status HRD
                column.setPreferredWidth(95);
            }else if(i==17){ // Waktu Setuju HRD
               column.setPreferredWidth(130);
            }
        }
        tbObat.setDefaultRenderer(Object.class, new WarnaTable());

        Kepentingan.setDocument(new batasInput((int)70).getKata(Kepentingan));
        NoPengajuan.setDocument(new batasInput((int)17).getKata(NoPengajuan));
        Alamat.setDocument(new batasInput((int)100).getKata(Alamat));
        TCari.setDocument(new batasInput((byte)100).getKata(TCari));
        Jumlah.setDocument(new batasInput((byte)3).getOnlyAngka(Jumlah));
        if(koneksiDB.CARICEPAT().equals("aktif")){
            TCari.getDocument().addDocumentListener(new javax.swing.event.DocumentListener(){
                @Override
                public void insertUpdate(DocumentEvent e) {
                    if(TCari.getText().length()>2){
                        tampil();
                    }
                }
                @Override
                public void removeUpdate(DocumentEvent e) {
                    if(TCari.getText().length()>2){
                        tampil();
                    }
                }
                @Override
                public void changedUpdate(DocumentEvent e) {
                    if(TCari.getText().length()>2){
                        tampil();
                    }
                }
            });
        }
        
        petugas.addWindowListener(new WindowListener() {
            @Override
            public void windowOpened(WindowEvent e) {}
            @Override
            public void windowClosing(WindowEvent e) {}
            @Override
            public void windowClosed(WindowEvent e) {
                if(petugas.getTable().getSelectedRow()!= -1){
                    if(pilihan==1){
                        KdPetugas.setText(petugas.getTable().getValueAt(petugas.getTable().getSelectedRow(),0).toString());
                        NmPetugas.setText(petugas.getTable().getValueAt(petugas.getTable().getSelectedRow(),1).toString());
                        Bidang.setText(petugas.getTable().getValueAt(petugas.getTable().getSelectedRow(),6).toString());
                        Departemen.setText(petugas.getTable().getValueAt(petugas.getTable().getSelectedRow(),5).toString());
                        btnPetugas.requestFocus();
                    }else if(pilihan==2){
                        KdPetugasPJ.setText(petugas.getTable().getValueAt(petugas.getTable().getSelectedRow(),0).toString());
                        NmPetugasPJ.setText(petugas.getTable().getValueAt(petugas.getTable().getSelectedRow(),1).toString());
                        btnPetugasPJ.requestFocus();
                    }
                }   
            }
            @Override
            public void windowIconified(WindowEvent e) {}
            @Override
            public void windowDeiconified(WindowEvent e) {}
            @Override
            public void windowActivated(WindowEvent e) {}
            @Override
            public void windowDeactivated(WindowEvent e) {}
        });
        
        ChkInput.setSelected(false);
        isForm();
    }
    
    private boolean cekApakahHRD() {
        boolean hrd = false;
        try (PreparedStatement p = koneksi.prepareStatement(
                "SELECT jbtn FROM pegawai WHERE nik = ? AND jbtn LIKE ?")) {
            p.setString(1, akses.getkode()); // Mengambil NIK user yang login
            p.setString(2, "%HRD%");
            try (ResultSet r = p.executeQuery()) {
                if (r.next()) {
                    hrd = true; // Jika ditemukan, set status hrd menjadi true
                }
            }
        } catch (Exception e) {
            System.out.println("Gagal cek jabatan HRD: " + e);
        }
        return hrd;
    }

    private DlgCariPegawai petugas=new DlgCariPegawai(null,false);

    /** This method is called from within the constructor to
     * initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is
     * always regenerated by the Form Editor.
     */
    @SuppressWarnings("unchecked")
    // <editor-fold defaultstate="collapsed" desc="Generated Code">//GEN-BEGIN:initComponents
    private void initComponents() {

        jPopupMenu1 = new javax.swing.JPopupMenu();
        MnCetakCuti = new javax.swing.JMenuItem();
        internalFrame1 = new widget.InternalFrame();
        Scroll = new widget.ScrollPane();
        tbObat = new widget.Table();
        jPanel3 = new javax.swing.JPanel();
        panelGlass8 = new widget.panelisi();
        BtnSimpan = new widget.Button();
        BtnBatal = new widget.Button();
        BtnHapus = new widget.Button();
        BtnEdit = new widget.Button();
        BtnPrint = new widget.Button();
        jLabel7 = new widget.Label();
        LCount = new widget.Label();
        BtnKeluar = new widget.Button();
        panelGlass9 = new widget.panelisi();
        jLabel19 = new widget.Label();
        DTPCari1 = new widget.Tanggal();
        jLabel21 = new widget.Label();
        DTPCari2 = new widget.Tanggal();
        jLabel6 = new widget.Label();
        TCari = new widget.TextBox();
        BtnCari = new widget.Button();
        BtnAll = new widget.Button();
        jLabel18 = new widget.Label();
        LCount1 = new widget.Label();
        PanelInput = new javax.swing.JPanel();
        FormInput = new widget.PanelBiasa();
        jLabel8 = new widget.Label();
        Tanggal = new widget.Tanggal();
        jLabel5 = new widget.Label();
        KdPetugas = new widget.TextBox();
        btnPetugas = new widget.Button();
        NmPetugas = new widget.TextBox();
        scrollPane1 = new widget.ScrollPane();
        Alamat = new widget.TextArea();
        jLabel9 = new widget.Label();
        jLabel3 = new widget.Label();
        NoPengajuan = new widget.TextBox();
        jLabel20 = new widget.Label();
        Urgensi = new widget.ComboBox();
        jLabel4 = new widget.Label();
        Kepentingan = new widget.TextBox();
        jLabel11 = new widget.Label();
        Jumlah = new widget.TextBox();
        jLabel15 = new widget.Label();
        Bidang = new widget.TextBox();
        jLabel16 = new widget.Label();
        Departemen = new widget.TextBox();
        jLabel17 = new widget.Label();
        KdPetugasPJ = new widget.TextBox();
        NmPetugasPJ = new widget.TextBox();
        btnPetugasPJ = new widget.Button();
        jLabel14 = new widget.Label();
        Tgl1 = new widget.Tanggal();
        jLabel22 = new widget.Label();
        Tgl2 = new widget.Tanggal();
        jLabel12 = new widget.Label();
        jLabel23 = new widget.Label();
        Status = new widget.ComboBox();
        ChkInput = new widget.CekBox();

        jPopupMenu1.setName("jPopupMenu1"); // NOI18N

        MnCetakCuti.setText("Cetak Approval Surat Cuti");
        MnCetakCuti.setName("MnCetakCuti"); // NOI18N
        MnCetakCuti.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                MnCetakCutiActionPerformed(evt);
            }
        });
        jPopupMenu1.add(MnCetakCuti);

        setDefaultCloseOperation(javax.swing.WindowConstants.DISPOSE_ON_CLOSE);
        setUndecorated(true);
        setResizable(false);
        addWindowListener(new java.awt.event.WindowAdapter() {
            public void windowOpened(java.awt.event.WindowEvent evt) {
                formWindowOpened(evt);
            }
        });

        internalFrame1.setBorder(javax.swing.BorderFactory.createTitledBorder(javax.swing.BorderFactory.createLineBorder(new java.awt.Color(240, 245, 235)), "::[ Pengajuan Cuti Pegawai ]::", javax.swing.border.TitledBorder.DEFAULT_JUSTIFICATION, javax.swing.border.TitledBorder.DEFAULT_POSITION, new java.awt.Font("Tahoma", 0, 11), new java.awt.Color(50, 50, 50))); // NOI18N
        internalFrame1.setFont(new java.awt.Font("Tahoma", 2, 12)); // NOI18N
        internalFrame1.setName("internalFrame1"); // NOI18N
        internalFrame1.setLayout(new java.awt.BorderLayout(1, 1));

        Scroll.setName("Scroll"); // NOI18N
        Scroll.setOpaque(true);
        Scroll.setPreferredSize(new java.awt.Dimension(452, 200));

        tbObat.setAutoCreateRowSorter(true);
        tbObat.setToolTipText("Silahkan klik untuk memilih data yang mau diedit ataupun dihapus");
        tbObat.setComponentPopupMenu(jPopupMenu1);
        tbObat.setName("tbObat"); // NOI18N
        tbObat.addMouseListener(new java.awt.event.MouseAdapter() {
            public void mouseClicked(java.awt.event.MouseEvent evt) {
                tbObatMouseClicked(evt);
            }
        });
        tbObat.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyReleased(java.awt.event.KeyEvent evt) {
                tbObatKeyReleased(evt);
            }
        });
        Scroll.setViewportView(tbObat);

        internalFrame1.add(Scroll, java.awt.BorderLayout.CENTER);

        jPanel3.setName("jPanel3"); // NOI18N
        jPanel3.setOpaque(false);
        jPanel3.setPreferredSize(new java.awt.Dimension(44, 100));
        jPanel3.setLayout(new java.awt.BorderLayout(1, 1));

        panelGlass8.setName("panelGlass8"); // NOI18N
        panelGlass8.setPreferredSize(new java.awt.Dimension(44, 44));
        panelGlass8.setLayout(new java.awt.FlowLayout(java.awt.FlowLayout.LEFT, 5, 9));

        BtnSimpan.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/save-16x16.png"))); // NOI18N
        BtnSimpan.setMnemonic('S');
        BtnSimpan.setText("Simpan");
        BtnSimpan.setToolTipText("Alt+S");
        BtnSimpan.setName("BtnSimpan"); // NOI18N
        BtnSimpan.setPreferredSize(new java.awt.Dimension(100, 30));
        BtnSimpan.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnSimpanActionPerformed(evt);
            }
        });
        BtnSimpan.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnSimpanKeyPressed(evt);
            }
        });
        panelGlass8.add(BtnSimpan);

        BtnBatal.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/Cancel-2-16x16.png"))); // NOI18N
        BtnBatal.setMnemonic('B');
        BtnBatal.setText("Baru");
        BtnBatal.setToolTipText("Alt+B");
        BtnBatal.setName("BtnBatal"); // NOI18N
        BtnBatal.setPreferredSize(new java.awt.Dimension(100, 30));
        BtnBatal.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnBatalActionPerformed(evt);
            }
        });
        BtnBatal.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnBatalKeyPressed(evt);
            }
        });
        panelGlass8.add(BtnBatal);

        BtnHapus.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/stop_f2.png"))); // NOI18N
        BtnHapus.setMnemonic('H');
        BtnHapus.setText("Hapus");
        BtnHapus.setToolTipText("Alt+H");
        BtnHapus.setName("BtnHapus"); // NOI18N
        BtnHapus.setPreferredSize(new java.awt.Dimension(100, 30));
        BtnHapus.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnHapusActionPerformed(evt);
            }
        });
        BtnHapus.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnHapusKeyPressed(evt);
            }
        });
        panelGlass8.add(BtnHapus);

        BtnEdit.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/inventaris.png"))); // NOI18N
        BtnEdit.setMnemonic('G');
        BtnEdit.setText("Ganti");
        BtnEdit.setToolTipText("Alt+G");
        BtnEdit.setName("BtnEdit"); // NOI18N
        BtnEdit.setPreferredSize(new java.awt.Dimension(100, 30));
        BtnEdit.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnEditActionPerformed(evt);
            }
        });
        BtnEdit.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnEditKeyPressed(evt);
            }
        });
        panelGlass8.add(BtnEdit);

        BtnPrint.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/b_print.png"))); // NOI18N
        BtnPrint.setMnemonic('T');
        BtnPrint.setText("Cetak");
        BtnPrint.setToolTipText("Alt+T");
        BtnPrint.setName("BtnPrint"); // NOI18N
        BtnPrint.setPreferredSize(new java.awt.Dimension(100, 30));
        BtnPrint.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnPrintActionPerformed(evt);
            }
        });
        BtnPrint.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnPrintKeyPressed(evt);
            }
        });
        panelGlass8.add(BtnPrint);

        jLabel7.setText("Record :");
        jLabel7.setName("jLabel7"); // NOI18N
        jLabel7.setPreferredSize(new java.awt.Dimension(70, 23));
        panelGlass8.add(jLabel7);

        LCount.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        LCount.setText("0");
        LCount.setName("LCount"); // NOI18N
        LCount.setPreferredSize(new java.awt.Dimension(70, 23));
        panelGlass8.add(LCount);

        BtnKeluar.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/exit.png"))); // NOI18N
        BtnKeluar.setMnemonic('K');
        BtnKeluar.setText("Keluar");
        BtnKeluar.setToolTipText("Alt+K");
        BtnKeluar.setName("BtnKeluar"); // NOI18N
        BtnKeluar.setPreferredSize(new java.awt.Dimension(100, 30));
        BtnKeluar.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnKeluarActionPerformed(evt);
            }
        });
        BtnKeluar.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnKeluarKeyPressed(evt);
            }
        });
        panelGlass8.add(BtnKeluar);

        jPanel3.add(panelGlass8, java.awt.BorderLayout.CENTER);

        panelGlass9.setName("panelGlass9"); // NOI18N
        panelGlass9.setPreferredSize(new java.awt.Dimension(44, 44));
        panelGlass9.setLayout(new java.awt.FlowLayout(java.awt.FlowLayout.LEFT, 5, 9));

        jLabel19.setText("Tanggal :");
        jLabel19.setName("jLabel19"); // NOI18N
        jLabel19.setPreferredSize(new java.awt.Dimension(55, 23));
        panelGlass9.add(jLabel19);

        DTPCari1.setForeground(new java.awt.Color(50, 70, 50));
        DTPCari1.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "27-08-2025" }));
        DTPCari1.setDisplayFormat("dd-MM-yyyy");
        DTPCari1.setName("DTPCari1"); // NOI18N
        DTPCari1.setOpaque(false);
        DTPCari1.setPreferredSize(new java.awt.Dimension(90, 23));
        panelGlass9.add(DTPCari1);

        jLabel21.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        jLabel21.setText("s.d.");
        jLabel21.setName("jLabel21"); // NOI18N
        jLabel21.setPreferredSize(new java.awt.Dimension(23, 23));
        panelGlass9.add(jLabel21);

        DTPCari2.setForeground(new java.awt.Color(50, 70, 50));
        DTPCari2.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "27-08-2025" }));
        DTPCari2.setDisplayFormat("dd-MM-yyyy");
        DTPCari2.setName("DTPCari2"); // NOI18N
        DTPCari2.setOpaque(false);
        DTPCari2.setPreferredSize(new java.awt.Dimension(90, 23));
        panelGlass9.add(DTPCari2);

        jLabel6.setText("Key Word :");
        jLabel6.setName("jLabel6"); // NOI18N
        jLabel6.setPreferredSize(new java.awt.Dimension(65, 23));
        panelGlass9.add(jLabel6);

        TCari.setName("TCari"); // NOI18N
        TCari.setPreferredSize(new java.awt.Dimension(190, 23));
        TCari.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                TCariKeyPressed(evt);
            }
        });
        panelGlass9.add(TCari);

        BtnCari.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/accept.png"))); // NOI18N
        BtnCari.setMnemonic('3');
        BtnCari.setToolTipText("Alt+3");
        BtnCari.setName("BtnCari"); // NOI18N
        BtnCari.setPreferredSize(new java.awt.Dimension(28, 23));
        BtnCari.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnCariActionPerformed(evt);
            }
        });
        BtnCari.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnCariKeyPressed(evt);
            }
        });
        panelGlass9.add(BtnCari);

        BtnAll.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/Search-16x16.png"))); // NOI18N
        BtnAll.setMnemonic('M');
        BtnAll.setToolTipText("Alt+M");
        BtnAll.setName("BtnAll"); // NOI18N
        BtnAll.setPreferredSize(new java.awt.Dimension(28, 23));
        BtnAll.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnAllActionPerformed(evt);
            }
        });
        BtnAll.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnAllKeyPressed(evt);
            }
        });
        panelGlass9.add(BtnAll);

        jLabel18.setText("Pengajuan :");
        jLabel18.setName("jLabel18"); // NOI18N
        jLabel18.setPreferredSize(new java.awt.Dimension(70, 23));
        panelGlass9.add(jLabel18);

        LCount1.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        LCount1.setText("0");
        LCount1.setName("LCount1"); // NOI18N
        LCount1.setPreferredSize(new java.awt.Dimension(80, 23));
        panelGlass9.add(LCount1);

        jPanel3.add(panelGlass9, java.awt.BorderLayout.PAGE_START);

        internalFrame1.add(jPanel3, java.awt.BorderLayout.PAGE_END);

        PanelInput.setName("PanelInput"); // NOI18N
        PanelInput.setOpaque(false);
        PanelInput.setPreferredSize(new java.awt.Dimension(72, 185));
        PanelInput.setLayout(new java.awt.BorderLayout(1, 1));

        FormInput.setName("FormInput"); // NOI18N
        FormInput.setPreferredSize(new java.awt.Dimension(55, 165));
        FormInput.setLayout(null);

        jLabel8.setText("Tgl. Pengajuan :");
        jLabel8.setName("jLabel8"); // NOI18N
        FormInput.add(jLabel8);
        jLabel8.setBounds(226, 10, 99, 23);

        Tanggal.setForeground(new java.awt.Color(50, 70, 50));
        Tanggal.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "27-08-2025" }));
        Tanggal.setDisplayFormat("dd-MM-yyyy");
        Tanggal.setName("Tanggal"); // NOI18N
        Tanggal.setOpaque(false);
        Tanggal.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                TanggalKeyPressed(evt);
            }
        });
        FormInput.add(Tanggal);
        Tanggal.setBounds(329, 10, 90, 23);

        jLabel5.setText("Alamat Tujuan :");
        jLabel5.setName("jLabel5"); // NOI18N
        FormInput.add(jLabel5);
        jLabel5.setBounds(436, 70, 95, 23);

        KdPetugas.setEditable(false);
        KdPetugas.setHighlighter(null);
        KdPetugas.setName("KdPetugas"); // NOI18N
        FormInput.add(KdPetugas);
        KdPetugas.setBounds(92, 40, 110, 23);

        btnPetugas.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/190.png"))); // NOI18N
        btnPetugas.setMnemonic('2');
        btnPetugas.setToolTipText("Alt+2");
        btnPetugas.setName("btnPetugas"); // NOI18N
        btnPetugas.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                btnPetugasActionPerformed(evt);
            }
        });
        btnPetugas.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                btnPetugasKeyPressed(evt);
            }
        });
        FormInput.add(btnPetugas);
        btnPetugas.setBounds(391, 40, 28, 23);

        NmPetugas.setEditable(false);
        NmPetugas.setHighlighter(null);
        NmPetugas.setName("NmPetugas"); // NOI18N
        FormInput.add(NmPetugas);
        NmPetugas.setBounds(204, 40, 185, 23);

        scrollPane1.setBorder(javax.swing.BorderFactory.createLineBorder(new java.awt.Color(0, 0, 0)));
        scrollPane1.setName("scrollPane1"); // NOI18N

        Alamat.setBorder(javax.swing.BorderFactory.createEmptyBorder(1, 1, 1, 1));
        Alamat.setColumns(20);
        Alamat.setRows(5);
        Alamat.setName("Alamat"); // NOI18N
        Alamat.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                AlamatKeyPressed(evt);
            }
        });
        scrollPane1.setViewportView(Alamat);

        FormInput.add(scrollPane1);
        scrollPane1.setBounds(535, 70, 235, 52);

        jLabel9.setText("Diajukan Oleh :");
        jLabel9.setName("jLabel9"); // NOI18N
        FormInput.add(jLabel9);
        jLabel9.setBounds(0, 40, 88, 23);

        jLabel3.setText("No.Pengajuan :");
        jLabel3.setName("jLabel3"); // NOI18N
        FormInput.add(jLabel3);
        jLabel3.setBounds(0, 10, 88, 23);

        NoPengajuan.setHighlighter(null);
        NoPengajuan.setName("NoPengajuan"); // NOI18N
        FormInput.add(NoPengajuan);
        NoPengajuan.setBounds(92, 10, 130, 23);

        jLabel20.setText("Jenis Cuti :");
        jLabel20.setName("jLabel20"); // NOI18N
        FormInput.add(jLabel20);
        jLabel20.setBounds(436, 10, 95, 23);

        Urgensi.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "Tahunan", "Besar", "Sakit", "Melahirkan", "Keterangan Lainnya", "Khitan Anak", "Istri Melahirkan", "Keluarga Meninggal Dunia", "Menikah", "Menikahkan Anak", "Pernikahan Saudara Kandung", "Ujian/Sidang Akademik" }));
        Urgensi.setName("Urgensi"); // NOI18N
        Urgensi.setPreferredSize(new java.awt.Dimension(55, 28));
        Urgensi.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                UrgensiKeyPressed(evt);
            }
        });
        FormInput.add(Urgensi);
        Urgensi.setBounds(535, 10, 159, 23);

        jLabel4.setText("Keterangan");
        jLabel4.setName("jLabel4"); // NOI18N
        FormInput.add(jLabel4);
        jLabel4.setBounds(250, 130, 120, 23);

        Kepentingan.setHighlighter(null);
        Kepentingan.setName("Kepentingan"); // NOI18N
        Kepentingan.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                KepentinganKeyPressed(evt);
            }
        });
        FormInput.add(Kepentingan);
        Kepentingan.setBounds(374, 130, 396, 23);

        jLabel11.setText("Jml. Cuti :");
        jLabel11.setName("jLabel11"); // NOI18N
        FormInput.add(jLabel11);
        jLabel11.setBounds(305, 100, 65, 23);

        Jumlah.setHighlighter(null);
        Jumlah.setName("Jumlah"); // NOI18N
        Jumlah.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                JumlahKeyPressed(evt);
            }
        });
        FormInput.add(Jumlah);
        Jumlah.setBounds(374, 100, 45, 23);

        jLabel15.setText("Bidang :");
        jLabel15.setName("jLabel15"); // NOI18N
        FormInput.add(jLabel15);
        jLabel15.setBounds(423, 40, 50, 23);

        Bidang.setEditable(false);
        Bidang.setHighlighter(null);
        Bidang.setName("Bidang"); // NOI18N
        Bidang.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BidangKeyPressed(evt);
            }
        });
        FormInput.add(Bidang);
        Bidang.setBounds(477, 40, 95, 23);

        jLabel16.setText("Departemen :");
        jLabel16.setName("jLabel16"); // NOI18N
        FormInput.add(jLabel16);
        jLabel16.setBounds(581, 40, 70, 23);

        Departemen.setEditable(false);
        Departemen.setHighlighter(null);
        Departemen.setName("Departemen"); // NOI18N
        Departemen.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                DepartemenKeyPressed(evt);
            }
        });
        FormInput.add(Departemen);
        Departemen.setBounds(655, 40, 115, 23);

        jLabel17.setText("Atasan / Kanit");
        jLabel17.setName("jLabel17"); // NOI18N
        FormInput.add(jLabel17);
        jLabel17.setBounds(0, 70, 88, 23);

        KdPetugasPJ.setEditable(false);
        KdPetugasPJ.setHighlighter(null);
        KdPetugasPJ.setName("KdPetugasPJ"); // NOI18N
        FormInput.add(KdPetugasPJ);
        KdPetugasPJ.setBounds(92, 70, 110, 23);

        NmPetugasPJ.setEditable(false);
        NmPetugasPJ.setHighlighter(null);
        NmPetugasPJ.setName("NmPetugasPJ"); // NOI18N
        FormInput.add(NmPetugasPJ);
        NmPetugasPJ.setBounds(204, 70, 185, 23);

        btnPetugasPJ.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/190.png"))); // NOI18N
        btnPetugasPJ.setMnemonic('2');
        btnPetugasPJ.setToolTipText("Alt+2");
        btnPetugasPJ.setName("btnPetugasPJ"); // NOI18N
        btnPetugasPJ.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                btnPetugasPJActionPerformed(evt);
            }
        });
        btnPetugasPJ.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                btnPetugasPJKeyPressed(evt);
            }
        });
        FormInput.add(btnPetugasPJ);
        btnPetugasPJ.setBounds(391, 70, 28, 23);

        jLabel14.setText("Tanggal Cuti :");
        jLabel14.setName("jLabel14"); // NOI18N
        FormInput.add(jLabel14);
        jLabel14.setBounds(0, 100, 88, 23);

        Tgl1.setForeground(new java.awt.Color(50, 70, 50));
        Tgl1.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "27-08-2025" }));
        Tgl1.setDisplayFormat("dd-MM-yyyy");
        Tgl1.setName("Tgl1"); // NOI18N
        Tgl1.setOpaque(false);
        Tgl1.addItemListener(new java.awt.event.ItemListener() {
            public void itemStateChanged(java.awt.event.ItemEvent evt) {
                Tgl1ItemStateChanged(evt);
            }
        });
        Tgl1.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                Tgl1ActionPerformed(evt);
            }
        });
        Tgl1.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                Tgl1KeyPressed(evt);
            }
        });
        FormInput.add(Tgl1);
        Tgl1.setBounds(92, 100, 90, 23);

        jLabel22.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        jLabel22.setText("s/d");
        jLabel22.setName("jLabel22"); // NOI18N
        FormInput.add(jLabel22);
        jLabel22.setBounds(184, 100, 25, 23);

        Tgl2.setForeground(new java.awt.Color(50, 70, 50));
        Tgl2.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "27-08-2025" }));
        Tgl2.setDisplayFormat("dd-MM-yyyy");
        Tgl2.setName("Tgl2"); // NOI18N
        Tgl2.setOpaque(false);
        Tgl2.addItemListener(new java.awt.event.ItemListener() {
            public void itemStateChanged(java.awt.event.ItemEvent evt) {
                Tgl2ItemStateChanged(evt);
            }
        });
        Tgl2.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                Tgl2KeyPressed(evt);
            }
        });
        FormInput.add(Tgl2);
        Tgl2.setBounds(211, 100, 90, 23);

        jLabel12.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel12.setText("Hari");
        jLabel12.setName("jLabel12"); // NOI18N
        FormInput.add(jLabel12);
        jLabel12.setBounds(421, 100, 40, 23);

        jLabel23.setText("Status :");
        jLabel23.setName("jLabel23"); // NOI18N
        FormInput.add(jLabel23);
        jLabel23.setBounds(0, 130, 88, 23);

        Status.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "Proses Pengajuan", "Disetujui", "Ditolak" }));
        Status.setName("Status"); // NOI18N
        Status.setPreferredSize(new java.awt.Dimension(55, 28));
        Status.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                StatusKeyPressed(evt);
            }
        });
        FormInput.add(Status);
        Status.setBounds(92, 130, 159, 23);

        PanelInput.add(FormInput, java.awt.BorderLayout.CENTER);

        ChkInput.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/143.png"))); // NOI18N
        ChkInput.setMnemonic('I');
        ChkInput.setText(".: Input Data");
        ChkInput.setToolTipText("Alt+I");
        ChkInput.setBorderPainted(true);
        ChkInput.setBorderPaintedFlat(true);
        ChkInput.setFocusable(false);
        ChkInput.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        ChkInput.setHorizontalTextPosition(javax.swing.SwingConstants.RIGHT);
        ChkInput.setName("ChkInput"); // NOI18N
        ChkInput.setPreferredSize(new java.awt.Dimension(192, 20));
        ChkInput.setRolloverIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/143.png"))); // NOI18N
        ChkInput.setRolloverSelectedIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/145.png"))); // NOI18N
        ChkInput.setSelectedIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/145.png"))); // NOI18N
        ChkInput.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                ChkInputActionPerformed(evt);
            }
        });
        PanelInput.add(ChkInput, java.awt.BorderLayout.PAGE_END);

        internalFrame1.add(PanelInput, java.awt.BorderLayout.PAGE_START);

        getContentPane().add(internalFrame1, java.awt.BorderLayout.CENTER);

        pack();
    }// </editor-fold>//GEN-END:initComponents

    private void BtnSimpanActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnSimpanActionPerformed
        if(NoPengajuan.getText().trim().equals("")){
            Valid.textKosong(NoPengajuan,"No.Pengajuan");
        }else if(NmPetugas.getText().trim().equals("")){
            Valid.textKosong(KdPetugas,"Yang Mengajukan");
        }else if(Jumlah.getText().trim().equals("")||Jumlah.getText().trim().equals("0")){
            Valid.textKosong(Jumlah,"Jml Cuti");
        }else if(Alamat.getText().trim().equals("")){
            Valid.textKosong(Alamat,"Alamat Tujuan");
        }else if(Kepentingan.getText().trim().equals("")){
            Valid.textKosong(Kepentingan,"Kepentingan Cuti");
        }else if(NmPetugasPJ.getText().trim().equals("")){
            Valid.textKosong(KdPetugasPJ,"P.J. terkait pengajuan");
        }else{
            if(Sequel.menyimpantf("pengajuan_cuti","?,?,?,?,?,?,?,?,?,?,?","Data",11,new String[]{
                    NoPengajuan.getText(),Valid.SetTgl(Tanggal.getSelectedItem()+""),Valid.SetTgl(Tgl2.getSelectedItem()+""),Valid.SetTgl(Tgl1.getSelectedItem()+""),
                    KdPetugas.getText(),Urgensi.getSelectedItem().toString(),Alamat.getText(),Jumlah.getText(),Kepentingan.getText(),KdPetugasPJ.getText(),
                    Status.getSelectedItem().toString()
                })==true){
                    tampil();
                    emptTeks();
            }
        }
}//GEN-LAST:event_BtnSimpanActionPerformed

    private void BtnSimpanKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnSimpanKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            BtnSimpanActionPerformed(null);
        }else{
            Valid.pindah(evt,Kepentingan,BtnBatal);
        }
}//GEN-LAST:event_BtnSimpanKeyPressed

    private void BtnBatalActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnBatalActionPerformed
        ChkInput.setSelected(true);
        isForm(); 
        emptTeks();
}//GEN-LAST:event_BtnBatalActionPerformed

    private void BtnBatalKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnBatalKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            emptTeks();
        }else{Valid.pindah(evt, BtnSimpan, BtnHapus);}
}//GEN-LAST:event_BtnBatalKeyPressed

    private void BtnHapusActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnHapusActionPerformed
        if(tbObat.getSelectedRow()> -1){
            Sequel.meghapus("pengajuan_cuti","no_pengajuan",tbObat.getValueAt(tbObat.getSelectedRow(),0).toString());
            tampil();
            emptTeks();
        }
}//GEN-LAST:event_BtnHapusActionPerformed

    private void BtnHapusKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnHapusKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            BtnHapusActionPerformed(null);
        }else{
            Valid.pindah(evt, BtnBatal, BtnEdit);
        }
}//GEN-LAST:event_BtnHapusKeyPressed

    private void BtnEditActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnEditActionPerformed
    if(NoPengajuan.getText().trim().equals("")){
        Valid.textKosong(NoPengajuan,"No.Pengajuan");
    }else if(NmPetugas.getText().trim().equals("")){
        Valid.textKosong(KdPetugas,"Yang Mengajukan");
    }else if(Alamat.getText().trim().equals("")){
        Valid.textKosong(Alamat,"Alamat Tujuan");
    }else if(Jumlah.getText().trim().equals("")||Jumlah.getText().trim().equals("0")){
        Valid.textKosong(Jumlah,"Jml Cuti");
    }else if(Kepentingan.getText().trim().equals("")){
        Valid.textKosong(Kepentingan,"Kepentingan Cuti");
    }else if(NmPetugasPJ.getText().trim().equals("")){
        Valid.textKosong(KdPetugasPJ,"P.J. terkait pengajuan");
    }else{
        if(tbObat.getSelectedRow()> -1){
            String noPengajuan = tbObat.getValueAt(tbObat.getSelectedRow(), 0).toString();
            String nikPJdiData = tbObat.getValueAt(tbObat.getSelectedRow(), 12).toString();
            String nikUserLogin = akses.getkode();

            // KASUS 1: Pengguna yang login adalah ATASAN LANGSUNG untuk pengajuan ini
            if (nikUserLogin.equals(nikPJdiData)) {
                // Cek apakah pengguna ini juga seorang HRD
                if (isHRD) {
                    // SKENARIO KHUSUS: HRD bertindak sebagai atasan langsung.
                    // Lakukan persetujuan Level 1 dan Level 2 sekaligus.
                    if(Sequel.mengedittf("pengajuan_cuti", "no_pengajuan=?",
                            "status=?, waktu_disetujui_atasan=NOW(), status_persetujuan_HRD=?, waktu_disetujui_HRD=NOW()", 3, new String[]{
                                Status.getSelectedItem().toString(), // untuk status atasan
                                Status.getSelectedItem().toString(), // untuk status HRD
                                noPengajuan                          // untuk klausa WHERE
                            })==true){
                        // Tawarkan kirim WA setelah approval gabungan
                        kirimNotifikasiWA();
                        tampil();
                        emptTeks();
                    }
                } else {
                    // SKENARIO NORMAL: Atasan biasa melakukan persetujuan Level 1.
                    // Cek dulu apakah sudah diproses HRD (sebagai pengaman).
                    if (Sequel.cariInteger("select count(*) from pengajuan_cuti where no_pengajuan=? and waktu_disetujui_HRD is not null", noPengajuan) > 0) {
                        JOptionPane.showMessageDialog(null, "Tidak bisa diedit, pengajuan ini sudah diproses oleh HRD.");
                    } else {
                        if(Sequel.mengedittf("pengajuan_cuti","no_pengajuan=?","status=?, waktu_disetujui_atasan=NOW()",2,new String[]{
                            Status.getSelectedItem().toString(),
                            noPengajuan
                        })==true){
                            tampil();
                            emptTeks();
                        }
                    }
                }
            }
            // KASUS 2: Pengguna yang login adalah HRD dan BUKAN atasan langsung
            else if (isHRD) {
                // Ini adalah persetujuan Level 2.
                String statusAtasan = tbObat.getValueAt(tbObat.getSelectedRow(), 14).toString();
                if (!statusAtasan.equals("Disetujui")) {
                    JOptionPane.showMessageDialog(null, "Harus disetujui oleh atasan terlebih dahulu sebelum diproses HRD.");
                } else {
                    if(Sequel.mengedittf("pengajuan_cuti", "no_pengajuan=?",
                            "status_persetujuan_HRD=?, waktu_disetujui_HRD=NOW()", 2, new String[]{
                                Status.getSelectedItem().toString(),
                                noPengajuan
                            })==true){
                        
                        // Tawarkan kirim WA setelah approval gabungan
                        kirimNotifikasiWA();
                        tampil();
                        emptTeks();
                    }
                }
            }
            
            
        }
    }
}//GEN-LAST:event_BtnEditActionPerformed

    private void BtnEditKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnEditKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            BtnEditActionPerformed(null);
        }else{
            Valid.pindah(evt, BtnHapus, BtnPrint);
        }
}//GEN-LAST:event_BtnEditKeyPressed

    private void BtnKeluarActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnKeluarActionPerformed
        dispose();
}//GEN-LAST:event_BtnKeluarActionPerformed

    private void BtnKeluarKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnKeluarKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            dispose();
        }else{Valid.pindah(evt,BtnEdit,TCari);}
}//GEN-LAST:event_BtnKeluarKeyPressed

    private void BtnPrintActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnPrintActionPerformed
        this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
        if(tabMode.getRowCount()==0){
            JOptionPane.showMessageDialog(null,"Maaf, data sudah habis. Tidak ada data yang bisa anda print...!!!!");
            BtnBatal.requestFocus();
        }else if(tabMode.getRowCount()!=0){
            Map<String, Object> param = new HashMap<>(); 
            param.put("namars",akses.getnamars());
            param.put("alamatrs",akses.getalamatrs());
            param.put("kotars",akses.getkabupatenrs());
            param.put("propinsirs",akses.getpropinsirs());
            param.put("kontakrs",akses.getkontakrs());
            param.put("emailrs",akses.getemailrs());   
            param.put("logo",Sequel.cariGambar("select setting.logo from setting")); 
            if(TCari.getText().equals("")){
                Valid.MyReportqry("rptPengajuanCutiAdmin.jasper","report","::[ Data Pengajuan Cuti ]::",
                        "select pengajuan_cuti.no_pengajuan,pengajuan_cuti.tanggal,pengajuan_cuti.tanggal_awal,pengajuan_cuti.tanggal_akhir,"+
                        "pengajuan_cuti.nik,peg1.nama as namapengaju,peg1.bidang,peg1.departemen,pengajuan_cuti.urgensi,pengajuan_cuti.alamat,"+
                        "pengajuan_cuti.jumlah,pengajuan_cuti.kepentingan,pengajuan_cuti.nik_pj,peg2.nama as namapj,pengajuan_cuti.status "+
                        "from pengajuan_cuti inner join pegawai as peg1 on pengajuan_cuti.nik=peg1.nik "+
                        "inner join pegawai as peg2 on pengajuan_cuti.nik_pj=peg2.nik where "+
                        "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' order by pengajuan_cuti.tanggal",param);
            }else{
                Valid.MyReportqry("rptPengajuanCutiAdmin.jasper","report","::[ Data Pengajuan Cuti ]::",
                       "select pengajuan_cuti.no_pengajuan,pengajuan_cuti.tanggal,pengajuan_cuti.tanggal_awal,pengajuan_cuti.tanggal_akhir,"+
                        "pengajuan_cuti.nik,peg1.nama as namapengaju,peg1.bidang,peg1.departemen,pengajuan_cuti.urgensi,pengajuan_cuti.alamat,"+
                        "pengajuan_cuti.jumlah,pengajuan_cuti.kepentingan,pengajuan_cuti.nik_pj,peg2.nama as namapj,pengajuan_cuti.status "+
                        "from pengajuan_cuti inner join pegawai as peg1 on pengajuan_cuti.nik=peg1.nik "+
                        "inner join pegawai as peg2 on pengajuan_cuti.nik_pj=peg2.nik where "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and pengajuan_cuti.no_pengajuan like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and pengajuan_cuti.nik like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and peg1.nama like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and peg1.bidang like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and peg1.departemen like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and pengajuan_cuti.urgensi like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and pengajuan_cuti.alamat like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and pengajuan_cuti.kepentingan like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and pengajuan_cuti.nik_pj like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and peg2.nama like '%"+TCari.getText().trim()+"%' or "+
                       "pengajuan_cuti.tanggal_awal between '"+Valid.SetTgl(DTPCari1.getSelectedItem()+"")+"' and '"+Valid.SetTgl(DTPCari2.getSelectedItem()+"")+"' and pengajuan_cuti.status like '%"+TCari.getText().trim()+"%' order by pengajuan_cuti.tanggal",param);
           }
                
        }
        this.setCursor(Cursor.getDefaultCursor());
}//GEN-LAST:event_BtnPrintActionPerformed

    private void BtnPrintKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnPrintKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            BtnPrintActionPerformed(null);
        }else{
            Valid.pindah(evt, BtnEdit, BtnKeluar);
        }
}//GEN-LAST:event_BtnPrintKeyPressed

    private void TCariKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_TCariKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_ENTER){
            BtnCariActionPerformed(null);
        }else if(evt.getKeyCode()==KeyEvent.VK_PAGE_DOWN){
            BtnCari.requestFocus();
        }else if(evt.getKeyCode()==KeyEvent.VK_PAGE_UP){
            BtnKeluar.requestFocus();
        }
}//GEN-LAST:event_TCariKeyPressed

    private void BtnCariActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnCariActionPerformed
        tampil();
}//GEN-LAST:event_BtnCariActionPerformed

    private void BtnCariKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnCariKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            BtnCariActionPerformed(null);
        }else{
            Valid.pindah(evt, TCari, BtnAll);
        }
}//GEN-LAST:event_BtnCariKeyPressed

    private void BtnAllActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnAllActionPerformed
        TCari.setText("");
        tampil();
}//GEN-LAST:event_BtnAllActionPerformed

    private void BtnAllKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnAllKeyPressed
        if(evt.getKeyCode()==KeyEvent.VK_SPACE){
            TCari.setText("");
            tampil();
        }else{
            Valid.pindah(evt, BtnCari,BtnKeluar);
        }
}//GEN-LAST:event_BtnAllKeyPressed

    private void TanggalKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_TanggalKeyPressed
        Valid.pindah(evt,TCari,Tgl1);
}//GEN-LAST:event_TanggalKeyPressed

    private void tbObatMouseClicked(java.awt.event.MouseEvent evt) {//GEN-FIRST:event_tbObatMouseClicked
        if(tabMode.getRowCount()!=0){
            try {
                getData();
            } catch (java.lang.NullPointerException e) {
            }
        }
}//GEN-LAST:event_tbObatMouseClicked

private void btnPetugasActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnPetugasActionPerformed
        pilihan=1;
        petugas.setSize(internalFrame1.getWidth()-20,internalFrame1.getHeight()-20);
        petugas.setLocationRelativeTo(internalFrame1);
        petugas.setVisible(true);
}//GEN-LAST:event_btnPetugasActionPerformed

    private void ChkInputActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_ChkInputActionPerformed
        isForm();
    }//GEN-LAST:event_ChkInputActionPerformed

    private void tbObatKeyReleased(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_tbObatKeyReleased
        if(tabMode.getRowCount()!=0){
            if((evt.getKeyCode()==KeyEvent.VK_ENTER)||(evt.getKeyCode()==KeyEvent.VK_UP)||(evt.getKeyCode()==KeyEvent.VK_DOWN)){
                try {
                    getData();
                } catch (java.lang.NullPointerException e) {
                }
            }
        }
    }//GEN-LAST:event_tbObatKeyReleased

    private void formWindowOpened(java.awt.event.WindowEvent evt) {//GEN-FIRST:event_formWindowOpened
        emptTeks();
        tampil();
    }//GEN-LAST:event_formWindowOpened

    private void BidangKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BidangKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_BidangKeyPressed

    private void DepartemenKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_DepartemenKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_DepartemenKeyPressed

    private void btnPetugasPJActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnPetugasPJActionPerformed
        pilihan=2;
        petugas.setSize(internalFrame1.getWidth()-20,internalFrame1.getHeight()-20);
        petugas.setLocationRelativeTo(internalFrame1);
        petugas.setVisible(true);
    }//GEN-LAST:event_btnPetugasPJActionPerformed

    private void KepentinganKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_KepentinganKeyPressed
        Valid.pindah(evt,Status,BtnSimpan);
    }//GEN-LAST:event_KepentinganKeyPressed

    private void btnPetugasKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_btnPetugasKeyPressed
        Valid.pindah(evt,Kepentingan,Urgensi);
    }//GEN-LAST:event_btnPetugasKeyPressed

    private void UrgensiKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_UrgensiKeyPressed
        Valid.pindah(evt,btnPetugas,btnPetugasPJ);
    }//GEN-LAST:event_UrgensiKeyPressed

    private void JumlahKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_JumlahKeyPressed
        Valid.pindah(evt,Tgl2,Status);
    }//GEN-LAST:event_JumlahKeyPressed

    private void AlamatKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_AlamatKeyPressed
        Valid.pindah(evt,btnPetugasPJ,Tgl1);
    }//GEN-LAST:event_AlamatKeyPressed

    private void btnPetugasPJKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_btnPetugasPJKeyPressed
        Valid.pindah(evt,btnPetugas,Alamat);
    }//GEN-LAST:event_btnPetugasPJKeyPressed

    private void Tgl1KeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_Tgl1KeyPressed
        Valid.pindah(evt,Alamat,Tgl2);
    }//GEN-LAST:event_Tgl1KeyPressed

    private void Tgl2KeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_Tgl2KeyPressed
        Valid.pindah(evt,Tgl1,Jumlah);
    }//GEN-LAST:event_Tgl2KeyPressed

    private void StatusKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_StatusKeyPressed
       Valid.pindah(evt,Jumlah,Kepentingan);
    }//GEN-LAST:event_StatusKeyPressed

    private void Tgl1ItemStateChanged(java.awt.event.ItemEvent evt) {//GEN-FIRST:event_Tgl1ItemStateChanged
        Sequel.cariIsi("select to_days('"+Valid.SetTgl(Tgl2.getSelectedItem()+"")+"')-to_days('"+Valid.SetTgl(Tgl1.getSelectedItem()+"")+"')",Jumlah); 
    }//GEN-LAST:event_Tgl1ItemStateChanged

    private void Tgl2ItemStateChanged(java.awt.event.ItemEvent evt) {//GEN-FIRST:event_Tgl2ItemStateChanged
        Sequel.cariIsi("select to_days('"+Valid.SetTgl(Tgl2.getSelectedItem()+"")+"')-to_days('"+Valid.SetTgl(Tgl1.getSelectedItem()+"")+"')",Jumlah); 
    }//GEN-LAST:event_Tgl2ItemStateChanged

    private void MnCetakCutiActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_MnCetakCutiActionPerformed
        if(NoPengajuan.getText().trim().equals("")){
            JOptionPane.showMessageDialog(null,"Maaf, Silahkan anda pilih dulu data cuti yang mana");
            }else{
            // ##### PENGECEKAN BARU DIMULAI DI SINI #####
            // Ambil nilai waktu persetujuan HRD dari database
            String waktuDisetujuiHRD = Sequel.cariIsi("select waktu_disetujui_HRD from pengajuan_cuti where no_pengajuan=?", NoPengajuan.getText());

        // Cek apakah nilainya null atau kosong
        if (waktuDisetujuiHRD == null || waktuDisetujuiHRD.trim().isEmpty()) {
            // Jika kosong, tampilkan pesan error dan hentikan proses
            JOptionPane.showMessageDialog(null, "Surat tidak bisa dicetak karena belum dilakukan approval oleh HRD");
            return; // Hentikan eksekusi metode
        }
        // ##### AKHIR DARI PENGECEKAN #####

        // Jika pengecekan lolos, lanjutkan proses cetak seperti biasa
        this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
            Map<String, Object> param = new HashMap<>();
            param.put("NoPengajuan",NoPengajuan.getText());  //ambil nomor surat cuti
            param.put("nippetugas",KdPetugas.getText());  //ambil NIP pengaju cuti
            param.put("namapetugas",NmPetugas.getText());  //ambil nama pengaju cuti
            param.put("namaatasan",NmPetugasPJ.getText());  //ambil nama Atasan cuti
            param.put("TanggalAwal",Sequel.cariIsi("select tanggal_awal from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //ambil tgl awal cuti
            param.put("TanggalAkhir",Sequel.cariIsi("select tanggal_akhir from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //ambil tgl akhir cuti
            param.put("lamacuti",Jumlah.getText());  //jumlah cuti berapa hari
            param.put("kepentingan",Kepentingan.getText());  //Keterangan / alasan cuti
            param.put("status",Sequel.cariIsi("select status_persetujuan_HRD from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //status approval hrd
            param.put("TanggalApproval",Sequel.cariIsi("select DATE_FORMAT(pengajuan_cuti.waktu_disetujui_HRD,'%d-%m-%Y') from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //ambil tgl approval hRD
            param.put("namars",akses.getnamars());
            param.put("alamatrs",akses.getalamatrs());
            param.put("kotars",akses.getkabupatenrs());
            param.put("propinsirs",akses.getpropinsirs());
            param.put("kontakrs",akses.getkontakrs());
            param.put("emailrs",akses.getemailrs());
            param.put("finger","Dikeluarkan di "+akses.getnamars()+", Kabupaten/Kota "+akses.getkabupatenrs()+"\nDitandatangani secara elektronik oleh Unit HRD \n "+Sequel.cariIsi("select DATE_FORMAT(pengajuan_cuti.waktu_disetujui_HRD,'%d-%m-%Y') from pengajuan_cuti where pengajuan_cuti.no_pengajuan=?",NoPengajuan.getText()));
            param.put("logo",Sequel.cariGambar("select setting.logo from setting"));
            Valid.MyReportqry("rptApprovalCuti.jasper","report","::[ Approval Cuti ]::",
                          "select * from pengajuan_cuti where pengajuan_cuti.no_pengajuan='"+NoPengajuan.getText()+"' ",param);
            this.setCursor(Cursor.getDefaultCursor());
   }
    }//GEN-LAST:event_MnCetakCutiActionPerformed

    private void Tgl1ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_Tgl1ActionPerformed
        // TODO add your handling code here:
    }//GEN-LAST:event_Tgl1ActionPerformed

    /**
    * @param args the command line arguments
    */
    public static void main(String args[]) {
        java.awt.EventQueue.invokeLater(() -> {
            PengajuanCutiAdmin dialog = new PengajuanCutiAdmin(new javax.swing.JFrame(), true);
            dialog.addWindowListener(new java.awt.event.WindowAdapter() {
                @Override
                public void windowClosing(java.awt.event.WindowEvent e) {
                    System.exit(0);
                }
            });
            dialog.setVisible(true);
        });
    }

    // Variables declaration - do not modify//GEN-BEGIN:variables
    private widget.TextArea Alamat;
    private widget.TextBox Bidang;
    private widget.Button BtnAll;
    private widget.Button BtnBatal;
    private widget.Button BtnCari;
    private widget.Button BtnEdit;
    private widget.Button BtnHapus;
    private widget.Button BtnKeluar;
    private widget.Button BtnPrint;
    private widget.Button BtnSimpan;
    private widget.CekBox ChkInput;
    private widget.Tanggal DTPCari1;
    private widget.Tanggal DTPCari2;
    private widget.TextBox Departemen;
    private widget.PanelBiasa FormInput;
    private widget.TextBox Jumlah;
    private widget.TextBox KdPetugas;
    private widget.TextBox KdPetugasPJ;
    private widget.TextBox Kepentingan;
    private widget.Label LCount;
    private widget.Label LCount1;
    private javax.swing.JMenuItem MnCetakCuti;
    private widget.TextBox NmPetugas;
    private widget.TextBox NmPetugasPJ;
    private widget.TextBox NoPengajuan;
    private javax.swing.JPanel PanelInput;
    private widget.ScrollPane Scroll;
    private widget.ComboBox Status;
    private widget.TextBox TCari;
    private widget.Tanggal Tanggal;
    private widget.Tanggal Tgl1;
    private widget.Tanggal Tgl2;
    private widget.ComboBox Urgensi;
    private widget.Button btnPetugas;
    private widget.Button btnPetugasPJ;
    private widget.InternalFrame internalFrame1;
    private widget.Label jLabel11;
    private widget.Label jLabel12;
    private widget.Label jLabel14;
    private widget.Label jLabel15;
    private widget.Label jLabel16;
    private widget.Label jLabel17;
    private widget.Label jLabel18;
    private widget.Label jLabel19;
    private widget.Label jLabel20;
    private widget.Label jLabel21;
    private widget.Label jLabel22;
    private widget.Label jLabel23;
    private widget.Label jLabel3;
    private widget.Label jLabel4;
    private widget.Label jLabel5;
    private widget.Label jLabel6;
    private widget.Label jLabel7;
    private widget.Label jLabel8;
    private widget.Label jLabel9;
    private javax.swing.JPanel jPanel3;
    private javax.swing.JPopupMenu jPopupMenu1;
    private widget.panelisi panelGlass8;
    private widget.panelisi panelGlass9;
    private widget.ScrollPane scrollPane1;
    private widget.Table tbObat;
    // End of variables declaration//GEN-END:variables

    /*
    private void tampil() {
        Valid.tabelKosong(tabMode);
        try{
            if(TCari.getText().equals("")){
                ps=koneksi.prepareStatement(
                   "select pengajuan_cuti.no_pengajuan,pengajuan_cuti.tanggal,pengajuan_cuti.tanggal_awal,pengajuan_cuti.tanggal_akhir,"+
                   "pengajuan_cuti.nik,peg1.nama as namapengaju,peg1.bidang,peg1.departemen,pengajuan_cuti.urgensi,pengajuan_cuti.alamat,"+
                   "pengajuan_cuti.jumlah,pengajuan_cuti.kepentingan,pengajuan_cuti.nik_pj,peg2.nama as namapj,pengajuan_cuti.status "+
                   "pengajuan_cuti.status_persetujuan_HRD " + // <-- KOLOM TAMBAHAN - ichsan
                   "from pengajuan_cuti inner join pegawai as peg1 on pengajuan_cuti.nik=peg1.nik "+
                   "inner join pegawai as peg2 on pengajuan_cuti.nik_pj=peg2.nik where "+
                   "pengajuan_cuti.tanggal between ? and ? order by pengajuan_cuti.tanggal");
            }else{
                ps=koneksi.prepareStatement(
                   "select pengajuan_cuti.no_pengajuan,pengajuan_cuti.tanggal,pengajuan_cuti.tanggal_awal,pengajuan_cuti.tanggal_akhir,"+
                   "pengajuan_cuti.nik,peg1.nama as namapengaju,peg1.bidang,peg1.departemen,pengajuan_cuti.urgensi,pengajuan_cuti.alamat,"+
                   "pengajuan_cuti.jumlah,pengajuan_cuti.kepentingan,pengajuan_cuti.nik_pj,peg2.nama as namapj,pengajuan_cuti.status, pengajuan_cuti.status_persetujuan_HRD "+ //tambahan pengajuan_cuti.status_persetujuan_HRD - ichsan
                   "from pengajuan_cuti inner join pegawai as peg1 on pengajuan_cuti.nik=peg1.nik "+
                   "inner join pegawai as peg2 on pengajuan_cuti.nik_pj=peg2.nik where "+
                   "pengajuan_cuti.tanggal between ? and ? and pengajuan_cuti.no_pengajuan like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and pengajuan_cuti.nik like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and peg1.nama like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and peg1.bidang like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and peg1.departemen like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and pengajuan_cuti.urgensi like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and pengajuan_cuti.alamat like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and pengajuan_cuti.kepentingan like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and pengajuan_cuti.nik_pj like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and peg2.nama like ? or "+
                   "pengajuan_cuti.tanggal between ? and ? and pengajuan_cuti.status like ? "+
                   "order by pengajuan_cuti.tanggal");
            }
                
            try {
                if(TCari.getText().equals("")){
                    ps.setString(1,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(2,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                }else{
                    ps.setString(1,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(2,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(3,"%"+TCari.getText().trim()+"%");
                    ps.setString(4,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(5,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(6,"%"+TCari.getText().trim()+"%");
                    ps.setString(7,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(8,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(9,"%"+TCari.getText().trim()+"%");
                    ps.setString(10,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(11,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(12,"%"+TCari.getText().trim()+"%");
                    ps.setString(13,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(14,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(15,"%"+TCari.getText().trim()+"%");
                    ps.setString(16,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(17,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(18,"%"+TCari.getText().trim()+"%");
                    ps.setString(19,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(20,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(21,"%"+TCari.getText().trim()+"%");
                    ps.setString(22,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(23,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(24,"%"+TCari.getText().trim()+"%");
                    ps.setString(25,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(26,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(27,"%"+TCari.getText().trim()+"%");
                    ps.setString(28,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(29,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(30,"%"+TCari.getText().trim()+"%");
                    ps.setString(31,Valid.SetTgl(DTPCari1.getSelectedItem()+""));
                    ps.setString(32,Valid.SetTgl(DTPCari2.getSelectedItem()+""));
                    ps.setString(33,"%"+TCari.getText().trim()+"%");
                }
                    
                rs=ps.executeQuery();
                total=0;
                while(rs.next()){
                    tabMode.addRow(new Object[]{
                        rs.getString("no_pengajuan"),rs.getString("tanggal"),rs.getString("tanggal_awal"),rs.getString("tanggal_akhir"),
                        rs.getString("nik"),rs.getString("namapengaju"),rs.getString("bidang"),rs.getString("departemen"),
                        rs.getString("urgensi"),rs.getString("alamat"),rs.getString("jumlah"),rs.getString("kepentingan"),
                        rs.getString("nik_pj"),rs.getString("namapj"),rs.getString("status"),
                        rs.getString("status_persetujuan_HRD") //tambahan kolom - ichsan
                    });
                    total=total+rs.getDouble("jumlah");
                }
            } catch (Exception e) {
                System.out.println("Notif : "+e);
            } finally{
                if(rs!=null){
                    rs.close();
                }
                if(ps!=null){
                    ps.close();
                }
            }
        }catch(Exception e){
            System.out.println("Notifikasi : "+e);
        }
        LCount.setText(""+tabMode.getRowCount());
        LCount1.setText(Valid.SetAngka(total));
    }

    */
    
    private void tampil() {
    Valid.tabelKosong(tabMode);
    try {
        // 1. Membangun query SQL dasar
        StringBuilder sql = new StringBuilder(
            "select pengajuan_cuti.no_pengajuan,pengajuan_cuti.tanggal,pengajuan_cuti.tanggal_awal,pengajuan_cuti.tanggal_akhir," +
            "pengajuan_cuti.nik,peg1.nama as namapengaju,peg1.bidang,peg1.departemen,pengajuan_cuti.urgensi,pengajuan_cuti.alamat," +
            "pengajuan_cuti.jumlah,pengajuan_cuti.kepentingan,pengajuan_cuti.nik_pj,peg2.nama as namapj,pengajuan_cuti.status," +
            "pengajuan_cuti.waktu_disetujui_atasan, pengajuan_cuti.status_persetujuan_HRD, pengajuan_cuti.waktu_disetujui_HRD " +
            "from pengajuan_cuti inner join pegawai as peg1 on pengajuan_cuti.nik=peg1.nik " +
            "inner join pegawai as peg2 on pengajuan_cuti.nik_pj=peg2.nik " +
            "where pengajuan_cuti.tanggal_awal between ? and ? "
        );

        // 2. Filter kondisional berdasarkan peran (role) pengguna
        boolean isAdminUtama = akses.getkode().equals("Admin Utama");

        if (!isAdminUtama) { // Jika BUKAN Admin Utama, terapkan filter biasa
            if (isHRD) {
                // Jika HRD, tampilkan (pengajuan untuknya SEBAGAI ATASAN) ATAU (pengajuan yang sudah disetujui atasan lain)
                sql.append("and (pengajuan_cuti.nik_pj = ? OR pengajuan_cuti.status = 'Disetujui') ");
            } else {
                // Jika BUKAN HRD, tampilkan hanya data yang ditujukan kepadanya sebagai atasan
                sql.append("and pengajuan_cuti.nik_pj = ? ");
            }
        }
        // Jika Admin Utama, tidak ada filter tambahan yang ditambahkan, sehingga semua data akan tampil

        // 3. Filter pencarian jika ada keyword
        if (!TCari.getText().trim().isEmpty()) {
            sql.append("and (pengajuan_cuti.no_pengajuan like ? or pengajuan_cuti.nik like ? or peg1.nama like ? or peg1.bidang like ? or " +
                       "peg1.departemen like ? or pengajuan_cuti.urgensi like ? or pengajuan_cuti.alamat like ? or pengajuan_cuti.kepentingan like ? or " +
                       "pengajuan_cuti.nik_pj like ? or peg2.nama like ? or pengajuan_cuti.status like ?) ");
        }

        sql.append("order by pengajuan_cuti.tanggal");

        ps = koneksi.prepareStatement(sql.toString());
        
        try {
            int paramIndex = 1;
            ps.setString(paramIndex++, Valid.SetTgl(DTPCari1.getSelectedItem() + ""));
            ps.setString(paramIndex++, Valid.SetTgl(DTPCari2.getSelectedItem() + ""));

            // Hanya set parameter NIK PJ jika BUKAN Admin Utama
            if (!isAdminUtama) {
                ps.setString(paramIndex++, akses.getkode());
            }
            
            if (!TCari.getText().trim().isEmpty()) {
                String keyword = "%" + TCari.getText().trim() + "%";
                for (int j = 0; j < 11; j++) { 
                    ps.setString(paramIndex++, keyword);
                }
            }

            rs = ps.executeQuery();
            total = 0;
            while (rs.next()) {
                tabMode.addRow(new Object[]{
                    rs.getString("no_pengajuan"), rs.getString("tanggal"), rs.getString("tanggal_awal"), rs.getString("tanggal_akhir"),
                    rs.getString("nik"), rs.getString("namapengaju"), rs.getString("bidang"), rs.getString("departemen"),
                    rs.getString("urgensi"), rs.getString("alamat"), rs.getString("jumlah"), rs.getString("kepentingan"),
                    rs.getString("nik_pj"), rs.getString("namapj"), rs.getString("status"),
                    rs.getString("waktu_disetujui_atasan"), rs.getString("status_persetujuan_HRD"), rs.getString("waktu_disetujui_HRD")
                });
                total = total + rs.getDouble("jumlah");
            }
        } catch (Exception e) {
            System.out.println("Notif : " + e);
        } finally {
            if (rs != null) { rs.close(); }
            if (ps != null) { ps.close(); }
        }
    } catch (Exception e) {
        System.out.println("Notifikasi : " + e);
    }
    LCount.setText("" + tabMode.getRowCount());
    LCount1.setText(Valid.SetAngka(total));
}

    
    private void emptTeks() {
        Tanggal.setDate(new Date());
        Tgl1.setDate(new Date());
        Tgl2.setDate(new Date());
        Alamat.setText("");
        Jumlah.setText("0");
        Kepentingan.setText("");
        KdPetugasPJ.setText("");
        NmPetugasPJ.setText("");
        KdPetugas.setText("");
        NmPetugas.setText("");
        autoNomor();
        Urgensi.requestFocus();
    }
    
    private void getData() {
        if(tbObat.getSelectedRow()!= -1){
        NoPengajuan.setText(tbObat.getValueAt(tbObat.getSelectedRow(),0).toString());
        Valid.SetTgl(Tanggal,tbObat.getValueAt(tbObat.getSelectedRow(),1).toString());
        Valid.SetTgl(Tgl1,tbObat.getValueAt(tbObat.getSelectedRow(),2).toString());
        Valid.SetTgl(Tgl2,tbObat.getValueAt(tbObat.getSelectedRow(),3).toString());
        KdPetugas.setText(tbObat.getValueAt(tbObat.getSelectedRow(),4).toString());
        NmPetugas.setText(tbObat.getValueAt(tbObat.getSelectedRow(),5).toString());
        Bidang.setText(tbObat.getValueAt(tbObat.getSelectedRow(),6).toString());
        Departemen.setText(tbObat.getValueAt(tbObat.getSelectedRow(),7).toString());
        Urgensi.setSelectedItem(tbObat.getValueAt(tbObat.getSelectedRow(),8).toString());
        Alamat.setText(tbObat.getValueAt(tbObat.getSelectedRow(),9).toString());
        Jumlah.setText(tbObat.getValueAt(tbObat.getSelectedRow(),10).toString());
        Kepentingan.setText(tbObat.getValueAt(tbObat.getSelectedRow(),11).toString());
        KdPetugasPJ.setText(tbObat.getValueAt(tbObat.getSelectedRow(),12).toString());
        NmPetugasPJ.setText(tbObat.getValueAt(tbObat.getSelectedRow(),13).toString());
        
        String nikPJdiData = tbObat.getValueAt(tbObat.getSelectedRow(), 12).toString();
        String nikUserLogin = akses.getkode();
        
        // Logika untuk menampilkan status yang relevan sesuai konteks
        // Jika pengguna login adalah atasan langsung di data ini, ATAU jika pengguna bukan HRD,
        // maka tampilkan status persetujuan atasan.
        if (nikUserLogin.equals(nikPJdiData) || !isHRD) {
            Status.setSelectedItem(tbObat.getValueAt(tbObat.getSelectedRow(), 14).toString());
        } else { // Jika pengguna adalah HRD dan bukan atasan langsung, tampilkan status HRD.
            Status.setSelectedItem(tbObat.getValueAt(tbObat.getSelectedRow(), 16).toString());
        }
        }
    }

    private void isForm(){
        if(ChkInput.isSelected()==true){
            ChkInput.setVisible(false);
            PanelInput.setPreferredSize(new Dimension(WIDTH,185));
            FormInput.setVisible(true);      
            ChkInput.setVisible(true);
        }else if(ChkInput.isSelected()==false){           
            ChkInput.setVisible(false);            
            PanelInput.setPreferredSize(new Dimension(WIDTH,20));
            FormInput.setVisible(false);      
            ChkInput.setVisible(true);
        }
    }
    
    public void isCek(){
        BtnSimpan.setEnabled(akses.getpengajuan_cuti());
        BtnHapus.setEnabled(akses.getpengajuan_cuti());
        BtnEdit.setEnabled(akses.getpengajuan_cuti());
    }
    
    private void autoNomor() {
        Valid.autoNomer3("select ifnull(MAX(CONVERT(RIGHT(no_pengajuan,3),signed)),0) from pengajuan_cuti where tanggal='"+Valid.SetTgl(Tanggal.getSelectedItem()+"")+"' ",
                "PC"+Tanggal.getSelectedItem().toString().substring(6,10)+Tanggal.getSelectedItem().toString().substring(3,5)+Tanggal.getSelectedItem().toString().substring(0,2),3,NoPengajuan); 
    }
    
    
    private void CreatePDFWA(String FileName) {
    this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
            Map<String, Object> param = new HashMap<>();
            param.put("NoPengajuan",NoPengajuan.getText());  //ambil nomor surat cuti
            param.put("nippetugas",KdPetugas.getText());  //ambil NIP pengaju cuti
            param.put("namapetugas",NmPetugas.getText());  //ambil nama pengaju cuti
            param.put("namaatasan",NmPetugasPJ.getText());  //ambil nama Atasan cuti
            param.put("TanggalAwal",Sequel.cariIsi("select tanggal_awal from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //ambil tgl awal cuti
            param.put("TanggalAkhir",Sequel.cariIsi("select tanggal_akhir from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //ambil tgl akhir cuti
            param.put("lamacuti",Jumlah.getText());  //jumlah cuti berapa hari
            param.put("kepentingan",Kepentingan.getText());  //Keterangan / alasan cuti
            param.put("status",Sequel.cariIsi("select status_persetujuan_HRD from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //status approval hrd
            param.put("TanggalApproval",Sequel.cariIsi("select DATE_FORMAT(pengajuan_cuti.waktu_disetujui_HRD,'%d-%m-%Y') from pengajuan_cuti where no_pengajuan=? LIMIT 1", NoPengajuan.getText())); //ambil tgl approval hRD
            param.put("namars",akses.getnamars());
            param.put("alamatrs",akses.getalamatrs());
            param.put("kotars",akses.getkabupatenrs());
            param.put("propinsirs",akses.getpropinsirs());
            param.put("kontakrs",akses.getkontakrs());
            param.put("emailrs",akses.getemailrs());
            param.put("finger","Dikeluarkan di "+akses.getnamars()+", Kabupaten/Kota "+akses.getkabupatenrs()+"\nDitandatangani secara elektronik oleh Unit HRD \n "+Sequel.cariIsi("select DATE_FORMAT(pengajuan_cuti.waktu_disetujui_HRD,'%d-%m-%Y') from pengajuan_cuti where pengajuan_cuti.no_pengajuan=?",NoPengajuan.getText()));
            param.put("logo",Sequel.cariGambar("select setting.logo from setting"));
            Valid.MyReportPDFqryUpload("rptApprovalCuti.jasper","report","::[ Approval Cuti ]::",
                          "select * from pengajuan_cuti where pengajuan_cuti.no_pengajuan='"+NoPengajuan.getText()+"' ",FileName, param);
    
        }
    
    private void UploadPDF2(String FileName, String docpath) {
     try {
        //koneksiwa = koneksiDBWa_mgm.condb();  //buat koneksi baru untuk persiapan mengirim values ke dalam wa_outbox di database lain
        // Ambil no_pengajuan dari baris tabel yang sedang dipilih
        String noPengajuan = tbObat.getValueAt(tbObat.getSelectedRow(), 0).toString();
        
        // Variabel untuk menampung data pegawai
        String nmPegawai = "";
        String noHpPegawai = "";
        String jk = "";

        // Step 1: Perbaiki query untuk mengambil data pegawai dengan lebih andal
        try (PreparedStatement ps1 = koneksi.prepareStatement(
            "SELECT p.nama, p.jk, COALESCE(pet.no_telp, dok.no_telp) as no_hp " +
            "FROM pengajuan_cuti pc " +
            "JOIN pegawai p ON pc.nik = p.nik " +
            "LEFT JOIN petugas pet ON p.nik = pet.nip " +
            "LEFT JOIN dokter dok ON p.nik = dok.kd_dokter " +
            "WHERE pc.no_pengajuan = ?")) {
            
            ps1.setString(1, noPengajuan);
            try (ResultSet rs1 = ps1.executeQuery()) {
                if (rs1.next()) {
                    nmPegawai = rs1.getString("nama");
                    noHpPegawai = rs1.getString("no_hp");
                    jk = rs1.getString("jk");
                }
            }
        } catch (Exception e) {
            System.out.println("Error saat mengambil data pegawai: " + e);
            JOptionPane.showMessageDialog(null, "Gagal mengambil data nomor HP pegawai.", "Kesalahan", JOptionPane.ERROR_MESSAGE);
            return;
        }

        // Step 2: Validasi nomor HP
        if (noHpPegawai == null || noHpPegawai.trim().isEmpty() || noHpPegawai.length() < 9 || !noHpPegawai.trim().matches("\\d+")) {
            JOptionPane.showMessageDialog(null, "Nomor HP pegawai tidak valid atau tidak ditemukan! (" + (noHpPegawai == null ? "NULL" : noHpPegawai) + ")", "Kesalahan", JOptionPane.ERROR_MESSAGE);
            return;
        }

        // Konversi nomor HP ke format internasional
        if (noHpPegawai.startsWith("0")) {
            noHpPegawai = "62" + noHpPegawai.substring(1);
        }

        // Step 3: Upload file (logika ini sudah benar)
        File file = new File("tmpPDF/" + FileName + ".pdf");
        if (!file.exists()) {
            System.out.println("File PDF tidak ditemukan: " + file.getAbsolutePath());
            JOptionPane.showMessageDialog(null, "File PDF Gagal Dibuat!", "Kesalahan", JOptionPane.ERROR_MESSAGE);
            return;
        }
        
        FileInputStream fis = new FileInputStream(file);
        HttpClient httpClient = new DefaultHttpClient();
        String uploadURL = "http://" + koneksiDBWa_mgm.IPFOLDERFILEWA1() + ":" + koneksiDBWa_mgm.PORTWEBWA1() + "/" + koneksiDBWa_mgm.FOLDERFILEWA1() + "/upload.php?doc=" + docpath;
        HttpPost postRequest = new HttpPost(uploadURL);
        
        MultipartEntity reqEntity = new MultipartEntity(HttpMultipartMode.BROWSER_COMPATIBLE);
        reqEntity.addPart("file", new InputStreamBody(fis, "application/pdf", FileName + ".pdf"));
        postRequest.setEntity(reqEntity);
        
        HttpResponse response = httpClient.execute(postRequest);
        fis.close();

        if (response.getStatusLine().getStatusCode() != 200) {
            System.out.println("File upload gagal. Response: " + response.getStatusLine());
            JOptionPane.showMessageDialog(null, "File Gagal di-Upload!", "Kesalahan", JOptionPane.ERROR_MESSAGE);
            return;
        }

        // ##### PERUBAHAN DIMULAI DI SINI #####
        // Step 4: Kirim pesan ke wa_outbox di database managerial
        try {
            // Inisialisasi koneksi ke database WA managerial
            koneksiwa = koneksiDBWa_mgm.condb();
            
            int currentHour = java.time.LocalTime.now().getHour();
            String greeting = (currentHour >= 4 && currentHour <= 10) ? "Selamat Pagi" : (currentHour > 10 && currentHour <= 15) ? "Selamat Siang" : (currentHour > 15 && currentHour <= 18) ? "Selamat Sore" : "Selamat Malam";
            
            String sapaan = "Pria".equalsIgnoreCase(jk) ? "Bpk " : "Wanita".equalsIgnoreCase(jk) ? "Ibu " : "Bpk/Ibu ";
            String salampembuka = greeting + ", " + sapaan + nmPegawai + "\n \n";
            String pesan = salampembuka + "Kami lampirkan surat persetujuan cuti Anda di " + akses.getnamars() + ".\n" +
                "Silakan unduh file terlampir. \n \n"+
                "Terima kasih atas perhatiannya. \nSalam Sehat. \n \n" +
                "*HRD " + akses.getnamars() + "*";      
            
            String waktukirim = java.time.LocalDateTime.now().format(java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
            
            // Menggunakan PreparedStatement dengan koneksiwa
            try (PreparedStatement psWa = koneksiwa.prepareStatement(
                "INSERT INTO wa_outbox (NOMOR, NOWA, PESAN, TANGGAL_JAM, STATUS, SOURCE, SENDER, TYPE, FILE) " +
                "VALUES (?, ?, ?, ?, 'ANTRIAN', 'KHANZA', 'NODEJS', 'FILE', ?)")) {
                
                psWa.setLong(1, 0);
                psWa.setString(2, noHpPegawai + "@c.us");
                psWa.setString(3, pesan);
                psWa.setString(4, waktukirim);
                psWa.setString(5, FileName + ".pdf");
                psWa.executeUpdate();
            }
        } catch (Exception e) {
            System.out.println("Gagal menyimpan ke wa_outbox managerial: " + e);
            JOptionPane.showMessageDialog(null, "Gagal mengirim notifikasi WA ke database managerial: " + e.getMessage(), "Kesalahan", JOptionPane.ERROR_MESSAGE);
        }
        // ##### AKHIR DARI PERUBAHAN #####
        
    } catch (Exception e) {
        System.out.println("Upload error: " + e);
        JOptionPane.showMessageDialog(null, "Terjadi kesalahan saat proses kirim WA: " + e.getMessage(), "Kesalahan", JOptionPane.ERROR_MESSAGE);
    }
    }
    
    private void HapusPDF() {
        File file = new File("tmpPDF");
        String[] myFiles;
        if (file.isDirectory()) {
            myFiles = file.list();
            for (int i = 0; i < myFiles.length; i++) {
                File myFile = new File(file, myFiles[i]);
                myFile.delete();
            }
        }
    }
    
    private void kirimNotifikasiWA() {
        reply = JOptionPane.showConfirmDialog(rootPane,"Mau memberitahukan ke pegawai via WA?","Konfirmasi",JOptionPane.YES_NO_OPTION);
        if (reply == JOptionPane.YES_OPTION) {
            try {
                this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
                FileName = "Surat_Approval_Cuti_" + tbObat.getValueAt(tbObat.getSelectedRow(), 0).toString();
                CreatePDFWA(FileName);
                UploadPDF2(FileName, "media/");
                HapusPDF();
                JOptionPane.showMessageDialog(null, "OK, notifikasi WA telah dimasukkan ke dalam antrian kirim.");
            } finally {
                this.setCursor(Cursor.getDefaultCursor());
            }
        }
    }
}
