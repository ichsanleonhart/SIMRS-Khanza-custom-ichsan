/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 /*
 * DlgLhtBiaya.java
 *
 * Created on 12 Jul 10, 16:21:34
 */
package laporan;

import fungsi.WarnaTable;
import fungsi.batasInput;
import fungsi.koneksiDB;
import fungsi.sekuel;
import fungsi.validasi;
import fungsi.akses;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.event.KeyEvent;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.HashMap;
import java.util.Map;
import javax.swing.JOptionPane;
import javax.swing.JTable;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableColumn;

/**
 *
 * @author perpustakaan
 */
public final class DlgRl310 extends javax.swing.JDialog {

    private final DefaultTableModel tabMode;
    private Connection koneksi = koneksiDB.condb();
    private sekuel Sequel = new sekuel();
    private validasi Valid = new validasi();
    private PreparedStatement ps, psrujukanrs, psrujukanpuskesmas, psrujukanfaskeslain,psrujukansemua,psrujukanbalikpuskesmas,
            psrujukanbalikrs,psrujukanbalikfaskeslain,psrujukanbaliksemua,psdirujukrujukan,psdirujukdatangsendiri, psdirujuksemua,psdirujukditerima;
    private ResultSet rs, rsrujukanrs, rsrujukanpuskesmas, rsrujukanfaskeslain,
            rsrujukansemua,rsrujukanbalikpuskesmas,rsrujukanbalikrs,rsrujukanbalikfaskeslain,rsrujukanbaliksemua, rsdirujukrujukan,rsdirujukdatangsendiri, 
            rsdirujuksemua,rsdirujukditerima;
    private int i = 0, rujukrs = 0, rujukpuskesmas = 0, rujukfaskeslain = 0, rujuksemua = 0,rujukbalikrs = 0, rujukbalikpuskesmas = 0, 
            rujukbalikfaskeslain = 0, rujukbaliksemua = 0, dirujukrujukan = 0,dirujukdatangsendiri = 0,dirujuksemua = 0,dirujukditerima =0,
            ttlrujukpuskesmas= 0,ttlrujukrs= 0, ttlrujukfaskeslain= 0,ttlrujuksemua= 0,ttlrujukbalikpuskesmas= 0,ttlrujukbalikrs= 0, ttlrujukbalikfaskeslain= 0,
            ttlrujukbaliksemua= 0,ttldirujukrujukan= 0,ttldirujukdatangsendiri= 0,ttldirujuksemua= 0,ttldirujukditerima= 0           
            ;

    /**
     * Creates new form DlgLhtBiaya
     *
     * @param parent
     * @param modal
     */
    public DlgRl310(java.awt.Frame parent, boolean modal) {
        super(parent, modal);
        initComponents();
        this.setLocation(8, 1);
        setSize(885, 674);

        Object[] rowRwJlDr = {
            "No.", "Jenis Spesialisasi", "Rujukan Masuk Puskesmas", "Rujukan Masuk Rs Lain", "Rujukan Masuk Faskes Lain", "Total Rujukan Masuk",
            "Rujukan Balik Puskesmas", "Rujukan Balik Rs Asal", "Rujukan Balik Puskesmas", "Rujukan Balik Faskes Lain", "Total Rujukan Balik",
            "Rujuk Keluar Pasien Rujukan", "Rujuk Keluar Pasien Datang Sendiri", "Total Dirujuk Keluar", "Diterima Kembali"
        };
        tabMode = new DefaultTableModel(null, rowRwJlDr) {
            @Override
            public boolean isCellEditable(int rowIndex, int colIndex) {
                return false;
            }
        };
        tbBangsal.setModel(tabMode);
        //tbBangsal.setDefaultRenderer(Object.class, new WarnaTable(jPanel2.getBackground(),tbBangsal.getBackground()));
        tbBangsal.setPreferredScrollableViewportSize(new Dimension(500, 500));
        tbBangsal.setAutoResizeMode(JTable.AUTO_RESIZE_OFF);

        for (i = 0; i < 13; i++) {
            TableColumn column = tbBangsal.getColumnModel().getColumn(i);
            if (i == 0) {
                column.setPreferredWidth(25);
            } else if (i == 1) {
                column.setPreferredWidth(300);
            } else {
                column.setPreferredWidth(150);
            }
        }
        tbBangsal.setDefaultRenderer(Object.class, new WarnaTable());

        TCari.setDocument(new batasInput((byte) 100).getKata(TCari));

        try {
            ps = koneksi.prepareStatement("SELECT kd_poli,nm_poli from poliklinik where status ='1' order by nm_poli ");

            psrujukanpuskesmas = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk_masuk on rujuk_masuk.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and rujuk_masuk.perujuk like '%pkm%' and reg_periksa.tgl_registrasi between ? and ? "
                    + "or reg_periksa.kd_poli=? and rujuk_masuk.perujuk like '%puskesmas%' and reg_periksa.tgl_registrasi between ? and ?");
            

            psrujukanrs = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk_masuk on rujuk_masuk.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and rujuk_masuk.perujuk like '%rs%' and reg_periksa.tgl_registrasi between ? and ? "
                    + "or reg_periksa.kd_poli=? and rujuk_masuk.perujuk like '%rumah sakit%' and reg_periksa.tgl_registrasi between ? and ?");

            psrujukanfaskeslain = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk_masuk on rujuk_masuk.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and rujuk_masuk.perujuk like '%klinik%' and reg_periksa.tgl_registrasi between ? and ? "
                    + "or reg_periksa.kd_poli=? and rujuk_masuk.perujuk like '%dr%' and reg_periksa.tgl_registrasi between ? and ?");

            psrujukansemua = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk_masuk on rujuk_masuk.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and reg_periksa.tgl_registrasi between ? and ? ");
            
            //surat_rujukan_balik
            
            psrujukanbalikpuskesmas = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join surat_rujukan_balik on surat_rujukan_balik.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and surat_rujukan_balik.ppk1 like '%pkm%' and reg_periksa.tgl_registrasi between ? and ? "
                    + "or reg_periksa.kd_poli=? and surat_rujukan_balik.ppk1 like '%puskesmas%' and reg_periksa.tgl_registrasi between ? and ?");

            psrujukanbalikrs = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join surat_rujukan_balik on surat_rujukan_balik.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and surat_rujukan_balik.ppk1 like '%rs%' and reg_periksa.tgl_registrasi between ? and ? "
                    + "or reg_periksa.kd_poli=? and surat_rujukan_balik.ppk1 like '%rumah sakit%' and reg_periksa.tgl_registrasi between ? and ?");

            psrujukanbalikfaskeslain = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join surat_rujukan_balik on surat_rujukan_balik.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and surat_rujukan_balik.ppk1 like '%klinik%' and reg_periksa.tgl_registrasi between ? and ? "
                    + "or reg_periksa.kd_poli=? and surat_rujukan_balik.ppk1 like '%dr%' and reg_periksa.tgl_registrasi between ? and ?");

            psrujukanbaliksemua = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join surat_rujukan_balik on surat_rujukan_balik.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and reg_periksa.tgl_registrasi between ? and ? ");

            psdirujukrujukan = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk on rujuk.no_rawat=reg_periksa.no_rawat inner join rujuk_masuk on rujuk_masuk.no_rawat = rujuk.no_rawat "
                    + "where reg_periksa.kd_poli=? and reg_periksa.tgl_registrasi between ? and ? ");
            
            psdirujukdatangsendiri = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk on rujuk.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.no_rawat not in (select no_rawat from rujuk_masuk) and reg_periksa.kd_poli=? and reg_periksa.tgl_registrasi between ? and ? ");
            
            psdirujuksemua = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk on rujuk.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.kd_poli=? and reg_periksa.tgl_registrasi between ? and ? ");
            
            psdirujukditerima = koneksi.prepareStatement(
                    "select count(reg_periksa.no_rawat) from reg_periksa inner join rujuk on rujuk.no_rawat=reg_periksa.no_rawat "
                    + "where rujuk.keterangan like '%terima%' and reg_periksa.kd_poli=? and reg_periksa.tgl_registrasi between ? and ? ");

        } catch (Exception e) {
            System.out.println(e);
        }
    }

    /**
     * This method is called from within the constructor to initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is always
     * regenerated by the Form Editor.
     */
    @SuppressWarnings("unchecked")
    // <editor-fold defaultstate="collapsed" desc="Generated Code">//GEN-BEGIN:initComponents
    private void initComponents() {

        internalFrame1 = new widget.InternalFrame();
        Scroll = new widget.ScrollPane();
        tbBangsal = new widget.Table();
        panelGlass5 = new widget.panelisi();
        label11 = new widget.Label();
        Tgl1 = new widget.Tanggal();
        label18 = new widget.Label();
        Tgl2 = new widget.Tanggal();
        jLabel6 = new widget.Label();
        TCari = new widget.TextBox();
        BtnCari = new widget.Button();
        BtnAll = new widget.Button();
        jLabel7 = new widget.Label();
        BtnPrint = new widget.Button();
        BtnKeluar = new widget.Button();

        setDefaultCloseOperation(javax.swing.WindowConstants.DISPOSE_ON_CLOSE);
        setUndecorated(true);
        setResizable(false);
        addWindowListener(new java.awt.event.WindowAdapter() {
            public void windowActivated(java.awt.event.WindowEvent evt) {
                formWindowActivated(evt);
            }
            public void windowOpened(java.awt.event.WindowEvent evt) {
                formWindowOpened(evt);
            }
        });

        internalFrame1.setBorder(javax.swing.BorderFactory.createTitledBorder(javax.swing.BorderFactory.createLineBorder(new java.awt.Color(240, 245, 235)), "::[ RL 3.10 Rekapitulasi Kegiatan Pelayanan Rujukan ]::", javax.swing.border.TitledBorder.DEFAULT_JUSTIFICATION, javax.swing.border.TitledBorder.DEFAULT_POSITION, new java.awt.Font("Tahoma", 0, 11), new java.awt.Color(50, 50, 50))); // NOI18N
        internalFrame1.setName("internalFrame1"); // NOI18N
        internalFrame1.setLayout(new java.awt.BorderLayout(1, 1));

        Scroll.setName("Scroll"); // NOI18N
        Scroll.setOpaque(true);

        tbBangsal.setName("tbBangsal"); // NOI18N
        Scroll.setViewportView(tbBangsal);

        internalFrame1.add(Scroll, java.awt.BorderLayout.CENTER);

        panelGlass5.setName("panelGlass5"); // NOI18N
        panelGlass5.setPreferredSize(new java.awt.Dimension(55, 55));
        panelGlass5.setLayout(new java.awt.FlowLayout(java.awt.FlowLayout.LEFT, 5, 9));

        label11.setText("Tanggal :");
        label11.setName("label11"); // NOI18N
        label11.setPreferredSize(new java.awt.Dimension(50, 23));
        panelGlass5.add(label11);

        Tgl1.setDisplayFormat("dd-MM-yyyy");
        Tgl1.setName("Tgl1"); // NOI18N
        Tgl1.setPreferredSize(new java.awt.Dimension(90, 23));
        panelGlass5.add(Tgl1);

        label18.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        label18.setText("s.d.");
        label18.setName("label18"); // NOI18N
        label18.setPreferredSize(new java.awt.Dimension(25, 23));
        panelGlass5.add(label18);

        Tgl2.setDisplayFormat("dd-MM-yyyy");
        Tgl2.setName("Tgl2"); // NOI18N
        Tgl2.setPreferredSize(new java.awt.Dimension(90, 23));
        panelGlass5.add(Tgl2);

        jLabel6.setText("Key Word :");
        jLabel6.setName("jLabel6"); // NOI18N
        jLabel6.setPreferredSize(new java.awt.Dimension(60, 23));
        panelGlass5.add(jLabel6);

        TCari.setName("TCari"); // NOI18N
        TCari.setPreferredSize(new java.awt.Dimension(155, 23));
        TCari.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                TCariKeyPressed(evt);
            }
        });
        panelGlass5.add(TCari);

        BtnCari.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/accept.png"))); // NOI18N
        BtnCari.setMnemonic('2');
        BtnCari.setToolTipText("Alt+2");
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
        panelGlass5.add(BtnCari);

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
        panelGlass5.add(BtnAll);

        jLabel7.setName("jLabel7"); // NOI18N
        jLabel7.setPreferredSize(new java.awt.Dimension(30, 23));
        panelGlass5.add(jLabel7);

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
        panelGlass5.add(BtnPrint);

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
        panelGlass5.add(BtnKeluar);

        internalFrame1.add(panelGlass5, java.awt.BorderLayout.PAGE_END);

        getContentPane().add(internalFrame1, java.awt.BorderLayout.CENTER);

        pack();
    }// </editor-fold>//GEN-END:initComponents

    private void BtnPrintActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnPrintActionPerformed
        this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
        if (tabMode.getRowCount() == 0) {
            JOptionPane.showMessageDialog(null, "Maaf, data sudah habis. Tidak ada data yang bisa anda print...!!!!");
            //TCari.requestFocus();
        } else if (tabMode.getRowCount() != 0) {

            Map<String, Object> param = new HashMap<>();
            param.put("namars", akses.getnamars());
            param.put("alamatrs", akses.getalamatrs());
            param.put("kotars", akses.getkabupatenrs());
            param.put("propinsirs", akses.getpropinsirs());
            param.put("kontakrs", akses.getkontakrs());
            param.put("emailrs", akses.getemailrs());
            param.put("periode", Tgl1.getSelectedItem() + " s.d. " + Tgl2.getSelectedItem());
            param.put("tanggal", Tgl2.getDate());
            param.put("logo", Sequel.cariGambar("select setting.logo from setting"));
            Sequel.queryu("delete from temporary where temp37='" + akses.getalamatip() + "'");
            for (int r = 0; r < tabMode.getRowCount(); r++) {
                if (!tbBangsal.getValueAt(r, 0).toString().contains(">>")) {
                    Sequel.menyimpan("temporary", "'" + r + "','"
                            + tabMode.getValueAt(r, 0).toString() + "','"
                            + tabMode.getValueAt(r, 1).toString().replaceAll("'", "`") + "','"
                            + tabMode.getValueAt(r, 2).toString() + "','"
                            + tabMode.getValueAt(r, 3).toString() + "','"
                            + tabMode.getValueAt(r, 4).toString() + "','"
                            + tabMode.getValueAt(r, 5).toString() + "','"
                            + tabMode.getValueAt(r, 6).toString() + "','"
                            + tabMode.getValueAt(r, 7).toString() + "','"
                            + tabMode.getValueAt(r, 8).toString() + "','0','0','0','"
                            + tabMode.getValueAt(r, 9).toString() + "','"
                            + tabMode.getValueAt(r, 10).toString() + "','"
                            + tabMode.getValueAt(r, 11).toString() + "','"
                            + tabMode.getValueAt(r, 12).toString() + "','"                                                     
                            + tabMode.getValueAt(r, 13).toString() + "','','','','','','','','','','','','','','','','','','','','" + akses.getalamatip() + "'", "Rekap Nota Pembayaran");
                }
            }

            Valid.MyReportqry("rptRl310.jasper", "report", "::[ Formulir RL 3.10 ]::", "select * from temporary where temporary.temp37='" + akses.getalamatip() + "' order by temporary.no", param);
        }
        this.setCursor(Cursor.getDefaultCursor());
}//GEN-LAST:event_BtnPrintActionPerformed

    private void BtnPrintKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnPrintKeyPressed
        if (evt.getKeyCode() == KeyEvent.VK_SPACE) {
            BtnPrintActionPerformed(null);
        } else {
            //Valid.pindah(evt, BtnHapus, BtnAll);
        }
}//GEN-LAST:event_BtnPrintKeyPressed

    private void BtnKeluarActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnKeluarActionPerformed
        dispose();
}//GEN-LAST:event_BtnKeluarActionPerformed

    private void BtnKeluarKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnKeluarKeyPressed
        if (evt.getKeyCode() == KeyEvent.VK_SPACE) {
            dispose();
        } else {
            Valid.pindah(evt, BtnKeluar, TCari);
        }
}//GEN-LAST:event_BtnKeluarKeyPressed

private void BtnCariActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnCariActionPerformed
    tampil();
}//GEN-LAST:event_BtnCariActionPerformed

private void BtnCariKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnCariKeyPressed
    if (evt.getKeyCode() == KeyEvent.VK_SPACE) {
        this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
        tampil();
        this.setCursor(Cursor.getDefaultCursor());
    } else {
        Valid.pindah(evt, TCari, BtnPrint);
    }
}//GEN-LAST:event_BtnCariKeyPressed

    private void formWindowOpened(java.awt.event.WindowEvent evt) {//GEN-FIRST:event_formWindowOpened
        tampil();
    }//GEN-LAST:event_formWindowOpened

    private void TCariKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_TCariKeyPressed
        if (evt.getKeyCode() == KeyEvent.VK_ENTER) {
            BtnCariActionPerformed(null);
        } else if (evt.getKeyCode() == KeyEvent.VK_PAGE_DOWN) {
            BtnCari.requestFocus();
        } else if (evt.getKeyCode() == KeyEvent.VK_PAGE_UP) {
            BtnKeluar.requestFocus();
        }
    }//GEN-LAST:event_TCariKeyPressed

    private void BtnAllActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnAllActionPerformed
        TCari.setText("");
        tampil();
    }//GEN-LAST:event_BtnAllActionPerformed

    private void BtnAllKeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnAllKeyPressed
        if (evt.getKeyCode() == KeyEvent.VK_SPACE) {
            BtnAllActionPerformed(null);
        } else {

        }
    }//GEN-LAST:event_BtnAllKeyPressed

    private void formWindowActivated(java.awt.event.WindowEvent evt) {//GEN-FIRST:event_formWindowActivated
        tampil();

    }//GEN-LAST:event_formWindowActivated

    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        java.awt.EventQueue.invokeLater(() -> {
            DlgRl310 dialog = new DlgRl310(new javax.swing.JFrame(), true);
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
    private widget.Button BtnAll;
    private widget.Button BtnCari;
    private widget.Button BtnKeluar;
    private widget.Button BtnPrint;
    private widget.ScrollPane Scroll;
    private widget.TextBox TCari;
    private widget.Tanggal Tgl1;
    private widget.Tanggal Tgl2;
    private widget.InternalFrame internalFrame1;
    private widget.Label jLabel6;
    private widget.Label jLabel7;
    private widget.Label label11;
    private widget.Label label18;
    private widget.panelisi panelGlass5;
    private widget.Table tbBangsal;
    // End of variables declaration//GEN-END:variables

    public void tampil() {
        try {
            this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
            Valid.tabelKosong(tabMode);
            rs = ps.executeQuery();
            i = 1;
            while (rs.next()) {
                psrujukanrs.setString(1, rs.getString("kd_poli"));
                psrujukanrs.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanrs.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                psrujukanrs.setString(4, rs.getString("kd_poli"));
                psrujukanrs.setString(5, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanrs.setString(6, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukanrs = psrujukanrs.executeQuery();
                rujukrs = 0;
                if (rsrujukanrs.next()) {
                    rujukrs = rsrujukanrs.getInt(1);
                }
                ttlrujukrs = ttlrujukrs + rujukrs;

                psrujukanpuskesmas.setString(1, rs.getString("kd_poli"));
                psrujukanpuskesmas.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanpuskesmas.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                psrujukanpuskesmas.setString(4, rs.getString("kd_poli"));
                psrujukanpuskesmas.setString(5, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanpuskesmas.setString(6, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukanpuskesmas = psrujukanpuskesmas.executeQuery();
                rujukpuskesmas = 0;
                if (rsrujukanpuskesmas.next()) {
                    rujukpuskesmas = rsrujukanpuskesmas.getInt(1);
                }
                ttlrujukpuskesmas = ttlrujukpuskesmas + rujukpuskesmas;

                psrujukanfaskeslain.setString(1, rs.getString("kd_poli"));
                psrujukanfaskeslain.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanfaskeslain.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                psrujukanfaskeslain.setString(4, rs.getString("kd_poli"));
                psrujukanfaskeslain.setString(5, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanfaskeslain.setString(6, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukanfaskeslain = psrujukanfaskeslain.executeQuery();
                rujukfaskeslain = 0;
                if (rsrujukanfaskeslain.next()) {
                    rujukfaskeslain = rsrujukanfaskeslain.getInt(1);
                }
                ttlrujukfaskeslain=ttlrujukfaskeslain+rujukfaskeslain;

                psrujukansemua.setString(1, rs.getString("kd_poli"));
                psrujukansemua.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukansemua.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukansemua = psrujukansemua.executeQuery();
                rujuksemua = 0;
                if (rsrujukansemua.next()) {
                    rujuksemua = rsrujukansemua.getInt(1);
                }
                ttlrujuksemua=ttlrujuksemua+rujuksemua;
                
                psrujukanbalikrs.setString(1, rs.getString("kd_poli"));
                psrujukanbalikrs.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanbalikrs.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                psrujukanbalikrs.setString(4, rs.getString("kd_poli"));
                psrujukanbalikrs.setString(5, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanbalikrs.setString(6, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukanbalikrs = psrujukanbalikrs.executeQuery();
                rujukbalikrs = 0;
                if (rsrujukanbalikrs.next()) {
                    rujukbalikrs = rsrujukanbalikrs.getInt(1);
                }
                
                ttlrujukbalikrs=ttlrujukbalikrs + rujukbalikrs;

                psrujukanbalikpuskesmas.setString(1, rs.getString("kd_poli"));
                psrujukanbalikpuskesmas.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanbalikpuskesmas.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                psrujukanbalikpuskesmas.setString(4, rs.getString("kd_poli"));
                psrujukanbalikpuskesmas.setString(5, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanbalikpuskesmas.setString(6, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukanbalikpuskesmas = psrujukanbalikpuskesmas.executeQuery();
                rujukbalikpuskesmas = 0;
                if (rsrujukanbalikpuskesmas.next()) {
                    rujukbalikpuskesmas = rsrujukanbalikpuskesmas.getInt(1);
                }
                ttlrujukbalikpuskesmas = ttlrujukbalikpuskesmas + rujukbalikpuskesmas;

                psrujukanbalikfaskeslain.setString(1, rs.getString("kd_poli"));
                psrujukanbalikfaskeslain.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanbalikfaskeslain.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                psrujukanbalikfaskeslain.setString(4, rs.getString("kd_poli"));
                psrujukanbalikfaskeslain.setString(5, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanbalikfaskeslain.setString(6, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukanbalikfaskeslain = psrujukanbalikfaskeslain.executeQuery();
                rujukbalikfaskeslain = 0;
                if (rsrujukanbalikfaskeslain.next()) {
                    rujukbalikfaskeslain = rsrujukanbalikfaskeslain.getInt(1);
                }
                ttlrujukbalikfaskeslain = ttlrujukbalikfaskeslain + rujukbalikfaskeslain;

                psrujukanbaliksemua.setString(1, rs.getString("kd_poli"));
                psrujukanbaliksemua.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psrujukanbaliksemua.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsrujukanbaliksemua = psrujukanbaliksemua.executeQuery();
                rujukbaliksemua = 0;
                if (rsrujukanbaliksemua.next()) {
                    rujukbaliksemua = rsrujukanbaliksemua.getInt(1);
                }
                ttlrujukbaliksemua = ttlrujukbaliksemua + rujukbaliksemua;

                psdirujukrujukan.setString(1, rs.getString("kd_poli"));
                psdirujukrujukan.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psdirujukrujukan.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsdirujukrujukan = psdirujukrujukan.executeQuery();
                dirujukrujukan = 0;
                if (rsdirujukrujukan.next()) {
                    dirujukrujukan = rsdirujukrujukan.getInt(1);
                }
                ttldirujukrujukan = ttldirujukrujukan+dirujukrujukan;
                
                psdirujukdatangsendiri.setString(1, rs.getString("kd_poli"));
                psdirujukdatangsendiri.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psdirujukdatangsendiri.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsdirujukdatangsendiri = psdirujukdatangsendiri.executeQuery();
                dirujukdatangsendiri = 0;
                if (rsdirujukdatangsendiri.next()) {
                    dirujukdatangsendiri = rsdirujukdatangsendiri.getInt(1);
                }
                ttldirujukdatangsendiri = ttldirujukdatangsendiri+dirujukdatangsendiri;
                
                psdirujuksemua.setString(1, rs.getString("kd_poli"));
                psdirujuksemua.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psdirujuksemua.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsdirujuksemua = psdirujuksemua.executeQuery();
                dirujuksemua = 0;
                if (rsdirujuksemua.next()) {
                    dirujuksemua = rsdirujuksemua.getInt(1);
                }
                ttldirujuksemua = ttldirujuksemua + dirujuksemua;
                
                psdirujukditerima.setString(1, rs.getString("kd_poli"));
                psdirujukditerima.setString(2, Valid.SetTgl(Tgl1.getSelectedItem() + "") + " 00:00:00.0");
                psdirujukditerima.setString(3, Valid.SetTgl(Tgl2.getSelectedItem() + "") + " 23:59:59.0");
                rsdirujukditerima = psdirujukditerima.executeQuery();
                dirujukditerima = 0;
                if (rsdirujukditerima.next()) {
                    dirujukditerima = rsdirujukditerima.getInt(1);
                }
                ttldirujukditerima = ttldirujukditerima + dirujukditerima;
                

                tabMode.addRow(new Object[]{
                    i, rs.getString("nm_poli"), rujukpuskesmas,rujukrs, rujukfaskeslain,rujuksemua,rujukbalikpuskesmas,rujukbalikrs, rujukbalikfaskeslain,rujukbaliksemua,
                    dirujukrujukan,dirujukdatangsendiri,dirujuksemua,dirujukditerima
                });
                i++;
            }
            if (i > 1) {
                tabMode.addRow(new Object[]{
                    "", "TOTAL", ttlrujukpuskesmas,ttlrujukrs, ttlrujukfaskeslain,ttlrujuksemua,ttlrujukbalikpuskesmas,ttlrujukbalikrs, ttlrujukbalikfaskeslain,
                    ttlrujukbaliksemua,ttldirujukrujukan,ttldirujukdatangsendiri,ttldirujuksemua,ttldirujukditerima
                });
            }
            this.setCursor(Cursor.getDefaultCursor());
        } catch (Exception e) {
            System.out.println(e);
        }
    }

}
