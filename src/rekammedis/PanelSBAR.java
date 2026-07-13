package rekammedis;

import fungsi.WarnaTable;
import fungsi.batasInput;
import fungsi.koneksiDB;
import fungsi.sekuel;
import fungsi.validasi;
import fungsi.akses;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.Font;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.event.ActionEvent;
import java.awt.event.KeyEvent;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import javax.swing.BorderFactory;
import javax.swing.DefaultComboBoxModel;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.SwingConstants;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableColumn;
import kepegawaian.DlgCariPetugas;
import kepegawaian.DlgCariDokter;

/**
 * Panel Catatan SBAR (Situation, Background, Assessment, Recommendation)
 * Digunakan di DlgRawatInap dan DlgRawatJalan sebagai tab.
 *
 * @author ichsan
 * //mod by ichsan - complete rebuild phase 2
 */
public class PanelSBAR extends JPanel {

    // ========== Komponen UI ==========
    private JLabel lblJudul;
    private JLabel lblPanduan; //mod by ichsan

    // Field tanggal & jam (hidden - dikendalikan form induk)
    private JLabel lblTgl;
    private widget.Tanggal dtpTanggal;
    private JLabel lblJam;
    private widget.ComboBox cmbJam;
    private widget.ComboBox cmbMnt;
    private JPanel pJam;

    // Field SBAR
    private JLabel lblSituation;
    private widget.TextArea taSituation;
    private JScrollPane spSituation;
    private JLabel lblBackground;
    private widget.TextArea taBackground;
    private JScrollPane spBackground;
    private JLabel lblAssessment;
    private widget.TextArea taAssessment;
    private JScrollPane spAssessment;
    private JLabel lblRecommendation;
    private widget.TextArea taRecommendation;
    private JScrollPane spRecommendation;

    // Field Pemberi & Penerima
    private JLabel lblPemberi;
    private widget.TextBox tKdPemberi;
    private widget.TextBox tNmPemberi;
    private widget.Button btnCariPemberi;
    private JLabel lblPenerima;
    private widget.TextBox tKdPenerima;
    private widget.TextBox tNmPenerima;
    private widget.Button btnCariPenerima;

    // Field TBAK
    private JLabel lblTbak;
    private widget.ComboBox cmbTbak;

    // Tombol - Simpan hidden, hanya Cetak tampil //mod by ichsan
    private widget.Button btnSimpan;
    private widget.Button btnPrint;

    // Parent Date/Time Controls //mod by ichsan
    private widget.Tanggal parentDtpTgl;
    private widget.ComboBox parentCmbJam;
    private widget.ComboBox parentCmbMnt;
    private widget.ComboBox parentCmbDtk;

    // Filter tanggal //mod by ichsan
    private JPanel panelFilter;
    private JLabel lblFilterAwal;
    private widget.Tanggal dtpFilterAwal;
    private JLabel lblFilterAkhir;
    private widget.Tanggal dtpFilterAkhir;
    private widget.Button btnFilter;
    private widget.Button btnFilterReset;

    // Tabel
    private JTable tbSBAR;
    private JScrollPane spTable;
    private DefaultTableModel tabMode;

    // ========== Data & Logic ==========
    private String noRawat = "";
    private boolean modeEdit = false;
    private String tglEdit = "";
    private String jamEdit = "";
    private String AKTIFKANTRACKSQL = koneksiDB.AKTIFKANTRACKSQL(); //mod by ichsan - audit trail

    private Connection koneksi = koneksiDB.condb();
    private sekuel Sequel = new sekuel();
    private validasi Valid = new validasi();

    private static final Font FONT_LABEL = new Font("Tahoma", Font.BOLD, 11);
    private static final Font FONT_NORMAL = new Font("Tahoma", Font.PLAIN, 11);
    private static final Font FONT_PANDUAN = new Font("Tahoma", Font.PLAIN, 10); //mod by ichsan
    private static final Color BG_PANEL = new Color(215, 225, 215);
    private static final Color BG_JUDUL = new Color(0, 102, 0);
    private static final Color BG_PANDUAN = new Color(255, 255, 204); //mod by ichsan

    public PanelSBAR() {
        initUI();
        setupListeners();
        resetForm();
        autoFillLogin(); //mod by ichsan
    }

    // =============================================
    //  Init UI
    // =============================================
    private void initUI() {
        setLayout(new BorderLayout(0, 2));
        setBackground(BG_PANEL);

        // Modifikasi SIMRS Khanza custom ichsan: Menghilangkan judul/panduan atas statis
        // Akhir Modifikasi SIMRS Khanza custom ichsan

        // --- PANEL FORM ---
        widget.PanelBiasa panelForm = new widget.PanelBiasa();
        panelForm.setBackground(BG_PANEL);
        panelForm.setLayout(new GridBagLayout());
        GridBagConstraints gbc = new GridBagConstraints();
        gbc.insets = new Insets(2, 4, 2, 4);
        gbc.anchor = GridBagConstraints.WEST;

        // Baris 0: Tanggal & Jam (hidden)
        gbc.gridx = 0; gbc.gridy = 0; gbc.gridwidth = 1;
        lblTgl = new JLabel("Tanggal :");
        lblTgl.setFont(FONT_LABEL);
        panelForm.add(lblTgl, gbc);

        gbc.gridx = 1; gbc.fill = GridBagConstraints.HORIZONTAL;
        dtpTanggal = new widget.Tanggal();
        dtpTanggal.setDate(new Date());
        dtpTanggal.setPreferredSize(new Dimension(120, 23));
        panelForm.add(dtpTanggal, gbc);

        gbc.gridx = 2; gbc.fill = GridBagConstraints.NONE;
        lblJam = new JLabel("Jam :");
        lblJam.setFont(FONT_LABEL);
        panelForm.add(lblJam, gbc);

        pJam = new JPanel();
        pJam.setOpaque(false);
        cmbJam = new widget.ComboBox();
        cmbJam.setPreferredSize(new Dimension(55, 23));
        cmbJam.setFont(FONT_NORMAL);
        String[] jam = new String[24];
        for (int h = 0; h < 24; h++) jam[h] = String.format("%02d", h);
        cmbJam.setModel(new DefaultComboBoxModel(jam));
        cmbMnt = new widget.ComboBox();
        cmbMnt.setPreferredSize(new Dimension(55, 23));
        cmbMnt.setFont(FONT_NORMAL);
        String[] mnt = new String[60];
        for (int m = 0; m < 60; m++) mnt[m] = String.format("%02d", m);
        cmbMnt.setModel(new DefaultComboBoxModel(mnt));
        pJam.add(cmbJam);
        pJam.add(new JLabel(":"));
        pJam.add(cmbMnt);
        gbc.gridx = 3; gbc.gridwidth = 2;
        panelForm.add(pJam, gbc);

        Calendar cal = Calendar.getInstance();
        cmbJam.setSelectedItem(String.format("%02d", cal.get(Calendar.HOUR_OF_DAY)));
        cmbMnt.setSelectedItem(String.format("%02d", cal.get(Calendar.MINUTE)));

        // Baris 1: Situation
        gbc.gridx = 0; gbc.gridy = 1; gbc.gridwidth = 1; gbc.fill = GridBagConstraints.NONE;
        lblSituation = new JLabel("Situation :");
        lblSituation.setFont(FONT_LABEL);
        panelForm.add(lblSituation, gbc);

        gbc.gridx = 1; gbc.gridwidth = 4; gbc.fill = GridBagConstraints.HORIZONTAL; gbc.weightx = 1.0;
        taSituation = new widget.TextArea();
        taSituation.setFont(FONT_NORMAL);
        taSituation.setRows(2);
        taSituation.setLineWrap(true);
        taSituation.setWrapStyleWord(true);
        spSituation = new JScrollPane(taSituation);
        spSituation.setPreferredSize(new Dimension(500, 48));
        panelForm.add(spSituation, gbc);

        // Baris 2: Background
        gbc.gridx = 0; gbc.gridy = 2; gbc.gridwidth = 1; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        lblBackground = new JLabel("Background :");
        lblBackground.setFont(FONT_LABEL);
        panelForm.add(lblBackground, gbc);

        gbc.gridx = 1; gbc.gridwidth = 4; gbc.fill = GridBagConstraints.HORIZONTAL; gbc.weightx = 1.0;
        taBackground = new widget.TextArea();
        taBackground.setFont(FONT_NORMAL);
        taBackground.setRows(2);
        taBackground.setLineWrap(true);
        taBackground.setWrapStyleWord(true);
        spBackground = new JScrollPane(taBackground);
        spBackground.setPreferredSize(new Dimension(500, 48));
        panelForm.add(spBackground, gbc);

        // Baris 3: Assessment
        gbc.gridx = 0; gbc.gridy = 3; gbc.gridwidth = 1; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        lblAssessment = new JLabel("Assessment :");
        lblAssessment.setFont(FONT_LABEL);
        panelForm.add(lblAssessment, gbc);

        gbc.gridx = 1; gbc.gridwidth = 4; gbc.fill = GridBagConstraints.HORIZONTAL; gbc.weightx = 1.0;
        taAssessment = new widget.TextArea();
        taAssessment.setFont(FONT_NORMAL);
        taAssessment.setRows(2);
        taAssessment.setLineWrap(true);
        taAssessment.setWrapStyleWord(true);
        spAssessment = new JScrollPane(taAssessment);
        spAssessment.setPreferredSize(new Dimension(500, 48));
        panelForm.add(spAssessment, gbc);

        // Baris 4: Recommendation
        gbc.gridx = 0; gbc.gridy = 4; gbc.gridwidth = 1; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        lblRecommendation = new JLabel("Recommendation :");
        lblRecommendation.setFont(FONT_LABEL);
        panelForm.add(lblRecommendation, gbc);

        gbc.gridx = 1; gbc.gridwidth = 4; gbc.fill = GridBagConstraints.HORIZONTAL; gbc.weightx = 1.0;
        taRecommendation = new widget.TextArea();
        taRecommendation.setFont(FONT_NORMAL);
        taRecommendation.setRows(2);
        taRecommendation.setLineWrap(true);
        taRecommendation.setWrapStyleWord(true);
        spRecommendation = new JScrollPane(taRecommendation);
        spRecommendation.setPreferredSize(new Dimension(500, 48));
        panelForm.add(spRecommendation, gbc);

        // Baris 5: Pemberi
        gbc.gridx = 0; gbc.gridy = 5; gbc.gridwidth = 1; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        lblPemberi = new JLabel("Pemberi :");
        lblPemberi.setFont(FONT_LABEL);
        panelForm.add(lblPemberi, gbc);

        tKdPemberi = new widget.TextBox();
        tKdPemberi.setFont(FONT_NORMAL);
        tKdPemberi.setDocument(new batasInput((byte) 20).getKata(tKdPemberi));
        tKdPemberi.setPreferredSize(new Dimension(80, 23));
        gbc.gridx = 1; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        panelForm.add(tKdPemberi, gbc);

        tNmPemberi = new widget.TextBox();
        tNmPemberi.setFont(FONT_NORMAL);
        tNmPemberi.setEditable(false);
        tNmPemberi.setPreferredSize(new Dimension(180, 23));
        gbc.gridx = 2; gbc.gridwidth = 2; gbc.fill = GridBagConstraints.HORIZONTAL; gbc.weightx = 0.5;
        panelForm.add(tNmPemberi, gbc);

        btnCariPemberi = new widget.Button();
        btnCariPemberi.setText("...");
        btnCariPemberi.setPreferredSize(new Dimension(30, 23));
        btnCariPemberi.setFocusPainted(false);
        gbc.gridx = 4; gbc.gridwidth = 1; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        panelForm.add(btnCariPemberi, gbc);

        // Baris 6: Penerima
        gbc.gridx = 0; gbc.gridy = 6; gbc.gridwidth = 1;
        lblPenerima = new JLabel("Penerima :");
        lblPenerima.setFont(FONT_LABEL);
        panelForm.add(lblPenerima, gbc);

        tKdPenerima = new widget.TextBox();
        tKdPenerima.setFont(FONT_NORMAL);
        tKdPenerima.setDocument(new batasInput((byte) 20).getKata(tKdPenerima));
        tKdPenerima.setPreferredSize(new Dimension(80, 23));
        gbc.gridx = 1;
        panelForm.add(tKdPenerima, gbc);

        tNmPenerima = new widget.TextBox();
        tNmPenerima.setFont(FONT_NORMAL);
        tNmPenerima.setEditable(false);
        tNmPenerima.setPreferredSize(new Dimension(180, 23));
        gbc.gridx = 2; gbc.gridwidth = 2; gbc.fill = GridBagConstraints.HORIZONTAL; gbc.weightx = 0.5;
        panelForm.add(tNmPenerima, gbc);

        btnCariPenerima = new widget.Button();
        btnCariPenerima.setText("...");
        btnCariPenerima.setPreferredSize(new Dimension(30, 23));
        btnCariPenerima.setFocusPainted(false);
        gbc.gridx = 4; gbc.gridwidth = 1; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        panelForm.add(btnCariPenerima, gbc);

        // Baris 7: TBAK
        gbc.gridx = 0; gbc.gridy = 7;
        lblTbak = new JLabel("TBAK :");
        lblTbak.setFont(FONT_LABEL);
        panelForm.add(lblTbak, gbc);

        cmbTbak = new widget.ComboBox();
        cmbTbak.setFont(FONT_NORMAL);
        cmbTbak.setModel(new DefaultComboBoxModel(new String[]{"Belum", "Sudah"}));
        cmbTbak.setPreferredSize(new Dimension(100, 23));
        gbc.gridx = 1;
        panelForm.add(cmbTbak, gbc);

        // Baris 8: Tombol Cetak //mod by ichsan
        JPanel panelBtn = new JPanel();
        panelBtn.setOpaque(false);

        btnSimpan = new widget.Button();
        btnSimpan.setText("Simpan");
        btnSimpan.setFont(FONT_NORMAL);
        btnSimpan.setPreferredSize(new Dimension(90, 25));
        btnSimpan.setFocusPainted(false);
        btnSimpan.setVisible(false); //mod by ichsan - trigger via simpanDariExternal
        panelBtn.add(btnSimpan);

        btnPrint = new widget.Button();
        btnPrint.setText("Cetak");
        btnPrint.setFont(FONT_NORMAL);
        btnPrint.setPreferredSize(new Dimension(80, 25));
        btnPrint.setFocusPainted(false);
        panelBtn.add(btnPrint);

        gbc.gridx = 0; gbc.gridy = 8; gbc.gridwidth = 5; gbc.fill = GridBagConstraints.NONE; gbc.weightx = 0;
        gbc.anchor = GridBagConstraints.CENTER;
        panelForm.add(panelBtn, gbc);

        // Modifikasi SIMRS Khanza custom ichsan: Form langsung dipasang di CENTER
        // Akhir Modifikasi SIMRS Khanza custom ichsan

        // --- PANEL BAWAH (filter + tabel) //mod by ichsan ---
        JPanel panelBawah = new JPanel(new BorderLayout(0, 2));
        panelBawah.setBackground(BG_PANEL);

        panelFilter = new JPanel(new FlowLayout(FlowLayout.LEFT, 4, 2));
        panelFilter.setOpaque(false);
        panelFilter.setBorder(BorderFactory.createTitledBorder("Filter Tanggal"));

        lblFilterAwal = new JLabel("Tgl Awal:");
        lblFilterAwal.setFont(FONT_LABEL);
        panelFilter.add(lblFilterAwal);

        dtpFilterAwal = new widget.Tanggal();
        dtpFilterAwal.setPreferredSize(new Dimension(110, 23));
        Calendar c1 = Calendar.getInstance();
        c1.add(Calendar.MONTH, -1);
        dtpFilterAwal.setDate(c1.getTime());
        panelFilter.add(dtpFilterAwal);

        lblFilterAkhir = new JLabel("s/d:");
        lblFilterAkhir.setFont(FONT_LABEL);
        panelFilter.add(lblFilterAkhir);

        dtpFilterAkhir = new widget.Tanggal();
        dtpFilterAkhir.setPreferredSize(new Dimension(110, 23));
        dtpFilterAkhir.setDate(new Date());
        panelFilter.add(dtpFilterAkhir);

        btnFilter = new widget.Button();
        btnFilter.setText("Filter");
        btnFilter.setFont(FONT_NORMAL);
        btnFilter.setPreferredSize(new Dimension(70, 23));
        panelFilter.add(btnFilter);

        btnFilterReset = new widget.Button();
        btnFilterReset.setText("Semua Data");
        btnFilterReset.setFont(FONT_NORMAL);
        btnFilterReset.setPreferredSize(new Dimension(100, 23));
        panelFilter.add(btnFilterReset);

        // --- TABEL ---
        //mod by ichsan - kolom diperluas agar komprehensif seperti SOAP
        tabMode = new DefaultTableModel(null, new Object[]{
            "No.Rawat", "No.R.M.", "Nama Pasien", "Tanggal", "Jam",
            "Situation", "Background", "Assessment", "Recommendation",
            "Pemberi", "Penerima", "TBAK", "Wkt.Konfirmasi", "Perekam"}) {
            @Override
            public boolean isCellEditable(int r, int c) { return false; }
        };

        tbSBAR = new JTable(tabMode);
        tbSBAR.setAutoResizeMode(JTable.AUTO_RESIZE_OFF);
        tbSBAR.setPreferredScrollableViewportSize(new Dimension(800, 120));
        tbSBAR.setDefaultRenderer(Object.class, new WarnaTable());

        //mod by ichsan - lebar kolom disesuaikan agar mudah dibaca
        int[] colWidths = {105, 70, 150, 80, 55, 150, 150, 150, 150, 120, 120, 55, 130, 120};
        for (int i = 0; i < colWidths.length; i++) {
            TableColumn col = tbSBAR.getColumnModel().getColumn(i);
            col.setPreferredWidth(colWidths[i]);
        }

        spTable = new JScrollPane(tbSBAR);
        spTable.setPreferredSize(new Dimension(800, 130));
        spTable.setBorder(BorderFactory.createTitledBorder("Daftar Catatan SBAR"));

        panelBawah.add(panelFilter, BorderLayout.NORTH);
        panelBawah.add(spTable, BorderLayout.CENTER);

        // Sembunyikan tanggal, jam internal //mod by ichsan
        lblTgl.setVisible(false);
        dtpTanggal.setVisible(false);
        lblJam.setVisible(false);
        pJam.setVisible(false);

        // Modifikasi SIMRS Khanza custom ichsan: Setup collapsible panel juknis di kanan (BorderLayout.EAST)
        JPanel panelJuknis = new JPanel(new BorderLayout(0, 0));
        panelJuknis.setBackground(BG_PANEL);

        widget.Button btnToggle = new widget.Button();
        btnToggle.setText("<<");
        btnToggle.setToolTipText("Tampilkan Panduan SBAR");
        btnToggle.setPreferredSize(new java.awt.Dimension(20, 20));
        btnToggle.setFocusPainted(false);
        panelJuknis.add(btnToggle, java.awt.BorderLayout.WEST);

        JPanel panelKonten = new JPanel(new java.awt.BorderLayout(0, 5));
        panelKonten.setBackground(BG_PANEL);
        panelKonten.setPreferredSize(new java.awt.Dimension(230, 400));
        panelKonten.setVisible(false); // Default collapsed

        lblJudul = new JLabel("PANDUAN / JUKNIS SBAR");
        lblJudul.setFont(new java.awt.Font("Tahoma", java.awt.Font.BOLD, 11));
        lblJudul.setForeground(Color.WHITE);
        lblJudul.setOpaque(true);
        lblJudul.setBackground(BG_JUDUL);
        lblJudul.setHorizontalAlignment(SwingConstants.CENTER);
        lblJudul.setPreferredSize(new java.awt.Dimension(230, 24));
        panelKonten.add(lblJudul, java.awt.BorderLayout.NORTH);

        lblPanduan = new JLabel(
            "<html>" +
            "<body style='padding: 6px; font-family: Tahoma; font-size: 10px; color: #333333;'>" +
            "<b>PANDUAN SBAR:</b><br><br>" +
            "<b>S (Situation):</b><br>Kondisi atau keluhan pasien saat ini.<br><br>" +
            "<b>B (Background):</b><br>Latar belakang klinis, riwayat penyakit, atau terapi.<br><br>" +
            "<b>A (Assessment):</b><br>Penilaian klinis atau hasil pemeriksaan.<br><br>" +
            "<b>R (Recommendation):</b><br>Rekomendasi tindakan atau instruksi.<br><br>" +
            "<hr style='border: 0; border-top: 1px solid #ccc;'>" +
            "<b>KAPAN TBAK?</b><br>" +
            "Wajib dilakukan saat menerima instruksi verbal atau via telepon.<br><br>" +
            "<b>LANGKAH TBAK:</b><br>" +
            "1. <b>Tulis</b> instruksi secara lengkap.<br>" +
            "2. <b>Baca ulang</b> kepada pemberi instruksi.<br>" +
            "3. <b>Konfirmasi</b> kebenaran (tandai 'Sudah')." +
            "</body>" +
            "</html>"
        );
        lblPanduan.setFont(FONT_PANDUAN);
        lblPanduan.setVerticalAlignment(SwingConstants.TOP);
        lblPanduan.setOpaque(true);
        lblPanduan.setBackground(BG_PANDUAN);
        lblPanduan.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(1, 1, 1, 1, new Color(200, 180, 0)),
            BorderFactory.createEmptyBorder(5, 5, 5, 5)
        ));
        panelKonten.add(lblPanduan, java.awt.BorderLayout.CENTER);

        panelJuknis.add(panelKonten, java.awt.BorderLayout.CENTER);
        panelJuknis.setPreferredSize(new java.awt.Dimension(20, 400));

        btnToggle.addActionListener(e -> {
            if (panelKonten.isVisible()) {
                panelKonten.setVisible(false);
                btnToggle.setText("<<");
                btnToggle.setToolTipText("Tampilkan Panduan SBAR");
                panelJuknis.setPreferredSize(new java.awt.Dimension(20, panelJuknis.getHeight()));
            } else {
                panelKonten.setVisible(true);
                btnToggle.setText(">>");
                btnToggle.setToolTipText("Sembunyikan Panduan SBAR");
                panelJuknis.setPreferredSize(new java.awt.Dimension(250, panelJuknis.getHeight()));
            }
            panelJuknis.revalidate();
            revalidate();
        });

        add(panelForm, java.awt.BorderLayout.CENTER);
        add(panelJuknis, java.awt.BorderLayout.EAST);
        add(panelBawah, java.awt.BorderLayout.SOUTH);
        // Akhir Modifikasi SIMRS Khanza custom ichsan
    }

    // =============================================
    //  Setup Listeners
    // =============================================
    private void setupListeners() {
        btnSimpan.addActionListener(this::onSimpan);
        btnPrint.addActionListener(this::onCetak);
        btnCariPemberi.addActionListener(e -> cariPetugas(true));
        btnCariPenerima.addActionListener(e -> cariPetugas(false));

        tKdPemberi.addKeyListener(new java.awt.event.KeyAdapter() {
            @Override
            public void keyPressed(java.awt.event.KeyEvent evt) {
                if (evt.getKeyCode() == KeyEvent.VK_ENTER) {
                    cariNamaPetugas(tKdPemberi.getText().trim(), true);
                }
            }
        });

        tKdPenerima.addKeyListener(new java.awt.event.KeyAdapter() {
            @Override
            public void keyPressed(java.awt.event.KeyEvent evt) {
                if (evt.getKeyCode() == KeyEvent.VK_ENTER) {
                    cariNamaPetugas(tKdPenerima.getText().trim(), false);
                }
            }
        });

        tbSBAR.addMouseListener(new MouseAdapter() {
            @Override
            public void mouseClicked(MouseEvent e) {
                onTabelKlik();
            }
        });

        //mod by ichsan - Filter listeners
        btnFilter.addActionListener(e -> tampilDenganFilter());
        btnFilterReset.addActionListener(e -> tampilSemuaData());
    }

    // =============================================
    //  Auto-fill Login //mod by ichsan
    // =============================================
    private void autoFillLogin() {
        String kode = akses.getkode();
        if (kode == null || kode.trim().isEmpty() || kode.equals("Admin Utama")) return;

        PreparedStatement psLoc = null;
        ResultSet rsLoc = null;
        try {
            psLoc = koneksi.prepareStatement("SELECT nama FROM petugas WHERE nip=?");
            psLoc.setString(1, kode);
            rsLoc = psLoc.executeQuery();
            if (rsLoc.next()) {
                tKdPemberi.setText(kode);
                tNmPemberi.setText(rsLoc.getString("nama"));
                return;
            }
        } catch (Exception ex) {
            System.out.println("PanelSBAR.autoFillLogin() petugas : " + ex);
        } finally {
            try { if (rsLoc != null) rsLoc.close(); } catch (Exception e) {}
            try { if (psLoc != null) psLoc.close(); } catch (Exception e) {}
        }

        PreparedStatement psLoc2 = null;
        ResultSet rsLoc2 = null;
        try {
            psLoc2 = koneksi.prepareStatement("SELECT nm_dokter FROM dokter WHERE kd_dokter=?");
            psLoc2.setString(1, kode);
            rsLoc2 = psLoc2.executeQuery();
            if (rsLoc2.next()) {
                tKdPenerima.setText(kode);
                tNmPenerima.setText(rsLoc2.getString("nm_dokter"));
            }
        } catch (Exception ex) {
            System.out.println("PanelSBAR.autoFillLogin() dokter : " + ex);
        } finally {
            try { if (rsLoc2 != null) rsLoc2.close(); } catch (Exception e) {}
            try { if (psLoc2 != null) psLoc2.close(); } catch (Exception e) {}
        }
    }

    // =============================================
    //  Public API
    // =============================================
    public void setNoRawat(String noRawat) {
        this.noRawat = noRawat;
        tampilDenganFilter(); //mod by ichsan
    }

    public String getNoRawat() {
        return noRawat;
    }

    //mod by ichsan - tampil dengan filter tanggal aktif, kolom diperluas
    public void tampilDenganFilter() {
        if (noRawat == null || noRawat.trim().isEmpty()) {
            Valid.tabelKosong(tabMode);
            return;
        }
        SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd");
        String tglAwal = sdf.format(dtpFilterAwal.getDate());
        String tglAkhir = sdf.format(dtpFilterAkhir.getDate());
        tampilQuery(
            "SELECT s.no_rawat, rp.no_rkm_medis, ps.nm_pasien, s.tanggal, s.jam, " +
            "s.situation, s.background, s.assessment, s.recommendation, " +
            "COALESCE(p.nama,'') AS nm_pemberi, COALESCE(d.nm_dokter,'') AS nm_penerima, " +
            "s.tbak, COALESCE(s.waktu_konfirmasi,'') AS waktu_konfirmasi, " +
            "COALESCE(u.nama,s.nip_perekam,'') AS nm_perekam " +
            "FROM rekammedis_sbar s " +
            "INNER JOIN reg_periksa rp ON s.no_rawat=rp.no_rawat " +
            "INNER JOIN pasien ps ON rp.no_rkm_medis=ps.no_rkm_medis " +
            "LEFT JOIN petugas p ON s.nip_pemberi=p.nip " +
            "LEFT JOIN dokter d ON s.nip_penerima=d.kd_dokter " +
            "LEFT JOIN petugas u ON s.nip_perekam=u.nip " +
            "WHERE s.no_rawat=? AND s.tanggal BETWEEN ? AND ? ORDER BY s.tanggal DESC, s.jam DESC",
            noRawat, tglAwal, tglAkhir
        );
    }

    //mod by ichsan - tampil semua data tanpa filter, kolom diperluas
    public void tampilSemuaData() {
        if (noRawat == null || noRawat.trim().isEmpty()) {
            Valid.tabelKosong(tabMode);
            return;
        }
        tampilQuery(
            "SELECT s.no_rawat, rp.no_rkm_medis, ps.nm_pasien, s.tanggal, s.jam, " +
            "s.situation, s.background, s.assessment, s.recommendation, " +
            "COALESCE(p.nama,'') AS nm_pemberi, COALESCE(d.nm_dokter,'') AS nm_penerima, " +
            "s.tbak, COALESCE(s.waktu_konfirmasi,'') AS waktu_konfirmasi, " +
            "COALESCE(u.nama,s.nip_perekam,'') AS nm_perekam " +
            "FROM rekammedis_sbar s " +
            "INNER JOIN reg_periksa rp ON s.no_rawat=rp.no_rawat " +
            "INNER JOIN pasien ps ON rp.no_rkm_medis=ps.no_rkm_medis " +
            "LEFT JOIN petugas p ON s.nip_pemberi=p.nip " +
            "LEFT JOIN dokter d ON s.nip_penerima=d.kd_dokter " +
            "LEFT JOIN petugas u ON s.nip_perekam=u.nip " +
            "WHERE s.no_rawat=? ORDER BY s.tanggal DESC, s.jam DESC",
            noRawat
        );
    }

    //mod by ichsan - eksekusi query tampil dengan variabel lokal
    private void tampilQuery(String query, String... params) {
        PreparedStatement psLoc = null;
        ResultSet rsLoc = null;
        try {
            Valid.tabelKosong(tabMode);
            psLoc = koneksi.prepareStatement(query);
            for (int i = 0; i < params.length; i++) {
                psLoc.setString(i + 1, params[i]);
            }
            rsLoc = psLoc.executeQuery();
            while (rsLoc.next()) {
                //mod by ichsan - kolom diperluas sesuai table model baru
                tabMode.addRow(new Object[]{
                    rsLoc.getString("no_rawat"), rsLoc.getString("no_rkm_medis"),
                    rsLoc.getString("nm_pasien"), rsLoc.getString("tanggal"),
                    rsLoc.getString("jam"), rsLoc.getString("situation"),
                    rsLoc.getString("background"), rsLoc.getString("assessment"),
                    rsLoc.getString("recommendation"), rsLoc.getString("nm_pemberi"),
                    rsLoc.getString("nm_penerima"), rsLoc.getString("tbak"),
                    rsLoc.getString("waktu_konfirmasi"), rsLoc.getString("nm_perekam")
                });
            }
        } catch (Exception ex) {
            System.out.println("PanelSBAR.tampilQuery() : " + ex);
        } finally {
            try { if (rsLoc != null) rsLoc.close(); } catch (Exception e) {}
            try { if (psLoc != null) psLoc.close(); } catch (Exception e) {}
        }
    }

    public void isCek() {
        btnSimpan.setEnabled(akses.gettindakan_ranap());
        btnPrint.setEnabled(akses.gettindakan_ranap());
    }

    // =============================================
    //  Event Handlers
    // =============================================
    private void onSimpan(ActionEvent e) {
        if (noRawat.trim().isEmpty()) {
            JOptionPane.showMessageDialog(this, "Pilih pasien terlebih dahulu!", "Peringatan", JOptionPane.WARNING_MESSAGE);
            return;
        }
        if (taSituation.getText().trim().isEmpty()) {
            JOptionPane.showMessageDialog(this, "Isi kolom Situation!", "Peringatan", JOptionPane.WARNING_MESSAGE);
            taSituation.requestFocus();
            return;
        }
        if (tKdPemberi.getText().trim().isEmpty()) {
            JOptionPane.showMessageDialog(this, "Isi NIP Pemberi!", "Peringatan", JOptionPane.WARNING_MESSAGE);
            tKdPemberi.requestFocus();
            return;
        }

        SimpleDateFormat sdfDate = new SimpleDateFormat("yyyy-MM-dd");
        String tanggal = sdfDate.format(dtpTanggal.getDate());
        String jamStr = cmbJam.getSelectedItem() + ":" + cmbMnt.getSelectedItem() + ":00";

        PreparedStatement psLoc = null; //mod by ichsan - local PS
        try {
            String newTbak = cmbTbak.getSelectedItem().toString();
            if (modeEdit) {
                String oldTbak = "";
                String waktuKonfirmasi = null;
                PreparedStatement psCek = koneksi.prepareStatement("SELECT tbak, waktu_konfirmasi FROM rekammedis_sbar WHERE no_rawat=? AND tanggal=? AND jam=?");
                psCek.setString(1, noRawat);
                psCek.setString(2, tglEdit);
                psCek.setString(3, jamEdit);
                ResultSet rsCek = psCek.executeQuery();
                if (rsCek.next()) {
                    oldTbak = rsCek.getString("tbak");
                    waktuKonfirmasi = rsCek.getString("waktu_konfirmasi");
                }
                rsCek.close();
                psCek.close();

                if (newTbak.equals("Sudah")) {
                    if (!oldTbak.equals("Sudah") || waktuKonfirmasi == null) {
                        waktuKonfirmasi = "NOW()";
                    }
                } else {
                    waktuKonfirmasi = "NULL";
                }

                //mod by ichsan - tambah nip_perekam pada UPDATE
                String query = "UPDATE rekammedis_sbar SET tanggal=?, jam=?, situation=?, background=?, assessment=?, " +
                               "recommendation=?, nip_pemberi=?, nip_penerima=?, tbak=?, waktu_konfirmasi=" +
                               (waktuKonfirmasi.equals("NOW()") ? "NOW()" : (waktuKonfirmasi.equals("NULL") ? "NULL" : "?")) +
                               ", nip_perekam=? WHERE no_rawat=? AND tanggal=? AND jam=?";
                psLoc = koneksi.prepareStatement(query);
                psLoc.setString(1, tanggal);
                psLoc.setString(2, jamStr);
                psLoc.setString(3, taSituation.getText().trim());
                psLoc.setString(4, taBackground.getText().trim());
                psLoc.setString(5, taAssessment.getText().trim());
                psLoc.setString(6, taRecommendation.getText().trim());
                psLoc.setString(7, tKdPemberi.getText().trim());
                psLoc.setString(8, tKdPenerima.getText().trim().isEmpty() ? null : tKdPenerima.getText().trim());
                psLoc.setString(9, newTbak);
                int idx = 10;
                if (!waktuKonfirmasi.equals("NOW()") && !waktuKonfirmasi.equals("NULL")) {
                    psLoc.setString(idx++, waktuKonfirmasi);
                }
                psLoc.setString(idx++, akses.getkode()); //mod by ichsan - perekam
                psLoc.setString(idx++, noRawat);
                psLoc.setString(idx++, tglEdit);
                psLoc.setString(idx++, jamEdit);
                psLoc.executeUpdate();
                simpanTrack("update rekammedis_sbar no_rawat='"+noRawat+"' tgl='"+tglEdit+"' jam='"+jamEdit+"'"); //mod by ichsan - audit trail
                JOptionPane.showMessageDialog(this, "Data berhasil diupdate!", "Info", JOptionPane.INFORMATION_MESSAGE);
            } else {
                PreparedStatement cek = koneksi.prepareStatement(
                    "SELECT 1 FROM rekammedis_sbar WHERE no_rawat=? AND tanggal=? AND jam=?");
                cek.setString(1, noRawat);
                cek.setString(2, tanggal);
                cek.setString(3, jamStr);
                ResultSet rsCek = cek.executeQuery();
                boolean ada = rsCek.next();
                rsCek.close();
                cek.close();
                if (ada) {
                    JOptionPane.showMessageDialog(this, "Data dengan tanggal & jam yang sama sudah ada!", "Peringatan", JOptionPane.WARNING_MESSAGE);
                    return;
                }

                //mod by ichsan - tambah nip_perekam dari akses.getkode()
                psLoc = koneksi.prepareStatement(
                    "INSERT INTO rekammedis_sbar (no_rawat,tanggal,jam,situation,background,assessment," +
                    "recommendation,nip_pemberi,nip_penerima,tbak,waktu_konfirmasi,nip_perekam) VALUES (?,?,?,?,?,?,?,?,?,?," +
                    (newTbak.equals("Sudah") ? "NOW()" : "NULL") + ",?)");
                psLoc.setString(1, noRawat);
                psLoc.setString(2, tanggal);
                psLoc.setString(3, jamStr);
                psLoc.setString(4, taSituation.getText().trim());
                psLoc.setString(5, taBackground.getText().trim());
                psLoc.setString(6, taAssessment.getText().trim());
                psLoc.setString(7, taRecommendation.getText().trim());
                psLoc.setString(8, tKdPemberi.getText().trim());
                psLoc.setString(9, tKdPenerima.getText().trim().isEmpty() ? null : tKdPenerima.getText().trim());
                psLoc.setString(10, newTbak);
                psLoc.setString(11, akses.getkode()); //mod by ichsan - perekam
                psLoc.executeUpdate();
                simpanTrack("insert into rekammedis_sbar no_rawat='"+noRawat+"' tgl='"+tanggal+"' jam='"+jamStr+"'"); //mod by ichsan - audit trail
                JOptionPane.showMessageDialog(this, "Data berhasil disimpan!", "Info", JOptionPane.INFORMATION_MESSAGE);
            }
            resetForm();
            tampilDenganFilter(); //mod by ichsan
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Gagal menyimpan data!\n" + ex.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
            System.out.println("PanelSBAR.onSimpan() : " + ex);
        } finally {
            try { if (psLoc != null) psLoc.close(); } catch (Exception ex) {}
        }
    }

    //mod by ichsan - dipanggil dari BtnEdit/Ganti form induk
    public void editDariExternal() {
        if (tbSBAR.getSelectedRow() == -1) {
            JOptionPane.showMessageDialog(this, "Pilih data yang ingin diedit!", "Peringatan", JOptionPane.WARNING_MESSAGE);
            return;
        }
        loadRowToForm(tbSBAR.getSelectedRow());
        modeEdit = true;
    }

    //mod by ichsan - dipanggil dari BtnHapus form induk
    public void hapusDariExternal() {
        if (tbSBAR.getSelectedRow() == -1) {
            JOptionPane.showMessageDialog(this, "Pilih data yang ingin dihapus!", "Peringatan", JOptionPane.WARNING_MESSAGE);
            return;
        }
        int reply = JOptionPane.showConfirmDialog(this, "Hapus catatan SBAR ini?", "Konfirmasi", JOptionPane.YES_NO_OPTION);
        if (reply != JOptionPane.YES_OPTION) return;

        int row = tbSBAR.getSelectedRow();
        //mod by ichsan - sesuaikan index kolom baru (3=Tanggal, 4=Jam)
        String tgl = tabMode.getValueAt(row, 3).toString();
        String jamVal = tabMode.getValueAt(row, 4).toString();

        PreparedStatement psLoc = null; //mod by ichsan - local PS
        try {
            psLoc = koneksi.prepareStatement("DELETE FROM rekammedis_sbar WHERE no_rawat=? AND tanggal=? AND jam=?");
            psLoc.setString(1, noRawat);
            psLoc.setString(2, tgl);
            psLoc.setString(3, jamVal);
            psLoc.executeUpdate();
            simpanTrack("delete from rekammedis_sbar no_rawat='"+noRawat+"' tgl='"+tgl+"' jam='"+jamVal+"'"); //mod by ichsan - audit trail
            JOptionPane.showMessageDialog(this, "Data berhasil dihapus!", "Info", JOptionPane.INFORMATION_MESSAGE);
            resetForm();
            tampilDenganFilter(); //mod by ichsan
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Gagal menghapus data!\n" + ex.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
            System.out.println("PanelSBAR.hapusDariExternal() : " + ex);
        } finally {
            try { if (psLoc != null) psLoc.close(); } catch (Exception ex) {}
        }
    }

    //mod by ichsan - dipanggil dari BtnBatal form induk
    public void batalDariExternal() {
        resetForm();
    }

    private void onCetak(ActionEvent e) {
        if (tbSBAR.getSelectedRow() == -1) {
            JOptionPane.showMessageDialog(this, "Pilih data SBAR yang ingin dicetak!", "Peringatan", JOptionPane.WARNING_MESSAGE);
            return;
        }
        int row = tbSBAR.getSelectedRow();
        //mod by ichsan - sesuaikan index kolom baru (3=Tanggal, 4=Jam)
        String tgl = tabMode.getValueAt(row, 3).toString();
        String jamVal = tabMode.getValueAt(row, 4).toString();

        java.util.Map<String, Object> param = new java.util.HashMap<>();
        param.put("namars", akses.getnamars());
        param.put("alamatrs", akses.getalamatrs());
        param.put("kotars", akses.getkabupatenrs());
        param.put("propinsirs", akses.getpropinsirs());
        param.put("kontakrs", akses.getkontakrs());
        param.put("emailrs", akses.getemailrs());
        param.put("logo", Sequel.cariGambar("SELECT setting.logo FROM setting"));
        param.put("no_rawat", noRawat);
        param.put("tanggal", tgl);
        param.put("jam", jamVal);

        Valid.MyReport("rptSBAR.jasper", "report", "::[ Cetak Catatan SBAR ]::", param);
    }

    private void onTabelKlik() {
        if (tabMode.getRowCount() == 0 || tbSBAR.getSelectedRow() == -1) return;
        loadRowToForm(tbSBAR.getSelectedRow());
    }

    // =============================================
    //  Helper Methods
    // =============================================

    //mod by ichsan - fix: ResultSet closed bug, gunakan variabel lokal psLoc/rsLoc
    private void loadRowToForm(int row) {
        try {
            //mod by ichsan - sesuaikan index kolom baru (3=Tanggal, 4=Jam)
            String tgl = tabMode.getValueAt(row, 3).toString();
            String jamVal = tabMode.getValueAt(row, 4).toString();

            tglEdit = tgl;
            jamEdit = jamVal;

            SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd");
            dtpTanggal.setDate(sdf.parse(tgl));

            String[] jamParts = jamVal.split(":");
            if (jamParts.length >= 2) {
                cmbJam.setSelectedItem(jamParts[0]);
                cmbMnt.setSelectedItem(jamParts[1]);
            }

            //mod by ichsan - sync ke parent form
            if (parentDtpTgl != null) {
                parentDtpTgl.setDate(sdf.parse(tgl));
                if (jamParts.length >= 1) parentCmbJam.setSelectedItem(jamParts[0]);
                if (jamParts.length >= 2) parentCmbMnt.setSelectedItem(jamParts[1]);
                if (jamParts.length >= 3) parentCmbDtk.setSelectedItem(jamParts[2]);
            }

            //mod by ichsan - fix: gunakan psLoc/rsLoc terpisah
            PreparedStatement psLoc = null;
            ResultSet rsLoc = null;
            String nipPemberi = null;
            String nipPenerima = null;
            try {
                psLoc = koneksi.prepareStatement(
                    "SELECT * FROM rekammedis_sbar WHERE no_rawat=? AND tanggal=? AND jam=?");
                psLoc.setString(1, noRawat);
                psLoc.setString(2, tgl);
                psLoc.setString(3, jamVal);
                rsLoc = psLoc.executeQuery();
                if (rsLoc.next()) {
                    taSituation.setText(rsLoc.getString("situation"));
                    taBackground.setText(rsLoc.getString("background"));
                    taAssessment.setText(rsLoc.getString("assessment"));
                    taRecommendation.setText(rsLoc.getString("recommendation"));
                    nipPemberi = rsLoc.getString("nip_pemberi");
                    nipPenerima = rsLoc.getString("nip_penerima");
                    tKdPemberi.setText(nipPemberi != null ? nipPemberi : "");
                    tKdPenerima.setText(nipPenerima != null ? nipPenerima : "");
                    cmbTbak.setSelectedItem(rsLoc.getString("tbak"));
                }
            } finally {
                //mod by ichsan - tutup RS/PS sebelum memanggil cariNamaPetugas
                try { if (rsLoc != null) rsLoc.close(); } catch (Exception e) {}
                try { if (psLoc != null) psLoc.close(); } catch (Exception e) {}
            }
            //mod by ichsan - cari nama setelah rs ditutup, mencegah konflik
            if (nipPemberi != null && !nipPemberi.isEmpty()) {
                cariNamaPetugas(nipPemberi, true);
            }
            if (nipPenerima != null && !nipPenerima.isEmpty()) {
                cariNamaPetugas(nipPenerima, false);
            }
        } catch (Exception ex) {
            System.out.println("PanelSBAR.loadRowToForm() : " + ex);
        }
    }

    private void resetForm() {
        modeEdit = false;
        tglEdit = "";
        jamEdit = "";
        dtpTanggal.setDate(new Date());
        Calendar cal = Calendar.getInstance();
        cmbJam.setSelectedItem(String.format("%02d", cal.get(Calendar.HOUR_OF_DAY)));
        cmbMnt.setSelectedItem(String.format("%02d", cal.get(Calendar.MINUTE)));
        taSituation.setText("");
        taBackground.setText("");
        taAssessment.setText("");
        taRecommendation.setText("");
        tKdPemberi.setText("");
        tNmPemberi.setText("");
        tKdPenerima.setText("");
        tNmPenerima.setText("");
        cmbTbak.setSelectedIndex(0);
        autoFillLogin(); //mod by ichsan - re-apply auto-fill setelah reset
    }

    private void cariPetugas(boolean isPemberi) {
        if (isPemberi) {
            DlgCariPetugas dlg = new DlgCariPetugas(null, false);
            dlg.addWindowListener(new WindowAdapter() {
                @Override
                public void windowClosed(WindowEvent e) {
                    if (dlg.getTable().getSelectedRow() != -1) {
                        int row = dlg.getTable().getSelectedRow();
                        String nip = dlg.getTable().getValueAt(row, 0).toString();
                        String nama = dlg.getTable().getValueAt(row, 1).toString();
                        tKdPemberi.setText(nip);
                        tNmPemberi.setText(nama);
                    }
                }
            });
            dlg.emptTeks();
            dlg.isCek();
            dlg.setSize(656, 300);
            dlg.setLocationRelativeTo(this);
            dlg.setVisible(true);
        } else {
            DlgCariDokter dlg = new DlgCariDokter(null, false);
            dlg.addWindowListener(new WindowAdapter() {
                @Override
                public void windowClosed(WindowEvent e) {
                    if (dlg.getTable().getSelectedRow() != -1) {
                        int row = dlg.getTable().getSelectedRow();
                        String kd = dlg.getTable().getValueAt(row, 0).toString();
                        String nama = dlg.getTable().getValueAt(row, 1).toString();
                        tKdPenerima.setText(kd);
                        tNmPenerima.setText(nama);
                    }
                }
            });
            dlg.setSize(656, 300);
            dlg.setLocationRelativeTo(this);
            dlg.setVisible(true);
        }
    }

    //mod by ichsan - fix: gunakan psLoc/rsLoc agar tidak overwrite outer ps/rs
    private void cariNamaPetugas(String nip, boolean isPemberi) {
        if (nip == null || nip.trim().isEmpty()) return;
        PreparedStatement psLoc = null;
        ResultSet rsLoc = null;
        try {
            if (isPemberi) {
                psLoc = koneksi.prepareStatement("SELECT nama FROM petugas WHERE nip=?");
                psLoc.setString(1, nip.trim());
                rsLoc = psLoc.executeQuery();
                tNmPemberi.setText(rsLoc.next() ? rsLoc.getString("nama") : "[tidak ditemukan]");
            } else {
                psLoc = koneksi.prepareStatement("SELECT nm_dokter FROM dokter WHERE kd_dokter=?");
                psLoc.setString(1, nip.trim());
                rsLoc = psLoc.executeQuery();
                tNmPenerima.setText(rsLoc.next() ? rsLoc.getString("nm_dokter") : "[tidak ditemukan]");
            }
        } catch (Exception ex) {
            System.out.println("PanelSBAR.cariNamaPetugas() : " + ex);
        } finally {
            try { if (rsLoc != null) rsLoc.close(); } catch (Exception e) {}
            try { if (psLoc != null) psLoc.close(); } catch (Exception e) {}
        }
    }

    //mod by ichsan - audit trail ke trackersql sesuai standar SIMKES Khanza
    private void simpanTrack(String sql) {
        if (AKTIFKANTRACKSQL.equals("yes")) {
            PreparedStatement psTrack = null;
            try {
                psTrack = koneksi.prepareStatement("insert into trackersql values(now(),?,?)");
                psTrack.setString(1, akses.getalamatip() + " " + sql);
                psTrack.setString(2, akses.getkode());
                psTrack.executeUpdate();
            } catch (Exception e) {
                System.out.println("PanelSBAR.simpanTrack() : " + e);
            } finally {
                try { if (psTrack != null) psTrack.close(); } catch (Exception e) {}
            }
        }
    }

    // =============================================
    //  Public API - Integrasi Form Induk //mod by ichsan
    // =============================================

    public void setParentDateControls(widget.Tanggal dtpTgl, widget.ComboBox jam, widget.ComboBox mnt, widget.ComboBox dtk) {
        this.parentDtpTgl = dtpTgl;
        this.parentCmbJam = jam;
        this.parentCmbMnt = mnt;
        this.parentCmbDtk = dtk;
    }

    public void setTanggalDanJam(String tanggal, String jam) {
        try {
            SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd");
            dtpTanggal.setDate(sdf.parse(tanggal));
            String[] parts = jam.split(":");
            if (parts.length >= 2) {
                cmbJam.setSelectedItem(parts[0]);
                cmbMnt.setSelectedItem(parts[1]);
            }
        } catch (Exception e) {
            System.out.println("PanelSBAR.setTanggalDanJam() error: " + e);
        }
    }

    //mod by ichsan - dipanggil dari tombol Simpan form induk
    public void simpanDariExternal(String tanggal, String jam) {
        setTanggalDanJam(tanggal, jam);
        onSimpan(null);
    }
}
