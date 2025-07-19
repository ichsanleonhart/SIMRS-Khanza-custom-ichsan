/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 /*
 * DlgAdmin.java
 *
 * Created on 04 Des 13, 12:59:34
 */
package khanzahmsanjungan;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import fungsi.akses;
import fungsi.koneksiDB;
import fungsi.sekuel;
import fungsi.validasi;
import java.awt.Cursor;
import java.awt.Dialog;
import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileReader;
import java.io.FileWriter;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.Calendar;
import java.util.Date;
import java.util.HashMap;
import java.util.Map;
import java.util.Properties;
import javax.swing.JDialog;
import javax.swing.JOptionPane;
import javax.swing.JProgressBar;
import javax.swing.ListSelectionModel;
import javax.swing.SwingUtilities;
import javax.swing.Timer;
import org.bouncycastle.crypto.engines.TnepresEngine;

/**
 *
 * @author Kode
 */
public class CekKontrolBedaPoli extends javax.swing.JDialog {

    private Connection koneksi = koneksiDB.condb();
    private sekuel Sequel = new sekuel();
    private validasi Valid = new validasi();
    private PreparedStatement ps, ps3;
    private ResultSet rs, rs3;
    private SimpleDateFormat dateformat = new SimpleDateFormat("yyyy/MM/dd");
    private String umur = "0", sttsumur = "Th", hari = "", kode_dokter = "", kode_poli = "", nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email;
    private String status = "Baru", BASENOREG = "", URUTNOREG = "", aktifjadwal = "";
    private Properties prop = new Properties();
    private File file;
    private DlgCariPoli poli = new DlgCariPoli(null, true);
    private DlgCariDokter2 dokter = new DlgCariDokter2(null, true);
    private FileWriter fileWriter;
    private String iyem;
    private ObjectMapper mapper = new ObjectMapper();
    public PilihanBedaPoli form = new PilihanBedaPoli(null, true);
    private JsonNode root;
    private JsonNode response;
    private FileReader myObj;
    private Calendar cal = Calendar.getInstance();
    private int day = cal.get(Calendar.DAY_OF_WEEK);
    private DlgPilihCaraBayar pilihbayar=new DlgPilihCaraBayar(null,true);

    /**
     * Creates new form DlgAdmin
     *
     * @param parent
     * @param id
     */
    public CekKontrolBedaPoli(java.awt.Frame parent, boolean id) {
        super(parent, id);
        initComponents();

        try {
            ps = koneksi.prepareStatement(
                    "select nm_pasien,concat(pasien.alamat,', ',kelurahan.nm_kel,', ',kecamatan.nm_kec,', ',kabupaten.nm_kab) asal,"
                    + "namakeluarga,keluarga,pasien.kd_pj,penjab.png_jawab,if(tgl_daftar=?,'Baru','Lama') as daftar, "
                    + "TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) as tahun, "
                    + "(TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12)) as bulan, "
                    + "TIMESTAMPDIFF(DAY, DATE_ADD(DATE_ADD(tgl_lahir,INTERVAL TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) YEAR), INTERVAL TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12) MONTH), CURDATE()) as hari from pasien "
                    + "inner join kelurahan inner join kecamatan inner join kabupaten inner join penjab "
                    + "on pasien.kd_kel=kelurahan.kd_kel and pasien.kd_pj=penjab.kd_pj "
                    + "and pasien.kd_kec=kecamatan.kd_kec and pasien.kd_kab=kabupaten.kd_kab "
                    + "where pasien.no_rkm_medis=?");
        } catch (Exception ex) {
            System.out.println(ex);
        }

        try {
            ps = koneksi.prepareStatement("select nama_instansi, alamat_instansi, kabupaten, propinsi, aktifkan, wallpaper,kontak,email,logo from setting");
            rs = ps.executeQuery();
            while (rs.next()) {
                nama_instansi = rs.getString("nama_instansi");
                alamat_instansi = rs.getString("alamat_instansi");
                kabupaten = rs.getString("kabupaten");
                propinsi = rs.getString("propinsi");
                kontak = rs.getString("kontak");
                email = rs.getString("email");
            }
        } catch (SQLException e) {
            System.out.println(e);
        }

        poli.addWindowListener(new WindowListener() {
            @Override
            public void windowOpened(WindowEvent e) {
            }

            @Override
            public void windowClosing(WindowEvent e) {
            }

            @Override
            public void windowClosed(WindowEvent e) {
                kode_poli = "";
                NamaPoli.setText(poli.getTable().getValueAt(poli.getTable().getSelectedRow(), 1).toString());
                kode_poli = poli.getTable().getValueAt(poli.getTable().getSelectedRow(), 0).toString();

            }

            @Override
            public void windowIconified(WindowEvent e) {
            }

            @Override
            public void windowDeiconified(WindowEvent e) {
            }

            @Override
            public void windowActivated(WindowEvent e) {
            }

            @Override
            public void windowDeactivated(WindowEvent e) {
            }
        });

        dokter.addWindowListener(new WindowListener() {
            @Override
            public void windowOpened(WindowEvent e) {
            }

            @Override
            public void windowClosing(WindowEvent e) {
            }

            @Override
            public void windowClosed(WindowEvent e) {
                kode_dokter = "";
                NamaDokter.setText(dokter.getTable().getValueAt(dokter.getTable().getSelectedRow(), 1).toString());
                kode_dokter = dokter.getTable().getValueAt(dokter.getTable().getSelectedRow(), 0).toString();
                tampilPenjab();
            }

            @Override
            public void windowIconified(WindowEvent e) {
            }

            @Override
            public void windowDeiconified(WindowEvent e) {
            }

            @Override
            public void windowActivated(WindowEvent e) {
            }

            @Override
            public void windowDeactivated(WindowEvent e) {
            }
        });
        
        pilihbayar.addWindowListener(new WindowListener() {
            @Override
            public void windowOpened(WindowEvent e) {
            }

            @Override
            public void windowClosing(WindowEvent e) {
            }

            @Override
            public void windowClosed(WindowEvent e) {                
                    kodecarabayar1.setText(pilihbayar.getTable().getValueAt(pilihbayar.getTable().getSelectedRow(),0).toString());
                    namacarabayar.setText(pilihbayar.getTable().getValueAt(pilihbayar.getTable().getSelectedRow(),1).toString());
            }

            @Override
            public void windowIconified(WindowEvent e) {
            }

            @Override
            public void windowDeiconified(WindowEvent e) {
            }

            @Override
            public void windowActivated(WindowEvent e) {
            }

            @Override
            public void windowDeactivated(WindowEvent e) {
            }
        });    
        
        form.addWindowListener(new WindowListener() {
            @Override
            public void windowOpened(WindowEvent e) {
            }

            @Override
            public void windowClosing(WindowEvent e) {
            }

            @Override
            public void windowClosed(WindowEvent e) {
                
                NamaDokter.setText(form.getNamaDOkter().getText());
                kode_dokter = form.getDataDOkter().getText();
                NamaPoli.setText(form.getNamaPoli().getText());
                kode_poli = form.getDataPoli().getText();
                KodeDOkterRS.setText(form.getKdDOkterBPJS().getText());
                KodepoliRS.setText(form.getKdPoliBpjs().getText());
 
            }

            @Override
            public void windowIconified(WindowEvent e) {
            }

            @Override
            public void windowDeiconified(WindowEvent e) {
            }

            @Override
            public void windowActivated(WindowEvent e) {
            }

            @Override
            public void windowDeactivated(WindowEvent e) {
            }
        });    

        try {
            prop.loadFromXML(new FileInputStream("setting/database.xml"));
            aktifjadwal = prop.getProperty("JADWALDOKTERDIREGISTRASI");
            URUTNOREG = prop.getProperty("URUTNOREG");
            BASENOREG = prop.getProperty("BASENOREG");
        } catch (Exception ex) {
            aktifjadwal = "";
            URUTNOREG = "";
            BASENOREG = "";
        }
        
        cmbCaraBayar.setVisible(false);
        btnSimpan2.setVisible(false);

    }

    /**
     * This method is called from within the constructor to initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is always
     * regenerated by the Form Editor.
     */
    @SuppressWarnings("unchecked")
    // <editor-fold defaultstate="collapsed" desc="Generated Code">//GEN-BEGIN:initComponents
    private void initComponents() {

        LblKdPoli = new component.Label();
        LblKdDokter = new component.Label();
        NoReg = new component.TextBox();
        NoRawat = new component.TextBox();
        Biaya = new component.TextBox();
        TAlmt = new component.Label();
        TPngJwb = new component.Label();
        THbngn = new component.Label();
        NoTelpPasien = new component.Label();
        nopesertaBPJS = new component.Label();
        noskdpPasien = new component.Label();
        KodepoliRS = new component.Label();
        KodeDOkterRS = new component.Label();
        namaDOkterRS = new component.Label();
        jPanel1 = new component.Panel();
        jPanel2 = new component.Panel();
        jLabel10 = new component.Label();
        jLabel11 = new component.Label();
        lblNamaPasien = new component.Label();
        jLabel28 = new component.Label();
        jLabel29 = new component.Label();
        jLabel30 = new component.Label();
        jLabel31 = new component.Label();
        TanggalPeriksa = new widget.Tanggal();
        lblNoRM = new component.Label();
        jLabel32 = new component.Label();
        jLabel33 = new component.Label();
        jLabel35 = new component.Label();
        cmbCaraBayar = new widget.ComboBox();
        btnSimpan1 = new component.Button();
        NamaPoli = new widget.TextBox();
        jLabel36 = new component.Label();
        TNoRw = new widget.TextBox();
        btnSimpan2 = new component.Button();
        NamaDokter = new widget.TextBox();
        jLabel34 = new component.Label();
        BtnCetak = new widget.Button();
        BtnCetak1 = new widget.Button();
        namacarabayar = new widget.TextBox();
        kodecarabayar1 = new widget.TextBox();
        PanelWall3 = new usu.widget.glass.PanelGlass();

        LblKdPoli.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        LblKdPoli.setText("Norm");
        LblKdPoli.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        LblKdPoli.setPreferredSize(new java.awt.Dimension(20, 14));

        LblKdDokter.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        LblKdDokter.setText("Norm");
        LblKdDokter.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        LblKdDokter.setPreferredSize(new java.awt.Dimension(20, 14));

        NoReg.setPreferredSize(new java.awt.Dimension(320, 30));
        NoReg.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                NoRegActionPerformed(evt);
            }
        });
        NoReg.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                NoRegKeyPressed(evt);
            }
        });

        NoRawat.setPreferredSize(new java.awt.Dimension(320, 30));
        NoRawat.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                NoRawatActionPerformed(evt);
            }
        });
        NoRawat.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                NoRawatKeyPressed(evt);
            }
        });

        Biaya.setPreferredSize(new java.awt.Dimension(320, 30));
        Biaya.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BiayaActionPerformed(evt);
            }
        });
        Biaya.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BiayaKeyPressed(evt);
            }
        });

        TAlmt.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        TAlmt.setText("Norm");
        TAlmt.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        TAlmt.setPreferredSize(new java.awt.Dimension(20, 14));

        TPngJwb.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        TPngJwb.setText("Norm");
        TPngJwb.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        TPngJwb.setPreferredSize(new java.awt.Dimension(20, 14));

        THbngn.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        THbngn.setText("Norm");
        THbngn.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        THbngn.setPreferredSize(new java.awt.Dimension(20, 14));

        NoTelpPasien.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        NoTelpPasien.setText("Norm");
        NoTelpPasien.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        NoTelpPasien.setPreferredSize(new java.awt.Dimension(20, 14));

        nopesertaBPJS.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        nopesertaBPJS.setText("Norm");
        nopesertaBPJS.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        nopesertaBPJS.setPreferredSize(new java.awt.Dimension(20, 14));

        noskdpPasien.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        noskdpPasien.setText("Norm");
        noskdpPasien.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        noskdpPasien.setPreferredSize(new java.awt.Dimension(20, 14));

        KodepoliRS.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        KodepoliRS.setText("Norm");
        KodepoliRS.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        KodepoliRS.setPreferredSize(new java.awt.Dimension(20, 14));

        KodeDOkterRS.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        KodeDOkterRS.setText("Norm");
        KodeDOkterRS.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        KodeDOkterRS.setPreferredSize(new java.awt.Dimension(20, 14));

        namaDOkterRS.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        namaDOkterRS.setText("Norm");
        namaDOkterRS.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        namaDOkterRS.setPreferredSize(new java.awt.Dimension(20, 14));

        setDefaultCloseOperation(javax.swing.WindowConstants.DISPOSE_ON_CLOSE);
        setBackground(new java.awt.Color(255, 255, 255));
        setModal(true);
        setUndecorated(true);
        setResizable(false);
        addWindowListener(new java.awt.event.WindowAdapter() {
            public void windowOpened(java.awt.event.WindowEvent evt) {
                formWindowOpened(evt);
            }
        });
        getContentPane().setLayout(new java.awt.BorderLayout(1, 1));

        jPanel1.setBackground(new java.awt.Color(255, 255, 255));
        jPanel1.setBorder(null);
        jPanel1.setForeground(new java.awt.Color(255, 255, 255));
        jPanel1.setPreferredSize(new java.awt.Dimension(400, 70));
        jPanel1.setLayout(new java.awt.BorderLayout());

        jPanel2.setBackground(new java.awt.Color(255, 255, 255));
        jPanel2.setBorder(null);
        jPanel2.setForeground(new java.awt.Color(255, 255, 255));
        jPanel2.setPreferredSize(new java.awt.Dimension(390, 120));
        jPanel2.setLayout(new org.netbeans.lib.awtextra.AbsoluteLayout());

        jLabel10.setBackground(new java.awt.Color(255, 255, 255));
        jLabel10.setForeground(new java.awt.Color(15, 81, 137));
        jLabel10.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel10.setText("No. RM / Nama");
        jLabel10.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        jLabel10.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel10, new org.netbeans.lib.awtextra.AbsoluteConstraints(70, 120, 200, 50));

        jLabel11.setBackground(new java.awt.Color(255, 255, 255));
        jLabel11.setForeground(new java.awt.Color(15, 81, 137));
        jLabel11.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel11.setText(":");
        jLabel11.setFont(new java.awt.Font("Trebuchet MS", 0, 24)); // NOI18N
        jLabel11.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel11, new org.netbeans.lib.awtextra.AbsoluteConstraints(260, 120, 30, 50));

        lblNamaPasien.setForeground(new java.awt.Color(0, 51, 102));
        lblNamaPasien.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        lblNamaPasien.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        lblNamaPasien.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(lblNamaPasien, new org.netbeans.lib.awtextra.AbsoluteConstraints(390, 120, 590, 50));

        jLabel28.setBackground(new java.awt.Color(255, 255, 255));
        jLabel28.setForeground(new java.awt.Color(15, 81, 137));
        jLabel28.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel28.setText(":");
        jLabel28.setFont(new java.awt.Font("Trebuchet MS", 0, 24)); // NOI18N
        jLabel28.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel28, new org.netbeans.lib.awtextra.AbsoluteConstraints(260, 170, 30, 50));

        jLabel29.setBackground(new java.awt.Color(255, 255, 255));
        jLabel29.setForeground(new java.awt.Color(15, 81, 137));
        jLabel29.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel29.setText("Tanggal Periksa");
        jLabel29.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        jLabel29.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel29, new org.netbeans.lib.awtextra.AbsoluteConstraints(70, 170, 200, 50));

        jLabel30.setBackground(new java.awt.Color(255, 255, 255));
        jLabel30.setForeground(new java.awt.Color(15, 81, 137));
        jLabel30.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel30.setText(":");
        jLabel30.setFont(new java.awt.Font("Trebuchet MS", 0, 24)); // NOI18N
        jLabel30.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel30, new org.netbeans.lib.awtextra.AbsoluteConstraints(260, 220, 30, 50));

        jLabel31.setBackground(new java.awt.Color(255, 255, 255));
        jLabel31.setForeground(new java.awt.Color(15, 81, 137));
        jLabel31.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel31.setText("Poliklinik");
        jLabel31.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        jLabel31.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel31, new org.netbeans.lib.awtextra.AbsoluteConstraints(70, 220, 200, 50));

        TanggalPeriksa.setBorder(javax.swing.BorderFactory.createEmptyBorder(1, 1, 1, 1));
        TanggalPeriksa.setForeground(new java.awt.Color(0, 51, 102));
        TanggalPeriksa.setModel(new javax.swing.DefaultComboBoxModel(new String[] { "06-05-2025" }));
        TanggalPeriksa.setDisplayFormat("dd-MM-yyyy");
        TanggalPeriksa.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        TanggalPeriksa.setPreferredSize(new java.awt.Dimension(95, 23));
        TanggalPeriksa.addItemListener(new java.awt.event.ItemListener() {
            public void itemStateChanged(java.awt.event.ItemEvent evt) {
                TanggalPeriksaItemStateChanged(evt);
            }
        });
        TanggalPeriksa.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                TanggalPeriksaActionPerformed(evt);
            }
        });
        TanggalPeriksa.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                TanggalPeriksaKeyPressed(evt);
            }
        });
        jPanel2.add(TanggalPeriksa, new org.netbeans.lib.awtextra.AbsoluteConstraints(290, 170, 180, 50));

        lblNoRM.setForeground(new java.awt.Color(0, 51, 102));
        lblNoRM.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        lblNoRM.setText("123456");
        lblNoRM.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        lblNoRM.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(lblNoRM, new org.netbeans.lib.awtextra.AbsoluteConstraints(290, 120, 100, 50));

        jLabel32.setBackground(new java.awt.Color(255, 255, 255));
        jLabel32.setForeground(new java.awt.Color(15, 81, 137));
        jLabel32.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel32.setText("Dokter Spesialis");
        jLabel32.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        jLabel32.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel32, new org.netbeans.lib.awtextra.AbsoluteConstraints(70, 270, 200, 50));

        jLabel33.setBackground(new java.awt.Color(255, 255, 255));
        jLabel33.setForeground(new java.awt.Color(15, 81, 137));
        jLabel33.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel33.setText(":");
        jLabel33.setFont(new java.awt.Font("Trebuchet MS", 0, 24)); // NOI18N
        jLabel33.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel33, new org.netbeans.lib.awtextra.AbsoluteConstraints(260, 270, 30, 50));

        jLabel35.setBackground(new java.awt.Color(255, 255, 255));
        jLabel35.setForeground(new java.awt.Color(15, 81, 137));
        jLabel35.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel35.setText(":");
        jLabel35.setFont(new java.awt.Font("Trebuchet MS", 0, 24)); // NOI18N
        jLabel35.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel35, new org.netbeans.lib.awtextra.AbsoluteConstraints(260, 320, 30, 50));

        cmbCaraBayar.setBorder(null);
        cmbCaraBayar.setForeground(new java.awt.Color(0, 51, 102));
        cmbCaraBayar.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        cmbCaraBayar.addItemListener(new java.awt.event.ItemListener() {
            public void itemStateChanged(java.awt.event.ItemEvent evt) {
                cmbCaraBayarItemStateChanged(evt);
            }
        });
        cmbCaraBayar.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                cmbCaraBayarActionPerformed(evt);
            }
        });
        cmbCaraBayar.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                cmbCaraBayarKeyPressed(evt);
            }
        });
        jPanel2.add(cmbCaraBayar, new org.netbeans.lib.awtextra.AbsoluteConstraints(400, 400, 620, 50));

        btnSimpan1.setBorder(null);
        btnSimpan1.setForeground(new java.awt.Color(15, 81, 137));
        btnSimpan1.setIcon(new javax.swing.ImageIcon(getClass().getResource("/48x48/pilih.png"))); // NOI18N
        btnSimpan1.setMnemonic('S');
        btnSimpan1.setText("Pilih Poli dan Dokter");
        btnSimpan1.setToolTipText("Alt+S");
        btnSimpan1.setFont(new java.awt.Font("Trebuchet MS", 2, 18)); // NOI18N
        btnSimpan1.setGlassColor(new java.awt.Color(255, 255, 255));
        btnSimpan1.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        btnSimpan1.setHorizontalTextPosition(javax.swing.SwingConstants.RIGHT);
        btnSimpan1.setIconTextGap(10);
        btnSimpan1.setPreferredSize(new java.awt.Dimension(300, 45));
        btnSimpan1.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                btnSimpan1ActionPerformed(evt);
            }
        });
        btnSimpan1.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                btnSimpan1KeyPressed(evt);
            }
        });
        jPanel2.add(btnSimpan1, new org.netbeans.lib.awtextra.AbsoluteConstraints(910, 220, 260, 50));

        NamaPoli.setEditable(false);
        NamaPoli.setBorder(null);
        NamaPoli.setForeground(new java.awt.Color(0, 51, 102));
        NamaPoli.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        NamaPoli.setPreferredSize(new java.awt.Dimension(72, 28));
        NamaPoli.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                NamaPoliKeyPressed(evt);
            }
        });
        jPanel2.add(NamaPoli, new org.netbeans.lib.awtextra.AbsoluteConstraints(290, 220, 620, 50));

        jLabel36.setBackground(new java.awt.Color(255, 255, 255));
        jLabel36.setForeground(new java.awt.Color(15, 81, 137));
        jLabel36.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        jLabel36.setText("Cara Bayar");
        jLabel36.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        jLabel36.setPreferredSize(new java.awt.Dimension(20, 14));
        jPanel2.add(jLabel36, new org.netbeans.lib.awtextra.AbsoluteConstraints(70, 320, 200, 50));

        TNoRw.setEditable(false);
        TNoRw.setBorder(null);
        TNoRw.setForeground(new java.awt.Color(51, 51, 51));
        TNoRw.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        TNoRw.setPreferredSize(new java.awt.Dimension(72, 28));
        TNoRw.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                TNoRwKeyPressed(evt);
            }
        });
        jPanel2.add(TNoRw, new org.netbeans.lib.awtextra.AbsoluteConstraints(390, 370, 520, 50));

        btnSimpan2.setBorder(null);
        btnSimpan2.setForeground(new java.awt.Color(15, 81, 137));
        btnSimpan2.setIcon(new javax.swing.ImageIcon(getClass().getResource("/48x48/pilih.png"))); // NOI18N
        btnSimpan2.setMnemonic('S');
        btnSimpan2.setText("Pilih Dokter");
        btnSimpan2.setToolTipText("Alt+S");
        btnSimpan2.setFont(new java.awt.Font("Trebuchet MS", 2, 18)); // NOI18N
        btnSimpan2.setGlassColor(new java.awt.Color(255, 255, 255));
        btnSimpan2.setHorizontalAlignment(javax.swing.SwingConstants.LEFT);
        btnSimpan2.setHorizontalTextPosition(javax.swing.SwingConstants.RIGHT);
        btnSimpan2.setIconTextGap(10);
        btnSimpan2.setPreferredSize(new java.awt.Dimension(300, 45));
        btnSimpan2.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                btnSimpan2ActionPerformed(evt);
            }
        });
        btnSimpan2.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                btnSimpan2KeyPressed(evt);
            }
        });
        jPanel2.add(btnSimpan2, new org.netbeans.lib.awtextra.AbsoluteConstraints(910, 270, 170, 50));

        NamaDokter.setEditable(false);
        NamaDokter.setBorder(null);
        NamaDokter.setForeground(new java.awt.Color(0, 51, 102));
        NamaDokter.setFont(new java.awt.Font("Trebuchet MS", 2, 24)); // NOI18N
        NamaDokter.setPreferredSize(new java.awt.Dimension(72, 28));
        NamaDokter.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                NamaDokterKeyPressed(evt);
            }
        });
        jPanel2.add(NamaDokter, new org.netbeans.lib.awtextra.AbsoluteConstraints(290, 270, 620, 50));

        jLabel34.setForeground(new java.awt.Color(15, 81, 137));
        jLabel34.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        jLabel34.setText("Silahkan melengkapi data berikut :");
        jLabel34.setVerticalAlignment(javax.swing.SwingConstants.BOTTOM);
        jLabel34.setFont(new java.awt.Font("Trebuchet MS", 2, 30)); // NOI18N
        jLabel34.setPreferredSize(new java.awt.Dimension(450, 75));
        jPanel2.add(jLabel34, new org.netbeans.lib.awtextra.AbsoluteConstraints(0, 20, 1290, 50));

        BtnCetak.setForeground(new java.awt.Color(15, 81, 137));
        BtnCetak.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/tombolatm - kanan.png"))); // NOI18N
        BtnCetak.setText("Simpan");
        BtnCetak.setFont(new java.awt.Font("Trebuchet MS", 1, 30)); // NOI18N
        BtnCetak.setHorizontalAlignment(javax.swing.SwingConstants.RIGHT);
        BtnCetak.setHorizontalTextPosition(javax.swing.SwingConstants.LEFT);
        BtnCetak.setIconTextGap(30);
        BtnCetak.setPreferredSize(new java.awt.Dimension(158, 125));
        BtnCetak.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnCetakActionPerformed(evt);
            }
        });
        jPanel2.add(BtnCetak, new org.netbeans.lib.awtextra.AbsoluteConstraints(1030, 410, 260, 80));

        BtnCetak1.setForeground(new java.awt.Color(15, 81, 137));
        BtnCetak1.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/tombolatm - kanan.png"))); // NOI18N
        BtnCetak1.setText("Kembali");
        BtnCetak1.setFont(new java.awt.Font("Trebuchet MS", 1, 30)); // NOI18N
        BtnCetak1.setHorizontalAlignment(javax.swing.SwingConstants.RIGHT);
        BtnCetak1.setHorizontalTextPosition(javax.swing.SwingConstants.LEFT);
        BtnCetak1.setIconTextGap(30);
        BtnCetak1.setPreferredSize(new java.awt.Dimension(158, 125));
        BtnCetak1.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnCetak1ActionPerformed(evt);
            }
        });
        jPanel2.add(BtnCetak1, new org.netbeans.lib.awtextra.AbsoluteConstraints(1030, 520, 260, 80));

        namacarabayar.setFont(new java.awt.Font("Poppins", 0, 18)); // NOI18N
        jPanel2.add(namacarabayar, new org.netbeans.lib.awtextra.AbsoluteConstraints(430, 320, 460, 50));

        kodecarabayar1.setFont(new java.awt.Font("Poppins", 0, 18)); // NOI18N
        jPanel2.add(kodecarabayar1, new org.netbeans.lib.awtextra.AbsoluteConstraints(290, 320, 140, 50));

        jPanel1.add(jPanel2, java.awt.BorderLayout.CENTER);

        PanelWall3.setBackground(new java.awt.Color(255, 255, 255));
        PanelWall3.setBackgroundImage(new javax.swing.ImageIcon(getClass().getResource("/picture/header.png"))); // NOI18N
        PanelWall3.setBackgroundImageType(usu.widget.constan.BackgroundConstan.BACKGROUND_IMAGE_CENTER_TOP);
        PanelWall3.setForeground(new java.awt.Color(255, 255, 255));
        PanelWall3.setPreferredSize(new java.awt.Dimension(1200, 300));
        PanelWall3.setRound(false);
        PanelWall3.setToolTipText("");
        PanelWall3.setLayout(new java.awt.BorderLayout());
        jPanel1.add(PanelWall3, java.awt.BorderLayout.PAGE_START);

        getContentPane().add(jPanel1, java.awt.BorderLayout.CENTER);

        pack();
    }// </editor-fold>//GEN-END:initComponents

    private void formWindowOpened(java.awt.event.WindowEvent evt) {//GEN-FIRST:event_formWindowOpened

    }//GEN-LAST:event_formWindowOpened

    private void NoRegActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_NoRegActionPerformed
        // TODO add your handling code here:
    }//GEN-LAST:event_NoRegActionPerformed

    private void NoRegKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_NoRegKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_NoRegKeyPressed

    private void NoRawatActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_NoRawatActionPerformed
        // TODO add your handling code here:
    }//GEN-LAST:event_NoRawatActionPerformed

    private void NoRawatKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_NoRawatKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_NoRawatKeyPressed

    private void BiayaActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BiayaActionPerformed
        // TODO add your handling code here:
    }//GEN-LAST:event_BiayaActionPerformed

    private void BiayaKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BiayaKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_BiayaKeyPressed

    private void TanggalPeriksaKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_TanggalPeriksaKeyPressed

    }//GEN-LAST:event_TanggalPeriksaKeyPressed

    private void cmbCaraBayarItemStateChanged(java.awt.event.ItemEvent evt) {//GEN-FIRST:event_cmbCaraBayarItemStateChanged
//        tentukanPilihan();
    
    }//GEN-LAST:event_cmbCaraBayarItemStateChanged

    private void cmbCaraBayarKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_cmbCaraBayarKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_cmbCaraBayarKeyPressed

    private void TanggalPeriksaItemStateChanged(java.awt.event.ItemEvent evt) {//GEN-FIRST:event_TanggalPeriksaItemStateChanged
        tentukanHari();
        kode_poli = "";
        NamaPoli.setText("");
        NamaDokter.setText("");
        kode_dokter = "";
//        poli.tampil(hari);
//        poli.setSize(jPanel2.getWidth() - 50, jPanel2.getHeight() - 50);
//        poli.setLocationRelativeTo(jPanel2);
//        poli.setVisible(true);
    }//GEN-LAST:event_TanggalPeriksaItemStateChanged

    private void btnSimpan1ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnSimpan1ActionPerformed
//        poli.tampil(hari);
//        poli.setSize(jPanel1.getWidth() - 50, jPanel1.getHeight() - 50);
//        poli.setLocationRelativeTo(jPanel2);
//        poli.setVisible(true);
    this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
        form.setSize(jPanel1.getWidth() - 50, jPanel1.getHeight() - 50);
        form.setLocationRelativeTo(jPanel2);
        form.setRM(lblNoRM.getText());
        form.setVisible(true);
        //dispose();
        this.setCursor(Cursor.getDefaultCursor());
    }//GEN-LAST:event_btnSimpan1ActionPerformed

    private void btnSimpan1KeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_btnSimpan1KeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_btnSimpan1KeyPressed

    private void NamaPoliKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_NamaPoliKeyPressed

    }//GEN-LAST:event_NamaPoliKeyPressed

    private void TanggalPeriksaActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_TanggalPeriksaActionPerformed
        // TODO add your handling code here:
    }//GEN-LAST:event_TanggalPeriksaActionPerformed

    private void TNoRwKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_TNoRwKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_TNoRwKeyPressed

    private void btnSimpan2ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnSimpan2ActionPerformed
        dokter.tampil(hari, kode_poli);
        dokter.setSize(jPanel1.getWidth() - 50, jPanel1.getHeight() - 50);
        dokter.setLocationRelativeTo(jPanel2);
        dokter.setVisible(true);
    }//GEN-LAST:event_btnSimpan2ActionPerformed

    private void btnSimpan2KeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_btnSimpan2KeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_btnSimpan2KeyPressed

    private void NamaDokterKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_NamaDokterKeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_NamaDokterKeyPressed

    private void cmbCaraBayarActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_cmbCaraBayarActionPerformed
        // TODO add your handling code here:
    }//GEN-LAST:event_cmbCaraBayarActionPerformed

    private void BtnCetakActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnCetakActionPerformed

       if (Sequel.cariInteger("select count(bridging_surat_kontrol_bpjs.no_surat) from bridging_surat_kontrol_bpjs where bridging_surat_kontrol_bpjs.no_surat='" + noskdpPasien.getText() + "'") > 0) {
           DlgCekNoRMDialog(this);
        } else {
            JOptionPane.showMessageDialog(rootPane, "Data surat kontrol tidak ditemukan!");
        }
    }//GEN-LAST:event_BtnCetakActionPerformed

    private void BtnCetak1ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnCetak1ActionPerformed
        dispose();        // TODO add your handling code here:
    }//GEN-LAST:event_BtnCetak1ActionPerformed

    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        java.awt.EventQueue.invokeLater(() -> {
            CekKontrolBedaPoli dialog = new CekKontrolBedaPoli(new javax.swing.JFrame(), true);
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
    private component.TextBox Biaya;
    private widget.Button BtnCetak;
    private widget.Button BtnCetak1;
    private component.Label KodeDOkterRS;
    private component.Label KodepoliRS;
    private component.Label LblKdDokter;
    private component.Label LblKdPoli;
    private widget.TextBox NamaDokter;
    private widget.TextBox NamaPoli;
    private component.TextBox NoRawat;
    private component.TextBox NoReg;
    private component.Label NoTelpPasien;
    private usu.widget.glass.PanelGlass PanelWall3;
    private component.Label TAlmt;
    private component.Label THbngn;
    private widget.TextBox TNoRw;
    private component.Label TPngJwb;
    private widget.Tanggal TanggalPeriksa;
    private component.Button btnSimpan1;
    private component.Button btnSimpan2;
    private widget.ComboBox cmbCaraBayar;
    private component.Label jLabel10;
    private component.Label jLabel11;
    private component.Label jLabel28;
    private component.Label jLabel29;
    private component.Label jLabel30;
    private component.Label jLabel31;
    private component.Label jLabel32;
    private component.Label jLabel33;
    private component.Label jLabel34;
    private component.Label jLabel35;
    private component.Label jLabel36;
    private component.Panel jPanel1;
    private component.Panel jPanel2;
    private widget.TextBox kodecarabayar1;
    private component.Label lblNamaPasien;
    private component.Label lblNoRM;
    private component.Label namaDOkterRS;
    private widget.TextBox namacarabayar;
    private component.Label nopesertaBPJS;
    private component.Label noskdpPasien;
    // End of variables declaration//GEN-END:variables

       public void setPasien(String norm) {
        System.out.println(norm);
        //noskdpPasien.setText(noskdp);
        lblNoRM.setText(norm);
        nopesertaBPJS.setText(Sequel.cariIsi("select pasien.no_peserta from pasien where pasien.no_rkm_medis='" + lblNoRM.getText() + "'"));
        noskdpPasien.setText(Sequel.cariIsi("select no_surat from bridging_surat_kontrol_bpjs inner join bridging_sep on bridging_surat_kontrol_bpjs.no_sep=bridging_sep.no_sep where bridging_sep.nomr=? ", lblNoRM.getText()));
        lblNamaPasien.setText(Sequel.cariIsi("select pasien.nm_pasien from pasien where pasien.no_rkm_medis='" + lblNoRM.getText() + "'"));
        kodecarabayar1.setText("BPJ");
        namacarabayar.setText("BPJS");
        
        
        if (!lblNoRM.getText().equals("") && !lblNamaPasien.getText().equals("")) {
            tentukanHari();
//            cmbDokterTujuan.setVisible(false);
//            tentukanPilihan();
//            tampilPoli();
        }

    }

    public void isCek(String norm) {

    }

    private void UpdateUmur() {
        Sequel.mengedit("pasien", "no_rkm_medis=?", "umur=CONCAT(CONCAT(CONCAT(TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()), ' Th '),CONCAT(TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12), ' Bl ')),CONCAT(TIMESTAMPDIFF(DAY, DATE_ADD(DATE_ADD(tgl_lahir,INTERVAL TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) YEAR), INTERVAL TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12) MONTH), CURDATE()), ' Hr'))", 1, new String[]{lblNoRM.getText()});
    }

    private void isNumber() {
        if (BASENOREG.equals("booking")) {
            switch (URUTNOREG) {
                case "poli":
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kode_poli + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kode_poli + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
                case "dokter":
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + kode_dokter + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + kode_dokter + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + kode_dokter + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + kode_dokter + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
                case "dokter + poli":
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + kode_dokter + "' and kd_poli='" + kode_poli + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + kode_dokter + "' and kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + kode_dokter + "' and kd_poli='" + kode_poli + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + kode_dokter + "' and kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
                default:
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kode_poli + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kode_poli + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
            }
        } else {
            switch (URUTNOREG) {
                case "poli":
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
                case "dokter":
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + kode_dokter + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
                case "dokter + poli":
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + kode_dokter + "' and kd_poli='" + kode_poli + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
                default:
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + kode_dokter + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
            }
        }

        Valid.autoNomer3("select ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0) from reg_periksa where tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "' ", Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()).replaceAll("-", "/") + "/", 6, NoRawat);
    }

    private void tampilPenjab() {
        try {
            file = new File("./cache/anjunganpenjamin.iyem");
            file.createNewFile();
            fileWriter = new FileWriter(file);
            iyem = "";
            ps = koneksi.prepareStatement("select * from penjab where status='1' AND kd_pj<>'BPJ' order by kd_pj");
            cmbCaraBayar.removeAllItems();
            try {
                rs = ps.executeQuery();
                while (rs.next()) {
                    cmbCaraBayar.addItem(rs.getString(2).replaceAll("\"", ""));
                    iyem = iyem + "{\"NamaPenjab\":\"" + rs.getString(2).replaceAll("\"", "") + "\",\"KodePenjab\":\"" + rs.getString(1) + "\"},";
                }
            } catch (Exception e) {
                System.out.println("Notifikasi : " + e);
            } finally {
                if (rs != null) {
                    rs.close();
                }
                if (ps != null) {
                    ps.close();
                }
            }
            fileWriter.write("{\"anjunganpenjamin\":[" + iyem.substring(0, iyem.length() - 1) + "]}");
            fileWriter.flush();
            fileWriter.close();
            iyem = null;

        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
        }
    }

//    private void tampilDokter() {
//        if (!kode_poli.equals("")) {
//
//            try {
//                file = new File("./cache/anjungandokter.iyem");
//                file.createNewFile();
//                fileWriter = new FileWriter(file);
//                iyem = "";
//                ps = koneksi.prepareStatement("SELECT\n"
//                        + "	dokter.nm_dokter, \n"
//                        + "	jadwal.kd_dokter, jadwal.jam_mulai,jadwal.jam_selesai\n"
//                        + "FROM\n"
//                        + "	dokter\n"
//                        + "	INNER JOIN\n"
//                        + "	jadwal\n"
//                        + "	ON \n"
//                        + "		dokter.kd_dokter = jadwal.kd_dokter\n"
//                        + "		where jadwal.hari_kerja='" + hari + "' and jadwal.kd_poli='" + kode_poli + "'");
//                cmbDokterTujuan.removeAllItems();
//                try {
//                    rs = ps.executeQuery();
//                    while (rs.next()) {
//                        cmbDokterTujuan.addItem(rs.getString(1) + " " + rs.getString(3) + " s/d " + rs.getString(4));
//
//                        iyem = iyem + "{\"NamaDokter\":\"" + rs.getString(1).replaceAll("\"", "") + "\",\"KodeDokter\":\"" + rs.getString(2) + "\"},";
//                    }
//                } catch (Exception e) {
//                    System.out.println("Notifikasi resultset dokter : " + e);
//                } finally {
//                    if (rs != null) {
//                        rs.close();
//                    }
//                    if (ps != null) {
//                        ps.close();
//                    }
//                }
//                fileWriter.write("{\"anjungandokter\":[" + iyem.substring(0, iyem.length() - 1) + "]}");
//                fileWriter.flush();
//                fileWriter.close();
//                iyem = null;
//
//            } catch (Exception e) {
//                System.out.println("Notifikasi dokter : " + e);
//            }
//        } else {
//            JOptionPane.showMessageDialog(rootPane, "Pilih poli terlebih dahulu");
//        }
//
//        tampilPenjab();
//    }
//    private void tampilPoli() {
//
//        try {
//            file = new File("./cache/anjunganpoli.iyem");
//            file.createNewFile();
//            fileWriter = new FileWriter(file);
//            iyem = "";
//            ps = koneksi.prepareStatement("SELECT\n"
//                    + "	poliklinik.nm_poli, \n"
//                    + "	poliklinik.kd_poli, \n"
//                    + "	jadwal.hari_kerja\n"
//                    + "FROM\n"
//                    + "	jadwal\n"
//                    + "	INNER JOIN\n"
//                    + "	poliklinik\n"
//                    + "	ON \n"
//                    + "		jadwal.kd_poli = poliklinik.kd_poli\n"
//                    + "		where poliklinik.status='1' and jadwal.hari_kerja='" + hari + "'\n"
//                    + "		group by jadwal.kd_poli");
//            cmbPoliTujuan.removeAllItems();
//            try {
//                rs = ps.executeQuery();
//                while (rs.next()) {
//                    System.out.println(rs.getString(1));
//                    cmbPoliTujuan.addItem(rs.getString(1).replaceAll("\"", ""));
//                    iyem = iyem + "{\"NamaPoli\":\"" + rs.getString(1).replaceAll("\"", "") + "\",\"KodePoli\":\"" + rs.getString(2) + "\"},";
//                }
//            } catch (Exception e) {
//                System.out.println("Notifikasi resultset : " + e);
//            } finally {
//                if (rs != null) {
//                    rs.close();
//                }
//                if (ps != null) {
//                    ps.close();
//                }
//            }
//            System.out.println("disini");
//            fileWriter.write("{\"anjunganpoli\":[" + iyem.substring(0, iyem.length() - 1) + "]}");
//            fileWriter.flush();
//            fileWriter.close();
//            iyem = null;
//
//        } catch (Exception e) {
//            System.out.println("Notifikasi poli : " + e);
//        }
//    }
    private void tentukanHari() {
        try {
            java.sql.Date hariperiksa = java.sql.Date.valueOf(Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()));
            cal.setTime(hariperiksa);
            day = cal.get(Calendar.DAY_OF_WEEK);
            switch (day) {
                case 1:
                    hari = "AKHAD";
                    break;
                case 2:
                    hari = "SENIN";
                    break;
                case 3:
                    hari = "SELASA";
                    break;
                case 4:
                    hari = "RABU";
                    break;
                case 5:
                    hari = "KAMIS";
                    break;
                case 6:
                    hari = "JUMAT";
                    break;
                case 7:
                    hari = "SABTU";
                    break;
                default:
                    break;
            }
            System.out.println(hari);

        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
        }

    }

    private void isCekPasien() {
        try {
            ps3 = koneksi.prepareStatement("select nm_pasien,concat(pasien.alamat,', ',kelurahan.nm_kel,', ',kecamatan.nm_kec,', ',kabupaten.nm_kab) asal,"
                    + "namakeluarga,keluarga,pasien.kd_pj,penjab.png_jawab,if(tgl_daftar=?,'Baru','Lama') as daftar, "
                    + "TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) as tahun,pasien.no_peserta, "
                    + "(TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12)) as bulan, "
                    + "TIMESTAMPDIFF(DAY, DATE_ADD(DATE_ADD(tgl_lahir,INTERVAL TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) YEAR), INTERVAL TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12) MONTH), CURDATE()) as hari,pasien.no_ktp,pasien.no_tlp "
                    + "from pasien inner join kelurahan on pasien.kd_kel=kelurahan.kd_kel "
                    + "inner join kecamatan on pasien.kd_kec=kecamatan.kd_kec "
                    + "inner join kabupaten on pasien.kd_kab=kabupaten.kd_kab "
                    + "inner join penjab on pasien.kd_pj=penjab.kd_pj "
                    + "where pasien.no_rkm_medis=?");
            try {
                ps3.setString(1, Valid.SetTgl(TanggalPeriksa.getSelectedItem() + ""));
                ps3.setString(2, lblNoRM.getText());
                rs = ps3.executeQuery();
                while (rs.next()) {
                    TAlmt.setText(rs.getString("asal"));
                    TPngJwb.setText(rs.getString("namakeluarga"));
                    THbngn.setText(rs.getString("keluarga"));
                    NoTelpPasien.setText(rs.getString("no_tlp"));
                    umur = "0";
                    sttsumur = "Th";
                    if (rs.getInt("tahun") > 0) {
                        umur = rs.getString("tahun");
                        sttsumur = "Th";
                    } else if (rs.getInt("tahun") == 0) {
                        if (rs.getInt("bulan") > 0) {
                            umur = rs.getString("bulan");
                            sttsumur = "Bl";
                        } else if (rs.getInt("bulan") == 0) {
                            umur = rs.getString("hari");
                            sttsumur = "Hr";
                        }
                    }
                }
            } catch (Exception ex) {
                System.out.println(ex);
            } finally {
                if (rs != null) {
                    rs.close();
                }

                if (ps3 != null) {
                    ps3.close();
                }
            }
        } catch (Exception e) {
            System.out.println(e);
        }

        status = "Baru";
        if (Sequel.cariInteger("select count(no_rkm_medis) from reg_periksa where no_rkm_medis=? and kd_poli=?", lblNoRM.getText(), kode_poli) > 0) {
            status = "Lama";
        }

    }

    private void MnCetakRegisterActionPerformed(String norawat) {
//        this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
//        Map<String, Object> param = new HashMap<>();
//        param.put("namars", Sequel.cariIsi("select nama_instansi from setting"));
//        param.put("alamatrs", Sequel.cariIsi("select alamat_instansi from setting"));
//        param.put("kotars", Sequel.cariIsi("select kabupaten from setting"));
//        param.put("propinsirs", Sequel.cariIsi("select propinsi from setting"));
//        param.put("kontakrs", Sequel.cariIsi("select kontak from setting"));
//        param.put("emailrs", Sequel.cariIsi("select email from setting"));
//        param.put("logo", Sequel.cariGambar("select logo from setting"));
//        Valid.MyReportqryabdul("rptBuktiRegister.jasper", "report", "::[ Bukti Register ]::",
//                "select IF ((SELECT count( booking_registrasi.no_rkm_medis ) FROM booking_registrasi WHERE booking_registrasi.STATUS = 'Terdaftar'  AND booking_registrasi.no_rkm_medis = reg_periksa.no_rkm_medis AND booking_registrasi.tanggal_periksa = reg_periksa .tgl_registrasi AND kd_dokter = reg_periksa.kd_dokter )= 1,CONCAT( 'A', reg_periksa.no_reg ),CONCAT( 'W', reg_periksa.no_reg ) ) AS no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.jam_reg,pasien.no_tlp,"
//                + "reg_periksa.kd_dokter,dokter.nm_dokter,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.jk,pasien.umur as umur,poliklinik.nm_poli,"
//                + "reg_periksa.p_jawab,reg_periksa.almt_pj,reg_periksa.hubunganpj,reg_periksa.biaya_reg,reg_periksa.stts_daftar,penjab.png_jawab "
//                + "from reg_periksa inner join dokter inner join pasien inner join poliklinik inner join penjab "
//                + "on reg_periksa.kd_dokter=dokter.kd_dokter and reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
//                + "and reg_periksa.kd_pj=penjab.kd_pj and reg_periksa.kd_poli=poliklinik.kd_poli where reg_periksa.no_rawat='" + norawat + "' ", param);
//        System.out.println(norawat);
//        this.setCursor(Cursor.getDefaultCursor());

        
           this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
        Map<String, Object> param = new HashMap<>();
        param.put("namars", Sequel.cariIsi("select nama_instansi from setting"));
        param.put("alamatrs", Sequel.cariIsi("select alamat_instansi from setting"));
        param.put("kotars", Sequel.cariIsi("select kabupaten from setting"));
        param.put("propinsirs", Sequel.cariIsi("select propinsi from setting"));
        param.put("kontakrs", Sequel.cariIsi("select kontak from setting"));
        param.put("emailrs", Sequel.cariIsi("select email from setting"));
        param.put("logo", Sequel.cariGambar("select logo from setting"));
        Valid.MyReportqry("rptBuktiRegister.jasper", "report", "::[ Bukti Register ]::",
                "select reg_periksa.no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.jam_reg,pasien.no_tlp,"
                + "reg_periksa.kd_dokter,dokter.nm_dokter,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.jk,pasien.umur as umur,poliklinik.nm_poli,"
                + "reg_periksa.p_jawab,reg_periksa.almt_pj,reg_periksa.hubunganpj,reg_periksa.biaya_reg,reg_periksa.stts_daftar,penjab.png_jawab "
                + "from reg_periksa inner join dokter inner join pasien inner join poliklinik inner join penjab "
                + "on reg_periksa.kd_dokter=dokter.kd_dokter and reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                + "and reg_periksa.kd_pj=penjab.kd_pj and reg_periksa.kd_poli=poliklinik.kd_poli where reg_periksa.no_rawat='" + norawat + "' ", param);
        this.setCursor(Cursor.getDefaultCursor());

    }

    private void MnCetakBarcodeRawatJalan(String norawat) {
        this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
        Map<String, Object> param = new HashMap<>();
        param.put("namars", akses.getnamars());
        param.put("alamatrs", akses.getalamatrs());
        param.put("kotars", akses.getkabupatenrs());
        param.put("propinsirs", akses.getpropinsirs());
        param.put("kontakrs", akses.getkontakrs());
        param.put("emailrs", akses.getemailrs());
        param.put("no_rawat", norawat);
        param.put("logo", Sequel.cariGambar("select logo from setting"));
        Valid.MyReportSilentPrint("rptBarcodeRawat3.jasper", param, "::[ Barcode No.RM ]::");
        this.setCursor(Cursor.getDefaultCursor());

    }

    public boolean GeneralConsentSatuSehat(String NoRMPasien) {
        int cariflaging = Sequel.cariInteger("select count(flagging_pasien_satusehat.no_rkm_medis) from flagging_pasien_satusehat where flagging_pasien_satusehat.no_rkm_medis='" + NoRMPasien + "'");
        boolean statussatusehat = false;

        if (cariflaging > 0) {
            statussatusehat = true;
        } else {
            statussatusehat = false;
        }

        return statussatusehat;
    }

    private void generalconsentsave(String nomorrm) {
        if (GeneralConsentSatuSehat(nomorrm) == false) {
            Sequel.menyimpan2("flagging_pasien_satusehat", "?,?,?", "Data", 3, new String[]{
                nomorrm, "yes", Sequel.cariIsi("select now()")
            });

        }
    }
    
    private void DlgCekNoRMDialog(Dialog parent) {
        // Buat dialog dengan progress bar
        JDialog dialog = new JDialog(this, true);
        dialog.setUndecorated(true);
        JProgressBar progressBar = new JProgressBar();
        progressBar.setIndeterminate(true);
        progressBar.setString("Harap tunggu...");

        progressBar.setStringPainted(true);
        Timer timer = new Timer(20, null); // update tiap 20 ms
        timer.addActionListener(e -> {
            int value = progressBar.getValue();
            if (value < 100) {
                progressBar.setValue(value + 10); // naik 1 per tick
            } else {
                timer.stop(); // selesai
            }
        });
        timer.start();

        dialog.add(progressBar);
        dialog.setSize(500, 50);
        dialog.setLocationRelativeTo(this);
        dialog.setDefaultCloseOperation(JDialog.DO_NOTHING_ON_CLOSE);

        // Tampilkan dialog di UI thread
        SwingUtilities.invokeLater(() -> dialog.setVisible(true));

        // Proses berat di thread lain
        new Thread(() -> {
            // Simulasi proses berat (contoh: simpan DB, koneksi, dsb)
            try {

                String tanggalkontrol = Sequel.cariIsi("select bridging_surat_kontrol_bpjs.tgl_rencana from bridging_surat_kontrol_bpjs where bridging_surat_kontrol_bpjs.no_surat='" + noskdpPasien.getText() + "'");
                String noSEP = Sequel.cariIsi("select bridging_surat_kontrol_bpjs.no_sep from bridging_surat_kontrol_bpjs where bridging_surat_kontrol_bpjs.no_surat='" + noskdpPasien.getText() + "'");

                this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
                DlgRegistrasiSEPPertama form = new DlgRegistrasiSEPPertama(null, true);
                form.tampilPecahSEP(nopesertaBPJS.getText(), KodeDOkterRS.getText(), KodepoliRS.getText(), kode_poli, kode_dokter, namaDOkterRS.getText(), noSEP, tanggalkontrol, noskdpPasien.getText());
                form.setSize(this.getWidth(), this.getHeight());
                form.setLocationRelativeTo(jPanel1);
                this.dispose();
                form.setVisible(true);
                this.setCursor(Cursor.getDefaultCursor());

                Thread.sleep(1000); // ganti dengan proses nyata
            } catch (InterruptedException ex) {
                ex.printStackTrace();
            }

            // Setelah selesai, tutup dialog
            SwingUtilities.invokeLater(dialog::dispose);
        }
        ).start();

// tidak bisa ditutup manual
}
    
   

}
