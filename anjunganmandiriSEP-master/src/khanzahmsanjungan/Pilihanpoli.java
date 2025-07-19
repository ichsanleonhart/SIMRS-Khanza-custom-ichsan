
package khanzahmsanjungan;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import fungsi.akses;
import fungsi.koneksiDB;
import fungsi.sekuel;
import fungsi.validasi;
import java.awt.Color;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.Rectangle;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Properties;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.swing.JLabel;
import javax.swing.JPanel;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import widget.PanelBiasa;
import widget.Label;
import javax.swing.*;


/**
 *
 * @author LENOVO
 */
public class Pilihanpoli extends javax.swing.JDialog {
    private sekuel Sequel = new sekuel();
    private Connection koneksi = koneksiDB.condb();
    private PreparedStatement ps, ps3, pscaripiutang;
    private ResultSet rs;
    private validasi Valid = new validasi();
    private String hari = "";
    private Calendar cal = Calendar.getInstance();
    private int day = cal.get(Calendar.DAY_OF_WEEK);
    private SimpleDateFormat dateformat = new SimpleDateFormat("ddMMyyyy");
    private String umur = "0", sttsumur = "Th",kode_dokter = "", kode_poli = "", nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email;
    private String status = "Baru", BASENOREG = "", URUTNOREG = "", aktifjadwal = "";
    private Properties prop = new Properties();
    private File file;
    private FileWriter fileWriter;
    private String iyem;
    private ObjectMapper mapper = new ObjectMapper();
    private JsonNode root;
    private JsonNode response;
    private FileReader myObj;
     
     

    /**
     * Creates new form Pilihanpoli
     */
     
  
    public Pilihanpoli(java.awt.Frame parent, boolean modal) {
        super(parent, modal);
        initComponents();
        this.setPreferredSize(new Dimension(1200,800));
        this.setSize(1200, 600);
        ArrayList<DataObject> dataList = new ArrayList<>();
         JSONArray jsonArray = new JSONArray();
         JSONObject rowObject = new JSONObject();
         
  
        String biasa[];
    
        PanelBiasa panel1 = new PanelBiasa();
        PanelBiasa panel2 = new PanelBiasa();
        PanelBiasa panel3 = new PanelBiasa();
        Label[] labelNamaPoli = new Label[3];
        JLabel labelkddokter = new JLabel();
 
         try {
                        
//             ps=koneksi.prepareStatement("SELECT dokter.kd_dokter,dokter.nm_dokter,poliklinik.kd_poli,poliklinik.nm_poli,maping_poli_bpjs.kd_poli_bpjs,maping_poli_bpjs.nm_poli_bpjs, maping_dokter_dpjpvclaim.kd_dokter_bpjs,dokter.nm_dokter from jadwal INNER JOIN dokter INNER JOIN poliklinik " +
//"inner join maping_poli_bpjs inner join maping_dokter_dpjpvclaim ON jadwal.kd_dokter=dokter.kd_dokter and jadwal.kd_poli=poliklinik.kd_poli and maping_poli_bpjs.kd_poli_rs=poliklinik.kd_poli and maping_dokter_dpjpvclaim.kd_dokter=dokter.kd_dokter where jadwal.hari_kerja=? order by poliklinik.nm_poli asc");
             ps=koneksi.prepareStatement("SELECT dokter.kd_dokter,dokter.nm_dokter,poliklinik.kd_poli,poliklinik.nm_poli from jadwal INNER JOIN dokter INNER JOIN poliklinik\n" +
"ON jadwal.kd_dokter=dokter.kd_dokter and jadwal.kd_poli=poliklinik.kd_poli where jadwal.hari_kerja=? order by poliklinik.nm_poli asc");
                    if (day == 1) {
                        hari = "MINGGU";
                    } else if (day == 2) {
                        hari = "SENIN";
                    } else if (day == 3) {
                        hari = "SELASA";
                    } else if (day == 4) {
                        hari = "RABU";
                    } else if (day == 5) {
                        hari = "KAMIS";
                    } else if (day == 6) {
                        hari = "JUMAT";
                    } else if (day == 7) {
                        hari = "SABTU";
                    }
                    ps.setString(1, hari);
                    rs = ps.executeQuery();

                while(rs.next()){
                    
                    String nama = rs.getString("nm_dokter");
                    String poli = rs.getString("nm_poli");
                    String kddokter = rs.getString("kd_dokter");
                    String kdpoli = rs.getString("kd_poli");
                    String kddokterbpjs = "";
                    String kdpolibpjs = "";
                    dataList.add(new DataObject(nama, poli,kddokter,kdpoli,kddokterbpjs,kdpolibpjs));
             }
                
                DataObject[] dataArray = dataList.toArray(new DataObject[0]);
                for (DataObject data : dataArray) {
                    //Menampilkan Panel 1   
                    panel1 = new PanelBiasa();
                    panel1.setWarnaAtas(new Color(144, 238, 144));
                    panel1.setWarnaBawah(new Color(25, 25, 112));
                    panel1.setPreferredSize(new Dimension(400, 110));
                    
                    panelBiasa3.add(panel1);

                    panel2 = new PanelBiasa();
                    panel2.setWarnaAtas(new Color(144, 238, 144));
                    panel2.setPreferredSize(new Dimension(400, 50));
                    panel1.add(panel2);

                    //Menampilkan Nama Dokter
                    JLabel labelNama = new JLabel();
                    labelNama.setText(data.getField2());
                    labelNama.setFont(new Font("Arial",Font.BOLD,20));
                    labelNama.setForeground(new Color(25, 25, 112));
                    panel2.add(labelNama);
                    
                    //Menampilkan Nama Poli
                    JLabel labelpoli = new JLabel();
                    labelpoli.setText(data.getField1());
                    labelpoli.setFont(new Font("Calibri",Font.BOLD,20));
                    labelpoli.setForeground(Color.WHITE);
                    panel1.add(labelpoli);
                    
                    
                    panel1.addMouseListener(new java.awt.event.MouseAdapter() {
                        public void mouseClicked(java.awt.event.MouseEvent evt) {
                            KdDokter.setText(data.getField3());
                            namadokter.setText(data.getField1());
                            kdpoli.setText(data.getField4());
                            namapoli.setText(data.getField2());
                            KdDokterbpjs.setText(data.getkddokterbpjs());
                            kdpolibpjs.setText(data.getkdpolibpjs());
                            dispose();
                            
                            //simpandaftar(kdpoli.getText(),KdDokter.getText());
                        }
                    });
                    dataList.clear();
            }
         } catch (Exception ex) {
             ex.printStackTrace();
             
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

        TanggalPeriksa = new widget.Tanggal();
        KdDokter = new javax.swing.JTextField();
        kdpoli = new javax.swing.JTextField();
        kdCaraBayar = new javax.swing.JTextField();
        NoReg = new javax.swing.JTextField();
        NoRawat = new javax.swing.JTextField();
        Biaya = new javax.swing.JTextField();
        TAlmt = new javax.swing.JTextField();
        THbngn = new javax.swing.JTextField();
        NoTelpPasien = new javax.swing.JTextField();
        TPngJwb = new javax.swing.JTextField();
        TAlmt5 = new javax.swing.JTextField();
        lblNoRM = new widget.TextBox();
        namadokter = new javax.swing.JTextField();
        namapoli = new javax.swing.JTextField();
        KdDokterbpjs = new javax.swing.JTextField();
        kdpolibpjs = new javax.swing.JTextField();
        scrollPane1 = new widget.ScrollPane();
        panelBiasa1 = new widget.PanelBiasa();
        panelBiasa3 = new widget.PanelBiasa();
        panelBiasa4 = new widget.PanelBiasa();
        label2 = new widget.Label();
        label4 = new widget.Label();
        panelBiasa2 = new widget.PanelBiasa();
        label3 = new widget.Label();
        label1 = new widget.Label();

        TanggalPeriksa.setDisplayFormat("dd-MM-yyyy");
        TanggalPeriksa.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        TanggalPeriksa.setPreferredSize(new java.awt.Dimension(140, 40));

        KdDokter.setText("jTextField1");

        kdpoli.setText("jTextField1");

        kdCaraBayar.setText("jTextField1");

        NoReg.setText("jTextField1");

        NoRawat.setText("jTextField1");

        Biaya.setText("jTextField1");

        TAlmt.setText("jTextField1");

        THbngn.setText("jTextField1");

        NoTelpPasien.setText("jTextField1");

        TPngJwb.setText("jTextField1");

        TAlmt5.setText("jTextField1");

        lblNoRM.setEditable(false);
        lblNoRM.setFont(new java.awt.Font("Tahoma", 0, 18)); // NOI18N
        lblNoRM.setPreferredSize(new java.awt.Dimension(64, 40));

        namadokter.setText("jTextField1");

        namapoli.setText("jTextField1");

        KdDokterbpjs.setText("jTextField1");

        kdpolibpjs.setText("jTextField1");

        setDefaultCloseOperation(javax.swing.WindowConstants.DISPOSE_ON_CLOSE);
        setUndecorated(true);

        scrollPane1.setHorizontalScrollBarPolicy(javax.swing.ScrollPaneConstants.HORIZONTAL_SCROLLBAR_ALWAYS);
        scrollPane1.setVerticalScrollBarPolicy(javax.swing.ScrollPaneConstants.VERTICAL_SCROLLBAR_ALWAYS);
        scrollPane1.setAutoscrolls(true);
        scrollPane1.setPreferredSize(new java.awt.Dimension(1000, 2000));
        scrollPane1.addMouseListener(new java.awt.event.MouseAdapter() {
            public void mousePressed(java.awt.event.MouseEvent evt) {
                scrollPane1MousePressed(evt);
            }
        });

        panelBiasa1.setPreferredSize(new java.awt.Dimension(1000, 1500));
        panelBiasa1.addMouseListener(new java.awt.event.MouseAdapter() {
            public void mouseClicked(java.awt.event.MouseEvent evt) {
                panelBiasa1MouseClicked(evt);
            }
        });
        panelBiasa1.setLayout(new java.awt.BorderLayout());
        panelBiasa1.add(panelBiasa3, java.awt.BorderLayout.CENTER);

        panelBiasa4.setPreferredSize(new java.awt.Dimension(70, 12));

        label2.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        label2.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/up-arrow.png"))); // NOI18N
        label2.setPreferredSize(new java.awt.Dimension(60, 70));
        label2.addMouseListener(new java.awt.event.MouseAdapter() {
            public void mouseClicked(java.awt.event.MouseEvent evt) {
                label2MouseClicked(evt);
            }
        });

        label4.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        label4.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/down-arrow(1).png"))); // NOI18N
        label4.setToolTipText("");
        label4.addMouseListener(new java.awt.event.MouseAdapter() {
            public void mouseClicked(java.awt.event.MouseEvent evt) {
                label4MouseClicked(evt);
            }
        });

        javax.swing.GroupLayout panelBiasa4Layout = new javax.swing.GroupLayout(panelBiasa4);
        panelBiasa4.setLayout(panelBiasa4Layout);
        panelBiasa4Layout.setHorizontalGroup(
            panelBiasa4Layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
            .addGroup(panelBiasa4Layout.createSequentialGroup()
                .addGroup(panelBiasa4Layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING, false)
                    .addComponent(label2, javax.swing.GroupLayout.DEFAULT_SIZE, javax.swing.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE)
                    .addComponent(label4, javax.swing.GroupLayout.DEFAULT_SIZE, 73, Short.MAX_VALUE))
                .addContainerGap(javax.swing.GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE))
        );
        panelBiasa4Layout.setVerticalGroup(
            panelBiasa4Layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
            .addGroup(panelBiasa4Layout.createSequentialGroup()
                .addComponent(label4, javax.swing.GroupLayout.PREFERRED_SIZE, 204, javax.swing.GroupLayout.PREFERRED_SIZE)
                .addGap(110, 110, 110)
                .addComponent(label2, javax.swing.GroupLayout.PREFERRED_SIZE, 194, javax.swing.GroupLayout.PREFERRED_SIZE)
                .addContainerGap())
        );

        panelBiasa1.add(panelBiasa4, java.awt.BorderLayout.LINE_END);

        scrollPane1.setViewportView(panelBiasa1);

        getContentPane().add(scrollPane1, java.awt.BorderLayout.CENTER);

        panelBiasa2.setBorder(null);
        panelBiasa2.setPreferredSize(new java.awt.Dimension(459, 80));
        panelBiasa2.setLayout(new java.awt.BorderLayout());

        label3.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        label3.setIcon(new javax.swing.ImageIcon(getClass().getResource("/picture/101.png"))); // NOI18N
        label3.setPreferredSize(new java.awt.Dimension(70, 70));
        label3.addMouseListener(new java.awt.event.MouseAdapter() {
            public void mouseClicked(java.awt.event.MouseEvent evt) {
                label3MouseClicked(evt);
            }
        });
        panelBiasa2.add(label3, java.awt.BorderLayout.LINE_START);

        label1.setForeground(new java.awt.Color(144, 238, 144));
        label1.setHorizontalAlignment(javax.swing.SwingConstants.CENTER);
        label1.setText("Daftar Poliklinik");
        label1.setFont(new java.awt.Font("Tahoma", 1, 48)); // NOI18N
        panelBiasa2.add(label1, java.awt.BorderLayout.CENTER);

        getContentPane().add(panelBiasa2, java.awt.BorderLayout.PAGE_START);

        pack();
    }// </editor-fold>//GEN-END:initComponents

    private void scrollPane1MousePressed(java.awt.event.MouseEvent evt) {//GEN-FIRST:event_scrollPane1MousePressed
        // TODO add your handling code here:
    }//GEN-LAST:event_scrollPane1MousePressed

    private void panelBiasa1MouseClicked(java.awt.event.MouseEvent evt) {//GEN-FIRST:event_panelBiasa1MouseClicked
        // TODO add your handling code here:
        int y = evt.getY();
         JViewport viewport = scrollPane1.getViewport();
         
         Rectangle viewRect = viewport.getViewRect();
         
         int relativeY = y + viewRect.y;
         
         Dimension viewSize = viewport.getViewSize();
         
         Dimension extentSize = viewport.getExtentSize();
         int maxScroll = viewSize.height - extentSize.height;
         int newScroll = Math.min(relativeY * maxScroll / scrollPane1.getHeight(), maxScroll);
         
         scrollPane1.getVerticalScrollBar().setValue(newScroll);
         
         if(evt.getClickCount()==2){
             JScrollBar verticalScrollBar = scrollPane1.getVerticalScrollBar();
             verticalScrollBar.setValue(verticalScrollBar.getMinimum());
        }
        
         
         
    }//GEN-LAST:event_panelBiasa1MouseClicked

    private void label3MouseClicked(java.awt.event.MouseEvent evt) {//GEN-FIRST:event_label3MouseClicked
        // TODO add your handling code here:
        this.dispose();
    }//GEN-LAST:event_label3MouseClicked

    private void label4MouseClicked(java.awt.event.MouseEvent evt) {//GEN-FIRST:event_label4MouseClicked
        // TODO add your handling code here:
        panelBiasa1MouseClicked(evt);
        
    }//GEN-LAST:event_label4MouseClicked

    private void label2MouseClicked(java.awt.event.MouseEvent evt) {//GEN-FIRST:event_label2MouseClicked
        // TODO add your handling code here:
        panelBiasa1MouseClicked(evt);
    }//GEN-LAST:event_label2MouseClicked

    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
        /* Set the Nimbus look and feel */
        //<editor-fold defaultstate="collapsed" desc=" Look and feel setting code (optional) ">
        /* If Nimbus (introduced in Java SE 6) is not available, stay with the default look and feel.
         * For details see http://download.oracle.com/javase/tutorial/uiswing/lookandfeel/plaf.html 
         */
        try {
            for (javax.swing.UIManager.LookAndFeelInfo info : javax.swing.UIManager.getInstalledLookAndFeels()) {
                if ("Nimbus".equals(info.getName())) {
                    javax.swing.UIManager.setLookAndFeel(info.getClassName());
                    break;
                }
            }
        } catch (ClassNotFoundException ex) {
            java.util.logging.Logger.getLogger(Pilihanpoli.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        } catch (InstantiationException ex) {
            java.util.logging.Logger.getLogger(Pilihanpoli.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        } catch (IllegalAccessException ex) {
            java.util.logging.Logger.getLogger(Pilihanpoli.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        } catch (javax.swing.UnsupportedLookAndFeelException ex) {
            java.util.logging.Logger.getLogger(Pilihanpoli.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        }
        //</editor-fold>
        //</editor-fold>

        /* Create and display the dialog */
        java.awt.EventQueue.invokeLater(new Runnable() {
            public void run() {
                Pilihanpoli dialog = new Pilihanpoli(new javax.swing.JFrame(), true);
                dialog.addWindowListener(new java.awt.event.WindowAdapter() {
                    @Override
                    public void windowClosing(java.awt.event.WindowEvent e) {
                        System.exit(0);
                    }
                });
          
                dialog.setVisible(true);
                
                
            }
        });
    }

    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JTextField Biaya;
    private javax.swing.JTextField KdDokter;
    private javax.swing.JTextField KdDokterbpjs;
    private javax.swing.JTextField NoRawat;
    private javax.swing.JTextField NoReg;
    private javax.swing.JTextField NoTelpPasien;
    private javax.swing.JTextField TAlmt;
    private javax.swing.JTextField TAlmt5;
    private javax.swing.JTextField THbngn;
    private javax.swing.JTextField TPngJwb;
    private widget.Tanggal TanggalPeriksa;
    private javax.swing.JTextField kdCaraBayar;
    private javax.swing.JTextField kdpoli;
    private javax.swing.JTextField kdpolibpjs;
    private widget.Label label1;
    private widget.Label label2;
    private widget.Label label3;
    private widget.Label label4;
    private widget.TextBox lblNoRM;
    private javax.swing.JTextField namadokter;
    private javax.swing.JTextField namapoli;
    private widget.PanelBiasa panelBiasa1;
    private widget.PanelBiasa panelBiasa2;
    private widget.PanelBiasa panelBiasa3;
    private widget.PanelBiasa panelBiasa4;
    private widget.ScrollPane scrollPane1;
    // End of variables declaration//GEN-END:variables
private void jPanel1MouseClicked(java.awt.event.MouseEvent evt) {
    // Kode untuk menangani klik pada JPanel
   

}

private void isNumber() {
        if (BASENOREG.equals("booking")) {
            switch (URUTNOREG) {
                case "poli":
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kdpoli.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kdpoli.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
                case "dokter":
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + KdDokter.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + KdDokter.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + KdDokter.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + KdDokter.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
                case "dokter + poli":
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + KdDokter.getText() + "' and kd_poli='" + kdpoli.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + KdDokter.getText() + "' and kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_dokter='" + KdDokter.getText() + "' and kd_poli='" + kdpoli.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + KdDokter.getText() + "' and kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
                default:
                    if (Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kdpoli.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")
                            >= Sequel.cariInteger("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'")) {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from booking_registrasi where kd_poli='" + kdpoli.getText() + "' and tanggal_periksa='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    } else {
                        Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    }
                    break;
            }
        } else {
            switch (URUTNOREG) {
                case "poli":
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
                case "dokter":
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + KdDokter.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
                case "dokter + poli":
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + KdDokter.getText() + "' and kd_poli='" + kdpoli.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
                default:
                    Valid.autoNomer3("select ifnull(MAX(CONVERT(no_reg,signed)),0) from reg_periksa where kd_dokter='" + KdDokter.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "'", "", 3, NoReg);
                    break;
            }
        }

//        //Valid.autoNomer3("select ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0) from reg_periksa where tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "' ", Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()).replaceAll("-", "/") + "/", 6, NoRawat);
//        Valid.autoNomer3("select ifnull(MAX(CONVERT(RIGHT(reg_periksa.no_rawat,4),signed)),0) from reg_periksa where reg_periksa.tgl_registrasi='"+Valid.SetTgl(TanggalPeriksa.getSelectedItem()+"")+"' ",dateformat.format(TanggalPeriksa.getDate())+"",4,NoRawat);
        Valid.autoNomer3("select ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0) from reg_periksa where tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()) + "' ", Valid.SetTgl(TanggalPeriksa.getSelectedItem().toString()).replaceAll("-", "/") + "/", 6, NoRawat);
    }

private void simpandaftar(String getkdpoli, String getkddokter){
    if (Sequel.cariInteger("select count(no_rkm_medis) from reg_periksa where kd_pj='A02' and no_rkm_medis='" + lblNoRM.getText() + "' and kd_poli='" + kdpoli.getText() + "' and kd_dokter='" + KdDokter.getText() + "' and tgl_registrasi='" + Valid.SetTgl(TanggalPeriksa.getSelectedItem() + "") + "' ") > 0) {
            JOptionPane.showMessageDialog(rootPane, "Maaf, anda sudah terdaftar pada hari ini dengan dokter yang sama ");
            this.dispose();
        } else {
        
            isNumber();
            String biayareg = Sequel.cariIsi("SELECT registrasilama FROM poliklinik WHERE kd_poli='" + kdpoli.getText() + "'");
            UpdateUmur();
            isCekPasien();
            if (Sequel.menyimpantf2("reg_periksa", "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?", "No.Rawat", 19,
                    new String[]{NoReg.getText(), NoRawat.getText(), Valid.SetTgl(TanggalPeriksa.getSelectedItem() + ""), Sequel.cariIsi("select current_time()"),
                        KdDokter.getText(), lblNoRM.getText(), kdpoli.getText(), TPngJwb.getText(), TAlmt.getText(), THbngn.getText(), biayareg, "Belum",
                        "Lama", "Ralan", kdCaraBayar.getText(), umur, sttsumur, "Belum Bayar", status}) == true) {

                JOptionPane.showMessageDialog(null, "Anda Berhasil Daftar ");

                 MnCetakRegisterActionPerformed(NoRawat.getText());
      
               
                NoReg.setText("");
                //TNoRw.setText("");
                NoRawat.setText("");
                lblNoRM.setText("");
                TPngJwb.setText("");
                TAlmt.setText("");
                THbngn.setText("");
                umur = "";
                sttsumur = "";

                kdpoli.setText("");
   
                KdDokter.setText("");
                
                this.dispose();
            }

        }
        
}

private void MnCetakRegisterActionPerformed(String norawat) {
          
      
            this.setCursor(Cursor.getPredefinedCursor(Cursor.WAIT_CURSOR));
            Map<String, Object> param = new HashMap<>();
            param.put("namars",akses.getnamars());
            param.put("alamatrs",akses.getalamatrs());
            param.put("kotars",akses.getkabupatenrs());
            param.put("propinsirs",akses.getpropinsirs());
            param.put("kontakrs",akses.getkontakrs());
            param.put("emailrs",akses.getemailrs());
            param.put("logo",Sequel.cariGambar("select setting.logo from setting"));
            Valid.MyReportqry("rptBuktiRegister.jasper","report","::[ Bukti Register ]::",
                   "select reg_periksa.no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.jam_reg,pasien.no_tlp,"+
                   "reg_periksa.kd_dokter,dokter.nm_dokter,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.jk,pasien.umur as umur,poliklinik.nm_poli,"+
                   "reg_periksa.p_jawab,reg_periksa.almt_pj,reg_periksa.hubunganpj,reg_periksa.biaya_reg,reg_periksa.stts_daftar,penjab.png_jawab "+
                   "from reg_periksa inner join dokter inner join pasien inner join poliklinik inner join penjab "+
                   "on reg_periksa.kd_dokter=dokter.kd_dokter and reg_periksa.no_rkm_medis=pasien.no_rkm_medis "+
                   "and reg_periksa.kd_pj=penjab.kd_pj and reg_periksa.kd_poli=poliklinik.kd_poli where reg_periksa.no_rawat='"+ norawat+"' ",param);
            this.setCursor(Cursor.getDefaultCursor());
        
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
          dispose();

    }

public void setRM(String norm){
        kdCaraBayar.setText(Sequel.cariIsi("select kd_pj from penjab where png_jawab like '%Umum%'"));
        //caraBayar.setText(Sequel.cariIsi("select png_jawab from penjab where kd_pj='A02'"));
        lblNoRM.setText(norm);
        
    }

 private void UpdateUmur() {
        Sequel.mengedit("pasien", "no_rkm_medis=?", "umur=CONCAT(CONCAT(CONCAT(TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()), ' Th '),CONCAT(TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12), ' Bl ')),CONCAT(TIMESTAMPDIFF(DAY, DATE_ADD(DATE_ADD(tgl_lahir,INTERVAL TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) YEAR), INTERVAL TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) - ((TIMESTAMPDIFF(MONTH, tgl_lahir, CURDATE()) div 12) * 12) MONTH), CURDATE()), ' Hr'))", 1, new String[]{lblNoRM.getText()});
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
        if (Sequel.cariInteger("select count(no_rkm_medis) from reg_periksa where no_rkm_medis=? and kd_poli=?", lblNoRM.getText(), kdpoli.getText()) > 0) {
            status = "Lama";
        }

    }
 
 public JTextField getDataDOkter(){
     return KdDokter;
 }
 public JTextField getDataPoli(){
     return kdpoli;
 }
 
 public JTextField getNamaDOkter(){
     return namadokter;
 }
 public JTextField getNamaPoli(){
     return namapoli;
 }
 
 public JTextField getKdDOkterBPJS(){
     return KdDokterbpjs;
 }
 
 public JTextField getKdPoliBpjs(){
     return kdpolibpjs;
 }
 
 

    
    
}





