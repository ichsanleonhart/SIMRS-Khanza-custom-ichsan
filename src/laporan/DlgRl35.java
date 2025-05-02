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
import java.awt.event.KeyListener;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.HashMap;
import java.util.Map;
import javax.swing.JOptionPane;
import javax.swing.JTable;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableColumn;
import simrskhanza.DlgKabupaten;

/**
 *
 * @author perpustakaan
 */
public final class DlgRl35 extends javax.swing.JDialog {

    private final DefaultTableModel tabMode;
    private Connection koneksi = koneksiDB.condb();
    private sekuel Sequel = new sekuel();
    private validasi Valid = new validasi();
    private PreparedStatement ps;
    private ResultSet rs;
    private DlgKabupaten kabupaten = new DlgKabupaten(null, false);
    private int i = 0, dalamkotal = 0, dalamkotap = 0, luarkotal = 0, luarkotap = 0, ttlkunjungan = 0, ttldalamkotal = 0, ttldalamkotap = 0, ttlluarkotal = 0,
            ttlluarkotap = 0, grandttlkunjungan = 0, jumlahharipoliklinikbuka = 0, ttljumlahharipoliklinikbuka = 0, jumlahpoliklinik = 0, ratapoliklinikbuka = 0;

    /**
     * Creates new form DlgLhtBiaya
     *
     * @param parent
     * @param modal
     */
    public DlgRl35(java.awt.Frame parent, boolean modal) {
        super(parent, modal);
        initComponents();
        this.setLocation(8, 1);
        setSize(885, 674);

        Object[] rowRwJlDr = {
            "No.", "Jenis Pelayanan", "Dalam Kota (L)", "Dalam Kota (P)",
            "Luar Kota (L)", "Luar Kota (P)", "Total Kunjungan"
        };

// Membuat model tabel dengan kolom tidak dapat diedit
        tabMode = new DefaultTableModel(null, rowRwJlDr) {
            @Override
            public boolean isCellEditable(int rowIndex, int colIndex) {
                return false;
            }
        };

// Mengatur model tabel
        tbBangsal.setModel(tabMode);
// Mengatur ukuran scroll pada tabel
        tbBangsal.setPreferredScrollableViewportSize(new Dimension(500, 500));
// Menonaktifkan pengaturan otomatis ukuran kolom
        tbBangsal.setAutoResizeMode(JTable.AUTO_RESIZE_OFF);

// Mengatur lebar kolom
        for (int i = 0; i < rowRwJlDr.length; i++) {
            TableColumn column = tbBangsal.getColumnModel().getColumn(i);
            if (i == 0) {
                column.setPreferredWidth(25); // Kolom "No." lebih kecil
            } else if (i == 1) {
                column.setPreferredWidth(300); // Kolom "Jenis Pelayanan" lebih besar
            } else {
                column.setPreferredWidth(120); // Kolom lainnya
            }
        }

        tbBangsal.setDefaultRenderer(Object.class, new WarnaTable());

        TCari.setDocument(new batasInput((byte) 100).getKata(TCari));

        kabupaten.addWindowListener(new WindowListener() {
            @Override
            public void windowOpened(WindowEvent e) {
            }

            @Override
            public void windowClosing(WindowEvent e) {
            }

            @Override
            public void windowClosed(WindowEvent e) {
                if (kabupaten.getTable().getSelectedRow() != -1) {
                    nmkabupaten.setText(kabupaten.getTable().getValueAt(kabupaten.getTable().getSelectedRow(), 0).toString());
                }
                nmkabupaten.requestFocus();
            }

            @Override
            public void windowIconified(WindowEvent e) {
            }

            @Override
            public void windowDeiconified(WindowEvent e) {
            }

            @Override
            public void windowActivated(WindowEvent e) {
                kabupaten.emptTeks();
            }

            @Override
            public void windowDeactivated(WindowEvent e) {
            }
        });

        kabupaten.getTable().addKeyListener(new KeyListener() {
            @Override
            public void keyTyped(KeyEvent e) {
            }

            @Override
            public void keyPressed(KeyEvent e) {
                if (e.getKeyCode() == KeyEvent.VK_SPACE) {
                    kabupaten.dispose();
                }
            }

            @Override
            public void keyReleased(KeyEvent e) {
            }
        });

        try {
            ps = koneksi.prepareStatement("SELECT kd_poli,nm_poli from poliklinik where nm_poli like ? order by nm_poli ");
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
        label21 = new widget.Label();
        nmkabupaten = new widget.TextBox();
        BtnSeek5 = new widget.Button();
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

        internalFrame1.setBorder(javax.swing.BorderFactory.createTitledBorder(javax.swing.BorderFactory.createLineBorder(new java.awt.Color(240, 245, 235)), "::[ Formulir RL 3.5 Rekapitulasi Kunjungan ]::", javax.swing.border.TitledBorder.DEFAULT_JUSTIFICATION, javax.swing.border.TitledBorder.DEFAULT_POSITION, new java.awt.Font("Tahoma", 0, 11), new java.awt.Color(50, 50, 50))); // NOI18N
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

        label21.setText("Dalam Kab/ Kota :");
        label21.setName("label21"); // NOI18N
        label21.setPreferredSize(new java.awt.Dimension(100, 23));
        panelGlass5.add(label21);

        nmkabupaten.setEditable(false);
        nmkabupaten.setName("nmkabupaten"); // NOI18N
        nmkabupaten.setPreferredSize(new java.awt.Dimension(215, 23));
        panelGlass5.add(nmkabupaten);

        BtnSeek5.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/190.png"))); // NOI18N
        BtnSeek5.setMnemonic('3');
        BtnSeek5.setToolTipText("Alt+3");
        BtnSeek5.setName("BtnSeek5"); // NOI18N
        BtnSeek5.setPreferredSize(new java.awt.Dimension(28, 23));
        BtnSeek5.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                BtnSeek5ActionPerformed(evt);
            }
        });
        BtnSeek5.addKeyListener(new java.awt.event.KeyAdapter() {
            public void keyPressed(java.awt.event.KeyEvent evt) {
                BtnSeek5KeyPressed(evt);
            }
        });
        panelGlass5.add(BtnSeek5);

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
                            + tabMode.getValueAt(r, 1).toString() + "','"
                            + tabMode.getValueAt(r, 2).toString() + "','"
                            + tabMode.getValueAt(r, 3).toString() + "','"
                            + tabMode.getValueAt(r, 4).toString() + "','"
                            + tabMode.getValueAt(r, 5).toString() + "','"
                            + tabMode.getValueAt(r, 6).toString() + "','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','" + akses.getalamatip() + "'", "Rekap Nota Pembayaran");
                }
            }
            Valid.MyReportqry("rptRl35.jasper", "report", "::[ Formulir RL 3.5 ]::", "select * from temporary where temporary.temp37='" + akses.getalamatip() + "' order by temporary.no", param);
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
    if (nmkabupaten.getText().equals("")) {
        Valid.textKosong(nmkabupaten, "Kabupaten");
    } else {
        tampil();
    }
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

    private void BtnSeek5ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_BtnSeek5ActionPerformed
        kabupaten.setSize(internalFrame1.getWidth() - 20, internalFrame1.getHeight() - 20);
        kabupaten.setLocationRelativeTo(internalFrame1);
        kabupaten.setAlwaysOnTop(false);
        kabupaten.setVisible(true);
    }//GEN-LAST:event_BtnSeek5ActionPerformed

    private void BtnSeek5KeyPressed(java.awt.event.KeyEvent evt) {//GEN-FIRST:event_BtnSeek5KeyPressed
        // TODO add your handling code here:
    }//GEN-LAST:event_BtnSeek5KeyPressed

    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        java.awt.EventQueue.invokeLater(() -> {
            DlgRl35 dialog = new DlgRl35(new javax.swing.JFrame(), true);
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
    private widget.Button BtnSeek5;
    private widget.ScrollPane Scroll;
    private widget.TextBox TCari;
    private widget.Tanggal Tgl1;
    private widget.Tanggal Tgl2;
    private widget.InternalFrame internalFrame1;
    private widget.Label jLabel6;
    private widget.Label jLabel7;
    private widget.Label label11;
    private widget.Label label18;
    private widget.Label label21;
    private widget.TextBox nmkabupaten;
    private widget.panelisi panelGlass5;
    private widget.Table tbBangsal;
    // End of variables declaration//GEN-END:variables

    public void tampil() {
        try {
            this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
            Valid.tabelKosong(tabMode);
            ps.setString(1, "%" + TCari.getText().trim() + "%");
            rs = ps.executeQuery();
            i = 1;
            dalamkotal = 0;
            dalamkotap = 0;
            luarkotal = 0;
            luarkotap = 0;
            ttlkunjungan = 0;
            ttldalamkotal = 0;
            ttldalamkotap = 0;
            ttlluarkotal = 0;
            ttlluarkotap = 0;
            grandttlkunjungan = 0;
            jumlahharipoliklinikbuka = 0;
            ttljumlahharipoliklinikbuka = 0;
            ratapoliklinikbuka = 0;
            jumlahpoliklinik = 0;

            while (rs.next()) {
                dalamkotal = Sequel.cariInteger("select count(reg_periksa.no_rawat) from reg_periksa inner join poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli inner join pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis "
                        + "where reg_periksa.kd_poli = '" + rs.getString("kd_poli") + "' and reg_periksa.tgl_registrasi between '" + Valid.SetTgl(Tgl1.getSelectedItem() + "") + "' and '" + Valid.SetTgl(Tgl2.getSelectedItem() + "") + "' "
                        + "and reg_periksa.almt_pj like '%" + nmkabupaten.getText() + "%' and pasien.jk = 'L' and reg_periksa.stts<>'Batal'");
                ttldalamkotal += dalamkotal;

                dalamkotap = Sequel.cariInteger("select count(reg_periksa.no_rawat) from reg_periksa inner join poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli inner join pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis "
                        + "where reg_periksa.kd_poli = '" + rs.getString("kd_poli") + "' and reg_periksa.tgl_registrasi between '" + Valid.SetTgl(Tgl1.getSelectedItem() + "") + "' and '" + Valid.SetTgl(Tgl2.getSelectedItem() + "") + "' "
                        + "and reg_periksa.almt_pj like '%" + nmkabupaten.getText() + "%' and pasien.jk = 'P' and reg_periksa.stts<>'Batal'");
                ttldalamkotap += dalamkotap;

                luarkotal = Sequel.cariInteger("select count(reg_periksa.no_rawat) from reg_periksa inner join poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli inner join pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis "
                        + "where reg_periksa.kd_poli = '" + rs.getString("kd_poli") + "' and reg_periksa.tgl_registrasi between '" + Valid.SetTgl(Tgl1.getSelectedItem() + "") + "' and '" + Valid.SetTgl(Tgl2.getSelectedItem() + "") + "' "
                        + "and reg_periksa.almt_pj not like '%" + nmkabupaten.getText() + "%' and pasien.jk = 'L' and reg_periksa.stts<>'Batal'");
                ttlluarkotal += luarkotal;

                luarkotap = Sequel.cariInteger("select count(reg_periksa.no_rawat) from reg_periksa inner join poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli inner join pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis "
                        + "where reg_periksa.kd_poli = '" + rs.getString("kd_poli") + "' and reg_periksa.tgl_registrasi between '" + Valid.SetTgl(Tgl1.getSelectedItem() + "") + "' and '" + Valid.SetTgl(Tgl2.getSelectedItem() + "") + "' "
                        + "and reg_periksa.almt_pj not like '%" + nmkabupaten.getText() + "%' and pasien.jk = 'P' and reg_periksa.stts<>'Batal'");
                ttlluarkotap += luarkotap;

                ttlkunjungan = dalamkotal + dalamkotap + luarkotal + luarkotap;
                grandttlkunjungan += ttlkunjungan;

                jumlahharipoliklinikbuka = Sequel.cariInteger("select count(distinct reg_periksa.tgl_registrasi) from reg_periksa where reg_periksa.kd_poli = '" + rs.getString("kd_poli") + "' and reg_periksa.tgl_registrasi between '" + Valid.SetTgl(Tgl1.getSelectedItem() + "") + "' and '" + Valid.SetTgl(Tgl2.getSelectedItem() + "") + "'");
                ttljumlahharipoliklinikbuka += jumlahharipoliklinikbuka;

                jumlahpoliklinik = Sequel.cariInteger("select count(poliklinik.kd_poli) from poliklinik where status = '1'");

                tabMode.addRow(new Object[]{
                    i, rs.getString("nm_poli"), dalamkotal, dalamkotap, luarkotal, luarkotap, ttlkunjungan
                });
                i++;
            }

            // Perhitungan rata-rata hanya jika jumlahpoliklinik > 0
            if (jumlahpoliklinik > 0) {
                ratapoliklinikbuka = ttljumlahharipoliklinikbuka / jumlahpoliklinik;
            } else {
                ratapoliklinikbuka = 0;
            }

            if (i > 1) {
                tabMode.addRow(new Object[]{"", "TOTAL", ttldalamkotal, ttldalamkotap, ttlluarkotal, ttlluarkotap, grandttlkunjungan});
                tabMode.addRow(new Object[]{"", "Rata-Rata Hari Poliklinik Buka", ratapoliklinikbuka, "", "", "", ""});

                // Perhitungan rata-rata kunjungan per hari hanya jika ratapoliklinikbuka > 0
                if (ratapoliklinikbuka > 0) {
                    tabMode.addRow(new Object[]{"", "Rata-Rata Kunjungan per Hari",
                         ttldalamkotal / ratapoliklinikbuka,
                         ttldalamkotap / ratapoliklinikbuka,
                         ttlluarkotal / ratapoliklinikbuka,
                         ttlluarkotap / ratapoliklinikbuka,
                         grandttlkunjungan / ratapoliklinikbuka
                    });
                } else {
                    tabMode.addRow(new Object[]{"", "Rata-Rata Kunjungan per Hari", "-", "-", "-", "-", "-"});
                }
            }

            this.setCursor(Cursor.getDefaultCursor());
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
        }
    }

}
