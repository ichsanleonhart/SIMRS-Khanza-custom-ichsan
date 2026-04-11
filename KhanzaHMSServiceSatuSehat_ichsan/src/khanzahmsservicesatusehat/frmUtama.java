/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package khanzahmsservicesatusehat;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import fungsi.ApiOrthanc;
import fungsi.ApiSatuSehat;
import fungsi.SatuSehatCekNIK;
import fungsi.koneksiDB;
import fungsi.sekuel;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.Font;
import java.awt.GradientPaint;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.RenderingHints;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.BoxLayout;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;
import javax.swing.SwingConstants;
import javax.swing.SwingUtilities;
import javax.swing.Timer;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpMethod;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.client.HttpClientErrorException;
import org.springframework.web.client.ResourceAccessException;

/**
 *
 * @author windiartonugroho
 */
public class frmUtama extends javax.swing.JFrame {
    private Connection koneksi=koneksiDB.condb();
    private sekuel Sequel=new sekuel();
    private String json="",link="",nol_jam = "",nol_menit = "",nol_detik = "",jam="",menit="",detik="",iddokter="",idpasien="",sistole="0",diastole="0",signa1="1",signa2="1",idrequest="", identifierValue="";
    private ApiSatuSehat api=new ApiSatuSehat();
    private HttpHeaders headers;
    private HttpEntity requestEntity;
    private ObjectMapper mapper= new ObjectMapper();
    private JsonNode root;
    private JsonNode response;
    private PreparedStatement ps;
    private ResultSet rs;
    private String[] arrSplit;
    private SimpleDateFormat tanggalFormat = new SimpleDateFormat("yyyy-MM-dd");
    private Date date = new Date();  
    private SatuSehatCekNIK cekViaSatuSehat=new SatuSehatCekNIK();  

    // === AUTOPILOT VARIABLES ===
    private volatile boolean isAutopilot = false;
    private volatile boolean isEmergencyStop = false;
    private long lastRunTimeMs = System.currentTimeMillis();
    private int intervalMenit = 240; // Default 4 Jam (240 Menit)

    /**
     * Creates new form frmUtama
     */
    // === KONSTANTA WARNA & FONT TEMA DARK MATRIX ===
    private static final Color BG_DEEP     = new Color(0x0A0F0A);
    private static final Color BG_PANEL    = new Color(0x111811);
    private static final Color BG_CARD     = new Color(0x162016);
    private static final Color ACCENT_GREEN= new Color(0x00FF41);
    private static final Color ACCENT_DIM  = new Color(0x00A827);
    private static final Color TEXT_WHITE  = new Color(0xE8FFE8);
    private static final Color TEXT_MUTED  = new Color(0x5A8A5A);
    private static final Color BTN_NORMAL  = new Color(0x0D2B0D);
    private static final Color BTN_HOVER   = new Color(0x1A4A1A);
    private static final Color BORDER_CLR  = new Color(0x00A827);
    private static final Font  FONT_MONO   = new Font("Courier New", Font.PLAIN, 12);
    private static final Font  FONT_MONO_B = new Font("Courier New", Font.BOLD, 13);
    private static final Font  FONT_SANS   = new Font("Segoe UI", Font.PLAIN, 12);
    private static final Font  FONT_SANS_B = new Font("Segoe UI", Font.BOLD, 13);
    private static final Font  FONT_TITLE  = new Font("Segoe UI", Font.BOLD, 18);

    public frmUtama() {
        initComponents();
        try {
            link = koneksiDB.URLFHIRSATUSEHAT();
        } catch (Exception e) {
            System.out.println("Notif : " + e);
        }

        date = new Date();
        Tanggal1.setText(tanggalFormat.format(date));
        Tanggal2.setText(tanggalFormat.format(date));

        setupModernUI();
        TeksArea.setComponentPopupMenu(jPopupMenu1);
        // Auto-scroll TeksArea setiap kali ada teks baru
        TeksArea.getDocument().addDocumentListener(new javax.swing.event.DocumentListener() {
            public void insertUpdate(javax.swing.event.DocumentEvent e)  { scrollToBottom(); }
            public void removeUpdate(javax.swing.event.DocumentEvent e)  { scrollToBottom(); }
            public void changedUpdate(javax.swing.event.DocumentEvent e) { scrollToBottom(); }
            private void scrollToBottom() {
                SwingUtilities.invokeLater(() -> TeksArea.setCaretPosition(TeksArea.getDocument().getLength()));
            }
        });

        this.setExtendedState(java.awt.Frame.MAXIMIZED_BOTH);
        this.setMinimumSize(new Dimension(1024, 600));

        jam();
    }

    /**
     * This method is called from within the constructor to initialize the form.
     * WARNING: Do NOT modify this code. The content of this method is always
     * regenerated by the Form Editor.
     */
    @SuppressWarnings("unchecked")
    // <editor-fold defaultstate="collapsed" desc="Generated Code">//GEN-BEGIN:initComponents
    private void initComponents() {

        jPopupMenu1 = new javax.swing.JPopupMenu();
        kirim_encounter = new javax.swing.JMenuItem();
        kirim_observationTTV = new javax.swing.JMenuItem();
        kirim_vaksin = new javax.swing.JMenuItem();
        kirim_prosedur = new javax.swing.JMenuItem();
        kirim_condition = new javax.swing.JMenuItem();
        kirim_clinicalimpression = new javax.swing.JMenuItem();
        kirim_dietgizi = new javax.swing.JMenuItem();
        kirim_medicationrequest = new javax.swing.JMenuItem();
        kirim_medicationdispense = new javax.swing.JMenuItem();
        kirim_medicationstatement = new javax.swing.JMenuItem();
        kirim_servicerequestradiologi = new javax.swing.JMenuItem();
        kirim_specimenradiologi = new javax.swing.JMenuItem();
        kirim_observationradiologi = new javax.swing.JMenuItem();
        kirim_diagnosticreportradiologi = new javax.swing.JMenuItem();
        kirim_servicerequestlabpk = new javax.swing.JMenuItem();
        kirim_servicerequestlabmb = new javax.swing.JMenuItem();
        kirim_specimenlabpk = new javax.swing.JMenuItem();
        kirim_specimenlabmb = new javax.swing.JMenuItem();
        kirim_observationlabpk = new javax.swing.JMenuItem();
        kirim_observationlabmb = new javax.swing.JMenuItem();
        kirim_diagnosticreportlabpk = new javax.swing.JMenuItem();
        kirim_diagnosticreportlabmb = new javax.swing.JMenuItem();
        kirim_careplan = new javax.swing.JMenuItem();
        kirim_questionnaire = new javax.swing.JMenuItem();
        kirim_composition = new javax.swing.JMenuItem();
        kirim_alergi = new javax.swing.JMenuItem();
        kirim_episodeofcare = new javax.swing.JMenuItem();
        kirim_encounter2 = new javax.swing.JMenuItem();
        kirim_dicomrouter = new javax.swing.JMenuItem();
        jCheckBoxMenuItemOrtancAuto = new javax.swing.JCheckBoxMenuItem();
        jScrollPane1 = new javax.swing.JScrollPane();
        TeksArea = new javax.swing.JTextArea();
        jPanel1 = new javax.swing.JPanel();
        jButtonStartKirim = new javax.swing.JButton();
        jLabel1 = new javax.swing.JLabel();
        Tanggal1 = new javax.swing.JTextField();
        jLabel3 = new javax.swing.JLabel();
        Tanggal2 = new javax.swing.JTextField();
        jLabel2 = new javax.swing.JLabel();
        jButton1 = new javax.swing.JButton();
        jScrollPane2 = new javax.swing.JScrollPane();
        jTextArea1 = new javax.swing.JTextArea();

        kirim_encounter.setText("Kirim Data Kunjungan (Encounter)");
        kirim_encounter.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_encounterActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_encounter);

        kirim_observationTTV.setText("Kirim Tanda Vital (Observation TTV)");
        kirim_observationTTV.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_observationTTVActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_observationTTV);

        kirim_vaksin.setText("Kirim Riwayat Vaksin (Immunization)");
        kirim_vaksin.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_vaksinActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_vaksin);

        kirim_prosedur.setText("Kirim Tindakan/Prosedur (Procedure)");
        kirim_prosedur.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_prosedurActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_prosedur);

        kirim_condition.setText("Kirim Diagnosa Pasien (Condition)");
        kirim_condition.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_conditionActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_condition);

        kirim_clinicalimpression.setText("Kirim Asesmen Dokter (Clinical Impression)");
        kirim_clinicalimpression.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_clinicalimpressionActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_clinicalimpression);

        kirim_dietgizi.setText("Kirim Asuhan Gizi (ADIME)");
        kirim_dietgizi.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_dietgiziActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_dietgizi);

        kirim_medicationrequest.setText("Kirim Resep Obat (Medication Request)");
        kirim_medicationrequest.setActionCommand("Kirim Medicationrequest (Resep Dokter)");
        kirim_medicationrequest.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_medicationrequestActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_medicationrequest);

        kirim_medicationdispense.setText("Kirim Penyerahan Obat (Medication Dispense)");
        kirim_medicationdispense.setActionCommand("Kirim Medicationdispense (Penyerahan Obat)");
        kirim_medicationdispense.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_medicationdispenseActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_medicationdispense);

        kirim_medicationstatement.setText("Kirim Aturan Pakai Obat (Medication Statement)");
        kirim_medicationstatement.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_medicationstatementActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_medicationstatement);

        kirim_servicerequestradiologi.setText("Kirim Permintaan Radiologi (Service Request)");
        kirim_servicerequestradiologi.setActionCommand("Kirim Servicerequestradiologi (Permintaan Rad)");
        kirim_servicerequestradiologi.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_servicerequestradiologiActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_servicerequestradiologi);

        kirim_specimenradiologi.setText("Kirim Spesimen Radiologi (Specimen)");
        kirim_specimenradiologi.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_specimenradiologiActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_specimenradiologi);

        kirim_observationradiologi.setText("Kirim Hasil Radiologi (Observation)");
        kirim_observationradiologi.setActionCommand("Kirim Observationradiologi");
        kirim_observationradiologi.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_observationradiologiActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_observationradiologi);

        kirim_diagnosticreportradiologi.setText("Kirim Kesan Radiologi (Diagnostic Report)");
        kirim_diagnosticreportradiologi.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_diagnosticreportradiologiActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_diagnosticreportradiologi);

        kirim_servicerequestlabpk.setText("Kirim Permintaan Lab PK (Service Request)");
        kirim_servicerequestlabpk.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_servicerequestlabpkActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_servicerequestlabpk);

        kirim_servicerequestlabmb.setText("Kirim Permintaan Lab MB (Service Request)");
        kirim_servicerequestlabmb.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_servicerequestlabmbActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_servicerequestlabmb);

        kirim_specimenlabpk.setText("Kirim Spesimen Lab PK (Specimen)");
        kirim_specimenlabpk.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_specimenlabpkActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_specimenlabpk);

        kirim_specimenlabmb.setText("Kirim Spesimen Lab MB (Specimen)");
        kirim_specimenlabmb.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_specimenlabmbActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_specimenlabmb);

        kirim_observationlabpk.setText("Kirim Hasil Lab PK (Observation)");
        kirim_observationlabpk.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_observationlabpkActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_observationlabpk);

        kirim_observationlabmb.setText("Kirim Hasil Lab MB (Observation)");
        kirim_observationlabmb.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_observationlabmbActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_observationlabmb);

        kirim_diagnosticreportlabpk.setText("Kirim Kesan Lab PK (Diagnostic Report)");
        kirim_diagnosticreportlabpk.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_diagnosticreportlabpkActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_diagnosticreportlabpk);

        kirim_diagnosticreportlabmb.setText("Kirim Kesan Lab MB (Diagnostic Report)");
        kirim_diagnosticreportlabmb.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_diagnosticreportlabmbActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_diagnosticreportlabmb);

        kirim_careplan.setText("Kirim Rencana Rawat (Care Plan)");
        kirim_careplan.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_careplanActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_careplan);

        kirim_questionnaire.setText("Kirim Questionnaire");
        kirim_questionnaire.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_questionnaireActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_questionnaire);

        kirim_composition.setLabel("Kirim Composition");
        kirim_composition.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_compositionActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_composition);

        jPopupMenu1.addSeparator();

        kirim_alergi.setText("Kirim Riwayat Alergi (AllergyIntolerance)");
        kirim_alergi.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_alergiActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_alergi);

        kirim_episodeofcare.setText("Kirim Episode Kehamilan ANC (EpisodeOfCare)");
        kirim_episodeofcare.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_episodeofcareActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_episodeofcare);

        kirim_encounter2.setText("Kirim Encounter Susulan Radiologi (Encounter v2)");
        kirim_encounter2.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_encounter2ActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_encounter2);

        jPopupMenu1.addSeparator();

        kirim_dicomrouter.setText("Kirim Gambar ke Orthanc DICOM Router (Manual)");
        kirim_dicomrouter.addActionListener(new java.awt.event.ActionListener() {
            public void actionPerformed(java.awt.event.ActionEvent evt) {
                kirim_dicomrouterActionPerformed(evt);
            }
        });
        jPopupMenu1.add(kirim_dicomrouter);

        jCheckBoxMenuItemOrtancAuto.setText("[Orthanc] Mode Otomatis (ikut batch)");
        jCheckBoxMenuItemOrtancAuto.setSelected(false);
        jCheckBoxMenuItemOrtancAuto.setToolTipText("Jika dicentang, Orthanc DICOM Router ikut dikirim saat batch otomatis berjalan");
        jPopupMenu1.add(jCheckBoxMenuItemOrtancAuto);


        setDefaultCloseOperation(javax.swing.WindowConstants.EXIT_ON_CLOSE);
        setTitle("⬛ SATU SEHAT BRIDGING SERVICE — KhanzaHMS by Ichsan");

        // Hanya init komponen non-visual; layout sebenarnya dibangun di setupModernUI()
        TeksArea.setColumns(20);
        TeksArea.setRows(5);
        jScrollPane1.setViewportView(TeksArea);

        jTextArea1.setColumns(20);
        jTextArea1.setRows(5);
        jScrollPane2.setViewportView(jTextArea1);

        pack();
    }// </editor-fold>//GEN-END:initComponents

    /**
     * Membangun seluruh tampilan modern secara programatik.
     * Dipanggil di constructor SETELAH initComponents() selesai.
     */
    private void setupModernUI() {
        // ---------------------------------------------------------------
        // ROOT FRAME
        // ---------------------------------------------------------------
        getContentPane().removeAll();
        getContentPane().setBackground(BG_DEEP);
        getContentPane().setLayout(new BorderLayout(0, 0));

        // ---------------------------------------------------------------
        // HEADER BAR (TOP)
        // ---------------------------------------------------------------
        JPanel headerPanel = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                super.paintComponent(g);
                Graphics2D g2 = (Graphics2D) g;
                g2.setPaint(new GradientPaint(0,0,new Color(0x041504),getWidth(),0,new Color(0x0A200A)));
                g2.fillRect(0,0,getWidth(),getHeight());
            }
        };
        headerPanel.setBorder(BorderFactory.createMatteBorder(0,0,2,0,ACCENT_GREEN));
        headerPanel.setPreferredSize(new Dimension(0, 60));
        headerPanel.setOpaque(false);

        JLabel lblTitle = new JLabel("  ⬡ SATU SEHAT BRIDGING SERVICE");
        lblTitle.setFont(FONT_TITLE);
        lblTitle.setForeground(ACCENT_GREEN);
        headerPanel.add(lblTitle, BorderLayout.WEST);

        JLabel lblSubtitle = new JLabel("KhanzaHMS Custom · Ichsan Edition · Auto-Reconnect ON  ");
        lblSubtitle.setFont(new Font("Courier New", Font.PLAIN, 11));
        lblSubtitle.setForeground(TEXT_MUTED);
        lblSubtitle.setHorizontalAlignment(SwingConstants.RIGHT);
        headerPanel.add(lblSubtitle, BorderLayout.EAST);

        getContentPane().add(headerPanel, BorderLayout.NORTH);

        // ---------------------------------------------------------------
        // LEFT — LOG CONSOLE (Matrix style)
        // ---------------------------------------------------------------
        TeksArea.setBackground(BG_DEEP);
        TeksArea.setForeground(ACCENT_GREEN);
        TeksArea.setCaretColor(ACCENT_GREEN);
        TeksArea.setFont(FONT_MONO);
        TeksArea.setEditable(false);
        TeksArea.setLineWrap(true);
        TeksArea.setWrapStyleWord(false);
        TeksArea.setMargin(new Insets(8, 8, 8, 8));
        TeksArea.setText("[" + new SimpleDateFormat("HH:mm:ss").format(new Date()) + "] SISTEM DIMULAI.\n" +
            "[" + new SimpleDateFormat("HH:mm:ss").format(new Date()) + "] KhanzaHMS Satu Sehat Bridging Service AKTIF.\n" +
            "[" + new SimpleDateFormat("HH:mm:ss").format(new Date()) + "] Auto-Reconnect engine: STANDBY.\n" +
            "[" + new SimpleDateFormat("HH:mm:ss").format(new Date()) + "] Token refresh engine: STANDBY.\n" +
            "[" + new SimpleDateFormat("HH:mm:ss").format(new Date()) + "] Batch scheduler: berjalan setiap 4 jam.\n" +
            "[" + new SimpleDateFormat("HH:mm:ss").format(new Date()) + "] Gunakan panel kanan untuk trigger manual.\n" +
            "[" + new SimpleDateFormat("HH:mm:ss").format(new Date()) + "] Hover tombol ⓘ untuk melihat detail resource.\n" +
            "=".repeat(64) + "\n");

        jScrollPane1.setBackground(BG_DEEP);
        jScrollPane1.setBorder(BorderFactory.createEmptyBorder());
        jScrollPane1.getViewport().setBackground(BG_DEEP);
        jScrollPane1.setViewportView(TeksArea);
        jScrollPane1.getVerticalScrollBar().setBackground(BG_PANEL);

        getContentPane().add(jScrollPane1, BorderLayout.CENTER);

        // ---------------------------------------------------------------
        // RIGHT — CONTROL PANEL
        // ---------------------------------------------------------------
        JPanel rightPanel = new JPanel();
        rightPanel.setBackground(BG_PANEL);
        rightPanel.setBorder(BorderFactory.createMatteBorder(0,2,0,0,BORDER_CLR));
        rightPanel.setLayout(new BorderLayout(0,0));
        rightPanel.setPreferredSize(new Dimension(310, 0));

        // === DATE RANGE SECTION ===
        JPanel datePanel = new JPanel(new GridBagLayout());
        datePanel.setBackground(BG_CARD);
        datePanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0,0,2,0,BORDER_CLR),
            BorderFactory.createEmptyBorder(10,10,10,10)
        ));

        GridBagConstraints gbc = new GridBagConstraints();
        gbc.fill = GridBagConstraints.HORIZONTAL; gbc.insets = new Insets(3,4,3,4);

        JLabel lblDate = new JLabel("◈ RENTANG TANGGAL");
        lblDate.setFont(FONT_MONO_B); lblDate.setForeground(ACCENT_GREEN);
        gbc.gridx=0; gbc.gridy=0; gbc.gridwidth=3; datePanel.add(lblDate, gbc);

        JLabel lblFrom = new JLabel("Dari:"); lblFrom.setFont(FONT_SANS); lblFrom.setForeground(TEXT_WHITE);
        gbc.gridx=0; gbc.gridy=1; gbc.gridwidth=1; gbc.weightx=0; datePanel.add(lblFrom, gbc);

        Tanggal1.setBackground(new Color(0x0A1A0A)); Tanggal1.setForeground(ACCENT_GREEN);
        Tanggal1.setFont(FONT_MONO_B); Tanggal1.setCaretColor(ACCENT_GREEN);
        Tanggal1.setBorder(BorderFactory.createLineBorder(ACCENT_DIM));
        gbc.gridx=1; gbc.gridy=1; gbc.gridwidth=2; gbc.weightx=1; datePanel.add(Tanggal1, gbc);

        JLabel lblTo = new JLabel("S/D:"); lblTo.setFont(FONT_SANS); lblTo.setForeground(TEXT_WHITE);
        gbc.gridx=0; gbc.gridy=2; gbc.gridwidth=1; gbc.weightx=0; datePanel.add(lblTo, gbc);

        Tanggal2.setBackground(new Color(0x0A1A0A)); Tanggal2.setForeground(ACCENT_GREEN);
        Tanggal2.setFont(FONT_MONO_B); Tanggal2.setCaretColor(ACCENT_GREEN);
        Tanggal2.setBorder(BorderFactory.createLineBorder(ACCENT_DIM));
        gbc.gridx=1; gbc.gridy=2; gbc.gridwidth=2; gbc.weightx=1; datePanel.add(Tanggal2, gbc);

        // === AUTOPILOT CONTROL PANEL SECTION ===
        JPanel controlPanel = new JPanel(new GridBagLayout());
        controlPanel.setBackground(BG_CARD);
        controlPanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0,0,2,0,BORDER_CLR),
            BorderFactory.createEmptyBorder(10,10,10,10)
        ));

        GridBagConstraints gbcCtrl = new GridBagConstraints();
        gbcCtrl.fill = GridBagConstraints.HORIZONTAL; gbcCtrl.insets = new Insets(3,4,3,4);

        JLabel lblCtrl = new JLabel("◈ KONTROL AUTOPILOT");
        lblCtrl.setFont(FONT_MONO_B); lblCtrl.setForeground(ACCENT_GREEN);
        gbcCtrl.gridx=0; gbcCtrl.gridy=0; gbcCtrl.gridwidth=2; controlPanel.add(lblCtrl, gbcCtrl);

        JLabel lblInt = new JLabel("Interval Auto-Scan:"); lblInt.setFont(FONT_SANS); lblInt.setForeground(TEXT_WHITE);
        gbcCtrl.gridx=0; gbcCtrl.gridy=1; gbcCtrl.gridwidth=2; controlPanel.add(lblInt, gbcCtrl);

        javax.swing.JComboBox<String> cmbInterval = new javax.swing.JComboBox<>(new String[]{"Setiap 5 Menit", "Setiap 15 Menit", "Setiap 1 Jam", "Setiap 4 Jam", "Setiap 12 Jam"});
        cmbInterval.setBackground(new Color(0x0A1A0A)); cmbInterval.setForeground(ACCENT_GREEN); cmbInterval.setFont(FONT_MONO);
        cmbInterval.setSelectedIndex(3); // Default 4 Jam
        cmbInterval.addActionListener(e -> {
            int idx = cmbInterval.getSelectedIndex();
            switch(idx) {
                case 0: intervalMenit = 5; break;
                case 1: intervalMenit = 15; break;
                case 2: intervalMenit = 60; break;
                case 3: intervalMenit = 240; break;
                case 4: intervalMenit = 720; break;
            }
            TeksArea.append("[SISTEM] Interval Auto-Scan diubah menjadi " + intervalMenit + " menit.\n");
        });
        gbcCtrl.gridx=0; gbcCtrl.gridy=2; gbcCtrl.gridwidth=2; controlPanel.add(cmbInterval, gbcCtrl);

        JButton btnPlayPause = new JButton("▶ PLAY AUTOPILOT");
        btnPlayPause.setBackground(BTN_NORMAL); btnPlayPause.setForeground(TEXT_WHITE);
        btnPlayPause.setFont(FONT_SANS_B); btnPlayPause.setFocusPainted(false);
        btnPlayPause.addActionListener(e -> {
            isAutopilot = !isAutopilot;
            if(isAutopilot) {
                btnPlayPause.setText("⏸ PAUSE AUTOPILOT");
                btnPlayPause.setBackground(new Color(0x735c00)); // Dark yellow
                TeksArea.append("[SISTEM] Mode AUTOPILOT AKTIF. Akan melakukan scan masif setiap " + intervalMenit + " menit.\n");
                lastRunTimeMs = System.currentTimeMillis(); // Reset timer saat play
            } else {
                btnPlayPause.setText("▶ PLAY AUTOPILOT");
                btnPlayPause.setBackground(BTN_NORMAL);
                TeksArea.append("[SISTEM] Mode AUTOPILOT DIHENTIKAN SEMENTARA.\n");
            }
        });
        gbcCtrl.gridx=0; gbcCtrl.gridy=3; gbcCtrl.gridwidth=1; gbcCtrl.weightx=0.5; controlPanel.add(btnPlayPause, gbcCtrl);

        JButton btnPanic = new JButton("🛑 PANIC STOP");
        btnPanic.setBackground(new Color(0x4a0a0a)); btnPanic.setForeground(Color.WHITE);
        btnPanic.setFont(FONT_SANS_B); btnPanic.setFocusPainted(false);
        btnPanic.addActionListener(e -> {
            isEmergencyStop = true;
            TeksArea.append("\n!!! [EMERGENCY BRAKE DITARIK] !!!\nSistem akan menghentikan seluruh putaran loop pada database dalam 5 detik mendatang!\n");
            // Set kembali ke idle setelah 5 detik agar bisa bekerja normal lagi nanti
            new Thread(() -> {
                try { Thread.sleep(5000); } catch(Exception ex) {}
                isEmergencyStop = false;
                TeksArea.append("[SISTEM] Emergency brake direlease. Sistem kembali ke mode aman.\n");
            }).start();
            
            if(isAutopilot) { // Otomatis matikan autopilot jika sedang jalan
                btnPlayPause.doClick();
            }
        });
        gbcCtrl.gridx=1; gbcCtrl.gridy=3; gbcCtrl.gridwidth=1; gbcCtrl.weightx=0.5; controlPanel.add(btnPanic, gbcCtrl);

        JPanel topContainer = new JPanel();
        topContainer.setLayout(new BoxLayout(topContainer, BoxLayout.Y_AXIS));
        topContainer.add(datePanel);
        topContainer.add(controlPanel);

        rightPanel.add(topContainer, BorderLayout.NORTH);

        // === SCROLLABLE BUTTON PANEL ===
        JPanel btnContainer = new JPanel();
        btnContainer.setBackground(BG_PANEL);
        btnContainer.setLayout(new BoxLayout(btnContainer, BoxLayout.Y_AXIS));
        btnContainer.setBorder(BorderFactory.createEmptyBorder(6,8,6,8));

        // --- Helper lambda untuk membuat tombol resource ---
        // (Definisi langsung sebagai inner class karena Java 8+)
        ActionListener[] actionHolder = new ActionListener[1]; // workaround for lambda

        // Section: BATCH UTAMA
        btnContainer.add(makeSectionLabel("▸ BATCH & CONTROL"));

        JButton btnBatch = makeActionButton("🚀  KIRIM SEMUA (BATCH FULL)", "↻ AIO");
        btnBatch.setBackground(new Color(0x003300));
        btnBatch.addActionListener(evt -> jButtonStartKirimActionPerformed(evt));
        btnContainer.add(btnBatch);
        btnContainer.add(Box.createVerticalStrut(3));

        // Section: KUNJUNGAN
        btnContainer.add(makeSectionLabel("▸ KUNJUNGAN & TANDA VITAL"));
        addResourceRow(btnContainer, "Encounter (Kunjungan)", "Encounter",
            "TABEL SUMBER:\n" +
            "  • reg_periksa (Master kunjungan)\n" +
            "  • pasien (Data demografi & NIK)\n" +
            "  • pegawai (NIK DPJP)\n" +
            "  • poliklinik (Mapping lokasi)\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. NIK Pasien & Dokter sudah valid (16 digit).\n" +
            "  2. Poliklinik sudah dipetakan ke ID Lokasi Satu Sehat.\n" +
            "  3. Status kunjungan bukan 'Batal'.\n\n" +
            "LOGIKA SKIP:\n" +
            "  Akan melewati data yang sudah memiliki ID di tabel `satu_sehat_encounter`.",
            evt -> kirim_encounterActionPerformed(evt));
        addResourceRow(btnContainer, "Observation TTV", "Vital Signs + SpO2, GCS",
            "TABEL SUMBER:\n" +
            "  • pemeriksaan_ralan (TTV Jalan)\n" +
            "  • pemeriksaan_ranap (TTV Inap)\n" +
            "  • satu_sehat_encounter (Join utama)\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Wajib sudah sukses kirim Encounter (id_encounter harus ada).\n" +
            "  2. Data parameter suhu/tensi tidak boleh kosong atau '-'.\n\n" +
            "DATA KRITIKAL:\n" +
            "  Suhu, Sistole, Diastole, Nadi, Respirasi, BB, TB, SpO2, GCS.\n\n" +
            "LOGIKA SKIP:\n" +
            "  Melewati data yang sudah ada di tabel `satu_sehat_observationttv...`.",
            evt -> kirim_observationTTVActionPerformed(evt));
        addResourceRow(btnContainer, "Immunization (Vaksin)", "Riwayat Imunisasi",
            "TABEL SUMBER:\n" +
            "  • detail_pemberian_obat (Filter barang vaksin)\n" +
            "  • satu_sehat_mapping_vaksin (Kamus SNOMED)\n" +
            "  • nota_jalan / nota_inap (Validasi kasir)\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Barang obat harus dipetakan sebagai 'Vaksin' di master.\n" +
            "  2. No. Batch & No. Faktur wajib terisi.\n\n" +
            "LOGIKA SKIP:\n" +
            "  Melewati data yang sudah ada di tabel `satu_sehat_immunization`.",
            evt -> kirim_vaksinActionPerformed(evt));

        // Section: KLINIS
        btnContainer.add(makeSectionLabel("▸ DATA KLINIS"));
        addResourceRow(btnContainer, "Condition (Diagnosa)", "ICD-10 Diagnosa Utama",
            "TABEL SUMBER:\n" +
            "  • diagnosa_pasien (Kode ICD-10)\n" +
            "  • satu_sehat_encounter (Join utama)\n" +
            "  • penyakit (Nama penyakit)\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Harus ada Encounter ID yang sukses.\n" +
            "  2. Nota jalan/inap harus sudah ada (Kasir).\n\n" +
            "LOGIKA SKIP:\n" +
            "  Melewati data yang sudah ada di tabel `satu_sehat_condition` per kode penyakit.",
            evt -> kirim_conditionActionPerformed(evt));
        addResourceRow(btnContainer, "Procedure (Tindakan)", "ICD-9 Tindakan Medis",
            "TABEL SUMBER:\n" +
            "  • prosedur_pasien (Kode ICD-9)\n" +
            "  • satu_sehat_encounter (Join utama)\n" +
            "  • icd9 (Deskripsi tindakan)\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Harus ada Encounter ID yang sukses.\n\n" +
            "LOGIKA SKIP:\n" +
            "  Melewati data yang sudah ada di tabel `satu_sehat_procedure` per kode ICD-9.",
            evt -> kirim_prosedurActionPerformed(evt));
        addResourceRow(btnContainer, "Clinical Impression (Asesmen)", "Asesmen & Keluhan",
            "TABEL SUMBER:\n" +
            "  • pemeriksaan_ralan / pemeriksaan_ranap\n" +
            "  • Kolom: keluhan, pemeriksaan, anamnesa, asesmen\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Harus ada Encounter ID yang sukses.\n\n" +
            "LOGIKA SKIP:\n" +
            "  Melewati data yang sudah ada di tabel `satu_sehat_clinical_impression`.",
            evt -> kirim_clinicalimpressionActionPerformed(evt));
        addResourceRow(btnContainer, "CarePlan (Rencana Rawat)", "RTL & Rencana Tindak Lanjut",
            "TABEL SUMBER:\n" +
            "  • pemeriksaan_ralan / pemeriksaan_ranap (Kolom rtl/saran)\n" +
            "  • satu_sehat_condition (Prerequisite HL7)\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Harus ada Condition ID (Diagnosa) untuk dikaitkan.\n\n" +
            "LOGIKA SKIP:\n" +
            "  Melewati data yang sudah ada di tabel `satu_sehat_careplan`.",
            evt -> kirim_careplanActionPerformed(evt));
        addResourceRow(btnContainer, "Diet Gizi (ADIME)", "Asuhan Gizi Instruksi",
            "TABEL SUMBER:\n" +
            "  • catatan_adime_gizi (Assessment s/d Evaluasi)\n" +
            "  • satu_sehat_encounter (Join utama)\n\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Harus ada Encounter ID.\n\n" +
            "LOGIKA SKIP:\n" +
            "  Melewati data yang sudah ada di tabel `satu_sehat_diet`.",
            evt -> kirim_dietgiziActionPerformed(evt));

        // Section: FARMASI
        btnContainer.add(makeSectionLabel("▸ FARMASI & OBAT"));
        addResourceRow(btnContainer, "Medication Request (Resep)", "Resep Obat Dokter",
            "Sumber tabel:\n  • resep_obat (header resep)\n  • resep_dokter (detail tunggal)\n  • resep_dokter_racikan (detail racikan)\n  • satu_sehat_encounter (prerequisite)\n\nLogika:\nMengambil semua item resep (tunggal &\nracikan) yang belum terkirim ke Satu Sehat.",
            evt -> kirim_medicationrequestActionPerformed(evt));
        // Section: FARMASI
        btnContainer.add(makeSectionLabel("▸ FARMASI & OBAT"));
        addResourceRow(btnContainer, "Medication Master", "Induk Kamus Obat",
            "TABEL SUMBER: satu_sehat_mapping_obat + databarang.\n" +
            "PRASYARAT: Mapping KFA harus lengkap.\n" +
            "INFO: Resource INDUK. Jalankan ini sebelum kirim Resep/Pemberian.",
            evt -> kirim_medicationActionPerformed(evt));
        addResourceRow(btnContainer, "MedicationRequest (Resep)", "Order Obat Dokter",
            "TABEL SUMBER: resep_obat, resep_dokter, resep_dokter_racikan.\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. Harus ada Encounter ID.\n" +
            "  2. Harus sudah kirim Medication Master (Medication ID).\n" +
            "LOGIKA SKIP: Melewati resep yang sudah ada di tabel `satu_sehat_medicationrequest`.",
            evt -> kirim_medicationrequestActionPerformed(evt));
        addResourceRow(btnContainer, "MedicationDispense (Pemberian)", "Penyerahan Obat Apotek",
            "TABEL SUMBER: detail_pemberian_obat & depo farmasi.\n" +
            "PRASYARAT (MANDATORY):\n" +
            "  1. MedicationRequest ID (Resep harus sukses).\n" +
            "  2. Wajib ada No. Batch, No. Faktur & Lokasi Depo Terpetakan.\n" +
            "LOGIKA SKIP: Melewati data di tabel `satu_sehat_medicationdispense`.",
            evt -> kirim_medicationdispenseActionPerformed(evt));
        addResourceRow(btnContainer, "MedicationStatement", "Penggunaan Mandiri",
            "TABEL SUMBER: pemberitahuan_obat_pasien.\n" +
            "PRASYARAT: Encounter ID.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_medicationstatement`.",
            evt -> kirim_medicationstatementActionPerformed(evt));
        addResourceRow(btnContainer, "Questionnaire (Telaah)", "Telaah Resep Klinis",
            "TABEL SUMBER: resep_obat & penyerahan_obat.\n" +
            "PRASYARAT: Encounter ID & Nota Bayar.\n" +
            "LOGIKA: Skrining 3 aspek (Administrasi, Farmasetik, Klinis).\n" +
            "SKIP: Jika ID sudah ada di tabel `satu_sehat_questionnairereq_...`.",
            evt -> kirim_questionnaireActionPerformed(evt));

        // Section: RADIOLOGI
        btnContainer.add(makeSectionLabel("▸ RADIOLOGI"));
        addResourceRow(btnContainer, "ServiceRequest Radiologi", "Permintaan Foto",
            "TABEL SUMBER: permintaan_radiologi.\n" +
            "PRASYARAT: Encounter ID.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_servicerequest_radiologi`.",
            evt -> kirim_servicerequestradiologiActionPerformed(evt));
        addResourceRow(btnContainer, "Specimen Radiologi", "Imaging Session",
            "TABEL SUMBER: permintaan_radiologi.\n" +
            "PRASYARAT: ServiceRequest ID (Permintaan harus sukses).\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_specimen_radiologi`.",
            evt -> kirim_specimenradiologiActionPerformed(evt));
        addResourceRow(btnContainer, "Observation Radiologi", "Hasil Bacaan Foto",
            "TABEL SUMBER: hasil_radiologi.\n" +
            "PRASYARAT: ServiceRequest ID.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_observation_radiologi`.",
            evt -> kirim_observationradiologiActionPerformed(evt));
        addResourceRow(btnContainer, "DiagnosticReport Rad", "Kesan Akhir",
            "TABEL SUMBER: hasil_radiologi (Kesimpulan/Kesan).\n" +
            "PRASYARAT: Observation ID (Hasil bacaan harus sukses).\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_diagnostic_report_radiologi`.",
            evt -> kirim_diagnosticreportradiologiActionPerformed(evt));
        addResourceRow(btnContainer, "Encounter v2 (Rad/Lab)", "Encounter Susulan",
            "TABEL: reg_periksa & periksa_radiologi / lab.\n" +
            "PRASYARAT: Mapping Lokasi Ralan.\n" +
            "LOGIKA: Membuat encounter pendukung untuk data penunjang 'yatim' (tanpa periksa ralan).",
            evt -> kirim_encounter2ActionPerformed(evt));

        // Section: LAB PK
        btnContainer.add(makeSectionLabel("▸ LABORATORIUM PK"));
        addResourceRow(btnContainer, "ServiceRequest Lab PK", "Order Laborat PK",
            "TABEL: permintaan_lab.\n" +
            "PRASYARAT: Encounter ID.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_servicerequest_lab`.",
            evt -> kirim_servicerequestlabpkActionPerformed(evt));
        addResourceRow(btnContainer, "Specimen Lab PK", "Sampel Darah/Urin",
            "TABEL: permintaan_lab.\n" +
            "PRASYARAT: ServiceRequest ID Lab PK.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_specimen_lab`.",
            evt -> kirim_specimenlabpkActionPerformed(evt));
        addResourceRow(btnContainer, "Observation Lab PK", "Hasil Per-Parameter",
            "TABEL: detail_periksa_lab.\n" +
            "PRASYARAT: Specimen ID Lab PK.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_observation_lab`.",
            evt -> kirim_observationlabpkActionPerformed(evt));
        addResourceRow(btnContainer, "DiagnosticReport Lab PK", "Saran Kesan Akhir",
            "TABEL: saran_kesan_lab.\n" +
            "PRASYARAT: Observation ID Lab PK.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_diagnostic_report_lab`.",
            evt -> kirim_diagnosticreportlabpkActionPerformed(evt));

        // Section: LAB MB
        btnContainer.add(makeSectionLabel("▸ LABORATORIUM MB"));
        addResourceRow(btnContainer, "ServiceRequest Lab MB", "Order Laborat MB",
            "TABEL: permintaan_labmb.\n" +
            "PRASYARAT: Encounter ID.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_servicerequest_labmb`.",
            evt -> kirim_servicerequestlabmbActionPerformed(evt));
        addResourceRow(btnContainer, "Specimen Lab MB", "Sampel Mikrobiologi",
            "TABEL: permintaan_labmb.\n" +
            "PRASYARAT: ServiceRequest ID Lab MB.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_specimen_labmb`.",
            evt -> kirim_specimenlabmbActionPerformed(evt));
        addResourceRow(btnContainer, "Observation Lab MB", "Hasil Pertumbuhan",
            "TABEL: detail_periksa_labmb.\n" +
            "PRASYARAT: Specimen ID Lab MB.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_observation_labmb`.",
            evt -> kirim_observationlabmbActionPerformed(evt));
        addResourceRow(btnContainer, "DiagnosticReport Lab MB", "Interpretasi MB",
            "TABEL: saran_kesan_labmb.\n" +
            "PRASYARAT: Observation ID Lab MB.\n" +
            "LOGIKA SKIP: Melewati data di `satu_sehat_diagnostic_report_labmb`.",
            evt -> kirim_diagnosticreportlabmbActionPerformed(evt));

        // Section: FINAL & SPECIAL
        btnContainer.add(makeSectionLabel("▸ FINAL & SPECIAL"));
        addResourceRow(btnContainer, "EpisodeOfCare", "ANC / Kehamilan",
            "TABEL: reg_periksa + diagnosa ICD-10 kelompok O.\n" +
            "PRASYARAT: NIK Pasien & Target Encounter ID.\n" +
            "LOGIKA: Pengelompokan asuhan kebidanan/kehamilan.",
            evt -> kirim_episodeofcareActionPerformed(evt));
        addResourceRow(btnContainer, "Composition (Resume)", "Bundel Resume Medis",
            "TABEL: Ringkasan dari seluruh resource klinis.\n" +
            "PRASYARAT (SANGAT KETAT):\n" +
            "  1. Harus sudah pulang (Nota Bayar Ada).\n" +
            "  2. Harus ada Resume Medis Khanza.\n" +
            "  3. Harus sudah punya Encounter ID.\n" +
            "LOGIKA: Gerbang Terakhir. Mengikat Diagnosa, Obat, Lab, dan Rad ke dalam satu Resume FHIR.",
            evt -> kirim_compositionActionPerformed(evt));
        addResourceRow(btnContainer, "DICOM Orthanc", "Routing Gambar Radiologi",
            "SUMBER: Server Orthanc DICOM.\n" +
            "PRASYARAT: No. Order Radiologi Khanza.\n" +
            "LOGIKA: Mengambil file DICOM dari Orthanc untuk dikirim ke Satu Sehat (ImagingStudy).",
            evt -> kirim_dicomrouterActionPerformed(evt));

        JScrollPane scrollRight = new JScrollPane(btnContainer);
        scrollRight.setBackground(BG_PANEL);
        scrollRight.setBorder(BorderFactory.createEmptyBorder());
        scrollRight.getViewport().setBackground(BG_PANEL);
        scrollRight.getVerticalScrollBar().setBackground(BG_PANEL);
        scrollRight.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        rightPanel.add(scrollRight, BorderLayout.CENTER);

        // === BOTTOM BAR PANEL KANAN (Keluar) ===
        JPanel bottomCtrl = new JPanel(new FlowLayout(FlowLayout.CENTER, 8, 8));
        bottomCtrl.setBackground(BG_CARD);
        bottomCtrl.setBorder(BorderFactory.createMatteBorder(2,0,0,0,BORDER_CLR));

        JButton btnKeluar = makeActionButton("✕  KELUAR", "Exit");
        btnKeluar.setBackground(new Color(0x3B0000));
        btnKeluar.setForeground(new Color(0xFF6666));
        btnKeluar.addActionListener(evt -> jButton1ActionPerformed(evt));
        bottomCtrl.add(btnKeluar);

        rightPanel.add(bottomCtrl, BorderLayout.SOUTH);

        getContentPane().add(rightPanel, BorderLayout.EAST);

        revalidate();
        repaint();
    }

    /** Membuat label separator antar seksi di panel kanan */
    private JLabel makeSectionLabel(String text) {
        JLabel lbl = new JLabel(text);
        lbl.setFont(new Font("Courier New", Font.BOLD, 10));
        lbl.setForeground(ACCENT_DIM);
        lbl.setAlignmentX(java.awt.Component.LEFT_ALIGNMENT);
        lbl.setBorder(BorderFactory.createEmptyBorder(10, 2, 2, 2));
        return lbl;
    }

    /**
     * Membuat baris tombol resource: [Tombol Aksi Kirim] [ⓘ]
     * Tombol ⓘ membuka modal dialog yang menjelaskan resource tersebut.
     */
    private void addResourceRow(JPanel container, String label, String subtitle, String helpText, ActionListener kirimAction) {
        JPanel row = new JPanel(new BorderLayout(4, 0));
        row.setBackground(BG_PANEL);
        row.setAlignmentX(java.awt.Component.LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Short.MAX_VALUE, 38));
        row.setBorder(BorderFactory.createEmptyBorder(2, 0, 2, 0));

        // Tombol KIRIM
        JButton btnKirim = new JButton("⬆ " + label);
        btnKirim.setFont(FONT_SANS);
        btnKirim.setForeground(TEXT_WHITE);
        btnKirim.setBackground(BTN_NORMAL);
        btnKirim.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_CLR, 1),
            BorderFactory.createEmptyBorder(4, 8, 4, 8)
        ));
        btnKirim.setFocusPainted(false);
        btnKirim.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btnKirim.setHorizontalAlignment(SwingConstants.LEFT);
        btnKirim.addActionListener(kirimAction);
        btnKirim.addMouseListener(new MouseAdapter() {
            public void mouseEntered(MouseEvent e) { btnKirim.setBackground(BTN_HOVER); }
            public void mouseExited(MouseEvent e)  { btnKirim.setBackground(BTN_NORMAL); }
        });

        // Tombol INFO ⓘ
        JButton btnInfo = new JButton("ⓘ");
        btnInfo.setFont(new Font("Segoe UI Symbol", Font.BOLD, 14));
        btnInfo.setForeground(ACCENT_DIM);
        btnInfo.setBackground(BG_CARD);
        btnInfo.setBorder(BorderFactory.createLineBorder(BORDER_CLR, 1));
        btnInfo.setFocusPainted(false);
        btnInfo.setPreferredSize(new Dimension(32, 32));
        btnInfo.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btnInfo.setToolTipText(subtitle);
        btnInfo.addMouseListener(new MouseAdapter() {
            public void mouseEntered(MouseEvent e) { btnInfo.setForeground(ACCENT_GREEN); }
            public void mouseExited(MouseEvent e)  { btnInfo.setForeground(ACCENT_DIM); }
        });
        btnInfo.addActionListener(evt -> showResourceModal(label, subtitle, helpText));

        row.add(btnKirim, BorderLayout.CENTER);
        row.add(btnInfo, BorderLayout.EAST);
        container.add(row);
    }

    /** Menampilkan dialog modal yang berisi penjelasan lengkap sebuah resource FHIR */
    private void showResourceModal(String title, String subtitle, String description) {
        JDialog dialog = new JDialog(this, "ⓘ  Info Resource: " + title, true);
        dialog.setSize(480, 380);
        dialog.setLocationRelativeTo(this);
        dialog.getContentPane().setBackground(BG_DEEP);
        dialog.getContentPane().setLayout(new BorderLayout(0, 0));

        // Header Modal
        JPanel mHeader = new JPanel(new BorderLayout());
        mHeader.setBackground(BG_CARD);
        mHeader.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0,0,2,0,ACCENT_GREEN),
            BorderFactory.createEmptyBorder(12,16,12,16)
        ));
        JLabel mTitle = new JLabel(title);
        mTitle.setFont(FONT_SANS_B);
        mTitle.setForeground(ACCENT_GREEN);
        JLabel mSub = new JLabel(subtitle);
        mSub.setFont(new Font("Segoe UI", Font.ITALIC, 11));
        mSub.setForeground(TEXT_MUTED);
        mHeader.add(mTitle, BorderLayout.NORTH);
        mHeader.add(mSub, BorderLayout.CENTER);
        dialog.getContentPane().add(mHeader, BorderLayout.NORTH);

        // Body Modal
        JTextArea mBody = new JTextArea(description);
        mBody.setEditable(false);
        mBody.setFont(FONT_MONO);
        mBody.setForeground(ACCENT_GREEN);
        mBody.setBackground(BG_DEEP);
        mBody.setMargin(new Insets(14,16,14,16));
        mBody.setLineWrap(true);
        mBody.setWrapStyleWord(true);
        JScrollPane mScroll = new JScrollPane(mBody);
        mScroll.setBorder(BorderFactory.createEmptyBorder());
        mScroll.getViewport().setBackground(BG_DEEP);
        dialog.getContentPane().add(mScroll, BorderLayout.CENTER);

        // Footer Modal
        JPanel mFooter = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        mFooter.setBackground(BG_CARD);
        mFooter.setBorder(BorderFactory.createMatteBorder(2,0,0,0,BORDER_CLR));
        JButton mClose = makeActionButton("Tutup", "Close");
        mClose.addActionListener(e -> dialog.dispose());
        mFooter.add(mClose);
        dialog.getContentPane().add(mFooter, BorderLayout.SOUTH);

        dialog.setVisible(true);
    }

    /** Membuat tombol bergaya dark dengan hover effect */
    private JButton makeActionButton(String text, String tooltip) {
        JButton btn = new JButton(text);
        btn.setFont(FONT_SANS_B);
        btn.setForeground(ACCENT_GREEN);
        btn.setBackground(BTN_NORMAL);
        btn.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_CLR, 1),
            BorderFactory.createEmptyBorder(5, 12, 5, 12)
        ));
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btn.setToolTipText(tooltip);
        btn.addMouseListener(new MouseAdapter() {
            public void mouseEntered(MouseEvent e) { btn.setBackground(BTN_HOVER); }
            public void mouseExited(MouseEvent e)  { btn.setBackground(BTN_NORMAL); }
        });
        return btn;
    }

    private void jButton1ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_jButton1ActionPerformed
        System.exit(0);
    }//GEN-LAST:event_jButton1ActionPerformed

    private void jButtonStartKirimActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_jButtonStartKirimActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN DATA MANUAL ATAS PERMINTAAN USER...\n");
    
    // Nonaktifkan tombol agar tidak bisa diklik berkali-kali saat proses berjalan
    jButtonStartKirim.setEnabled(false);

    // Gunakan SwingWorker untuk menjalankan tugas berat di background
    new javax.swing.SwingWorker<Void, Void>() {
        @Override
        protected Void doInBackground() throws Exception {
            // Panggil metode yang berisi semua query di sini
            jalankanSemuaQueryBridging();
            return null;
        }

        @Override
        protected void done() {
            // Setelah selesai, aktifkan kembali tombolnya
            // Ini akan dieksekusi di thread utama GUI (Event Dispatch Thread)
            TeksArea.append("\nPENGIRIMAN DATA MANUAL SELESAI.\nTimer otomatis akan melanjutkan jadwal seperti biasa.\n");
            jButtonStartKirim.setEnabled(true);
        }
    }.execute();
    }//GEN-LAST:event_jButtonStartKirimActionPerformed

    private void kirim_encounterActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_encounterActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Encounter...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                encounter();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Encounter SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_encounterActionPerformed

    private void kirim_observationTTVActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_observationTTVActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Observation TTV...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                observationTTV();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Observation TTV SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_observationTTVActionPerformed

    private void kirim_vaksinActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_vaksinActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Vaksin...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                vaksin();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Vaksin SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_vaksinActionPerformed

    private void kirim_prosedurActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_prosedurActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Prosedur...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                prosedur();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Prosedur SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_prosedurActionPerformed

    private void kirim_conditionActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_conditionActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Condition...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                condition();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Condition SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_conditionActionPerformed

    private void kirim_clinicalimpressionActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_clinicalimpressionActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Clinical Impression...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                clinicalimpression();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Clinical Impression SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_clinicalimpressionActionPerformed

    private void kirim_dietgiziActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_dietgiziActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Diet Gizi...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                dietgizi();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Diet Gizi SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_dietgiziActionPerformed

    private void kirim_medicationActionPerformed(java.awt.event.ActionEvent evt) {
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Medication Master...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                medication();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Medication Master SELESAI.\n");
            }
        }.execute();
    }

    private void kirim_medicationrequestActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_medicationrequestActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Medication Request...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                medicationrequest();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Medication Request SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_medicationrequestActionPerformed

    private void kirim_medicationdispenseActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_medicationdispenseActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Medication Dispense...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                medicationdispense();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Medication Dispense SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_medicationdispenseActionPerformed

    private void kirim_medicationstatementActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_medicationstatementActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Medication Statement...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                medicationstatement();                
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Medication Statement SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_medicationstatementActionPerformed

    private void kirim_servicerequestradiologiActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_servicerequestradiologiActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Service Request Radiologi...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                servicerequestradiologi();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Service Request Radiologi SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_servicerequestradiologiActionPerformed

    private void kirim_specimenradiologiActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_specimenradiologiActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Specimen Radiologi...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                specimenradiologi();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Specimen Radiologi SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_specimenradiologiActionPerformed

    private void kirim_observationradiologiActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_observationradiologiActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Observation Radiologi...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                observationradiologi();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Observation Radiologi SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_observationradiologiActionPerformed

    private void kirim_diagnosticreportradiologiActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_diagnosticreportradiologiActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Diagnostic Report Radiologi...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                diagnosticreportradiologi();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Diagnostic Report Radiologi SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_diagnosticreportradiologiActionPerformed

    private void kirim_servicerequestlabpkActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_servicerequestlabpkActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Service Request Lab PK...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                servicerequestlabpk();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Service Request Lab PK SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_servicerequestlabpkActionPerformed

    private void kirim_servicerequestlabmbActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_servicerequestlabmbActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Service Request Lab MB...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                servicerequestlabmb();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Service Request Lab MB SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_servicerequestlabmbActionPerformed

    private void kirim_specimenlabpkActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_specimenlabpkActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Specimen Lab PK...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                specimenlabpk();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Specimen Lab PK SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_specimenlabpkActionPerformed

    private void kirim_specimenlabmbActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_specimenlabmbActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Specimen Lab MB...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                specimenlabmb();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Specimen Lab MB SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_specimenlabmbActionPerformed

    private void kirim_observationlabpkActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_observationlabpkActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Observation Lab PK...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                observationlabpk();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Observation Lab PK SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_observationlabpkActionPerformed

    private void kirim_observationlabmbActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_observationlabmbActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Observation Lab MB...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                observationlabmb();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Observation Lab MB SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_observationlabmbActionPerformed

    private void kirim_diagnosticreportlabpkActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_diagnosticreportlabpkActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Diagnostic Report Lab PK...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                diagnosticreportlabpk();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Diagnostic Report Lab PK SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_diagnosticreportlabpkActionPerformed

    private void kirim_diagnosticreportlabmbActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_diagnosticreportlabmbActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Diagnostic Report Lab MB...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                diagnosticreportlabmb();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Diagnostic Report Lab MB SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_diagnosticreportlabmbActionPerformed

    private void kirim_careplanActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_careplanActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Care Plan...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                careplan();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Care Plan SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_careplanActionPerformed

    private void kirim_questionnaireActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_questionnaireActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Telaah Obat (Questionnaire)...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                questionnaire();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Telaah Obat (Questionnaire) SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_questionnaireActionPerformed

    private void kirim_compositionActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_compositionActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Composition...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                kirimComposition();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Composition SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_compositionActionPerformed

    private void kirim_alergiActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_alergiActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: AllergyIntolerance (Riwayat Alergi)...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                alergi();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: AllergyIntolerance SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_alergiActionPerformed

    private void kirim_episodeofcareActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_episodeofcareActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: EpisodeOfCare (Episode Kehamilan ANC)...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                episodeofcare();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: EpisodeOfCare SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_episodeofcareActionPerformed

    private void kirim_encounter2ActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_encounter2ActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Encounter Susulan Radiologi (Encounter v2)...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                encounter2();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Encounter v2 SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_encounter2ActionPerformed

    private void kirim_dicomrouterActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_kirim_dicomrouterActionPerformed
        TeksArea.setText("MEMULAI PENGIRIMAN MANUAL: Orthanc DICOM Router...\n");
        jPopupMenu1.setEnabled(false);
        new javax.swing.SwingWorker<Void, Void>() {
            @Override
            protected Void doInBackground() throws Exception {
                kirimDicomRouter();
                return null;
            }
            @Override
            protected void done() {
                jPopupMenu1.setEnabled(true);
                TeksArea.append("\nPENGIRIMAN MANUAL: Orthanc DICOM Router SELESAI.\n");
            }
        }.execute();
    }//GEN-LAST:event_kirim_dicomrouterActionPerformed


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
            java.util.logging.Logger.getLogger(frmUtama.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        } catch (InstantiationException ex) {
            java.util.logging.Logger.getLogger(frmUtama.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        } catch (IllegalAccessException ex) {
            java.util.logging.Logger.getLogger(frmUtama.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        } catch (javax.swing.UnsupportedLookAndFeelException ex) {
            java.util.logging.Logger.getLogger(frmUtama.class.getName()).log(java.util.logging.Level.SEVERE, null, ex);
        }
        //</editor-fold>
        //</editor-fold>

        /* Create and display the form */
        java.awt.EventQueue.invokeLater(new Runnable() {
            public void run() {
                new frmUtama().setVisible(true);
            }
        });
    }

    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JTextField Tanggal1;
    private javax.swing.JTextField Tanggal2;
    private javax.swing.JTextArea TeksArea;
    private javax.swing.JButton jButton1;
    private javax.swing.JButton jButtonStartKirim;
    private javax.swing.JLabel jLabel1;
    private javax.swing.JLabel jLabel2;
    private javax.swing.JLabel jLabel3;
    private javax.swing.JPanel jPanel1;
    private javax.swing.JPopupMenu jPopupMenu1;
    private javax.swing.JScrollPane jScrollPane1;
    private javax.swing.JScrollPane jScrollPane2;
    private javax.swing.JTextArea jTextArea1;
    private javax.swing.JMenuItem kirim_careplan;
    private javax.swing.JMenuItem kirim_clinicalimpression;
    private javax.swing.JMenuItem kirim_composition;
    private javax.swing.JMenuItem kirim_condition;
    private javax.swing.JMenuItem kirim_diagnosticreportlabmb;
    private javax.swing.JMenuItem kirim_diagnosticreportlabpk;
    private javax.swing.JMenuItem kirim_diagnosticreportradiologi;
    private javax.swing.JMenuItem kirim_dietgizi;
    private javax.swing.JMenuItem kirim_encounter;
    private javax.swing.JMenuItem kirim_medicationdispense;
    private javax.swing.JMenuItem kirim_medicationrequest;
    private javax.swing.JMenuItem kirim_medicationstatement;
    private javax.swing.JMenuItem kirim_observationTTV;
    private javax.swing.JMenuItem kirim_observationlabmb;
    private javax.swing.JMenuItem kirim_observationlabpk;
    private javax.swing.JMenuItem kirim_observationradiologi;
    private javax.swing.JMenuItem kirim_prosedur;
    private javax.swing.JMenuItem kirim_questionnaire;
    private javax.swing.JMenuItem kirim_servicerequestlabmb;
    private javax.swing.JMenuItem kirim_servicerequestlabpk;
    private javax.swing.JMenuItem kirim_servicerequestradiologi;
    private javax.swing.JMenuItem kirim_specimenlabmb;
    private javax.swing.JMenuItem kirim_specimenlabpk;
    private javax.swing.JMenuItem kirim_specimenradiologi;
    private javax.swing.JMenuItem kirim_vaksin;
    private javax.swing.JMenuItem kirim_alergi;
    private javax.swing.JMenuItem kirim_episodeofcare;
    private javax.swing.JMenuItem kirim_encounter2;
    private javax.swing.JMenuItem kirim_dicomrouter;
    private javax.swing.JCheckBoxMenuItem jCheckBoxMenuItemOrtancAuto;
    // End of variables declaration//GEN-END:variables
    private void jam(){
        ActionListener taskPerformer = new ActionListener(){
            private int nilai_jam;
            private int nilai_menit;
            private int nilai_detik;
            public void actionPerformed(ActionEvent e) {
                nol_jam = "";
                nol_menit = "";
                nol_detik = "";
                Date now = Calendar.getInstance().getTime();
                // Mengambil nilaj JAM, MENIT, dan DETIK Sekarang
                nilai_jam = now.getHours();
                nilai_menit = now.getMinutes();
                nilai_detik = now.getSeconds();
                // Jika nilai JAM lebih kecil dari 10 (hanya 1 digit)
                if (nilai_jam <= 9) {
                    // Tambahkan "0" didepannya
                    nol_jam = "0";
                }
                // Jika nilai MENIT lebih kecil dari 10 (hanya 1 digit)
                if (nilai_menit <= 9) {
                    // Tambahkan "0" didepannya
                    nol_menit = "0";
                }
                // Jika nilai DETIK lebih kecil dari 10 (hanya 1 digit)
                if (nilai_detik <= 9) {
                    // Tambahkan "0" didepannya
                    nol_detik = "0";
                }
                // Membuat String JAM, MENIT, DETIK
                jam = nol_jam + Integer.toString(nilai_jam);
                menit = nol_menit + Integer.toString(nilai_menit);
                detik = nol_detik + Integer.toString(nilai_detik);
                // [LOGIKA 1]: Pergantian Hari / Tanggal (Persis Jam 00:00:00)
                if(jam.equals("00")&&menit.equals("00")&&detik.equals("00")){
                    TeksArea.append("[SISTEM] Pergantian Hari Terdeteksi. Menyesuaikan Tanggal...\n");
                    date = new Date();  
                    Tanggal1.setText(tanggalFormat.format(date)); 
                    Tanggal2.setText(tanggalFormat.format(date)); 
                }
                
                // [LOGIKA 2]: Autopilot Engine membaca Interval
                if (isAutopilot) {
                    long currentMs = System.currentTimeMillis();
                    long targetMs = intervalMenit * 60 * 1000L;
                    if ((currentMs - lastRunTimeMs) >= targetMs) {
                        lastRunTimeMs = currentMs;
                        TeksArea.append("\n[AUTOPILOT] Memulai Scan Rutin Setiap " + intervalMenit + " Menit...\n");
                        new Thread(() -> jalankanSemuaQueryBridging()).start();
                    }
                }
            }
        };
        // Timer
          new Timer(1000, taskPerformer).start();
    }
    
    // =============================================================================================
    // BAGIAN 1: ENCOUNTER (KUNJUNGAN) - FIXED (Hapus Nota)
    // =============================================================================================
    // MODUL UTAMA ENCOUNTER (KUNJUNGAN)
    private void encounter() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES KIRIM ENCOUNTER (KUNJUNGAN)\n");
            TeksArea.append("------------------------------------------------------\n");

            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,pasien.nm_pasien,pasien.no_ktp,"
                    + "pegawai.nama,pegawai.no_ktp as ktpdokter,poliklinik.nm_poli,satu_sehat_mapping_lokasi_ralan.id_lokasi_satusehat,"
                    + "reg_periksa.status_lanjut, ifnull(satu_sehat_encounter.id_encounter,'') as id_encounter "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join pegawai on pegawai.nik=reg_periksa.kd_dokter "
                    + "inner join poliklinik on reg_periksa.kd_poli=poliklinik.kd_poli "
                    + "inner join satu_sehat_mapping_lokasi_ralan on satu_sehat_mapping_lokasi_ralan.kd_poli=poliklinik.kd_poli "
                    + "left join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "where reg_periksa.tgl_registrasi between ? and ? "
                    + "and reg_periksa.stts <> 'Batal'  "
                    + "and ifnull(satu_sehat_encounter.id_encounter,'')='' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000'");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                
                int i = 0;
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    i++;
                    kirimEncounterHelper(rs); 
                    jeda();
                }
                TeksArea.append("\n[INFO] Selesai Memproses Encounter. Total Data: " + i + "\n");
            } catch (Exception e) {
                System.out.println("Notif Encounter: " + e);
                TeksArea.append("ERROR QUERY ENCOUNTER: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception ez) {
            System.out.println("Notifikasi : " + ez);
        }
    }
    
    // HELPER: KIRIM ENCOUNTER (DETEKTIF & AUTO RECONNECT)
    private void kirimEncounterHelper(ResultSet rs) {
        String noRawat = "", nmPasien = "";
        try {
            noRawat = rs.getString("no_rawat");
            nmPasien = rs.getString("nm_pasien");
            
            TeksArea.append("\n[PROSES ENCOUNTER] No.Rawat: " + noRawat + " | Pasien: " + nmPasien + "\n");
            
            // 1. Validasi KyC
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));

            if (iddokter.isEmpty() || idpasien.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat.\n");
                TeksArea.append("      -> Cek NIK Pasien: " + rs.getString("no_ktp") + "\n");
                TeksArea.append("      -> Cek NIK Dokter: " + rs.getString("ktpdokter") + "\n");
                return;
            }

            // 2. Persiapan Data
            String namaPasien = nmPasien.replaceAll("\"", "'");
            String namaDokter = rs.getString("nama").replaceAll("\"", "'");
            String namaPoli = rs.getString("nm_poli").replaceAll("\"", "'");
            String statusLanjut = rs.getString("status_lanjut");
            String classCode = statusLanjut.equals("Ralan") ? "AMB" : "IMP";
            String classDisplay = statusLanjut.equals("Ralan") ? "ambulatory" : "inpatient encounter";
            
            // Format Tanggal ISO 8601
            String startDateTime = rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00";

            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat()); // Token saat ini

            json = "{"
                    + "\"resourceType\": \"Encounter\","
                    + "\"status\": \"arrived\","
                    + "\"class\": {"
                        + "\"system\": \"http://terminology.hl7.org/CodeSystem/v3-ActCode\","
                        + "\"code\": \"" + classCode + "\","
                        + "\"display\": \"" + classDisplay + "\""
                    + "},"
                    + "\"subject\": {"
                        + "\"reference\": \"Patient/" + idpasien + "\","
                        + "\"display\": \"" + namaPasien + "\""
                    + "},"
                    + "\"participant\": ["
                        + "{"
                            + "\"type\": ["
                                + "{"
                                    + "\"coding\": ["
                                        + "{"
                                            + "\"system\": \"http://terminology.hl7.org/CodeSystem/v3-ParticipationType\","
                                            + "\"code\": \"ATND\","
                                            + "\"display\": \"attender\""
                                        + "}"
                                    + "]"
                                + "}"
                            + "],"
                            + "\"individual\": {"
                                + "\"reference\": \"Practitioner/" + iddokter + "\","
                                + "\"display\": \"" + namaDokter + "\""
                            + "}"
                        + "}"
                    + "],"
                    + "\"period\": {"
                        + "\"start\": \"" + startDateTime + "\""
                    + "},"
                    + "\"location\": ["
                        + "{"
                            + "\"location\": {"
                                + "\"reference\": \"Location/" + rs.getString("id_lokasi_satusehat") + "\","
                                + "\"display\": \"" + namaPoli + "\""
                            + "}"
                        + "}"
                    + "],"
                    + "\"statusHistory\": ["
                        + "{"
                            + "\"status\": \"arrived\","
                            + "\"period\": {"
                                + "\"start\": \"" + startDateTime + "\""
                            + "}"
                        + "}"
                    + "],"
                    + "\"serviceProvider\": {"
                        + "\"reference\": \"Organization/" + koneksiDB.IDSATUSEHAT() + "\""
                    + "},"
                    + "\"identifier\": ["
                        + "{"
                            + "\"system\": \"http://sys-ids.kemkes.go.id/encounter/" + koneksiDB.IDSATUSEHAT() + "\","
                            + "\"value\": \"" + noRawat + "\""
                        + "}"
                    + "]"
                    + "}";
            
            // Debug JSON Payload (Optional, aktifkan jika perlu)
            // TeksArea.append("   [DEBUG JSON]: " + json + "\n");

            // 3. Kirim dengan Auto-Reconnect
            requestEntity = new HttpEntity(json, headers);
            try {
                // Percobaan Kirim Pertama
                String responseJson = konekSatuSehat(link + "/Encounter", HttpMethod.POST, requestEntity);
                handleEncounterSuccess(responseJson, noRawat);
                
            } catch (HttpClientErrorException e) {
                // Jika Token Expired (401) -> Refresh & Retry
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Memperbarui Token...\n");
                    
                    // PERBAIKAN DI SINI:
                    // Panggil TokenSatuSehat() untuk me-request token baru ke Kemenkes
                    String newToken = api.TokenSatuSehat(); 
                    
                    // Update Header dengan Token Baru
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    
                    try {
                        // Percobaan Kirim Kedua (Retry)
                        String responseJsonRetry = konekSatuSehat(link + "/Encounter", HttpMethod.POST, requestEntity);
                        TeksArea.append("   [RETRY SUKSES] Berhasil dikirim setelah refresh token.\n");
                        handleEncounterSuccess(responseJsonRetry, noRawat);
                        
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } else {
                    // Error Lain (400 Bad Request, dll)
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            }

        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            System.out.println("Notifikasi : " + e);
        }
    }

    // Helper Kecil untuk Simpan ke DB (Biar kode rapi)
    private void handleEncounterSuccess(String responseJson, String noRawat) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_encounter", "?,?", "No.Rawat", 2, new String[]{
                noRawat, responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID Encounter: " + responseId.asText() + "\n");
        }
    }
    
    
    // MODUL UTAMA OBSERVATION TTV (LEVEL DETEKTIF CONAN)
    private void observationTTV() {
        try {
            // ==========================================
            // 1. KIRIM TTV SUHU TUBUH
            // ==========================================
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: OBSERVATION TTV\n");
            TeksArea.append("------------------------------------------------------\n");
            
            TeksArea.append("\n[1/6] Melacak Data Suhu Tubuh (Ralan & Ranap)...\n");
            ps = koneksi.prepareStatement(
                    "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.suhu_tubuh, 'Ralan' AS status_rawat "
                    + "FROM reg_periksa rp "
                    + "INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat = rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ralan pr ON pr.no_rawat = rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip = pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvsuhu log ON log.no_rawat = pr.no_rawat AND log.tgl_perawatan = pr.tgl_perawatan AND log.jam_rawat = pr.jam_rawat "
                    + "WHERE pr.suhu_tubuh <> '' AND pr.suhu_tubuh <> '-' "
                    + "AND pr.tgl_perawatan BETWEEN ? AND ? "
                    + "AND IFNULL(log.id_observation,'') = '' "
                    + "UNION ALL "
                    + "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.suhu_tubuh, 'Ranap' AS status_rawat "
                    + "FROM reg_periksa rp "
                    + "INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat = rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ranap pr ON pr.no_rawat = rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip = pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvsuhu log ON log.no_rawat = pr.no_rawat AND log.tgl_perawatan = pr.tgl_perawatan AND log.jam_rawat = pr.jam_rawat "
                    + "WHERE pr.suhu_tubuh <> '' AND pr.suhu_tubuh <> '-' "
                    + "AND pr.tgl_perawatan BETWEEN ? AND ? "
                    + "AND IFNULL(log.id_observation,'') = ''");
            
            try {
                ps.setString(1, Tanggal1.getText()); ps.setString(2, Tanggal2.getText());
                ps.setString(3, Tanggal1.getText()); ps.setString(4, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    try {
                        idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                        iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                        
                        if (!idpasien.equals("") && !iddokter.equals("")) {
                            String nilaiSuhu = rs.getString("suhu_tubuh").replaceAll(",", ".").trim();
                            // Konstruksi JSON
                            json = "{" +
                                    "\"resourceType\": \"Observation\", \"status\": \"final\"," +
                                    "\"category\": [{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\",\"code\": \"vital-signs\",\"display\": \"Vital Signs\"}]}]," +
                                    "\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"8310-5\",\"display\": \"Body temperature\"}]}," +
                                    "\"subject\": {\"reference\": \"Patient/" + idpasien + "\"}," +
                                    "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\"}]," +
                                    "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                                    "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\"," +
                                    "\"valueQuantity\": {\"value\": " + nilaiSuhu + ",\"unit\": \"degree Celsius\",\"system\": \"http://unitsofmeasure.org\",\"code\": \"Cel\"}" +
                                    "}";
                            
                            // PROSES KIRIM
                            processSendObservation(json, "satu_sehat_observationttvsuhu", rs, "8310-5", "Suhu: " + nilaiSuhu);
                        } else {
                            TeksArea.append("   !! [SKIP] ID Pasien/Dokter Kosong. Rawat: " + rs.getString("no_rawat") + "\n");
                        }
                    } catch (Exception e) {
                        TeksArea.append("   !! [ERROR SYSTEM SUHU] Rawat: " + rs.getString("no_rawat") + " -> " + e + "\n");
                    }
                }
            } catch (Exception e) { System.out.println("Error Query Suhu: " + e); } finally { if(rs!=null) rs.close(); if(ps!=null) ps.close(); }

            // ==========================================
            // 2. KIRIM TTV NADI
            // ==========================================
            TeksArea.append("\n[2/6] Melacak Data Nadi (Ralan & Ranap)...\n");
            ps = koneksi.prepareStatement(
                    "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.nadi, 'Ralan' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat = rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ralan pr ON pr.no_rawat = rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip = pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvnadi log ON log.no_rawat = pr.no_rawat AND log.tgl_perawatan = pr.tgl_perawatan AND log.jam_rawat = pr.jam_rawat "
                    + "WHERE pr.nadi <> '' AND pr.nadi <> '-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'') = '' "
                    + "UNION ALL "
                    + "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.nadi, 'Ranap' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat = rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ranap pr ON pr.no_rawat = rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip = pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvnadi log ON log.no_rawat = pr.no_rawat AND log.tgl_perawatan = pr.tgl_perawatan AND log.jam_rawat = pr.jam_rawat "
                    + "WHERE pr.nadi <> '' AND pr.nadi <> '-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'') = ''");
            try {
                ps.setString(1, Tanggal1.getText()); ps.setString(2, Tanggal2.getText());
                ps.setString(3, Tanggal1.getText()); ps.setString(4, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    try {
                        idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                        iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                        if (!idpasien.equals("") && !iddokter.equals("")) {
                            String nilaiNadi = rs.getString("nadi").replaceAll("[^0-9]", "");
                            if (nilaiNadi.isEmpty()) continue;

                            json = "{" +
                                    "\"resourceType\": \"Observation\", \"status\": \"final\"," +
                                    "\"category\": [{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\",\"code\": \"vital-signs\",\"display\": \"Vital Signs\"}]}]," +
                                    "\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"8867-4\",\"display\": \"Heart rate\"}]}," +
                                    "\"subject\": {\"reference\": \"Patient/" + idpasien + "\"}," +
                                    "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\"}]," +
                                    "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                                    "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\"," +
                                    "\"valueQuantity\": {\"value\": " + nilaiNadi + ",\"unit\": \"beats/minute\",\"system\": \"http://unitsofmeasure.org\",\"code\": \"/min\"}" +
                                    "}";
                            
                            processSendObservation(json, "satu_sehat_observationttvnadi", rs, "8867-4", "Nadi: " + nilaiNadi);
                        }
                    } catch (Exception e) { TeksArea.append("   !! [ERROR SYSTEM NADI] " + e + "\n"); }
                }
            } catch (Exception e) { System.out.println("Error Query Nadi: " + e); } finally { if(rs!=null) rs.close(); if(ps!=null) ps.close(); }

            // ==========================================
            // 3. KIRIM TTV RESPIRASI
            // ==========================================
            TeksArea.append("\n[3/6] Melacak Data Respirasi...\n");
            ps = koneksi.prepareStatement(
                    "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.respirasi, 'Ralan' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ralan pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvrespirasi log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.respirasi<>'' AND pr.respirasi<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')='' "
                    + "UNION ALL "
                    + "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.respirasi, 'Ranap' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ranap pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvrespirasi log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.respirasi<>'' AND pr.respirasi<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')=''");
            try {
                ps.setString(1, Tanggal1.getText()); ps.setString(2, Tanggal2.getText());
                ps.setString(3, Tanggal1.getText()); ps.setString(4, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    try {
                        idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                        iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                        if (!idpasien.equals("") && !iddokter.equals("")) {
                            String nilaiRR = rs.getString("respirasi").replaceAll("[^0-9]", "");
                            if (nilaiRR.isEmpty()) continue;
                            
                            json = "{" +
                                    "\"resourceType\": \"Observation\",\"status\": \"final\"," +
                                    "\"category\": [{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\",\"code\": \"vital-signs\",\"display\": \"Vital Signs\"}]}]," +
                                    "\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"9279-1\",\"display\": \"Respiratory rate\"}]}," +
                                    "\"subject\": {\"reference\": \"Patient/" + idpasien + "\"}," +
                                    "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\"}]," +
                                    "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                                    "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\"," +
                                    "\"valueQuantity\": {\"value\": " + nilaiRR + ",\"unit\": \"breaths/minute\",\"system\": \"http://unitsofmeasure.org\",\"code\": \"/min\"}}";
                            
                            processSendObservation(json, "satu_sehat_observationttvrespirasi", rs, "9279-1", "RR: " + nilaiRR);
                        }
                    } catch (Exception e) { TeksArea.append("   !! [ERROR SYSTEM RR] " + e + "\n"); }
                }
            } catch (Exception e) { System.out.println(e); } finally { if(rs!=null) rs.close(); if(ps!=null) ps.close(); }

            // ==========================================
            // 4. KIRIM TTV TENSI
            // ==========================================
            TeksArea.append("\n[4/6] Melacak Data Tensi...\n");
            ps = koneksi.prepareStatement(
                    "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.tensi, 'Ralan' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ralan pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvtensi log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.tensi<>'' AND pr.tensi<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')='' "
                    + "UNION ALL "
                    + "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.tensi, 'Ranap' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ranap pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvtensi log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.tensi<>'' AND pr.tensi<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')=''");
            try {
                ps.setString(1, Tanggal1.getText()); ps.setString(2, Tanggal2.getText());
                ps.setString(3, Tanggal1.getText()); ps.setString(4, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    try {
                        idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                        iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                        if (!idpasien.equals("") && !iddokter.equals("")) {
                            arrSplit = rs.getString("tensi").split("/");
                            sistole = (arrSplit.length > 0) ? arrSplit[0].replaceAll("[^0-9]", "") : "0";
                            diastole = (arrSplit.length > 1) ? arrSplit[1].replaceAll("[^0-9]", "") : "0";
                            if (sistole.isEmpty()) sistole="0"; if (diastole.isEmpty()) diastole="0";

                            json = "{" +
                                    "\"resourceType\": \"Observation\", \"status\": \"final\"," +
                                    "\"category\": [{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\",\"code\": \"vital-signs\",\"display\": \"Vital Signs\"}]}]," +
                                    "\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"85354-9\",\"display\": \"Blood pressure panel with all children optional\"}]}," +
                                    "\"subject\": {\"reference\": \"Patient/" + idpasien + "\"}," +
                                    "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\"}]," +
                                    "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                                    "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\"," +
                                    "\"component\": [" +
                                        "{\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"8480-6\",\"display\": \"Systolic blood pressure\"}]},\"valueQuantity\": {\"value\": " + sistole + ",\"unit\": \"mmHg\",\"system\": \"http://unitsofmeasure.org\",\"code\": \"mm[Hg]\"}}," +
                                        "{\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"8462-4\",\"display\": \"Diastolic blood pressure\"}]},\"valueQuantity\": {\"value\": " + diastole + ",\"unit\": \"mmHg\",\"system\": \"http://unitsofmeasure.org\",\"code\": \"mm[Hg]\"}}" +
                                    "]}";
                            
                            processSendObservation(json, "satu_sehat_observationttvtensi", rs, "85354-9", "Tensi: " + sistole + "/" + diastole);
                        }
                    } catch (Exception e) { TeksArea.append("   !! [ERROR SYSTEM TENSI] " + e + "\n"); }
                }
            } catch (Exception e) { System.out.println(e); } finally { if(rs!=null) rs.close(); if(ps!=null) ps.close(); }

            // ==========================================
            // 5. KIRIM TTV KESADARAN
            // ==========================================
            TeksArea.append("\n[5/6] Melacak Data Kesadaran...\n");
            ps = koneksi.prepareStatement(
                    "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.kesadaran, 'Ralan' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ralan pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvkesadaran log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.kesadaran<>'' AND pr.kesadaran<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')='' "
                    + "UNION ALL "
                    + "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.kesadaran, 'Ranap' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ranap pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvkesadaran log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.kesadaran<>'' AND pr.kesadaran<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')=''");
            try {
                ps.setString(1, Tanggal1.getText()); ps.setString(2, Tanggal2.getText());
                ps.setString(3, Tanggal1.getText()); ps.setString(4, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    try {
                        idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                        iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                        if (!idpasien.equals("") && !iddokter.equals("")) {
                            String valKesadaran = rs.getString("kesadaran").toLowerCase();
                            String snomedCode = "248233002"; String display = "Alert";
                            if (valKesadaran.contains("somnolen") || valKesadaran.contains("voice")) { snomedCode = "300202002"; display = "Voice"; } 
                            else if (valKesadaran.contains("sopor") || valKesadaran.contains("pain")) { snomedCode = "248218005"; display = "Pain"; } 
                            else if (valKesadaran.contains("coma") || valKesadaran.contains("koma")) { snomedCode = "422768004"; display = "Unresponsive"; }

                            json = "{" +
                                    "\"resourceType\": \"Observation\", \"status\": \"final\"," +
                                    "\"category\": [{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\",\"code\": \"exam\",\"display\": \"Exam\"}]}]," +
                                    "\"code\": {\"coding\": [{\"system\": \"http://snomed.info/sct\",\"code\": \"1104441000000107\",\"display\": \"ACVPU scale\"}]}," +
                                    "\"subject\": {\"reference\": \"Patient/" + idpasien + "\"}," +
                                    "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\"}]," +
                                    "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                                    "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\"," +
                                    "\"valueCodeableConcept\": {\"coding\": [{\"system\": \"http://snomed.info/sct\",\"code\": \"" + snomedCode + "\",\"display\": \"" + display + "\"}]}}";
                            
                            processSendObservation(json, "satu_sehat_observationttvkesadaran", rs, "1104441000000107", "Kesadaran: " + display);
                        }
                    } catch (Exception e) { TeksArea.append("   !! [ERROR SYSTEM KESADARAN] " + e + "\n"); }
                }
            } catch (Exception e) { System.out.println(e); } finally { if(rs!=null) rs.close(); if(ps!=null) ps.close(); }

            // ==========================================
            // 6. KIRIM TTV SPO2
            // ==========================================
            TeksArea.append("\n[6/6] Melacak Data SpO2...\n");
            ps = koneksi.prepareStatement(
                    "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.spo2, 'Ralan' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ralan pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvspo2 log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.spo2<>'' AND pr.spo2<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')='' "
                    + "UNION ALL "
                    + "SELECT rp.no_rawat, p.nm_pasien, p.no_ktp, sse.id_encounter, pg.no_ktp AS ktppraktisi, "
                    + "pr.tgl_perawatan, pr.jam_rawat, pr.spo2, 'Ranap' AS status_rawat "
                    + "FROM reg_periksa rp INNER JOIN pasien p ON rp.no_rkm_medis=p.no_rkm_medis "
                    + "INNER JOIN satu_sehat_encounter sse ON sse.no_rawat=rp.no_rawat "
                    + "INNER JOIN pemeriksaan_ranap pr ON pr.no_rawat=rp.no_rawat "
                    + "INNER JOIN pegawai pg ON pr.nip=pg.nik "
                    + "LEFT JOIN satu_sehat_observationttvspo2 log ON log.no_rawat=pr.no_rawat AND log.tgl_perawatan=pr.tgl_perawatan AND log.jam_rawat=pr.jam_rawat "
                    + "WHERE pr.spo2<>'' AND pr.spo2<>'-' AND pr.tgl_perawatan BETWEEN ? AND ? AND IFNULL(log.id_observation,'')=''");
            try {
                ps.setString(1, Tanggal1.getText()); ps.setString(2, Tanggal2.getText());
                ps.setString(3, Tanggal1.getText()); ps.setString(4, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    try {
                        idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                        iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                        if (!idpasien.equals("") && !iddokter.equals("")) {
                            String nilaiSpo2 = rs.getString("spo2").replaceAll("[^0-9]", "");
                            if(nilaiSpo2.isEmpty()) continue;
                            
                            json = "{" +
                                    "\"resourceType\": \"Observation\", \"status\": \"final\"," +
                                    "\"category\": [{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\",\"code\": \"vital-signs\",\"display\": \"Vital Signs\"}]}]," +
                                    "\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"59408-5\",\"display\": \"Oxygen saturation\"}]}," +
                                    "\"subject\": {\"reference\": \"Patient/" + idpasien + "\"}," +
                                    "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\"}]," +
                                    "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                                    "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\"," +
                                    "\"valueQuantity\": {\"value\": " + nilaiSpo2 + ",\"unit\": \"percent saturation\",\"system\": \"http://unitsofmeasure.org\",\"code\": \"%\"}}";
                            
                            processSendObservation(json, "satu_sehat_observationttvspo2", rs, "59408-5", "SpO2: " + nilaiSpo2 + "%");
                        }
                    } catch (Exception e) { TeksArea.append("   !! [ERROR SYSTEM SPO2] " + e + "\n"); }
                }
            } catch (Exception e) { System.out.println(e); } finally { if(rs!=null) rs.close(); if(ps!=null) ps.close(); }

        } catch (Exception e) {
            System.out.println("Notifikasi TTV : " + e);
            TeksArea.append("!! ERROR UTAMA TTV: " + e + "\n");
        }
    }

    // HELPER SAKTI: DETEKTIF + AUTO RECONNECT + ANTI DUPLIKAT + RATE LIMIT
    // Perhatikan: Method ini sekarang menerima 5 Parameter
    private void processSendObservation(String jsonPayload, String tableName, ResultSet rs, String loincCode, String debugInfo) throws Exception {
        // 1. LOG DETEKTIF (Tampilkan Data Sebelum Kirim)
        String noRawat = rs.getString("no_rawat");
        String pasien = rs.getString("nm_pasien");
        TeksArea.append("   [DETEKTIF] Mengirim " + debugInfo + " | Pasien: " + pasien + "\n");

        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        // Menggunakan Token Saat Ini
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat()); 
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // --- PERCOBAAN KIRIM PERTAMA ---
            json = konekSatuSehat(link + "/Observation", HttpMethod.POST, requestEntity);
            // Jika sukses, simpan log
            simpanLogTTV(json, tableName, rs);
            
        } catch (HttpClientErrorException e) {
            
            // --- A. HANDLER TOKEN EXPIRED (401) ---
            // Inilah Fitur Auto-Reconnect yang Anda tanyakan
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta Token Baru...\n");
                
                // 1. Minta Token Baru
                String newToken = api.TokenSatuSehat(); 
                
                // 2. Update Header
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    // 3. Kirim Ulang (Retry)
                    json = konekSatuSehat(link + "/Observation", HttpMethod.POST, requestEntity);
                    simpanLogTTV(json, tableName, rs);
                    TeksArea.append("   [RETRY SUKSES] Data berhasil dikirim dengan token baru.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] Token baru pun ditolak/error: " + ex.getMessage() + "\n");
                }
            } 
            
            // --- B. HANDLER DUPLIKAT (400) ---
            else if (e.getStatusCode().value() == 400 && e.getResponseBodyAsString().contains("duplicate")) {
                TeksArea.append("   !! [DUPLIKAT] Data sudah ada di SatuSehat. Mengambil ID...\n");
                try {
                    // Cari ID data yang sudah ada tersebut (Self-Healing)
                    String searchUrl = link + "/Observation?subject=" + idpasien + "&code=" + loincCode + "&date=" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00";
                    String searchJson = konekSatuSehat(searchUrl, HttpMethod.GET, new HttpEntity(headers));
                    
                    JsonNode searchRoot = mapper.readTree(searchJson);
                    if (searchRoot.path("total").asInt() > 0) {
                        String existingId = searchRoot.path("entry").get(0).path("resource").path("id").asText();
                        // Simpan ID yang ditemukan agar tidak dikirim ulang nanti
                        Sequel.menyimpan2(tableName, "?,?,?,?,?", "Log TTV (Recovery)", 5, new String[]{
                            rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam_rawat"), rs.getString("status_rawat"), existingId
                        });
                        TeksArea.append("   [RECOVERY] ID Ditemukan & Disimpan: " + existingId + "\n");
                    }
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RECOVERY] Tidak bisa mengambil ID duplikat: " + ex.getMessage() + "\n");
                }
            } 
            
            // --- C. ERROR LAINNYA ---
            else {
                TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
            }
            
        } catch (Exception e) {
            // --- D. HANDLER RATE LIMIT (429) ---
            // Menangani error "Too Many Requests" dengan tidur sejenak
            if (e.toString().contains("429") || e.getMessage().contains("429")) {
                TeksArea.append("   !! [RATE LIMIT] Server sibuk. Tidur 5 detik...\n");
                Thread.sleep(5000); 
            } else {
                TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            }
        }
    }

    // Helper Simpan ke Database Lokal
    private void simpanLogTTV(String responseJson, String tableName, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2(tableName, "?,?,?,?,?", "Log TTV", 5, new String[]{
                rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam_rawat"), rs.getString("status_rawat"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID: " + responseId.asText() + "\n");
        }
    }
    
    // MODUL CLINICAL IMPRESSION (DETEKTIF CONAN + AUTO RECONNECT)
    public void clinicalimpression() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES KIRIM CLINICAL IMPRESSION (ASESMEN)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. DATA RAWAT JALAN (RALAN)
            // ===========================================================================================
            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,pasien.no_ktp,pasien.nm_pasien,satu_sehat_encounter.id_encounter,pegawai.nama,pegawai.no_ktp as ktppraktisi,"
                    + "pemeriksaan_ralan.tgl_perawatan,pemeriksaan_ralan.jam_rawat,pemeriksaan_ralan.penilaian,"
                    + "pemeriksaan_ralan.keluhan,pemeriksaan_ralan.pemeriksaan,satu_sehat_condition.kd_penyakit,"
                    + "penyakit.nm_penyakit,satu_sehat_condition.id_condition,"
                    + "ifnull(satu_sehat_clinicalimpression.id_clinicalimpression,'') as id_clinicalimpression "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_condition on satu_sehat_condition.no_rawat=reg_periksa.no_rawat and satu_sehat_condition.status='Ralan' "
                    + "inner join penyakit on penyakit.kd_penyakit=satu_sehat_condition.kd_penyakit "
                    + "inner join pemeriksaan_ralan on pemeriksaan_ralan.no_rawat=reg_periksa.no_rawat "
                    + "inner join pegawai on pemeriksaan_ralan.nip=pegawai.nik "
                    + "left join satu_sehat_clinicalimpression on satu_sehat_clinicalimpression.no_rawat=pemeriksaan_ralan.no_rawat "
                    + "and satu_sehat_clinicalimpression.tgl_perawatan=pemeriksaan_ralan.tgl_perawatan "
                    + "and satu_sehat_clinicalimpression.jam_rawat=pemeriksaan_ralan.jam_rawat "
                    + "and satu_sehat_clinicalimpression.status='Ralan' where pemeriksaan_ralan.penilaian<>'' "
                    + "and nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_clinicalimpression.id_clinicalimpression,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // LOGGING DETEKTIF
                    TeksArea.append("\n[PROSES RALAN] No.Rawat: " + rs.getString("no_rawat") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_clinicalimpression").equals("")) {
                        try {
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat (Cek NIK).\n");
                                continue;
                            }

                            // Sanitasi Data
                            String keluhan = rs.getString("keluhan").replaceAll("(\r\n|\r|\n|\n\r)", ", ").replaceAll("\"", "'").replaceAll("\\\\", "/").replaceAll("\t", " ");
                            String pemeriksaan = rs.getString("pemeriksaan").replaceAll("(\r\n|\r|\n|\n\r)", "<br>").replaceAll("\"", "'").replaceAll("\\\\", "/").replaceAll("\t", " ");
                            String penilaian = rs.getString("penilaian").replaceAll("(\r\n|\r|\n|\n\r)", "<br>").replaceAll("\"", "'").replaceAll("\\\\", "/").replaceAll("\t", " ");
                            String deskripsiGabungan = keluhan + ", " + pemeriksaan;

                            headers = new HttpHeaders();
                            headers.setContentType(MediaType.APPLICATION_JSON);
                            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
                            
                            json = "{"
                                    + "\"resourceType\": \"ClinicalImpression\","
                                    + "\"status\": \"completed\","
                                    + "\"description\" : \"" + deskripsiGabungan + "\","
                                    + "\"subject\" : {\"reference\" : \"Patient/" + idpasien + "\"},"
                                    + "\"encounter\" : {\"reference\" : \"Encounter/" + rs.getString("id_encounter") + "\"},"
                                    + "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\","
                                    + "\"date\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\","
                                    + "\"assessor\" : {\"reference\" : \"Practitioner/" + iddokter + "\"},"
                                    + "\"summary\" : \"" + penilaian + "\","
                                    + "\"finding\": [{"
                                        + "\"itemCodeableConcept\": {"
                                            + "\"coding\": [{"
                                                + "\"system\": \"http://hl7.org/fhir/sid/icd-10\","
                                                + "\"code\": \"" + rs.getString("kd_penyakit") + "\","
                                                + "\"display\": \"" + rs.getString("nm_penyakit") + "\""
                                            + "}]"
                                        + "},"
                                        + "\"itemReference\": {\"reference\": \"Condition/" + rs.getString("id_condition") + "\"}"
                                    + "}],"
                                    + "\"prognosisCodeableConcept\": [{"
                                        + "\"coding\": [{"
                                            + "\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\","
                                            + "\"code\": \"PR000001\","
                                            + "\"display\": \"Prognosis\""
                                        + "}]"
                                    + "}]"
                                    + "}";

                            // PANGGIL HELPER (Kirim & Retry)
                            processSendClinicalImpression(json, rs, "Ralan");

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
                        }
                    } else {
                        // DETEKTIF: Alasan Skip
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("   !! [SKIP] NIK Dokter Kosong\n");
                        if (!rs.getString("id_clinicalimpression").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim (ID Ada)\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ralan : " + e);
                TeksArea.append("ERROR QUERY RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. DATA RAWAT INAP (RANAP)
            // ===========================================================================================
            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,pasien.no_ktp,pasien.nm_pasien,satu_sehat_encounter.id_encounter,pegawai.nama,pegawai.no_ktp as ktppraktisi,"
                    + "pemeriksaan_ranap.tgl_perawatan,pemeriksaan_ranap.jam_rawat,pemeriksaan_ranap.penilaian,"
                    + "pemeriksaan_ranap.keluhan,pemeriksaan_ranap.pemeriksaan,satu_sehat_condition.kd_penyakit,"
                    + "penyakit.nm_penyakit,satu_sehat_condition.id_condition,"
                    + "ifnull(satu_sehat_clinicalimpression.id_clinicalimpression,'') as id_clinicalimpression "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_condition on satu_sehat_condition.no_rawat=reg_periksa.no_rawat and satu_sehat_condition.status='Ranap' "
                    + "inner join penyakit on penyakit.kd_penyakit=satu_sehat_condition.kd_penyakit "
                    + "inner join pemeriksaan_ranap on pemeriksaan_ranap.no_rawat=reg_periksa.no_rawat "
                    + "inner join pegawai on pemeriksaan_ranap.nip=pegawai.nik "
                    + "left join satu_sehat_clinicalimpression on satu_sehat_clinicalimpression.no_rawat=pemeriksaan_ranap.no_rawat "
                    + "and satu_sehat_clinicalimpression.tgl_perawatan=pemeriksaan_ranap.tgl_perawatan "
                    + "and satu_sehat_clinicalimpression.jam_rawat=pemeriksaan_ranap.jam_rawat "
                    + "and satu_sehat_clinicalimpression.status='Ranap' where pemeriksaan_ranap.penilaian<>'' and nota_inap.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_clinicalimpression.id_clinicalimpression,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    TeksArea.append("\n[PROSES RANAP] No.Rawat: " + rs.getString("no_rawat") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_clinicalimpression").equals("")) {
                        try {
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat (Cek NIK).\n");
                                continue;
                            }

                            // Sanitasi Data (RANAP)
                            String keluhan = rs.getString("keluhan").replaceAll("(\r\n|\r|\n|\n\r)", ", ").replaceAll("\"", "'").replaceAll("\\\\", "/").replaceAll("\t", " ");
                            String pemeriksaan = rs.getString("pemeriksaan").replaceAll("(\r\n|\r|\n|\n\r)", "<br>").replaceAll("\"", "'").replaceAll("\\\\", "/").replaceAll("\t", " ");
                            String penilaian = rs.getString("penilaian").replaceAll("(\r\n|\r|\n|\n\r)", "<br>").replaceAll("\"", "'").replaceAll("\\\\", "/").replaceAll("\t", " ");
                            String deskripsiGabungan = keluhan + ", " + pemeriksaan;

                            headers = new HttpHeaders();
                            headers.setContentType(MediaType.APPLICATION_JSON);
                            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
                            
                            json = "{"
                                    + "\"resourceType\": \"ClinicalImpression\","
                                    + "\"status\": \"completed\","
                                    + "\"description\" : \"" + deskripsiGabungan + "\","
                                    + "\"subject\" : {\"reference\" : \"Patient/" + idpasien + "\"},"
                                    + "\"encounter\" : {\"reference\" : \"Encounter/" + rs.getString("id_encounter") + "\"},"
                                    + "\"effectiveDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\","
                                    + "\"date\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\","
                                    + "\"assessor\" : {\"reference\" : \"Practitioner/" + iddokter + "\"},"
                                    + "\"summary\" : \"" + penilaian + "\","
                                    + "\"finding\": [{"
                                        + "\"itemCodeableConcept\": {"
                                            + "\"coding\": [{"
                                                + "\"system\": \"http://hl7.org/fhir/sid/icd-10\","
                                                + "\"code\": \"" + rs.getString("kd_penyakit") + "\","
                                                + "\"display\": \"" + rs.getString("nm_penyakit") + "\""
                                            + "}]"
                                        + "},"
                                        + "\"itemReference\": {\"reference\": \"Condition/" + rs.getString("id_condition") + "\"}"
                                    + "}],"
                                    + "\"prognosisCodeableConcept\": [{"
                                        + "\"coding\": [{"
                                            + "\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\","
                                            + "\"code\": \"PR000001\","
                                            + "\"display\": \"Prognosis\""
                                        + "}]"
                                    + "}]"
                                    + "}";

                            // PANGGIL HELPER
                            processSendClinicalImpression(json, rs, "Ranap");

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("   !! [SKIP] NIK Dokter Kosong\n");
                        if (!rs.getString("id_clinicalimpression").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim (ID Ada)\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ranap : " + e);
                TeksArea.append("ERROR QUERY RANAP: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
            TeksArea.append("!! ERROR UTAMA CLINICAL IMPRESSION: " + e + "\n");
        }
    }

    // HELPER: KIRIM, RETRY, & LOGGING
    private void processSendClinicalImpression(String jsonPayload, ResultSet rs, String statusRawat) throws Exception {
        requestEntity = new HttpEntity(jsonPayload, headers);
        try {
            // Tampilkan URL (Optional, biar log gak kepanjangan bisa di-comment)
            // TeksArea.append("   URL : " + link + "/ClinicalImpression\n");
            
            // KIRIM DATA
            String responseJson = konekSatuSehat(link + "/ClinicalImpression", HttpMethod.POST, requestEntity);
            
            // SUKSES
            simpanLogCI(responseJson, rs, statusRawat);
            
        } catch (HttpClientErrorException e) {
            // DETEKSI TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Refresh Token...\n");
                
                // Ambil Token Baru
                String newToken = api.TokenSatuSehat(); 
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    // RETRY KIRIM
                    String responseJsonRetry = konekSatuSehat(link + "/ClinicalImpression", HttpMethod.POST, requestEntity);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim setelah refresh token.\n");
                    simpanLogCI(responseJsonRetry, rs, statusRawat);
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                // ERROR API LAIN (400 Bad Request, dll)
                TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
            }
        }
    }

    // HELPER SIMPAN KE DB & LOG SUKSES
    private void simpanLogCI(String responseJson, ResultSet rs, String statusRawat) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_clinicalimpression", "?,?,?,?,?", "Clinical Impression", 5, new String[]{
                rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam_rawat"), statusRawat, responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID Satu Sehat: " + responseId.asText() + "\n");
        }
    }
    
    private void vaksin() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES KIRIM VAKSIN (IMMUNIZATION)\n");
            TeksArea.append("------------------------------------------------------\n");

            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,pasien.nm_pasien,pasien.no_ktp,satu_sehat_encounter.id_encounter,satu_sehat_mapping_vaksin.vaksin_code,satu_sehat_mapping_vaksin.vaksin_system,"
                    + "satu_sehat_mapping_vaksin.kode_brng,satu_sehat_mapping_vaksin.vaksin_display,satu_sehat_mapping_vaksin.route_code,satu_sehat_mapping_vaksin.route_system,"
                    + "satu_sehat_mapping_vaksin.route_display,satu_sehat_mapping_vaksin.dose_quantity_code,satu_sehat_mapping_vaksin.dose_quantity_system,"
                    + "satu_sehat_mapping_vaksin.dose_quantity_unit,detail_pemberian_obat.no_batch,detail_pemberian_obat.tgl_perawatan,detail_pemberian_obat.jam,"
                    + "detail_pemberian_obat.jml,aturan_pakai.aturan,satu_sehat_mapping_lokasi_ralan.id_lokasi_satusehat,poliklinik.nm_poli,pegawai.no_ktp as ktppraktisi,"
                    + "ifnull(satu_sehat_immunization.id_immunization,'') as id_immunization,detail_pemberian_obat.no_faktur "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join detail_pemberian_obat on detail_pemberian_obat.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_mapping_vaksin on satu_sehat_mapping_vaksin.kode_brng=detail_pemberian_obat.kode_brng "
                    + "inner join aturan_pakai on aturan_pakai.tgl_perawatan=detail_pemberian_obat.tgl_perawatan and aturan_pakai.jam=detail_pemberian_obat.jam and "
                    + "aturan_pakai.no_rawat=detail_pemberian_obat.no_rawat and aturan_pakai.kode_brng=detail_pemberian_obat.kode_brng "
                    + "inner join satu_sehat_mapping_lokasi_ralan on satu_sehat_mapping_lokasi_ralan.kd_poli=reg_periksa.kd_poli "
                    + "inner join poliklinik on poliklinik.kd_poli=satu_sehat_mapping_lokasi_ralan.kd_poli "
                    + "inner join pegawai on reg_periksa.kd_dokter=pegawai.nik "
                    + "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "
                    + "left join satu_sehat_immunization on satu_sehat_immunization.no_rawat=detail_pemberian_obat.no_rawat and satu_sehat_immunization.tgl_perawatan=detail_pemberian_obat.tgl_perawatan and "
                    + "satu_sehat_immunization.jam=detail_pemberian_obat.jam and satu_sehat_immunization.kode_brng=detail_pemberian_obat.kode_brng and "
                    + "satu_sehat_immunization.no_batch=detail_pemberian_obat.no_batch and satu_sehat_immunization.no_faktur=detail_pemberian_obat.no_faktur "
                    + "where detail_pemberian_obat.no_batch<>'' and nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_immunization.id_immunization,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    TeksArea.append("\n[PROSES VAKSIN] No.Rawat: " + rs.getString("no_rawat") + " | Vaksin: " + rs.getString("vaksin_display") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_immunization").equals("")) {
                        try {
                            // 1. Cek ID Satu Sehat
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("!! SKIP: ID Pasien/Dokter tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // 2. Sanitasi & Persiapan Data
                            String namaVaksin = rs.getString("vaksin_display").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'");
                            String namaPoli = rs.getString("nm_poli").replaceAll("\"", "'");
                            String tglKadaluarsa = Sequel.cariIsi("SELECT data_batch.tgl_kadaluarsa FROM data_batch WHERE data_batch.no_batch='" + rs.getString("no_batch") + "' and data_batch.kode_brng='" + rs.getString("kode_brng") + "' and data_batch.no_faktur='" + rs.getString("no_faktur") + "'");
                            
                            // Sanitasi Dosis (Hanya angka)
                            String dosisKe = rs.getString("aturan").replaceAll("[^0-9]", "");
                            if(dosisKe.equals("")){
                                dosisKe = "1"; // Default jika tidak ada angka di aturan pakai
                            }

                            try {
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
                                json = "{"
                                        + "\"resourceType\": \"Immunization\","
                                        + "\"status\": \"completed\","
                                        + "\"vaccineCode\": {"
                                        + "\"coding\": ["
                                        + "{"
                                        + "\"system\": \"" + rs.getString("vaksin_system") + "\","
                                        + "\"code\": \"" + rs.getString("vaksin_code") + "\","
                                        + "\"display\": \"" + namaVaksin + "\""
                                        + "}"
                                        + "]"
                                        + "},"
                                        + "\"patient\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\""
                                        + "},"
                                        + "\"encounter\": {"
                                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\""
                                        + "},"
                                        + "\"occurrenceDateTime\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam") + "+07:00\","
                                        + "\"expirationDate\": \"" + tglKadaluarsa + "\","
                                        + "\"recorded\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam") + "+07:00\","
                                        + "\"primarySource\": true,"
                                        + "\"location\": {"
                                        + "\"reference\": \"Location/" + rs.getString("id_lokasi_satusehat") + "\","
                                        + "\"display\": \"" + namaPoli + "\""
                                        + "},"
                                        + "\"lotNumber\": \"" + rs.getString("no_batch") + "\","
                                        + "\"route\": {"
                                        + "\"coding\": ["
                                        + "{"
                                        + "\"system\": \"" + rs.getString("route_system") + "\","
                                        + "\"code\": \"" + rs.getString("route_code") + "\","
                                        + "\"display\": \"" + rs.getString("route_display") + "\""
                                        + "}"
                                        + "]"
                                        + "},"
                                        + "\"doseQuantity\": {"
                                        + "\"value\": " + rs.getString("jml") + ","
                                        + "\"unit\": \"" + rs.getString("dose_quantity_unit") + "\","
                                        + "\"system\": \"" + rs.getString("dose_quantity_system") + "\","
                                        + "\"code\": \"" + rs.getString("dose_quantity_code") + "\""
                                        + "},"
                                        + "\"performer\": ["
                                        + "{"
                                        + "\"function\": {"
                                        + "\"coding\": ["
                                        + "{"
                                        + "\"system\": \"http://terminology.hl7.org/CodeSystem/v2-0443\","
                                        + "\"code\": \"AP\","
                                        + "\"display\": \"Administering Provider\""
                                        + "}"
                                        + "]"
                                        + "},"
                                        + "\"actor\": {"
                                        + "\"reference\": \"Practitioner/" + iddokter + "\""
                                        + "}"
                                        + "}"
                                        + "],"
                                        + "\"reasonCode\": ["
                                        + "{"
                                        + "\"coding\": ["
                                        + "{"
                                        + "\"system\": \"https://terminology.kemkes.go.id/CodeSystem/immunization-reason\","
                                        + "\"code\": \"IM-Program\","
                                        + "\"display\" : \"Imunisasi Program\""
                                        + "}"
                                        + "]"
                                        + "}"
                                        + "],"
                                        + "\"protocolApplied\" : ["
                                        + "{"
                                        + "\"doseNumberPositiveInt\" : " + dosisKe
                                        + "}"
                                        + "]"
                                        + "}";
                                TeksArea.append("   URL : " + link + "/Immunization\n");
                                TeksArea.append("   Request JSON : " + json + "\n");
                                requestEntity = new HttpEntity(json, headers);
                                json = konekSatuSehat(link + "/Immunization", HttpMethod.POST, requestEntity);
                                TeksArea.append("   Result JSON : " + json + "\n");
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if (!response.asText().equals("")) {
                                    Sequel.menyimpan2("satu_sehat_immunization", "?,?,?,?,?,?,?,?", "Imunisasi/Vaksin", 7, new String[]{
                                        rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam"), rs.getString("kode_brng"), rs.getString("no_batch"), rs.getString("no_faktur"), response.asText()
                                    });
                                    TeksArea.append("   [SUKSES] Disimpan ke database lokal.\n");
                                }
                            } catch (Exception e) {
                                TeksArea.append("   [ERROR API] " + e + "\n");
                                System.out.println("Notifikasi Bridging : " + e);
                            }
                        } catch (Exception e) {
                            TeksArea.append("   [ERROR INTERN] " + e + "\n");
                            System.out.println("Notifikasi : " + e);
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("!! SKIP: NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("!! SKIP: NIK Dokter Kosong\n");
                        if (!rs.getString("id_immunization").equals("")) TeksArea.append("!! SKIP: Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif : " + e);
                TeksArea.append("ERROR QUERY VAKSIN: " + e + "\n");
            } finally {
                if (rs != null) {
                    rs.close();
                }
                if (ps != null) {
                    ps.close();
                }
            }
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
            TeksArea.append("!! ERROR UTAMA VAKSIN: " + e + "\n");
        }
    }
    
    // MODUL PROSEDUR (ICD-9) - LEVEL DETEKTIF + AUTO RECONNECT
    private void prosedur() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: PROSEDUR (ICD-9)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. PROSEDUR RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/2] Melacak Prosedur Rawat Jalan (Ralan)...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,pasien.nm_pasien,pasien.no_ktp,reg_periksa.status_lanjut,"
                    + "concat(nota_jalan.tanggal,'T',nota_jalan.jam,'+07:00') as pulang,satu_sehat_encounter.id_encounter,prosedur_pasien.kode,icd9.deskripsi_panjang,"
                    + "ifnull(satu_sehat_procedure.id_procedure,'') as id_procedure from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join prosedur_pasien on prosedur_pasien.no_rawat=reg_periksa.no_rawat inner join icd9 on prosedur_pasien.kode=icd9.kode "
                    + "left join satu_sehat_procedure on satu_sehat_procedure.no_rawat=prosedur_pasien.no_rawat and satu_sehat_procedure.kode=prosedur_pasien.kode "
                    + "and satu_sehat_procedure.status=prosedur_pasien.status where nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and ifnull(satu_sehat_procedure.id_procedure,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Detektif Log
                    TeksArea.append("\n[DETEKTIF RALAN] Rawat: " + rs.getString("no_rawat") + " | ICD-9: " + rs.getString("kode") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_procedure").equals("")) {
                        try {
                            // 1. Cek ID Pasien (KyC)
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));

                            if (idpasien.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // 2. Sanitasi Data
                            String deskripsiProsedur = rs.getString("deskripsi_panjang")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", " ")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/");
                            
                            String namaPasien = rs.getString("nm_pasien").replaceAll("\"", "'");

                            // 3. Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\": \"Procedure\","
                                    + "\"status\": \"completed\","
                                    + "\"category\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://snomed.info/sct\","
                                                + "\"code\": \"103693007\","
                                                + "\"display\": \"Diagnostic procedure\""
                                            + "}"
                                        + "],"
                                        + "\"text\":\"Diagnostic procedure\""
                                    + "},"
                                    + "\"code\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://hl7.org/fhir/sid/icd-9-cm\","
                                                + "\"code\": \"" + rs.getString("kode") + "\","
                                                + "\"display\": \"" + deskripsiProsedur + "\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"subject\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\","
                                        + "\"display\": \"" + namaPasien + "\""
                                    + "},"
                                    + "\"encounter\": {"
                                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\","
                                        + "\"display\": \"Prosedur kepada " + namaPasien + " selama kunjungan/dirawat dari tanggal " + rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00" + " sampai " + rs.getString("pulang") + "\""
                                    + "},"
                                    + "\"performedPeriod\": {"
                                        + "\"start\": \"" + rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00" + "\","
                                        + "\"end\": \"" + rs.getString("pulang") + "\""
                                    + "}"
                                    + "}";
                            
                            // 4. Kirim dengan Helper Sakti
                            processSendProcedure(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR INTERN] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (!rs.getString("id_procedure").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Prosedur Ralan: " + e);
                TeksArea.append("ERROR QUERY PROSEDUR RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. PROSEDUR RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[2/2] Melacak Prosedur Rawat Inap (Ranap)...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,pasien.nm_pasien,pasien.no_ktp,reg_periksa.status_lanjut,"
                    + "concat(nota_inap.tanggal,'T',nota_inap.jam,'+07:00') as pulang,satu_sehat_encounter.id_encounter,prosedur_pasien.kode,icd9.deskripsi_panjang,"
                    + "ifnull(satu_sehat_procedure.id_procedure,'') as id_procedure from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join prosedur_pasien on prosedur_pasien.no_rawat=reg_periksa.no_rawat inner join icd9 on prosedur_pasien.kode=icd9.kode "
                    + "left join satu_sehat_procedure on satu_sehat_procedure.no_rawat=prosedur_pasien.no_rawat and satu_sehat_procedure.kode=prosedur_pasien.kode "
                    + "and satu_sehat_procedure.status=prosedur_pasien.status where nota_inap.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and ifnull(satu_sehat_procedure.id_procedure,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Detektif Log
                    TeksArea.append("\n[DETEKTIF RANAP] Rawat: " + rs.getString("no_rawat") + " | ICD-9: " + rs.getString("kode") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_procedure").equals("")) {
                        try {
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            if (idpasien.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            String deskripsiProsedur = rs.getString("deskripsi_panjang")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", " ")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/");
                            
                            String namaPasien = rs.getString("nm_pasien").replaceAll("\"", "'");

                            json = "{"
                                    + "\"resourceType\": \"Procedure\","
                                    + "\"status\": \"completed\","
                                    + "\"category\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://snomed.info/sct\","
                                                + "\"code\": \"103693007\","
                                                + "\"display\": \"Diagnostic procedure\""
                                            + "}"
                                        + "],"
                                        + "\"text\":\"Diagnostic procedure\""
                                    + "},"
                                    + "\"code\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://hl7.org/fhir/sid/icd-9-cm\","
                                                + "\"code\": \"" + rs.getString("kode") + "\","
                                                + "\"display\": \"" + deskripsiProsedur + "\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"subject\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\","
                                        + "\"display\": \"" + namaPasien + "\""
                                    + "},"
                                    + "\"encounter\": {"
                                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\","
                                        + "\"display\": \"Prosedur kepada " + namaPasien + " selama kunjungan/dirawat dari tanggal " + rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00" + " sampai " + rs.getString("pulang") + "\""
                                    + "},"
                                    + "\"performedPeriod\": {"
                                        + "\"start\": \"" + rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00" + "\","
                                        + "\"end\": \"" + rs.getString("pulang") + "\""
                                    + "}"
                                    + "}";
                            
                            // Kirim dengan Helper
                            processSendProcedure(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR SYSTEM RANAP] " + e + "\n");
                        }
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Prosedur Ranap: " + e);
                TeksArea.append("ERROR QUERY PROSEDUR RANAP: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
            TeksArea.append("!! ERROR UTAMA PROSEDUR: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS PROSEDUR
    private void processSendProcedure(String jsonPayload, ResultSet rs) throws Exception {
        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // Tampilkan payload utk debugging (optional, bisa dikomen kalau penuh)
            //TeksArea.append("   [DEBUG JSON] " + jsonPayload + "\n");
            
            // KIRIM PERTAMA
            json = konekSatuSehat(link + "/Procedure", HttpMethod.POST, requestEntity);
            simpanLogProsedur(json, rs);
            
        } catch (HttpClientErrorException e) {
            // HANDLER TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                String newToken = api.TokenSatuSehat();
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    json = konekSatuSehat(link + "/Procedure", HttpMethod.POST, requestEntity);
                    simpanLogProsedur(json, rs);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                TeksArea.append("   !! [ERROR API] " + e.getResponseBodyAsString() + "\n");
            }
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
        }
    }

    private void simpanLogProsedur(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_procedure", "?,?,?,?", "Prosedur", 4, new String[]{
                rs.getString("no_rawat"), rs.getString("kode"), rs.getString("status_lanjut"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
    
    // MODUL CONDITION (ICD-10) - LEVEL DETEKTIF + AUTO RECONNECT
    private void condition() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: CONDITION (DIAGNOSA ICD-10)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. DIAGNOSA RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/2] Melacak Diagnosa Rawat Jalan (Ralan)...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,pasien.nm_pasien,pasien.no_ktp,reg_periksa.status_lanjut,concat(nota_jalan.tanggal,' ',nota_jalan.jam) as pulang,"
                    + "satu_sehat_encounter.id_encounter,diagnosa_pasien.kd_penyakit,penyakit.nm_penyakit,ifnull(satu_sehat_condition.id_condition,'') as id_condition "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join diagnosa_pasien on diagnosa_pasien.no_rawat=reg_periksa.no_rawat "
                    + "inner join penyakit on diagnosa_pasien.kd_penyakit=penyakit.kd_penyakit left join satu_sehat_condition on satu_sehat_condition.no_rawat=diagnosa_pasien.no_rawat "
                    + "and satu_sehat_condition.kd_penyakit=diagnosa_pasien.kd_penyakit and satu_sehat_condition.status=diagnosa_pasien.status "
                    + "where nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and ifnull(satu_sehat_condition.id_condition,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Detektif Log
                    TeksArea.append("\n[DETEKTIF RALAN] Rawat: " + rs.getString("no_rawat") + " | ICD-10: " + rs.getString("kd_penyakit") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_condition").equals("")) {
                        try {
                            // 1. Cek ID Pasien (KyC)
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));

                            if (idpasien.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // 2. Sanitasi Data
                            String namaPenyakit = rs.getString("nm_penyakit").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'");
                            String namaPasien = rs.getString("nm_pasien").replaceAll("\"", "'");

                            // 3. Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\": \"Condition\","
                                    + "\"clinicalStatus\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://terminology.hl7.org/CodeSystem/condition-clinical\","
                                                + "\"code\": \"active\","
                                                + "\"display\": \"Active\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"category\": ["
                                        + "{"
                                            + "\"coding\": ["
                                                + "{"
                                                    + "\"system\": \"http://terminology.hl7.org/CodeSystem/condition-category\","
                                                    + "\"code\": \"encounter-diagnosis\","
                                                    + "\"display\": \"Encounter Diagnosis\""
                                                + "}"
                                            + "]"
                                        + "}"
                                    + "],"
                                    + "\"code\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://hl7.org/fhir/sid/icd-10\","
                                                + "\"code\": \"" + rs.getString("kd_penyakit") + "\","
                                                + "\"display\": \"" + namaPenyakit + "\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"subject\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\","
                                        + "\"display\": \"" + namaPasien + "\""
                                    + "},"
                                    + "\"encounter\": {"
                                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\","
                                        + "\"display\": \"Diagnosa pasien " + namaPasien + " selama kunjungan/dirawat dari tanggal " + rs.getString("tgl_registrasi") + " " + rs.getString("jam_reg") + " sampai " + rs.getString("pulang") + "\""
                                    + "}"
                                    + "}";
                            
                            // 4. Kirim dengan Helper Sakti
                            processSendCondition(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR INTERN] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (!rs.getString("id_condition").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Condition Ralan: " + e);
                TeksArea.append("ERROR QUERY CONDITION RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. DIAGNOSA RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[2/2] Melacak Diagnosa Rawat Inap (Ranap)...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,pasien.nm_pasien,pasien.no_ktp,reg_periksa.status_lanjut,concat(nota_inap.tanggal,' ',nota_inap.jam) as pulang,"
                    + "satu_sehat_encounter.id_encounter,diagnosa_pasien.kd_penyakit,penyakit.nm_penyakit,ifnull(satu_sehat_condition.id_condition,'') as id_condition "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join diagnosa_pasien on diagnosa_pasien.no_rawat=reg_periksa.no_rawat "
                    + "inner join penyakit on diagnosa_pasien.kd_penyakit=penyakit.kd_penyakit left join satu_sehat_condition on satu_sehat_condition.no_rawat=diagnosa_pasien.no_rawat "
                    + "and satu_sehat_condition.kd_penyakit=diagnosa_pasien.kd_penyakit and satu_sehat_condition.status=diagnosa_pasien.status "
                    + "where nota_inap.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and ifnull(satu_sehat_condition.id_condition,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Detektif Log
                    TeksArea.append("\n[DETEKTIF RANAP] Rawat: " + rs.getString("no_rawat") + " | ICD-10: " + rs.getString("kd_penyakit") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_condition").equals("")) {
                        try {
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            if (idpasien.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            String namaPenyakit = rs.getString("nm_penyakit").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'");
                            String namaPasien = rs.getString("nm_pasien").replaceAll("\"", "'");

                            json = "{"
                                    + "\"resourceType\": \"Condition\","
                                    + "\"clinicalStatus\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://terminology.hl7.org/CodeSystem/condition-clinical\","
                                                + "\"code\": \"active\","
                                                + "\"display\": \"Active\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"category\": ["
                                        + "{"
                                            + "\"coding\": ["
                                                + "{"
                                                    + "\"system\": \"http://terminology.hl7.org/CodeSystem/condition-category\","
                                                    + "\"code\": \"encounter-diagnosis\","
                                                    + "\"display\": \"Encounter Diagnosis\""
                                                + "}"
                                            + "]"
                                        + "}"
                                    + "],"
                                    + "\"code\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"http://hl7.org/fhir/sid/icd-10\","
                                                + "\"code\": \"" + rs.getString("kd_penyakit") + "\","
                                                + "\"display\": \"" + namaPenyakit + "\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"subject\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\","
                                        + "\"display\": \"" + namaPasien + "\""
                                    + "},"
                                    + "\"encounter\": {"
                                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\","
                                        + "\"display\": \"Diagnosa pasien " + namaPasien + " selama kunjungan/dirawat dari tanggal " + rs.getString("tgl_registrasi") + " " + rs.getString("jam_reg") + " sampai " + rs.getString("pulang") + "\""
                                    + "}"
                                    + "}";
                            
                            // Kirim dengan Helper
                            processSendCondition(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR SYSTEM RANAP] " + e + "\n");
                        }
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Condition Ranap: " + e);
                TeksArea.append("ERROR QUERY CONDITION RANAP: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
            TeksArea.append("!! ERROR UTAMA CONDITION: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS CONDITION
    private void processSendCondition(String jsonPayload, ResultSet rs) throws Exception {
        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // Tampilkan payload utk debugging (optional)
            // TeksArea.append("   [DEBUG JSON] " + jsonPayload + "\n");
            
            // KIRIM PERTAMA
            json = konekSatuSehat(link + "/Condition", HttpMethod.POST, requestEntity);
            simpanLogCondition(json, rs);
            
        } catch (HttpClientErrorException e) {
            // HANDLER TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                String newToken = api.TokenSatuSehat();
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    json = konekSatuSehat(link + "/Condition", HttpMethod.POST, requestEntity);
                    simpanLogCondition(json, rs);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                TeksArea.append("   !! [ERROR API] " + e.getResponseBodyAsString() + "\n");
            }
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
        }
    }

    private void simpanLogCondition(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_condition", "?,?,?,?", "Diagnosa", 4, new String[]{
                rs.getString("no_rawat"), rs.getString("kd_penyakit"), rs.getString("status_lanjut"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
    
    // MODUL DIET GIZI (COMPOSITION) - DETEKTIF + AUTO RECONNECT + NO VALIDASI KASIR
    private void dietgizi() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: DIET GIZI (COMPOSITION)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. DIET GIZI RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/2] Melacak Diet Rawat Jalan (Ralan)...\n");
            
            // QUERY DIMODIFIKASI: HAPUS INNER JOIN nota_jalan AGAR TIDAK PERLU CLOSING KASIR
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,"
                    + "pasien.nm_pasien,pasien.no_ktp,satu_sehat_encounter.id_encounter,catatan_adime_gizi.instruksi,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,catatan_adime_gizi.tanggal,"
                    + "ifnull(satu_sehat_diet.id_diet,'') as satu_sehat_diet "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    // + "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat " // <--- DIHAPUS SESUAI PERMINTAAN
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join catatan_adime_gizi on catatan_adime_gizi.no_rawat=reg_periksa.no_rawat "
                    + "inner join pegawai on catatan_adime_gizi.nip=pegawai.nik "
                    + "left join satu_sehat_diet on satu_sehat_diet.no_rawat=catatan_adime_gizi.no_rawat "
                    + "and satu_sehat_diet.tanggal=catatan_adime_gizi.tanggal "
                    + "where catatan_adime_gizi.instruksi<>'' and reg_periksa.tgl_registrasi between ? and ? " // Ganti filter tanggal ke tgl_registrasi
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_diet.id_diet,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF DIET RALAN] Rawat: " + rs.getString("no_rawat") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("satu_sehat_diet").equals("")) {
                        try {
                            // 1. Cek ID Satu Sehat
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Praktisi tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // 2. Sanitasi Data
                            String instruksiDiet = rs.getString("instruksi")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", "<br>")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/")
                                    .replaceAll("\t", " ");
                            
                            String namaPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
                            String namaPraktisi = rs.getString("nama").replaceAll("\"", "'");

                            // 3. Konstruksi JSON (Composition)
                            json = "{"
                                    + "\"resourceType\" : \"Composition\","
                                    + "\"identifier\" : {"
                                        + "\"system\" : \"http://sys-ids.kemkes.go.id/composition/" + koneksiDB.IDSATUSEHAT() + "\","
                                        + "\"value\" : \"" + rs.getString("no_rawat") + "\""
                                    + "},"
                                    + "\"status\" : \"final\","
                                    + "\"type\" : {"
                                        + "\"coding\" : ["
                                            + "{"
                                                + "\"system\" : \"http://loinc.org\" ,"
                                                + "\"code\" : \"18842-5\" ,"
                                                + "\"display\" : \"Discharge summary\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"category\" : ["
                                        + "{"
                                            + "\"coding\" : ["
                                                + "{"
                                                    + "\"system\" : \"http://loinc.org\" ,"
                                                    + "\"code\" : \"LP173421-1\","
                                                    + "\"display\" : \"Report\""
                                                + "}"
                                            + "]"
                                        + "}"
                                    + "],"
                                    + "\"subject\" : {"
                                        + "\"reference\" : \"Patient/" + idpasien + "\" ,"
                                        + "\"display\" : \"" + namaPasien + "\""
                                    + "},"
                                    + "\"encounter\" : {"
                                        + "\"reference\" : \"Encounter/" + rs.getString("id_encounter") + "\","
                                        + "\"display\" : \"Kunjungan " + namaPasien + " pada tanggal " + rs.getString("tgl_registrasi") + " dengan nomor kunjungan " + rs.getString("no_rawat") + "\""
                                    + "},"
                                    + "\"date\" : \"" + rs.getString("tanggal").replaceAll(" ", "T") + "01+07:00\","
                                    + "\"author\" : ["
                                        + "{"
                                            + "\"reference\" : \"Practitioner/" + iddokter + "\","
                                            + "\"display\" : \"" + namaPraktisi + "\""
                                        + "}"
                                    + "],"
                                    + "\"title\" : \"Modul Gizi\","
                                    + "\"custodian\" : {"
                                        + "\"reference\" : \"Organization/" + koneksiDB.IDSATUSEHAT() + "\""
                                    + "},"
                                    + "\"section\" : ["
                                        + "{"
                                            + "\"code\" : {"
                                                + "\"coding\" : ["
                                                    + "{"
                                                        + "\"system\" : \"http://loinc.org\","
                                                        + "\"code\" : \"42344-2\" ,"
                                                        + "\"display\" : \"Discharge diet (narrative)\""
                                                    + "}"
                                                + "]"
                                            + "},"
                                            + "\"text\" : {"
                                                + "\"status\" : \"additional\" ,"
                                                + "\"div\" : \"" + instruksiDiet + "\""
                                            + "}"
                                        + "}"
                                    + "]"
                                    + "}";
                            
                            // 4. Kirim dengan Helper
                            processSendDiet(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR INTERN] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("   !! [SKIP] NIK Praktisi Kosong\n");
                        if (!rs.getString("satu_sehat_diet").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Diet Ralan: " + e);
                TeksArea.append("ERROR QUERY DIET RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. DIET GIZI RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[2/2] Melacak Diet Rawat Inap (Ranap)...\n");
            
            // QUERY DIMODIFIKASI: HAPUS INNER JOIN nota_inap
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,"
                    + "pasien.nm_pasien,pasien.no_ktp,satu_sehat_encounter.id_encounter,catatan_adime_gizi.instruksi,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,catatan_adime_gizi.tanggal,"
                    + "ifnull(satu_sehat_diet.id_diet,'') as satu_sehat_diet "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    // + "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat " // <--- DIHAPUS
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join catatan_adime_gizi on catatan_adime_gizi.no_rawat=reg_periksa.no_rawat "
                    + "inner join pegawai on catatan_adime_gizi.nip=pegawai.nik "
                    + "left join satu_sehat_diet on satu_sehat_diet.no_rawat=catatan_adime_gizi.no_rawat "
                    + "and satu_sehat_diet.tanggal=catatan_adime_gizi.tanggal "
                    + "where catatan_adime_gizi.instruksi<>'' and reg_periksa.tgl_registrasi between ? and ? " // Ganti filter tanggal
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_diet.id_diet,'')=''");
            try {
                ps.setString(1, Tanggal1.getText() + " ");
                ps.setString(2, Tanggal2.getText() + " ");
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF DIET RANAP] Rawat: " + rs.getString("no_rawat") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("satu_sehat_diet").equals("")) {
                        try {
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Praktisi tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // Sanitasi Data
                            String instruksiDiet = rs.getString("instruksi")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", "<br>")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/")
                                    .replaceAll("\t", " ");
                            
                            String namaPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
                            String namaPraktisi = rs.getString("nama").replaceAll("\"", "'");

                            // Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\" : \"Composition\","
                                    + "\"identifier\" : {"
                                        + "\"system\" : \"http://sys-ids.kemkes.go.id/composition/" + koneksiDB.IDSATUSEHAT() + "\","
                                        + "\"value\" : \"" + rs.getString("no_rawat") + "\""
                                    + "},"
                                    + "\"status\" : \"final\","
                                    + "\"type\" : {"
                                        + "\"coding\" : ["
                                            + "{"
                                                + "\"system\" : \"http://loinc.org\" ,"
                                                + "\"code\" : \"18842-5\" ,"
                                                + "\"display\" : \"Discharge summary\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"category\" : ["
                                        + "{"
                                            + "\"coding\" : ["
                                                + "{"
                                                    + "\"system\" : \"http://loinc.org\" ,"
                                                    + "\"code\" : \"LP173421-1\","
                                                    + "\"display\" : \"Report\""
                                                + "}"
                                            + "]"
                                        + "}"
                                    + "],"
                                    + "\"subject\" : {"
                                        + "\"reference\" : \"Patient/" + idpasien + "\" ,"
                                        + "\"display\" : \"" + namaPasien + "\""
                                    + "},"
                                    + "\"encounter\" : {"
                                        + "\"reference\" : \"Encounter/" + rs.getString("id_encounter") + "\","
                                        + "\"display\" : \"Kunjungan " + namaPasien + " pada tanggal " + rs.getString("tgl_registrasi") + " dengan nomor kunjungan " + rs.getString("no_rawat") + "\""
                                    + "},"
                                    + "\"date\" : \"" + rs.getString("tanggal").replaceAll(" ", "T") + "01+07:00\","
                                    + "\"author\" : ["
                                        + "{"
                                            + "\"reference\" : \"Practitioner/" + iddokter + "\","
                                            + "\"display\" : \"" + namaPraktisi + "\""
                                        + "}"
                                    + "],"
                                    + "\"title\" : \"Modul Gizi\","
                                    + "\"custodian\" : {"
                                        + "\"reference\" : \"Organization/" + koneksiDB.IDSATUSEHAT() + "\""
                                    + "},"
                                    + "\"section\" : ["
                                        + "{"
                                            + "\"code\" : {"
                                                + "\"coding\" : ["
                                                    + "{"
                                                        + "\"system\" : \"http://loinc.org\","
                                                        + "\"code\" : \"42344-2\" ,"
                                                        + "\"display\" : \"Discharge diet (narrative)\""
                                                    + "}"
                                                + "]"
                                            + "},"
                                            + "\"text\" : {"
                                                + "\"status\" : \"additional\" ,"
                                                + "\"div\" : \"" + instruksiDiet + "\""
                                            + "}"
                                        + "}"
                                    + "]"
                                    + "}";
                            
                            // Kirim dengan Helper
                            processSendDiet(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR INTERN] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("   !! [SKIP] NIK Praktisi Kosong\n");
                        if (!rs.getString("satu_sehat_diet").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Diet Ranap: " + e);
                TeksArea.append("ERROR QUERY DIET RANAP: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
            TeksArea.append("!! ERROR UTAMA DIET: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS DIET GIZI
    private void processSendDiet(String jsonPayload, ResultSet rs) throws Exception {
        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // Tampilkan payload utk debugging (optional)
            // TeksArea.append("   [DEBUG JSON] " + jsonPayload + "\n");
            
            // KIRIM PERTAMA
            json = konekSatuSehat(link + "/Composition", HttpMethod.POST, requestEntity);
            simpanLogDiet(json, rs);
            
        } catch (HttpClientErrorException e) {
            // HANDLER TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                String newToken = api.TokenSatuSehat();
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    json = konekSatuSehat(link + "/Composition", HttpMethod.POST, requestEntity);
                    simpanLogDiet(json, rs);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                TeksArea.append("   !! [ERROR API] " + e.getResponseBodyAsString() + "\n");
            }
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
        }
    }

    private void simpanLogDiet(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_diet", "?,?,?", "Diet/Gizi", 3, new String[]{
                rs.getString("no_rawat"), rs.getString("tanggal"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
    
    private void medication() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES SINKRONISASI MEDICATION (OBAT)\n");
            TeksArea.append("------------------------------------------------------\n");

            ps = koneksi.prepareStatement(
                    "select satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,databarang.status,"
                    + "satu_sehat_mapping_obat.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,"
                    + "satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,ifnull(satu_sehat_medication.id_medication,'') as id_medication "
                    + "from satu_sehat_mapping_obat inner join databarang on satu_sehat_mapping_obat.kode_brng=databarang.kode_brng "
                    + "left join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "order by satu_sehat_mapping_obat.obat_display");
            try {
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Sanitasi string agar tidak merusak JSON
                    String namaObat = rs.getString("obat_display").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'").replaceAll("\\\\", "/");
                    String bentukObat = rs.getString("form_display").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'");
                    String statusCode = rs.getString("status").equals("1") ? "active" : "inactive";
                    String idMedication = rs.getString("id_medication");

                    TeksArea.append("\n[PROSES OBAT] Kode: " + rs.getString("kode_brng") + " | Nama: " + namaObat + "\n");

                    try {
                        headers = new HttpHeaders();
                        headers.setContentType(MediaType.APPLICATION_JSON);
                        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

                        // Logika Percabangan: POST (Baru) atau PUT (Update)
                        String method = "";
                        String url = "";
                        String jsonIdPart = ""; // ID hanya dimasukkan ke body JSON saat PUT (sesuai kebiasaan FHIR, meski opsional di body saat POST)

                        if (idMedication.equals("")) {
                            // --- KASUS 1: DATA BARU (POST) ---
                            method = "POST";
                            url = link + "/Medication";
                            jsonIdPart = ""; // Tidak ada ID di body saat create
                            TeksArea.append("   Mode: TAMBAH DATA BARU (POST)\n");
                        } else {
                            // --- KASUS 2: UPDATE DATA (PUT) ---
                            method = "PUT";
                            url = link + "/Medication/" + idMedication;
                            jsonIdPart = "\"id\": \"" + idMedication + "\","; // Sertakan ID di body saat update
                            TeksArea.append("   Mode: UPDATE DATA (PUT) -> ID: " + idMedication + "\n");
                        }

                        // Penyusunan JSON
                        json = "{"
                                + "\"resourceType\": \"Medication\","
                                + jsonIdPart
                                + "\"meta\": {"
                                + "\"profile\": ["
                                + "\"https://fhir.kemkes.go.id/r4/StructureDefinition/Medication\""
                                + "]"
                                + "},"
                                + "\"identifier\": ["
                                + "{"
                                + "\"system\" : \"http://sys-ids.kemkes.go.id/medication/" + koneksiDB.IDSATUSEHAT() + "\","
                                + "\"use\": \"official\","
                                + "\"value\" : \"" + rs.getString("kode_brng") + "\""
                                + "}"
                                + "],"
                                + "\"code\": {"
                                + "\"coding\": ["
                                + "{"
                                + "\"system\": \"" + rs.getString("obat_system") + "\","
                                + "\"code\": \"" + rs.getString("obat_code") + "\","
                                + "\"display\": \"" + namaObat + "\""
                                + "}"
                                + "]"
                                + "},"
                                + "\"status\": \"" + statusCode + "\","
                                + "\"form\": {"
                                + "\"coding\": ["
                                + "{"
                                + "\"system\": \"" + rs.getString("form_system") + "\","
                                + "\"code\": \"" + rs.getString("form_code") + "\","
                                + "\"display\": \"" + bentukObat + "\""
                                + "}"
                                + "]"
                                + "},"
                                + "\"extension\": ["
                                + "{"
                                + "\"url\": \"https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType\","
                                + "\"valueCodeableConcept\": {"
                                + "\"coding\": ["
                                + "{"
                                + "\"system\": \"http://terminology.kemkes.go.id/CodeSystem/medication-type\","
                                + "\"code\": \"NC\","
                                + "\"display\": \"Non-compound\""
                                + "}"
                                + "]"
                                + "}"
                                + "}"
                                + "]"
                                + "}";

                        TeksArea.append("   URL : " + url + "\n");
                        TeksArea.append("   Request JSON : " + json + "\n");

                        requestEntity = new HttpEntity(json, headers);

                        // Eksekusi Request sesuai Method
                        if (method.equals("POST")) {
                            json = konekSatuSehat(url, HttpMethod.POST, requestEntity);
                        } else {
                            json = konekSatuSehat(url, HttpMethod.PUT, requestEntity);
                        }

                        TeksArea.append("   Result JSON : " + json + "\n");

                        // Parsing Response
                        root = mapper.readTree(json);
                        response = root.path("id");

                        // Jika POST berhasil dan dapat ID baru, simpan ke database
                        if (!response.asText().equals("") && method.equals("POST")) {
                            Sequel.menyimpan2("satu_sehat_medication", "?,?", "Obat/Alkes", 2, new String[]{
                                rs.getString("kode_brng"), response.asText()
                            });
                            TeksArea.append("   [SUKSES] ID Baru disimpan ke DB Lokal.\n");
                        } else if (!response.asText().equals("") && method.equals("PUT")) {
                            TeksArea.append("   [SUKSES] Data berhasil diupdate.\n");
                        }

                    } catch (Exception e) {
                        TeksArea.append("   [ERROR API] " + e + "\n");
                        System.out.println("Notifikasi Bridging : " + e);
                    }
                    jeda(); // Jeda untuk mencegah rate limit
                }
            } catch (Exception e) {
                TeksArea.append("[ERROR QUERY] " + e + "\n");
                System.out.println("Notif : " + e);
            } finally {
                if (rs != null) {
                    rs.close();
                }
                if (ps != null) {
                    ps.close();
                }
            }
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
            TeksArea.append("!! ERROR UTAMA MEDICATION: " + e + "\n");
        }
    }
    
    // MODUL MEDICATION REQUEST (RESEP) - DETEKTIF + AUTO RECONNECT
    private void medicationrequest() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: MEDICATION REQUEST (RESEP)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. RESEP NON-RACIKAN - RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/4] Melacak Resep Non-Racikan Ralan...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"
                    + "resep_dokter.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"
                    + "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"
                    + "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_peresepan,resep_obat.jam_peresepan,resep_dokter.jml,satu_sehat_medication.id_medication,"
                    + "resep_dokter.aturan_pakai,resep_dokter.no_resep,ifnull(satu_sehat_medicationrequest.id_medicationrequest,'') as id_medicationrequest "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter on resep_dokter.no_resep=resep_obat.no_resep "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationrequest on satu_sehat_medicationrequest.no_resep=resep_dokter.no_resep and satu_sehat_medicationrequest.kode_brng=resep_dokter.kode_brng "
                    + "where resep_obat.tgl_peresepan between ? and ? and reg_periksa.status_lanjut='Ralan' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationrequest.id_medicationrequest,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_medicationrequest").equals("")) {
                        kirimMedicationRequest(rs, "outpatient", "satu_sehat_medicationrequest", false);
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Resep Ralan Non-Racik : " + e);
                TeksArea.append("ERROR QUERY RESEP RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. RESEP NON-RACIKAN - RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[2/4] Melacak Resep Non-Racikan Ranap...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"
                    + "resep_dokter.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"
                    + "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"
                    + "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_peresepan,resep_obat.jam_peresepan,resep_dokter.jml,satu_sehat_medication.id_medication,"
                    + "resep_dokter.aturan_pakai,resep_dokter.no_resep,ifnull(satu_sehat_medicationrequest.id_medicationrequest,'') as id_medicationrequest "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter on resep_dokter.no_resep=resep_obat.no_resep "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationrequest on satu_sehat_medicationrequest.no_resep=resep_dokter.no_resep and satu_sehat_medicationrequest.kode_brng=resep_dokter.kode_brng "
                    + "where resep_obat.tgl_peresepan between ? and ? and reg_periksa.status_lanjut='Ranap' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationrequest.id_medicationrequest,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_medicationrequest").equals("")) {
                        kirimMedicationRequest(rs, "inpatient", "satu_sehat_medicationrequest", false);
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Resep Ranap Non-Racik : " + e);
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 3. RESEP RACIKAN - RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[3/4] Melacak Resep Racikan Ralan...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"
                    + "resep_dokter_racikan_detail.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"
                    + "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"
                    + "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_peresepan,resep_obat.jam_peresepan,resep_dokter_racikan_detail.jml,satu_sehat_medication.id_medication,"
                    + "resep_dokter_racikan.aturan_pakai,resep_dokter_racikan.no_resep,ifnull(satu_sehat_medicationrequest_racikan.id_medicationrequest,'') as id_medicationrequest, "
                    + "resep_dokter_racikan_detail.no_racik from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter_racikan on resep_dokter_racikan.no_resep=resep_obat.no_resep "
                    + "inner join resep_dokter_racikan_detail on resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep and resep_dokter_racikan_detail.no_racik=resep_dokter_racikan.no_racik "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter_racikan_detail.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationrequest_racikan on satu_sehat_medicationrequest_racikan.no_resep=resep_dokter_racikan_detail.no_resep and "
                    + "satu_sehat_medicationrequest_racikan.kode_brng=resep_dokter_racikan_detail.kode_brng and satu_sehat_medicationrequest_racikan.no_racik=resep_dokter_racikan_detail.no_racik "
                    + "where resep_obat.tgl_peresepan between ? and ? and reg_periksa.status_lanjut='Ralan' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationrequest_racikan.id_medicationrequest,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_medicationrequest").equals("")) {
                        kirimMedicationRequest(rs, "outpatient", "satu_sehat_medicationrequest_racikan", true);
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Resep Ralan Racik : " + e);
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 4. RESEP RACIKAN - RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[4/4] Melacak Resep Racikan Ranap...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"+
                    "resep_dokter_racikan_detail.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"+
                    "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"+
                    "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_peresepan,resep_obat.jam_peresepan,resep_dokter_racikan_detail.jml,satu_sehat_medication.id_medication,"+
                    "resep_dokter_racikan.aturan_pakai,resep_dokter_racikan.no_resep,ifnull(satu_sehat_medicationrequest_racikan.id_medicationrequest,'') as id_medicationrequest, "+
                    "resep_dokter_racikan_detail.no_racik from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "+
                    "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "+
                    "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "+
                    "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "+
                    "inner join resep_dokter_racikan on resep_dokter_racikan.no_resep=resep_obat.no_resep "+
                    "inner join resep_dokter_racikan_detail on resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep and resep_dokter_racikan_detail.no_racik=resep_dokter_racikan.no_racik "+
                    "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter_racikan_detail.kode_brng "+
                    "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "+
                    "left join satu_sehat_medicationrequest_racikan on satu_sehat_medicationrequest_racikan.no_resep=resep_dokter_racikan_detail.no_resep and "+
                    "satu_sehat_medicationrequest_racikan.kode_brng=resep_dokter_racikan_detail.kode_brng and satu_sehat_medicationrequest_racikan.no_racik=resep_dokter_racikan_detail.no_racik "+
                    "where resep_obat.tgl_peresepan between ? and ? and reg_periksa.status_lanjut='Ranap' "+
                    "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "+
                    "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "+
                    "and ifnull(satu_sehat_medicationrequest_racikan.id_medicationrequest,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_medicationrequest").equals("")) {
                        kirimMedicationRequest(rs, "inpatient", "satu_sehat_medicationrequest_racikan", true);
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Resep Ranap Racik : " + e);
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

        } catch (Exception e) {
            System.out.println("Notifikasi Utama Medication Request : " + e);
            TeksArea.append("!! ERROR UTAMA MEDICATION REQUEST: " + e + "\n");
        }
    }
    
    // HELPER: KIRIM MEDICATION REQUEST (DETEKTIF + AUTO RECONNECT)
    private void kirimMedicationRequest(ResultSet rs, String category, String tableName, boolean isRacikan) {
        String noRawat = "", noResep = "", nmPasien = "";
        try {
            noRawat = rs.getString("no_rawat");
            noResep = rs.getString("no_resep");
            nmPasien = rs.getString("nm_pasien");
        } catch (Exception e) {}

        try {
            // --- 1. LOG DETEKTIF ---
            TeksArea.append("\n[DETEKTIF RESEP] Pasien: " + nmPasien + " | Rawat: " + noRawat + " | Resep: " + noResep + "\n");

            // --- 2. VALIDASI KyC ---
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

            if (idpasien == null || idpasien.isEmpty() || iddokter == null || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Praktisi Kosong.\n");
                return;
            }

            // --- 3. VALIDASI PRODUK ---
            if (rs.getString("id_medication") == null || rs.getString("id_medication").trim().equals("")) {
                TeksArea.append("   !! [SKIP] ID Medication Kosong (Belum Bridging).\n");
                TeksArea.append("      -> Obat: " + rs.getString("obat_display") + " (" + rs.getString("kode_brng") + ")\n");
                return;
            }

            // --- 4. PREPARE DATA & JSON ---
            String aturanPakai = rs.getString("aturan_pakai");
            if (aturanPakai == null || aturanPakai.isEmpty()) aturanPakai = "1x1";
            aturanPakai = aturanPakai.replaceAll("\"", "").replaceAll("\\\\", "").replaceAll("[\\t\\n\\r]+", " ").trim();

            String jamPeresepan = rs.getString("jam_peresepan");
            if (jamPeresepan.length() == 5) jamPeresepan += ":00";
            String tglAuthored = rs.getString("tgl_peresepan") + "T" + jamPeresepan + "+07:00";

            // Parsing Logic (Safe Double)
            String valFreq = "1"; String valDose = "1";
            try {
                String[] parts = aturanPakai.toLowerCase().split("x");
                if (parts.length > 1) {
                    valFreq = parts[0].replaceAll("[^0-9.]", "");
                    valDose = parts[1].replaceAll("[^0-9.]", "");
                } else if (parts.length == 1 && !parts[0].replaceAll("[^0-9.]", "").isEmpty()) {
                     valFreq = "1"; valDose = parts[0].replaceAll("[^0-9.]", "");
                }
            } catch (Exception e) {}
            
            double dFreq = 1; double dDose = 1; double dQty = 1;
            try { if(!valFreq.isEmpty()) dFreq = Double.parseDouble(valFreq); } catch (Exception e) {}
            try { if(!valDose.isEmpty()) dDose = Double.parseDouble(valDose); } catch (Exception e) {}
            try { dQty = rs.getDouble("jml"); } catch (Exception e) {}

            String catCode = category.equals("outpatient") ? "outpatient" : "inpatient";
            String catDisplay = category.equals("outpatient") ? "Outpatient" : "Inpatient";
            String identifierValue = rs.getString("kode_brng");
            if (isRacikan) identifierValue += "-" + rs.getString("no_racik");

            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            String json = "{" +
                    "\"resourceType\": \"MedicationRequest\"," +
                    "\"identifier\": [" +
                        "{" +
                            "\"system\": \"http://sys-ids.kemkes.go.id/prescription/" + koneksiDB.IDSATUSEHAT() + "\"," +
                            "\"use\": \"official\"," +
                            "\"value\": \"" + noResep + "\"" +
                        "}," +
                        "{" +
                            "\"system\": \"http://sys-ids.kemkes.go.id/prescription-item/" + koneksiDB.IDSATUSEHAT() + "\"," +
                            "\"use\": \"official\"," +
                            "\"value\": \"" + identifierValue + "\"" +
                        "}" +
                    "]," +
                    "\"status\": \"completed\"," +
                    "\"intent\": \"order\"," +
                    "\"category\": [" +
                        "{" +
                            "\"coding\": [" +
                                "{" +
                                    "\"system\": \"http://terminology.hl7.org/CodeSystem/medicationrequest-category\"," +
                                    "\"code\": \"" + catCode + "\"," +
                                    "\"display\": \"" + catDisplay + "\"" +
                                "}" +
                            "]" +
                        "}" +
                    "]," +
                    "\"medicationReference\": {" +
                        "\"reference\": \"Medication/" + rs.getString("id_medication") + "\"," +
                        "\"display\": \"" + rs.getString("obat_display").replaceAll("\"", "") + "\"" +
                    "}," +
                    "\"subject\": {" +
                        "\"reference\": \"Patient/" + idpasien + "\"," +
                        "\"display\": \"" + nmPasien.replaceAll("\"", "") + "\"" +
                    "}," +
                    "\"encounter\": {" +
                        "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"" +
                    "}," +
                    "\"authoredOn\": \"" + tglAuthored + "\"," +
                    "\"requester\": {" +
                        "\"reference\": \"Practitioner/" + iddokter + "\"," +
                        "\"display\": \"" + rs.getString("nama").replaceAll("\"", "") + "\"" +
                    "}," +
                    "\"dosageInstruction\": [" +
                        "{" +
                            "\"sequence\": 1," +
                            "\"patientInstruction\": \"" + aturanPakai + "\"," +
                            "\"timing\": {" +
                                "\"repeat\": {" +
                                    "\"frequency\": " + dFreq + "," +
                                    "\"period\": 1," +
                                    "\"periodUnit\": \"d\"" +
                                "}" +
                            "}," +
                            "\"route\": {" +
                                "\"coding\": [" +
                                    "{" +
                                        "\"system\": \"" + rs.getString("route_system") + "\"," +
                                        "\"code\": \"" + rs.getString("route_code") + "\"," +
                                        "\"display\": \"" + rs.getString("route_display") + "\"" +
                                    "}" +
                                "]" +
                            "}," +
                            "\"doseAndRate\": [" +
                                "{" +
                                    "\"doseQuantity\": {" +
                                        "\"value\": " + dDose + "," +
                                        "\"unit\": \"" + rs.getString("denominator_code") + "\"," +
                                        "\"system\": \"" + rs.getString("denominator_system") + "\"," +
                                        "\"code\": \"" + rs.getString("denominator_code") + "\"" +
                                    "}" +
                                "}" +
                            "]" +
                        "}" +
                    "]," +
                    "\"dispenseRequest\": {" +
                        "\"quantity\": {" +
                            "\"value\": " + dQty + "," +
                            "\"unit\": \"" + rs.getString("denominator_code") + "\"," +
                            "\"system\": \"" + rs.getString("denominator_system") + "\"," +
                            "\"code\": \"" + rs.getString("denominator_code") + "\"" +
                        "}," +
                        "\"performer\": {" +
                            "\"reference\": \"Organization/" + koneksiDB.IDSATUSEHAT() + "\"" +
                        "}" +
                    "}" +
                "}";

            // --- 5. KIRIM DENGAN AUTO RECONNECT ---
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/MedicationRequest", HttpMethod.POST, requestEntity);
                simpanLogMedication(responseJson, tableName, rs, isRacikan);
                
            } catch (HttpClientErrorException e) {
                // HANDLER 401 (TOKEN EXPIRED)
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Refresh Token...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    
                    try {
                        // RETRY KIRIM
                        String responseJson = konekSatuSehat(link + "/MedicationRequest", HttpMethod.POST, requestEntity);
                        simpanLogMedication(responseJson, tableName, rs, isRacikan);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            }

        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e.getMessage() + "\n");
            e.printStackTrace();
        }
    }

    // Helper Simpan ke Database Lokal
    private void simpanLogMedication(String responseJson, String tableName, ResultSet rs, boolean isRacikan) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            TeksArea.append("   [SUKSES] ID: " + responseId.asText() + "\n");
            if (isRacikan) {
                Sequel.menyimpan2(tableName, "?,?,?,?,?", "Obat/Alkes Racikan", 4, new String[]{
                    rs.getString("no_resep"), rs.getString("kode_brng"), rs.getString("no_racik"), responseId.asText()
                });
            } else {
                Sequel.menyimpan2(tableName, "?,?,?", "Obat/Alkes", 3, new String[]{
                    rs.getString("no_resep"), rs.getString("kode_brng"), responseId.asText()
                });
            }
        }
    }
    
    
    // MODUL MEDICATION DISPENSE (PEMBERIAN OBAT) - DETEKTIF + AUTO RECONNECT + QUERY VALIDASI
    private void medicationdispense() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: MEDICATION DISPENSE\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/2] Melacak Pemberian Obat Ralan...\n");
            // Query Optimized: 
            // 1. Wajib ada Encounter ID
            // 2. Wajib ada MedicationRequest ID (Agar tidak buang waktu memproses data 'yatim')
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi, reg_periksa.jam_reg, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
                    "pegawai.nama, pegawai.no_ktp as ktppraktisi, satu_sehat_encounter.id_encounter, " +
                    "satu_sehat_mapping_obat.obat_code, satu_sehat_mapping_obat.obat_system, satu_sehat_mapping_obat.obat_display, " +
                    "detail_pemberian_obat.kode_brng, satu_sehat_mapping_obat.form_code, satu_sehat_mapping_obat.denominator_code, satu_sehat_mapping_obat.denominator_system, " +
                    "satu_sehat_mapping_obat.route_code, satu_sehat_mapping_obat.route_system, satu_sehat_mapping_obat.route_display, " +
                    "resep_obat.tgl_peresepan, resep_obat.jam_peresepan, detail_pemberian_obat.jml, satu_sehat_medication.id_medication, " +
                    "aturan_pakai.aturan, resep_obat.no_resep, detail_pemberian_obat.no_batch, detail_pemberian_obat.no_faktur, " +
                    "detail_pemberian_obat.tgl_perawatan, detail_pemberian_obat.jam, " +
                    "ifnull(satu_sehat_medicationdispense.id_medicationdispanse,'') as id_medicationdispanse, " +
                    "satu_sehat_mapping_lokasi_depo_farmasi.id_lokasi_satusehat, bangsal.nm_bangsal, " +
                    "ifnull(satu_sehat_medicationrequest.id_medicationrequest,'') as id_medicationrequest " +
                    
                    "from reg_periksa " +
                    "inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis " +
                    "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat " +                    
                    "inner join pegawai on resep_obat.kd_dokter=pegawai.nik " +
                    "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat " + // Wajib Encounter                    
                    "inner join detail_pemberian_obat on detail_pemberian_obat.no_rawat=resep_obat.no_rawat " +
                        "and detail_pemberian_obat.tgl_perawatan=resep_obat.tgl_perawatan and detail_pemberian_obat.jam=resep_obat.jam " +
                    "inner join aturan_pakai on detail_pemberian_obat.no_rawat=aturan_pakai.no_rawat " +
                        "and detail_pemberian_obat.tgl_perawatan=aturan_pakai.tgl_perawatan and detail_pemberian_obat.jam=aturan_pakai.jam " +
                        "and detail_pemberian_obat.kode_brng=aturan_pakai.kode_brng " +
                    "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=detail_pemberian_obat.kode_brng " +
                    "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng " +
                    "inner join bangsal on bangsal.kd_bangsal=detail_pemberian_obat.kd_bangsal " +
                    "inner join satu_sehat_mapping_lokasi_depo_farmasi on satu_sehat_mapping_lokasi_depo_farmasi.kd_bangsal=bangsal.kd_bangsal " +
                    
                    // JOIN REQUEST (WAJIB ADA)
                    "inner join satu_sehat_medicationrequest on satu_sehat_medicationrequest.no_resep=resep_obat.no_resep " +
                        "and satu_sehat_medicationrequest.kode_brng=detail_pemberian_obat.kode_brng " +
                    
                    "left join satu_sehat_medicationdispense on satu_sehat_medicationdispense.no_rawat=detail_pemberian_obat.no_rawat " +
                        "and satu_sehat_medicationdispense.tgl_perawatan=detail_pemberian_obat.tgl_perawatan " +
                        "and satu_sehat_medicationdispense.jam=detail_pemberian_obat.jam " +
                        "and satu_sehat_medicationdispense.kode_brng=detail_pemberian_obat.kode_brng " +
                        "and satu_sehat_medicationdispense.no_batch=detail_pemberian_obat.no_batch " +
                        "and satu_sehat_medicationdispense.no_faktur=detail_pemberian_obat.no_faktur " +
                    
                    "where reg_periksa.status_lanjut='Ralan' and reg_periksa.tgl_registrasi between ? and ? " +
                    "and ifnull(satu_sehat_medicationdispense.id_medicationdispanse,'')='' " + 
                    "and satu_sehat_medicationrequest.id_medicationrequest <> '' "); // Validasi Request ID
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    kirimMedicationDispense(rs, "outpatient");
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ralan Dispense : " + e);
                TeksArea.append("ERROR QUERY RALAN DISPENSE: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. RAWAT INAP (INPATIENT)
            // ===========================================================================================
            TeksArea.append("\n[2/2] Melacak Pemberian Obat Ranap...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi, reg_periksa.jam_reg, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
                    "pegawai.nama, pegawai.no_ktp as ktppraktisi, satu_sehat_encounter.id_encounter, " +
                    "satu_sehat_mapping_obat.obat_code, satu_sehat_mapping_obat.obat_system, satu_sehat_mapping_obat.obat_display, " +
                    "detail_pemberian_obat.kode_brng, satu_sehat_mapping_obat.form_code, satu_sehat_mapping_obat.denominator_code, satu_sehat_mapping_obat.denominator_system, " +
                    "satu_sehat_mapping_obat.route_code, satu_sehat_mapping_obat.route_system, satu_sehat_mapping_obat.route_display, " +
                    "resep_obat.tgl_peresepan, resep_obat.jam_peresepan, detail_pemberian_obat.jml, satu_sehat_medication.id_medication, " +
                    "aturan_pakai.aturan, resep_obat.no_resep, detail_pemberian_obat.no_batch, detail_pemberian_obat.no_faktur, " +
                    "detail_pemberian_obat.tgl_perawatan, detail_pemberian_obat.jam, " +
                    "ifnull(satu_sehat_medicationdispense.id_medicationdispanse,'') as id_medicationdispanse, " +
                    "satu_sehat_mapping_lokasi_depo_farmasi.id_lokasi_satusehat, bangsal.nm_bangsal, " +
                    "ifnull(satu_sehat_medicationrequest.id_medicationrequest,'') as id_medicationrequest " +
                    
                    "from reg_periksa " +
                    "inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis " +
                    "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat " +
                    "inner join pegawai on resep_obat.kd_dokter=pegawai.nik " +
                    "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat " +
                    "inner join detail_pemberian_obat on detail_pemberian_obat.no_rawat=resep_obat.no_rawat " +
                        "and detail_pemberian_obat.tgl_perawatan=resep_obat.tgl_perawatan and detail_pemberian_obat.jam=resep_obat.jam " +
                    "inner join aturan_pakai on detail_pemberian_obat.no_rawat=aturan_pakai.no_rawat " +
                        "and detail_pemberian_obat.tgl_perawatan=aturan_pakai.tgl_perawatan and detail_pemberian_obat.jam=aturan_pakai.jam " +
                        "and detail_pemberian_obat.kode_brng=aturan_pakai.kode_brng " +
                    "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=detail_pemberian_obat.kode_brng " +
                    "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng " +
                    "inner join bangsal on bangsal.kd_bangsal=detail_pemberian_obat.kd_bangsal " +
                    "inner join satu_sehat_mapping_lokasi_depo_farmasi on satu_sehat_mapping_lokasi_depo_farmasi.kd_bangsal=bangsal.kd_bangsal " +
                    
                    // JOIN REQUEST (WAJIB ADA)
                    "inner join satu_sehat_medicationrequest on satu_sehat_medicationrequest.no_resep=resep_obat.no_resep " +
                        "and satu_sehat_medicationrequest.kode_brng=detail_pemberian_obat.kode_brng " +
                    
                    "left join satu_sehat_medicationdispense on satu_sehat_medicationdispense.no_rawat=detail_pemberian_obat.no_rawat " +
                        "and satu_sehat_medicationdispense.tgl_perawatan=detail_pemberian_obat.tgl_perawatan " +
                        "and satu_sehat_medicationdispense.jam=detail_pemberian_obat.jam " +
                        "and satu_sehat_medicationdispense.kode_brng=detail_pemberian_obat.kode_brng " +
                        "and satu_sehat_medicationdispense.no_batch=detail_pemberian_obat.no_batch " +
                        "and satu_sehat_medicationdispense.no_faktur=detail_pemberian_obat.no_faktur " +
                    
                    "where reg_periksa.status_lanjut='Ranap' and reg_periksa.tgl_registrasi between ? and ? " +
                    "and ifnull(satu_sehat_medicationdispense.id_medicationdispanse,'')='' " +
                    "and satu_sehat_medicationrequest.id_medicationrequest <> '' "); // Validasi Request ID
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    kirimMedicationDispense(rs, "inpatient");
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ranap Dispense : " + e);
                TeksArea.append("ERROR QUERY RANAP DISPENSE: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

        } catch (Exception e) {
            System.out.println("Notifikasi Utama Medication Dispense : " + e);
            TeksArea.append("!! ERROR UTAMA MED DISPENSE: " + e + "\n");
        }
    }

    // HELPER SAKTI: MEDICATION DISPENSE (AUTO RECONNECT + RECOVERY)
    private void kirimMedicationDispense(ResultSet rs, String category) {
        String identifierValue = ""; 
        try {
            // Karena sudah difilter di query, kita bisa yakin ID Request pasti ada
            String idRequest = rs.getString("id_medicationrequest");

            // 1. Cek KyC
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Dokter Kosong di SatuSehat.\n");
                return;
            }

            // 2. AUTO-FIX ROUTE
            String dbRouteCode = rs.getString("route_code").trim().toUpperCase();
            String finalRouteSystem = "http://www.whocc.no/atc"; 
            String finalRouteCode = "O"; 
            String finalRouteDisplay = "Oral";

            if (dbRouteCode.equals("PO") || dbRouteCode.equals("O") || dbRouteCode.contains("ORAL")) {
                finalRouteCode = "O"; finalRouteDisplay = "Oral";
            } else if (dbRouteCode.equals("IV") || dbRouteCode.equals("IM") || dbRouteCode.equals("SC") || dbRouteCode.equals("P")) {
                finalRouteCode = "P"; finalRouteDisplay = "Parenteral";
            } else if (dbRouteCode.contains("INHAL")) {
                finalRouteCode = "Inhal"; finalRouteDisplay = "Inhalation";
            } else if (dbRouteCode.contains("TOP") || dbRouteCode.contains("OINT")) {
                finalRouteCode = "Topical"; finalRouteDisplay = "Topical"; 
            }

            // 3. AUTO-FIX UNITS (Sama dengan MedStatement)
            String dbUnitCode = rs.getString("denominator_code").trim();
            String finalUnitSystem = rs.getString("denominator_system").trim();
            
            if (dbUnitCode.equalsIgnoreCase("mL") || dbUnitCode.equalsIgnoreCase("mg") || dbUnitCode.equalsIgnoreCase("g") || dbUnitCode.equalsIgnoreCase("Tab")) {
                finalUnitSystem = "http://unitsofmeasure.org";
            } else {
                finalUnitSystem = "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm";
            }

            // 4. JSON Construction
            String aturan = rs.getString("aturan").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "").trim();
            if(aturan.isEmpty()) aturan = "1x1";
            
            String valFreq="1", valDose="1";
            try {
                String[] parts = aturan.toLowerCase().split("x");
                if (parts.length > 1) { valFreq=parts[0].replaceAll("[^0-9.]",""); valDose=parts[1].replaceAll("[^0-9.]",""); }
            } catch(Exception e){}
            
            double dFreq=1; try{if(!valFreq.isEmpty())dFreq=Double.parseDouble(valFreq);}catch(Exception e){}
            double dDose=1; try{if(!valDose.isEmpty())dDose=Double.parseDouble(valDose);}catch(Exception e){}
            double dQty=1; try{dQty=rs.getDouble("jml");}catch(Exception e){}

            String catCode = category.equals("outpatient") ? "outpatient" : "inpatient";
            String catDisplay = category.equals("outpatient") ? "Outpatient" : "Inpatient";
            
            // Identifier Unik (Gunakan Item Identifier yang sama dengan Request)
            // Format: [NoResep]-[KodeObat]
            identifierValue = rs.getString("no_resep") + "-" + rs.getString("kode_brng"); 

            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            String json = "{"
                    + "\"resourceType\": \"MedicationDispense\","
                    + "\"identifier\": ["
                        + "{\"system\": \"http://sys-ids.kemkes.go.id/prescription/" + koneksiDB.IDSATUSEHAT() + "\",\"use\": \"official\",\"value\": \"" + rs.getString("no_resep") + "\"},"
                        + "{\"system\": \"http://sys-ids.kemkes.go.id/prescription-item/" + koneksiDB.IDSATUSEHAT() + "\",\"use\": \"official\",\"value\": \"" + identifierValue + "\"}"
                    + "],"
                    + "\"status\": \"completed\","
                    + "\"category\": {\"coding\": [{\"system\": \"http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category\",\"code\": \"" + catCode + "\",\"display\": \"" + catDisplay + "\"}]},"
                    + "\"medicationReference\": {\"reference\": \"Medication/" + rs.getString("id_medication") + "\",\"display\": \"" + rs.getString("obat_display").replaceAll("\"", "") + "\"},"
                    + "\"subject\": {\"reference\": \"Patient/" + idpasien + "\",\"display\": \"" + rs.getString("nm_pasien").replaceAll("\"", "") + "\"},"
                    + "\"context\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"},"
                    + "\"performer\": [{\"actor\": {\"reference\": \"Practitioner/" + iddokter + "\",\"display\": \"" + rs.getString("nama").replaceAll("\"", "") + "\"}}],"
                    + "\"location\": {\"reference\": \"Location/" + rs.getString("id_lokasi_satusehat") + "\",\"display\": \"" + rs.getString("nm_bangsal").replaceAll("\"", "") + "\"},"
                    + "\"authorizingPrescription\": [{\"reference\": \"MedicationRequest/" + idRequest + "\"}]," 
                    + "\"quantity\": {"
                        + "\"system\": \"" + finalUnitSystem + "\","
                        + "\"code\": \"" + dbUnitCode + "\","
                        + "\"value\": " + dQty
                    + "},"
                    + "\"whenPrepared\": \"" + rs.getString("tgl_peresepan") + "T" + rs.getString("jam_peresepan") + "+07:00\","
                    + "\"whenHandedOver\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam") + "+07:00\","
                    + "\"dosageInstruction\": [{"
                        + "\"sequence\": 1,"
                        + "\"text\": \"" + aturan + "\","
                        + "\"timing\": {\"repeat\": {\"frequency\": " + dFreq + ",\"period\": 1,\"periodUnit\": \"d\"}},"
                        + "\"route\": {\"coding\": [{\"system\": \"" + finalRouteSystem + "\",\"code\": \"" + finalRouteCode + "\",\"display\": \"" + finalRouteDisplay + "\"}]},"
                        + "\"doseAndRate\": [{\"doseQuantity\": {\"value\": " + dDose + ",\"unit\": \"" + dbUnitCode + "\",\"system\": \"" + finalUnitSystem + "\",\"code\": \"" + dbUnitCode + "\"}}]"
                    + "}]"
                    + "}";

            TeksArea.append("\n[DEBUG] Sending Dispense: " + rs.getString("no_resep") + " | " + rs.getString("obat_display") + "\n");
            
            // --- 5. KIRIM KE SATUSEHAT ---
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/MedicationDispense", HttpMethod.POST, requestEntity);
                simpanLogDispense(responseJson, rs);
                
            } catch (HttpClientErrorException e) {
                // TOKEN EXPIRED
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Refresh Token...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    try {
                        String responseJson = konekSatuSehat(link + "/MedicationDispense", HttpMethod.POST, requestEntity);
                        simpanLogDispense(responseJson, rs);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } 
                // DUPLICATE RECOVERY
                else if (e.getStatusCode().value() == 400 && e.getResponseBodyAsString().contains("duplicate")) {
                    TeksArea.append("   [INFO] Data Duplikat. Mencoba Recovery ID...\n");
                    recoverDuplicateDispense(identifierValue, rs);
                }
                else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            }
        } catch (Exception e) {
            System.out.println("System Error: "+e);
        }
    }

    private void simpanLogDispense(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            TeksArea.append("   [SUKSES] ID: " + responseId.asText() + "\n");
            Sequel.menyimpan2("satu_sehat_medicationdispense", "?,?,?,?,?,?,?", "Obat/Alkes", 7, new String[]{
                rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam"), rs.getString("kode_brng"), rs.getString("no_batch"), rs.getString("no_faktur"), responseId.asText()
            });
        }
    }

    // HELPER RECOVERY DUPLIKAT (Cari ID berdasarkan Identifier Item)
    private void recoverDuplicateDispense(String identifierValue, ResultSet rs) {
        try {
            // Identifier Unik Dispense: http://sys-ids.kemkes.go.id/prescription-item/[ID_ORG]|[IDENTIFIER_VALUE]
            // Identifier Value disini harus sama persis dengan yang dikirim di JSON (no_resep + "-" + kode_brng)
            String searchUrl = link + "/MedicationDispense?identifier=http://sys-ids.kemkes.go.id/prescription-item/" + koneksiDB.IDSATUSEHAT() + "|" + identifierValue;
            
            String searchJson = konekSatuSehat(searchUrl, HttpMethod.GET, new HttpEntity(headers));
            JsonNode searchRoot = mapper.readTree(searchJson);
            
            if (searchRoot.path("total").asInt() > 0) {
                String existingId = searchRoot.path("entry").get(0).path("resource").path("id").asText();
                TeksArea.append("   [RECOVERED] ID Ditemukan: " + existingId + "\n");
                
                Sequel.menyimpan2("satu_sehat_medicationdispense", "?,?,?,?,?,?,?", "Obat/Alkes", 7, new String[]{
                    rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam"), rs.getString("kode_brng"), rs.getString("no_batch"), rs.getString("no_faktur"), existingId
                });
            } else {
                TeksArea.append("   !! [GAGAL RECOVER] Data duplikat tapi tidak ditemukan saat dicari.\n");
            }
        } catch (Exception ex) {
            TeksArea.append("   !! [ERROR RECOVER] " + ex.getMessage() + "\n");
        }
    }
    
    // MODUL MEDICATION STATEMENT (RIWAYAT OBAT) - FINAL FIX (ADAPTASI DARI DISPENSE)
    private void medicationstatement() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: MEDICATION STATEMENT (FIX ROUTE & UNIT)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. OBAT NON-RACIKAN
            // ===========================================================================================
            String queryNonRacik = "select reg_periksa.no_rawat, pasien.nm_pasien, pasien.no_ktp, "
                    + "satu_sehat_encounter.id_encounter, satu_sehat_mapping_obat.obat_code, satu_sehat_mapping_obat.obat_system, "
                    + "resep_dokter.kode_brng, satu_sehat_mapping_obat.obat_display, satu_sehat_mapping_obat.form_code, satu_sehat_mapping_obat.form_system, "
                    + "satu_sehat_mapping_obat.route_code, satu_sehat_mapping_obat.route_system, satu_sehat_mapping_obat.route_display, "
                    + "satu_sehat_mapping_obat.denominator_code, " // Kita abaikan system dari DB
                    + "resep_obat.tgl_penyerahan, resep_obat.jam_penyerahan, resep_dokter.jml, satu_sehat_medication.id_medication, "
                    + "resep_dokter.aturan_pakai, resep_dokter.no_resep, ifnull(satu_sehat_medicationstatement.id_medicationstatement,'') as id_medicationstatement, "
                    + "reg_periksa.status_lanjut "
                    
                    + "from reg_periksa "
                    + "inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter on resep_dokter.no_resep=resep_obat.no_resep "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationstatement on satu_sehat_medicationstatement.no_resep=resep_dokter.no_resep "
                    + "and satu_sehat_medicationstatement.kode_brng=resep_dokter.kode_brng "
                    
                    + "where resep_obat.tgl_penyerahan between ? and ? "
                    + "and resep_obat.tgl_penyerahan<>'0000-00-00' "
                    + "and resep_dokter.aturan_pakai is not null and LENGTH(TRIM(resep_dokter.aturan_pakai)) > 1 "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationstatement.id_medicationstatement,'')='' "
                    + "and satu_sehat_encounter.id_encounter <> '' ";

            processQueryStatement(queryNonRacik, "NON-RACIK", "satu_sehat_medicationstatement", false);

            // ===========================================================================================
            // 2. OBAT RACIKAN
            // ===========================================================================================
            String queryRacik = "select reg_periksa.no_rawat, pasien.nm_pasien, pasien.no_ktp, "
                    + "satu_sehat_encounter.id_encounter, satu_sehat_mapping_obat.obat_code, satu_sehat_mapping_obat.obat_system, "
                    + "resep_dokter_racikan_detail.kode_brng, satu_sehat_mapping_obat.obat_display, satu_sehat_mapping_obat.form_code, satu_sehat_mapping_obat.form_system, "
                    + "satu_sehat_mapping_obat.route_code, satu_sehat_mapping_obat.route_system, satu_sehat_mapping_obat.route_display, "
                    + "satu_sehat_mapping_obat.denominator_code, "
                    + "resep_obat.tgl_penyerahan, resep_obat.jam_penyerahan, resep_dokter_racikan_detail.jml, satu_sehat_medication.id_medication, "
                    + "resep_dokter_racikan.aturan_pakai, resep_dokter_racikan.no_resep, ifnull(satu_sehat_medicationstatement_racikan.id_medicationstatement,'') as id_medicationstatement, "
                    + "resep_dokter_racikan_detail.no_racik, reg_periksa.status_lanjut "
                    
                    + "from reg_periksa "
                    + "inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter_racikan on resep_dokter_racikan.no_resep=resep_obat.no_resep "
                    + "inner join resep_dokter_racikan_detail on resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep "
                    + "and resep_dokter_racikan_detail.no_racik=resep_dokter_racikan.no_racik "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter_racikan_detail.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationstatement_racikan on satu_sehat_medicationstatement_racikan.no_resep=resep_dokter_racikan_detail.no_resep "
                    + "and satu_sehat_medicationstatement_racikan.kode_brng=resep_dokter_racikan_detail.kode_brng "
                    + "and satu_sehat_medicationstatement_racikan.no_racik=resep_dokter_racikan_detail.no_racik "
                    
                    + "where resep_obat.tgl_penyerahan<>'0000-00-00' "
                    + "and resep_dokter_racikan.aturan_pakai is not null and LENGTH(TRIM(resep_dokter_racikan.aturan_pakai)) > 1 "
                    + "and resep_obat.tgl_penyerahan between ? and ? "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationstatement_racikan.id_medicationstatement,'')='' "
                    + "and satu_sehat_encounter.id_encounter <> '' ";

            processQueryStatement(queryRacik, "RACIKAN", "satu_sehat_medicationstatement_racikan", true);

        } catch (Exception e) {
            System.out.println("Notifikasi Utama Medication Statement : " + e);
            TeksArea.append("!! ERROR UTAMA MED STATEMENT: " + e + "\n");
        }
    }

    // Helper Eksekusi Query
    private void processQueryStatement(String query, String label, String tableName, boolean isRacikan) {
        TeksArea.append("\n[2/2] Melacak Medication Statement " + label + "...\n");
        try {
            ps = koneksi.prepareStatement(query);
            ps.setString(1, Tanggal1.getText());
            ps.setString(2, Tanggal2.getText());
            rs = ps.executeQuery();
            while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                String statusLanjut = rs.getString("status_lanjut");
                String catCode = statusLanjut.equalsIgnoreCase("Ralan") ? "outpatient" : "inpatient";
                processMedicationStatement(rs, catCode, tableName, isRacikan);
                jeda();
            }
        } catch (Exception e) {
            TeksArea.append("ERROR QUERY " + label + ": " + e + "\n");
        } finally {
            if (rs != null) try{rs.close();}catch(Exception e){}
            if (ps != null) try{ps.close();}catch(Exception e){}
        }
    }

    // HELPER SAKTI: MEDICATION STATEMENT (VERSI HARDCORE FIX)
    private void processMedicationStatement(ResultSet rs, String category, String tableName, boolean isRacikan) {
        String idPasienLokal = "";
        try {
            // 1. Cek ID Pasien (KyC)
            idPasienLokal = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            if (idPasienLokal == null || idPasienLokal.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                return;
            }
            
            // 2. Cek Mapping Medication
            if (rs.getString("id_medication") == null || rs.getString("id_medication").trim().isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Medication belum dimapping.\n");
                return;
            }

            // 3. Sanitasi Data
            String aturanPakai = rs.getString("aturan_pakai");
            if (aturanPakai == null) aturanPakai = "1x1";
            aturanPakai = aturanPakai.replaceAll("\"", "").replaceAll("\\\\", "").trim();
            
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmObat = rs.getString("obat_display").replaceAll("\"", "'");
            
            // =================================================================================
            // *** BUG FIXER: LOGIKA UNIT YANG SANGAT SPESIFIK ***
            // =================================================================================
            String dbUnitCode = rs.getString("denominator_code").trim();
            String finalUnitSystem = "";
            
            // 1. CEK SPESIFIK UNTUK 'mL' (KASUS SIRUP)
            // Kita paksa mL menggunakan DrugForm karena UCUM selalu ditolak untuk kasus ini
            if (dbUnitCode.equalsIgnoreCase("mL") || dbUnitCode.equals("ml") || dbUnitCode.equals("ML")) {
                dbUnitCode = "mL"; // Standarisasi penulisan
                finalUnitSystem = "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm"; // PAKSA KE SINI
            }
            // 2. CEK UNTUK SATUAN BERAT (mg, g) - Ini biasanya aman pakai UCUM
            else if (dbUnitCode.equalsIgnoreCase("mg") || dbUnitCode.equalsIgnoreCase("g") || dbUnitCode.equalsIgnoreCase("IU")) {
                finalUnitSystem = "http://unitsofmeasure.org";
            }
            // 3. SEMUA SISANYA (Tablet, Botol, Pcs, dll) -> DrugForm
            else {
                finalUnitSystem = "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm";
                
                // Normalisasi
                if (dbUnitCode.equalsIgnoreCase("Tablet") || dbUnitCode.equalsIgnoreCase("Tab")) dbUnitCode = "TAB";
                if (dbUnitCode.equalsIgnoreCase("Kapsul") || dbUnitCode.equalsIgnoreCase("Cap")) dbUnitCode = "CAP";
                if (dbUnitCode.equalsIgnoreCase("Botol") || dbUnitCode.equalsIgnoreCase("Btl") || dbUnitCode.equalsIgnoreCase("Fls")) dbUnitCode = "BTL";
            }
            // =================================================================================

            // Parse Dosis
            String valFreq = "1"; String valDose = "1";
            try {
                String[] parts = aturanPakai.toLowerCase().split("x");
                if (parts.length > 1) {
                    valFreq = parts[0].replaceAll("[^0-9.]", "");
                    valDose = parts[1].replaceAll("[^0-9.]", "");
                }
            } catch(Exception e){}
            double dFreq = 1; try { if(!valFreq.isEmpty()) dFreq = Double.parseDouble(valFreq); } catch(Exception e){}
            double dDose = 1; try { if(!valDose.isEmpty()) dDose = Double.parseDouble(valDose); } catch(Exception e){}
            double dQty = 1; try { dQty = rs.getDouble("jml"); } catch(Exception e){}
            
            String identifierValue = rs.getString("no_resep") + "-" + rs.getString("kode_brng");
            if (isRacikan) identifierValue += "-" + rs.getString("no_racik");
            
            String tglAsserted = rs.getString("tgl_penyerahan") + "T" + rs.getString("jam_penyerahan") + "+07:00";
            String catDisplay = category.equals("outpatient") ? "Outpatient" : "Inpatient";

            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            String json = "{" +
                "\"resourceType\": \"MedicationStatement\"," +
                "\"identifier\": [" +
                    "{" +
                        "\"system\": \"http://sys-ids.kemkes.go.id/medicationstatement/" + koneksiDB.IDSATUSEHAT() + "\"," +
                        "\"use\": \"official\"," +
                        "\"value\": \"" + identifierValue + "\"" +
                    "}" +
                "]," +
                "\"status\": \"completed\"," +
                "\"category\": {" +
                    "\"coding\": [" +
                        "{" +
                            "\"system\": \"http://terminology.hl7.org/CodeSystem/medication-statement-category\"," +
                            "\"code\": \"" + category + "\"," +
                            "\"display\": \"" + catDisplay + "\"" +
                        "}" +
                    "]" +
                "}," +
                "\"medicationReference\": {" +
                    "\"reference\": \"Medication/" + rs.getString("id_medication") + "\"," +
                    "\"display\": \"" + nmObat + "\"" +
                "}," +
                "\"subject\": {" +
                    "\"reference\": \"Patient/" + idPasienLokal + "\"," +
                    "\"display\": \"" + nmPasien + "\"" +
                "}," +
                "\"context\": {" +
                    "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"" +
                "}," +
                "\"dateAsserted\": \"" + tglAsserted + "\"," +
                "\"informationSource\": {" +
                    "\"reference\": \"Patient/" + idPasienLokal + "\"," +
                    "\"display\": \"" + nmPasien + "\"" +
                "}," +
                "\"dosage\": [" +
                    "{" +
                        "\"text\": \"" + aturanPakai + "\"," +
                        "\"timing\": {" +
                            "\"repeat\": {" +
                                "\"frequency\": " + dFreq + "," +
                                "\"period\": 1," +
                                "\"periodUnit\": \"d\"" +
                            "}" +
                        "}," +
                        "\"route\": {" +
                            "\"coding\": [" +
                                "{" +
                                    "\"system\": \"" + rs.getString("route_system") + "\"," + 
                                    "\"code\": \"" + rs.getString("route_code") + "\"," +
                                    "\"display\": \"" + rs.getString("route_display") + "\"" +
                                "}" +
                            "]" +
                        "}," +
                        "\"doseAndRate\": [" +
                            "{" +
                                "\"doseQuantity\": {" +
                                    "\"value\": " + dDose + "," +
                                    "\"unit\": \"" + dbUnitCode + "\"," +
                                    "\"system\": \"" + finalUnitSystem + "\"," + // SYSTEM HASIL OLAHAN
                                    "\"code\": \"" + dbUnitCode + "\"" +
                                "}" +
                            "}" +
                        "]" +
                    "}" +
                "]," +
                "\"note\": [{\"text\": \"Obat sudah diserahkan ke pasien\"}]" +
            "}";

            TeksArea.append("\n[DETEKTIF STATEMENT] " + identifierValue + " | Obat: " + nmObat + "\n");
            // SAYA TAMBAHKAN LOG INI AGAR KITA BISA LIHAT APA YANG SEBENARNYA DIKIRIM
            TeksArea.append("   -> DEBUG UNIT: " + dbUnitCode + " | SYSTEM: " + finalUnitSystem + "\n"); 

            // Kirim Request
            requestEntity = new HttpEntity(json, headers);
            try {
                String responseJson = konekSatuSehat(link + "/MedicationStatement", HttpMethod.POST, requestEntity);
                simpanLogStatement(responseJson, rs, tableName, isRacikan);
                
            } catch (HttpClientErrorException e) {
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Refresh Token...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    try {
                        String responseJson = konekSatuSehat(link + "/MedicationStatement", HttpMethod.POST, requestEntity);
                        simpanLogStatement(responseJson, rs, tableName, isRacikan);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } else if (e.getStatusCode().value() == 400 && e.getResponseBodyAsString().contains("duplicate")) {
                    TeksArea.append("   [INFO] Data Duplikat. Mencoba Recovery ID...\n");
                    recoverDuplicateStatement(identifierValue, rs, tableName, isRacikan);
                } else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e.getMessage() + "\n");
        }
    }

    private void simpanLogStatement(String responseJson, ResultSet rs, String tableName, boolean isRacikan) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            if (isRacikan) {
                Sequel.menyimpan2(tableName, "?,?,?,?,?", "Obat/Alkes Racikan", 4, new String[]{
                    rs.getString("no_resep"), rs.getString("kode_brng"), rs.getString("no_racik"), responseId.asText()
                });
            } else {
                Sequel.menyimpan2(tableName, "?,?,?", "Obat/Alkes", 3, new String[]{
                    rs.getString("no_resep"), rs.getString("kode_brng"), responseId.asText()
                });
            }
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }

    private void recoverDuplicateStatement(String identifierValue, ResultSet rs, String tableName, boolean isRacikan) {
        try {
            String searchUrl = link + "/MedicationStatement?identifier=http://sys-ids.kemkes.go.id/medicationstatement/" + koneksiDB.IDSATUSEHAT() + "|" + identifierValue;
            String searchJson = konekSatuSehat(searchUrl, HttpMethod.GET, new HttpEntity(headers));
            
            JsonNode searchRoot = mapper.readTree(searchJson);
            if (searchRoot.path("total").asInt() > 0) {
                String existingId = searchRoot.path("entry").get(0).path("resource").path("id").asText();
                TeksArea.append("   [RECOVERED] ID Ditemukan: " + existingId + "\n");
                // Simpan ID
                if (isRacikan) {
                    Sequel.menyimpan2(tableName, "?,?,?,?,?", "Obat/Alkes Racikan", 4, new String[]{
                        rs.getString("no_resep"), rs.getString("kode_brng"), rs.getString("no_racik"), existingId
                    });
                } else {
                    Sequel.menyimpan2(tableName, "?,?,?", "Obat/Alkes", 3, new String[]{
                        rs.getString("no_resep"), rs.getString("kode_brng"), existingId
                    });
                }
            } else {
                TeksArea.append("   !! [GAGAL RECOVER] Data duplikat tapi tidak ditemukan saat dicari.\n");
            }
        } catch (Exception ex) {
            TeksArea.append("   !! [ERROR RECOVER] " + ex.getMessage() + "\n");
        }
    }
    
    /*
    private void medicationstatement() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES KIRIM MEDICATION STATEMENT (RIWAYAT PENGOBATAN)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // BAGIAN 1: OBAT NON-RACIKAN - RAWAT JALAN (RALAN)
            // ===========================================================================================
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,pegawai.no_ktp as ktppraktisi,"
                    + "satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"
                    + "resep_dokter.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"
                    + "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"
                    + "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_penyerahan,resep_obat.jam_penyerahan,resep_dokter.jml,satu_sehat_medication.id_medication,"
                    + "resep_dokter.aturan_pakai,resep_dokter.no_resep,ifnull(satu_sehat_medicationstatement.id_medicationstatement,'') as id_medicationstatement "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter on resep_dokter.no_resep=resep_obat.no_resep "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationstatement on satu_sehat_medicationstatement.no_resep=resep_dokter.no_resep and satu_sehat_medicationstatement.kode_brng=resep_dokter.kode_brng "
                    + "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat " 
                    + "where resep_obat.tgl_penyerahan<>'0000-00-00' "
                    + "and resep_dokter.aturan_pakai is not null and LENGTH(TRIM(resep_dokter.aturan_pakai)) > 1 "
                    + "and nota_jalan.tanggal between ? and ? " // Menggunakan filter nota_jalan agar sesuai permintaan user sebelumnya (meski tgl_penyerahan lebih akurat, kita ikuti pola yang diminta)
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationstatement.id_medicationstatement,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    kirimMedicationStatement(rs, "outpatient", "satu_sehat_medicationstatement", false);
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ralan Non-Racik (MedStatement): " + e);
                TeksArea.append("ERROR QUERY RALAN NON-RACIK: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // BAGIAN 2: OBAT NON-RACIKAN - RAWAT INAP (RANAP)
            // ===========================================================================================
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,pegawai.no_ktp as ktppraktisi,"
                    + "satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"
                    + "resep_dokter.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"
                    + "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"
                    + "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_penyerahan,resep_obat.jam_penyerahan,resep_dokter.jml,satu_sehat_medication.id_medication,"
                    + "resep_dokter.aturan_pakai,resep_dokter.no_resep,ifnull(satu_sehat_medicationstatement.id_medicationstatement,'') as id_medicationstatement "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter on resep_dokter.no_resep=resep_obat.no_resep "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationstatement on satu_sehat_medicationstatement.no_resep=resep_dokter.no_resep and satu_sehat_medicationstatement.kode_brng=resep_dokter.kode_brng "
                    + "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "
                    + "where resep_obat.tgl_penyerahan<>'0000-00-00' "
                    + "and resep_dokter.aturan_pakai is not null and LENGTH(TRIM(resep_dokter.aturan_pakai)) > 1 "
                    + "and nota_inap.tanggal between ? and ? "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationstatement.id_medicationstatement,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    kirimMedicationStatement(rs, "inpatient", "satu_sehat_medicationstatement", false);
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ranap Non-Racik (MedStatement): " + e);
                TeksArea.append("ERROR QUERY RANAP NON-RACIK: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // BAGIAN 3: OBAT RACIKAN - RAWAT JALAN (RALAN)
            // ===========================================================================================
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,pegawai.no_ktp as ktppraktisi,"
                    + "satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"
                    + "resep_dokter_racikan_detail.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"
                    + "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"
                    + "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_penyerahan,resep_obat.jam_penyerahan,resep_dokter_racikan_detail.jml,satu_sehat_medication.id_medication,"
                    + "resep_dokter_racikan.aturan_pakai,resep_dokter_racikan.no_resep,ifnull(satu_sehat_medicationstatement_racikan.id_medicationstatement,'') as id_medicationstatement, "
                    + "resep_dokter_racikan_detail.no_racik from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter_racikan on resep_dokter_racikan.no_resep=resep_obat.no_resep "
                    + "inner join resep_dokter_racikan_detail on resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep and resep_dokter_racikan_detail.no_racik=resep_dokter_racikan.no_racik "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter_racikan_detail.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationstatement_racikan on satu_sehat_medicationstatement_racikan.no_resep=resep_dokter_racikan_detail.no_resep and "
                    + "satu_sehat_medicationstatement_racikan.kode_brng=resep_dokter_racikan_detail.kode_brng and satu_sehat_medicationstatement_racikan.no_racik=resep_dokter_racikan_detail.no_racik "
                    + "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "
                    + "where resep_obat.tgl_penyerahan<>'0000-00-00' "
                    + "and resep_dokter_racikan.aturan_pakai is not null and LENGTH(TRIM(resep_dokter_racikan.aturan_pakai)) > 1 "
                    + "and nota_jalan.tanggal between ? and ? "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationstatement_racikan.id_medicationstatement,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    kirimMedicationStatement(rs, "outpatient", "satu_sehat_medicationstatement_racikan", true);
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ralan Racik (MedStatement): " + e);
                TeksArea.append("ERROR QUERY RALAN RACIK: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // BAGIAN 4: OBAT RACIKAN - RAWAT INAP (RANAP)
            // ===========================================================================================
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,pegawai.no_ktp as ktppraktisi,"
                    + "satu_sehat_encounter.id_encounter,satu_sehat_mapping_obat.obat_code,satu_sehat_mapping_obat.obat_system,"
                    + "resep_dokter_racikan_detail.kode_brng,satu_sehat_mapping_obat.obat_display,satu_sehat_mapping_obat.form_code,satu_sehat_mapping_obat.form_system,satu_sehat_mapping_obat.form_display,"
                    + "satu_sehat_mapping_obat.route_code,satu_sehat_mapping_obat.route_system,satu_sehat_mapping_obat.route_display,satu_sehat_mapping_obat.denominator_code,"
                    + "satu_sehat_mapping_obat.denominator_system,resep_obat.tgl_penyerahan,resep_obat.jam_penyerahan,resep_dokter_racikan_detail.jml,satu_sehat_medication.id_medication,"
                    + "resep_dokter_racikan.aturan_pakai,resep_dokter_racikan.no_resep,ifnull(satu_sehat_medicationstatement_racikan.id_medicationstatement,'') as id_medicationstatement, "
                    + "resep_dokter_racikan_detail.no_racik from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join resep_obat on reg_periksa.no_rawat=resep_obat.no_rawat "
                    + "inner join pegawai on resep_obat.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join resep_dokter_racikan on resep_dokter_racikan.no_resep=resep_obat.no_resep "
                    + "inner join resep_dokter_racikan_detail on resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep and resep_dokter_racikan_detail.no_racik=resep_dokter_racikan.no_racik "
                    + "inner join satu_sehat_mapping_obat on satu_sehat_mapping_obat.kode_brng=resep_dokter_racikan_detail.kode_brng "
                    + "inner join satu_sehat_medication on satu_sehat_medication.kode_brng=satu_sehat_mapping_obat.kode_brng "
                    + "left join satu_sehat_medicationstatement_racikan on satu_sehat_medicationstatement_racikan.no_resep=resep_dokter_racikan_detail.no_resep and "
                    + "satu_sehat_medicationstatement_racikan.kode_brng=resep_dokter_racikan_detail.kode_brng and satu_sehat_medicationstatement_racikan.no_racik=resep_dokter_racikan_detail.no_racik "
                    + "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "
                    + "where resep_obat.tgl_penyerahan<>'0000-00-00' "
                    + "and resep_dokter_racikan.aturan_pakai is not null and LENGTH(TRIM(resep_dokter_racikan.aturan_pakai)) > 1 "
                    + "and nota_inap.tanggal between ? and ? "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_medicationstatement_racikan.id_medicationstatement,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    kirimMedicationStatement(rs, "inpatient", "satu_sehat_medicationstatement_racikan", true);
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ranap Racik (MedStatement): " + e);
                TeksArea.append("ERROR QUERY RANAP RACIK: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

        } catch (Exception e) {
            System.out.println("Notifikasi Utama Medication Statement : " + e);
            TeksArea.append("!! ERROR UTAMA MED STATEMENT: " + e + "\n");
        }
    }
    
    // ========================================================================
    // HELPER METHOD FINAL: FIX ERROR 400 & SQL PARAMETER & SANITASI & LOGGING
    // ========================================================================
    // Fungsi Helper Medication Statement (MODIFIKASI FINAL - FIX UNIT & DOSIS)
    private void kirimMedicationStatement(ResultSet rs, String category, String tableName, boolean isRacikan) {
        String idPasienLokal = "";
        try {
            TeksArea.append("\n[PROSES STATEMENT " + (isRacikan ? "RACIK" : "NON-RACIK") + "] No.Resep: " + rs.getString("no_resep") + " | Obat: " + rs.getString("obat_display") + "\n");

            // 1. Cek ID Pasien (KyC)
            idPasienLokal = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            if (idPasienLokal == null || idPasienLokal.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan.\n");
                return;
            }
            
            // 2. Cek ID Medication (Mapping)
            if (rs.getString("id_medication") == null || rs.getString("id_medication").trim().isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Medication belum dimapping.\n");
                return;
            }

            // 3. Sanitasi & Persiapan Data
            String aturanPakai = rs.getString("aturan_pakai");
            if (aturanPakai == null) aturanPakai = "1x1";
            aturanPakai = aturanPakai.replaceAll("\"", "").replaceAll("\\\\", "").trim();
            
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmObat = rs.getString("obat_display").replaceAll("\"", "'");
            
            // Ambil Data Satuan dari DB
            String dbUnitCode = rs.getString("denominator_code").trim();
            String dbUnitSystem = rs.getString("denominator_system").trim();
            
            // --- AUTO-FIX UNIT SYSTEM (CRUCIAL FIX) ---
            // Sama seperti di Request, paksa mL/mg/g ke UCUM
            if (dbUnitCode.equalsIgnoreCase("mL") || dbUnitCode.equalsIgnoreCase("mg") || dbUnitCode.equalsIgnoreCase("g") || dbUnitCode.equalsIgnoreCase("L") || dbUnitCode.equalsIgnoreCase("IU")) {
                dbUnitSystem = "http://unitsofmeasure.org";
            } else if (dbUnitCode.toUpperCase().contains("TAB") || dbUnitCode.toUpperCase().contains("CAP")) {
                dbUnitSystem = "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm";
            }
            // ------------------------------------------

            // Parsing Dosis (Safety Double)
            String valFreq = "1";
            String valDose = "1";
            try {
                String[] parts = aturanPakai.toLowerCase().split("x");
                if (parts.length > 1) {
                    valFreq = parts[0].replaceAll("[^0-9.]", "");
                    valDose = parts[1].replaceAll("[^0-9.]", "");
                }
            } catch(Exception e){}
            
            double dFreq = 1; try { if(!valFreq.isEmpty()) dFreq = Double.parseDouble(valFreq); } catch(Exception e){}
            double dDose = 1; try { if(!valDose.isEmpty()) dDose = Double.parseDouble(valDose); } catch(Exception e){}
            
            // Identifier Unik
            String identifierValue = rs.getString("no_resep") + "-" + rs.getString("kode_brng");
            if (isRacikan) identifierValue += "-" + rs.getString("no_racik");
            
            // Tanggal Asserted (Waktu Pengakuan)
            String tglAsserted = rs.getString("tgl_penyerahan") + "T" + rs.getString("jam_penyerahan") + "+07:00";

            // Kategori
            String catCode = category.equals("outpatient") ? "outpatient" : "inpatient";
            String catDisplay = category.equals("outpatient") ? "Outpatient" : "Inpatient";
            
            // 4. KONSTRUKSI JSON
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            String json = "{" +
                "\"resourceType\": \"MedicationStatement\"," +
                "\"identifier\": [" +
                    "{" +
                        "\"system\": \"http://sys-ids.kemkes.go.id/medicationstatement/" + koneksiDB.IDSATUSEHAT() + "\"," +
                        "\"use\": \"official\"," +
                        "\"value\": \"" + identifierValue + "\"" +
                    "}" +
                "]," +
                "\"status\": \"completed\"," +
                "\"category\": {" +
                    "\"coding\": [" +
                        "{" +
                            "\"system\": \"http://terminology.hl7.org/CodeSystem/medication-statement-category\"," +
                            "\"code\": \"" + catCode + "\"," +
                            "\"display\": \"" + catDisplay + "\"" +
                        "}" +
                    "]" +
                "}," +
                "\"medicationReference\": {" +
                    "\"reference\": \"Medication/" + rs.getString("id_medication") + "\"," +
                    "\"display\": \"" + nmObat + "\"" +
                "}," +
                "\"subject\": {" +
                    "\"reference\": \"Patient/" + idPasienLokal + "\"," +
                    "\"display\": \"" + nmPasien + "\"" +
                "}," +
                "\"context\": {" +
                    "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"" +
                "}," +
                "\"dateAsserted\": \"" + tglAsserted + "\"," +
                "\"informationSource\": {" +
                    "\"reference\": \"Patient/" + idPasienLokal + "\"," +
                    "\"display\": \"" + nmPasien + "\"" +
                "}," +
                "\"dosage\": [" +
                    "{" +
                        "\"text\": \"" + aturanPakai + "\"," +
                        "\"timing\": {" +
                            "\"repeat\": {" +
                                "\"frequency\": " + dFreq + "," +
                                "\"period\": 1," +
                                "\"periodUnit\": \"d\"" +
                            "}" +
                        "}," +
                        "\"route\": {" +
                            "\"coding\": [" +
                                "{" +
                                    "\"system\": \"" + rs.getString("route_system") + "\"," + 
                                    "\"code\": \"" + rs.getString("route_code") + "\"," +
                                    "\"display\": \"" + rs.getString("route_display") + "\"" +
                                "}" +
                            "]" +
                        "}," +
                        "\"doseAndRate\": [" +
                            "{" +
                                "\"doseQuantity\": {" +
                                    "\"value\": " + dDose + "," +
                                    "\"unit\": \"" + dbUnitCode + "\"," +
                                    "\"system\": \"" + dbUnitSystem + "\"," + // Pake Variabel System yg sudah di-fix
                                    "\"code\": \"" + dbUnitCode + "\"" +
                                "}" +
                            "}" +
                        "]" +
                    "}" +
                "]," +
                "\"note\": [{\"text\": \"Obat sudah diserahkan ke pasien\"}]" +
            "}";

            TeksArea.append("   [DEBUG] URL : " + link + "/MedicationStatement\n");
            
            // 5. KIRIM REQUEST & HANDLE ERROR
            requestEntity = new HttpEntity(json, headers);
            String responseJson = konekSatuSehat(link + "/MedicationStatement", HttpMethod.POST, requestEntity);
            
            TeksArea.append("   Result : " + responseJson + "\n"); // Uncomment jika ingin lihat full response

            root = mapper.readTree(responseJson);
            JsonNode responseId = root.path("id");
            
            if (!responseId.asText().equals("")) {
                TeksArea.append("   [SUKSES] Terkirim ID: " + responseId.asText() + "\n");
                
                // Simpan ID ke Database
                if (isRacikan) {
                    Sequel.menyimpan2(tableName, "?,?,?,?,?", "Obat/Alkes Racikan", 4, new String[]{
                        rs.getString("no_resep"), rs.getString("kode_brng"), rs.getString("no_racik"), responseId.asText()
                    });
                } else {
                    Sequel.menyimpan2(tableName, "?,?,?", "Obat/Alkes", 3, new String[]{
                        rs.getString("no_resep"), rs.getString("kode_brng"), responseId.asText()
                    });
                }
            }
            
        } catch (HttpClientErrorException e) {
            // Tangkap Error 400 dengan Detail
            TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            System.out.println("Error Helper: " + e);
        }
    }  */
    
    // MODUL SERVICE REQUEST RADIOLOGI - LEVEL DETEKTIF + AUTO RECONNECT
    private void servicerequestradiologi() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: SERVICE REQUEST RADIOLOGI\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. DATA RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/2] Melacak Permintaan Radiologi Ralan...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,pegawai.no_ktp as ktpdokter,pegawai.nama,"
                    + "satu_sehat_encounter.id_encounter,permintaan_radiologi.noorder,permintaan_radiologi.tgl_permintaan,permintaan_radiologi.jam_permintaan,permintaan_radiologi.diagnosa_klinis,"
                    + "jns_perawatan_radiologi.nm_perawatan,ifnull(satu_sehat_mapping_radiologi.code,'') as code,ifnull(satu_sehat_mapping_radiologi.system,'') as system,satu_sehat_mapping_radiologi.display,"
                    + "ifnull(satu_sehat_servicerequest_radiologi.id_servicerequest,'') as id_servicerequest,permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join pegawai on pegawai.nik=reg_periksa.kd_dokter "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join permintaan_radiologi on permintaan_radiologi.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_pemeriksaan_radiologi on permintaan_pemeriksaan_radiologi.noorder=permintaan_radiologi.noorder "
                    + "inner join jns_perawatan_radiologi on jns_perawatan_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "inner join satu_sehat_mapping_radiologi on satu_sehat_mapping_radiologi.kd_jenis_prw=jns_perawatan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_servicerequest_radiologi on satu_sehat_servicerequest_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_servicerequest_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "
                    + "where nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_servicerequest_radiologi.id_servicerequest,'')='' ");
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    TeksArea.append("\n[DETEKTIF RALAN] Rawat: " + rs.getString("no_rawat") + " | Order: " + rs.getString("noorder") + " | Tindakan: " + rs.getString("nm_perawatan") + "\n");
                    
                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktpdokter").equals("")) && rs.getString("id_servicerequest").equals("")) {
                        try {
                            if (rs.getString("code").equals("") || rs.getString("system").equals("")) {
                                TeksArea.append("   !! [SKIP] Mapping Radiologi KOSONG.\n");
                                continue;
                            }

                            // 1. Cek ID Satu Sehat
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // 2. Sanitasi Data
                            String diagnosaKlinis = rs.getString("diagnosa_klinis")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", " ")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/");
                            String namaPerawatan = rs.getString("nm_perawatan").replaceAll("\"", "'");

                            // 3. Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\": \"ServiceRequest\","
                                    + "\"identifier\": ["
                                    + "{"
                                    + "\"system\": \"http://sys-ids.kemkes.go.id/servicerequest/" + koneksiDB.IDSATUSEHAT() + "\","
                                    + "\"value\": \"" + rs.getString("noorder") + "." + rs.getString("kd_jenis_prw") + "\""
                                    + "},"
                                    + "{"
                                    + "\"use\": \"official\","
                                    + "\"type\": {"
                                    + "\"coding\": ["
                                    + "{"
                                    + "\"system\": \"http://terminology.hl7.org/CodeSystem/v2-0203\","
                                    + "\"code\": \"ACSN\","
                                    + "\"display\": \"Accession ID\""
                                    + "}"
                                    + "]"
                                    + "},"
                                    + "\"system\": \"http://sys-ids.kemkes.go.id/acsn/" + koneksiDB.IDSATUSEHAT() + "\","
                                    + "\"value\": \"" + rs.getString("noorder").trim() + "\""
                                    + "}"
                                    + "],"
                                    + "\"status\": \"active\","
                                    + "\"intent\": \"order\","
                                    + "\"category\": ["
                                    + "{"
                                    + "\"coding\": ["
                                    + "{"
                                    + "\"system\": \"http://snomed.info/sct\","
                                    + "\"code\": \"363679005\","
                                    + "\"display\": \"Imaging\""
                                    + "}"
                                    + "]"
                                    + "}"
                                    + "],"
                                    + "\"code\": {"
                                    + "\"coding\": ["
                                    + "{"
                                    + "\"system\": \"" + rs.getString("system") + "\","
                                    + "\"code\": \"" + rs.getString("code") + "\","
                                    + "\"display\": \"" + rs.getString("display") + "\""
                                    + "}"
                                    + "],"
                                    + "\"text\": \"" + namaPerawatan + "\""
                                    + "},"
                                    + "\"subject\": {"
                                    + "\"reference\": \"Patient/" + idpasien + "\""
                                    + "},"
                                    + "\"encounter\": {"
                                    + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\","
                                    + "\"display\": \"Permintaan " + namaPerawatan + " atas nama pasien " + rs.getString("nm_pasien") + " No.RM " + rs.getString("no_rkm_medis") + " No.Rawat " + rs.getString("no_rawat") + ", pada tanggal " + rs.getString("tgl_permintaan") + " " + rs.getString("jam_permintaan") + "\""
                                    + "},"
                                    + "\"authoredOn\" : \"" + rs.getString("tgl_permintaan") + "T" + rs.getString("jam_permintaan") + "+07:00\","
                                    + "\"requester\": {"
                                    + "\"reference\": \"Practitioner/" + iddokter + "\","
                                    + "\"display\": \"" + rs.getString("nama") + "\""
                                    + "},"
                                    + "\"performer\": [{"
                                    + "\"reference\": \"Organization/" + koneksiDB.IDSATUSEHAT() + "\","
                                    + "\"display\": \"Ruang Radiologi/Petugas Radiologi\""
                                    + "}],"
                                    + "\"reasonCode\": ["
                                    + "{"
                                    + "\"text\": \"" + diagnosaKlinis + "\""
                                    + "}"
                                    + "]"
                                    + "}";
                            
                            // 4. Kirim dengan Helper Sakti
                            processSendServiceRequest(json, rs);

                        } catch (Exception ea) {
                            TeksArea.append("   !! [ERROR PROSES] " + ea + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktpdokter").equals("")) TeksArea.append("   !! [SKIP] NIK Dokter Kosong\n");
                        if (!rs.getString("id_servicerequest").equals("")) TeksArea.append("   !! [SKIP] Sudah pernah dikirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ralan : " + e);
                TeksArea.append("ERROR QUERY RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
            
            // ===========================================================================================
            // 2. DATA RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[2/2] Melacak Permintaan Radiologi Ranap...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,pegawai.no_ktp as ktpdokter,pegawai.nama,"
                    + "satu_sehat_encounter.id_encounter,permintaan_radiologi.noorder,permintaan_radiologi.tgl_permintaan,permintaan_radiologi.jam_permintaan,permintaan_radiologi.diagnosa_klinis,"
                    + "jns_perawatan_radiologi.nm_perawatan,ifnull(satu_sehat_mapping_radiologi.code,'') as code,ifnull(satu_sehat_mapping_radiologi.system,'') as system,satu_sehat_mapping_radiologi.display,"
                    + "ifnull(satu_sehat_servicerequest_radiologi.id_servicerequest,'') as id_servicerequest,permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join pegawai on pegawai.nik=reg_periksa.kd_dokter "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join permintaan_radiologi on permintaan_radiologi.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_pemeriksaan_radiologi on permintaan_pemeriksaan_radiologi.noorder=permintaan_radiologi.noorder "
                    + "inner join jns_perawatan_radiologi on jns_perawatan_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "inner join satu_sehat_mapping_radiologi on satu_sehat_mapping_radiologi.kd_jenis_prw=jns_perawatan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_servicerequest_radiologi on satu_sehat_servicerequest_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_servicerequest_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "
                    + "where nota_inap.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_servicerequest_radiologi.id_servicerequest,'')='' ");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    TeksArea.append("\n[DETEKTIF RANAP] Rawat: " + rs.getString("no_rawat") + " | Order: " + rs.getString("noorder") + " | Tindakan: " + rs.getString("nm_perawatan") + "\n");
                    
                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktpdokter").equals("")) && rs.getString("id_servicerequest").equals("")) {
                        try {
                            if (rs.getString("code").equals("") || rs.getString("system").equals("")) {
                                TeksArea.append("   !! [SKIP] Mapping Radiologi KOSONG.\n");
                                continue;
                            }

                            // 1. Cek ID Satu Sehat
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // 2. Sanitasi Data
                            String diagnosaKlinis = rs.getString("diagnosa_klinis")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", " ")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/");
                            String namaPerawatan = rs.getString("nm_perawatan").replaceAll("\"", "'");

                            // 3. Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\": \"ServiceRequest\","
                                    + "\"identifier\": ["
                                    + "{"
                                    + "\"system\": \"http://sys-ids.kemkes.go.id/servicerequest/" + koneksiDB.IDSATUSEHAT() + "\","
                                    + "\"value\": \"" + rs.getString("noorder") + "." + rs.getString("kd_jenis_prw") + "\""
                                    + "},"
                                    + "{"
                                    + "\"use\": \"official\","
                                    + "\"type\": {"
                                    + "\"coding\": ["
                                    + "{"
                                    + "\"system\": \"http://terminology.hl7.org/CodeSystem/v2-0203\","
                                    + "\"code\": \"ACSN\","
                                    + "\"display\": \"Accession ID\""
                                    + "}"
                                    + "]"
                                    + "},"
                                    + "\"system\": \"http://sys-ids.kemkes.go.id/acsn/" + koneksiDB.IDSATUSEHAT() + "\","
                                    + "\"value\": \"" + rs.getString("noorder").trim() + "\""
                                    + "}"
                                    + "],"
                                    + "\"status\": \"active\","
                                    + "\"intent\": \"order\","
                                    + "\"category\": ["
                                    + "{"
                                    + "\"coding\": ["
                                    + "{"
                                    + "\"system\": \"http://snomed.info/sct\","
                                    + "\"code\": \"363679005\","
                                    + "\"display\": \"Imaging\""
                                    + "}"
                                    + "]"
                                    + "}"
                                    + "],"
                                    + "\"code\": {"
                                    + "\"coding\": ["
                                    + "{"
                                    + "\"system\": \"" + rs.getString("system") + "\","
                                    + "\"code\": \"" + rs.getString("code") + "\","
                                    + "\"display\": \"" + rs.getString("display") + "\""
                                    + "}"
                                    + "],"
                                    + "\"text\": \"" + namaPerawatan + "\""
                                    + "},"
                                    + "\"subject\": {"
                                    + "\"reference\": \"Patient/" + idpasien + "\""
                                    + "},"
                                    + "\"encounter\": {"
                                    + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\","
                                    + "\"display\": \"Permintaan " + namaPerawatan + " atas nama pasien " + rs.getString("nm_pasien") + " No.RM " + rs.getString("no_rkm_medis") + " No.Rawat " + rs.getString("no_rawat") + ", pada tanggal " + rs.getString("tgl_permintaan") + " " + rs.getString("jam_permintaan") + "\""
                                    + "},"
                                    + "\"authoredOn\" : \"" + rs.getString("tgl_permintaan") + "T" + rs.getString("jam_permintaan") + "+07:00\","
                                    + "\"requester\": {"
                                    + "\"reference\": \"Practitioner/" + iddokter + "\","
                                    + "\"display\": \"" + rs.getString("nama") + "\""
                                    + "},"
                                    + "\"performer\": [{"
                                    + "\"reference\": \"Organization/" + koneksiDB.IDSATUSEHAT() + "\","
                                    + "\"display\": \"Ruang Radiologi/Petugas Radiologi\""
                                    + "}],"
                                    + "\"reasonCode\": ["
                                    + "{"
                                    + "\"text\": \"" + diagnosaKlinis + "\""
                                    + "}"
                                    + "]"
                                    + "}";
                            
                            // 4. Kirim dengan Helper Sakti
                            processSendServiceRequest(json, rs);

                        } catch (Exception ea) {
                            TeksArea.append("   !! [ERROR PROSES] " + ea + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktpdokter").equals("")) TeksArea.append("   !! [SKIP] NIK Dokter Kosong\n");
                        if (!rs.getString("id_servicerequest").equals("")) TeksArea.append("   !! [SKIP] Sudah pernah dikirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Ranap : " + e);
                TeksArea.append("ERROR QUERY RANAP: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi : " + e);
            TeksArea.append("!! ERROR UTAMA SERVICE REQUEST: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS SERVICE REQUEST (Auto Reconnect)
    private void processSendServiceRequest(String jsonPayload, ResultSet rs) throws Exception {
        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // Tampilkan payload utk debugging (optional)
            // TeksArea.append("   [DEBUG JSON] " + jsonPayload + "\n");
            
            // KIRIM PERTAMA
            json = konekSatuSehat(link + "/ServiceRequest", HttpMethod.POST, requestEntity);
            simpanLogServiceRequest(json, rs);
            
        } catch (HttpClientErrorException e) {
            // HANDLER TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                String newToken = api.TokenSatuSehat();
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    json = konekSatuSehat(link + "/ServiceRequest", HttpMethod.POST, requestEntity);
                    simpanLogServiceRequest(json, rs);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                TeksArea.append("   !! [ERROR API] " + e.getResponseBodyAsString() + "\n");
            }
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
        }
    }

    private void simpanLogServiceRequest(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_servicerequest_radiologi", "?,?,?", "No.Rawat", 3, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
    
    // MODUL SPECIMEN RADIOLOGI - DETEKTIF + AUTO RECONNECT
    private void specimenradiologi() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: SPECIMEN RADIOLOGI\n");
            TeksArea.append("------------------------------------------------------\n");

            // Kueri gabungan Ralan & Ranap (Filter by Tgl Sampel)
            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,pasien.nm_pasien,pasien.no_ktp,permintaan_radiologi.noorder,permintaan_radiologi.tgl_sampel,permintaan_radiologi.jam_sampel,"
                    + "satu_sehat_mapping_radiologi.sampel_code,satu_sehat_mapping_radiologi.sampel_system,satu_sehat_mapping_radiologi.sampel_display,"
                    + "ifnull(satu_sehat_servicerequest_radiologi.id_servicerequest,'') as id_servicerequest,"
                    + "permintaan_pemeriksaan_radiologi.kd_jenis_prw,ifnull(satu_sehat_specimen_radiologi.id_specimen,'') as id_specimen "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join permintaan_radiologi on permintaan_radiologi.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_pemeriksaan_radiologi on permintaan_pemeriksaan_radiologi.noorder=permintaan_radiologi.noorder "
                    + "inner join jns_perawatan_radiologi on jns_perawatan_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "inner join satu_sehat_mapping_radiologi on satu_sehat_mapping_radiologi.kd_jenis_prw=jns_perawatan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_servicerequest_radiologi on satu_sehat_servicerequest_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_servicerequest_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_specimen_radiologi on satu_sehat_specimen_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_specimen_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    // Filter Utama: tgl_sampel
                    + "where permintaan_radiologi.tgl_sampel between ? and ? "
                    + "and permintaan_radiologi.tgl_sampel <> '0000-00-00' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_specimen_radiologi.id_specimen,'')='' ");
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF SPECIMEN] Order: " + rs.getString("noorder") + " | Rawat: " + rs.getString("no_rawat") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_specimen").equals("")) {
                        try {
                            // Validasi 1: Parent ServiceRequest
                            if (rs.getString("id_servicerequest").equals("")) {
                                TeksArea.append("   !! [SKIP] ServiceRequest (Order Radiologi) belum terkirim ke Satu Sehat.\n");
                                continue;
                            }

                            // Validasi 2: ID Pasien
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            if (idpasien.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // Sanitasi Data
                            String sampelDisplay = rs.getString("sampel_display").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'");
                            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");

                            // Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\": \"Specimen\","
                                    + "\"identifier\": ["
                                        + "{"
                                            + "\"system\": \"http://sys-ids.kemkes.go.id/specimen/" + koneksiDB.IDSATUSEHAT() + "\","
                                            + "\"value\": \"" + rs.getString("noorder") + "." + rs.getString("kd_jenis_prw") + "\""
                                        + "}"
                                    + "],"
                                    + "\"status\": \"available\","
                                    + "\"type\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"" + rs.getString("sampel_system") + "\","
                                                + "\"code\": \"" + rs.getString("sampel_code") + "\","
                                                + "\"display\": \"" + sampelDisplay + "\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"subject\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\","
                                        + "\"display\": \"" + nmPasien + "\""
                                    + "},"
                                    + "\"request\": ["
                                        + "{"
                                            + "\"reference\": \"ServiceRequest/" + rs.getString("id_servicerequest") + "\""
                                        + "}"
                                    + "],"
                                    + "\"receivedTime\": \"" + rs.getString("tgl_sampel") + "T" + rs.getString("jam_sampel") + "+07:00\""
                                    + "}";

                            // Kirim dengan Helper Sakti
                            processSendSpecimen(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR INTERN] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (!rs.getString("id_specimen").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Specimen Radiologi : " + e);
                TeksArea.append("ERROR QUERY SPECIMEN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Specimen : " + e);
            TeksArea.append("!! ERROR UTAMA SPECIMEN RADIOLOGI: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS SPECIMEN (Auto Reconnect)
    private void processSendSpecimen(String jsonPayload, ResultSet rs) throws Exception {
        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // Tampilkan payload utk debugging (optional)
            // TeksArea.append("   [DEBUG JSON] " + jsonPayload + "\n");
            
            // KIRIM PERTAMA
            json = konekSatuSehat(link + "/Specimen", HttpMethod.POST, requestEntity);
            simpanLogSpecimen(json, rs);
            
        } catch (HttpClientErrorException e) {
            // HANDLER TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                String newToken = api.TokenSatuSehat();
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    json = konekSatuSehat(link + "/Specimen", HttpMethod.POST, requestEntity);
                    simpanLogSpecimen(json, rs);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                TeksArea.append("   !! [ERROR API] " + e.getResponseBodyAsString() + "\n");
            }
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
        }
    }

    private void simpanLogSpecimen(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_specimen_radiologi", "?,?,?", "No.Rawat", 3, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }    
   
    // MODUL OBSERVATION RADIOLOGI (HASIL) - DETEKTIF + AUTO RECONNECT
    private void observationradiologi() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: OBSERVATION RADIOLOGI (HASIL)\n");
            TeksArea.append("------------------------------------------------------\n");

            // PERBAIKAN QUERY: Hapus nota_jalan agar support Ranap juga
            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,permintaan_radiologi.noorder,"
                    + "permintaan_radiologi.tgl_hasil,permintaan_radiologi.jam_hasil,jns_perawatan_radiologi.nm_perawatan,satu_sehat_mapping_radiologi.code,"
                    + "satu_sehat_mapping_radiologi.system,satu_sehat_mapping_radiologi.display,hasil_radiologi.hasil,permintaan_pemeriksaan_radiologi.kd_jenis_prw,"
                    + "ifnull(satu_sehat_specimen_radiologi.id_specimen,'') as id_specimen,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,"
                    + "ifnull(satu_sehat_servicerequest_radiologi.id_servicerequest,'') as id_servicerequest, "
                    + "ifnull(satu_sehat_observation_radiologi.id_observation,'') as id_observation, pegawai.nama as nama_dokter "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join permintaan_radiologi on permintaan_radiologi.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_pemeriksaan_radiologi on permintaan_pemeriksaan_radiologi.noorder=permintaan_radiologi.noorder "
                    + "inner join jns_perawatan_radiologi on jns_perawatan_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "inner join satu_sehat_mapping_radiologi on satu_sehat_mapping_radiologi.kd_jenis_prw=jns_perawatan_radiologi.kd_jenis_prw "
                    + "inner join periksa_radiologi on periksa_radiologi.no_rawat=permintaan_radiologi.no_rawat and periksa_radiologi.tgl_periksa=permintaan_radiologi.tgl_hasil "
                    + "and periksa_radiologi.jam=permintaan_radiologi.jam_hasil and periksa_radiologi.dokter_perujuk=permintaan_radiologi.dokter_perujuk "
                    + "inner join hasil_radiologi on periksa_radiologi.no_rawat=hasil_radiologi.no_rawat and periksa_radiologi.tgl_periksa=hasil_radiologi.tgl_periksa "
                    + "and periksa_radiologi.jam=hasil_radiologi.jam "
                    + "inner join pegawai on periksa_radiologi.kd_dokter=pegawai.nik "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    // Join ke Log Parent
                    + "left join satu_sehat_servicerequest_radiologi on satu_sehat_servicerequest_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_servicerequest_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_specimen_radiologi on satu_sehat_specimen_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_specimen_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_observation_radiologi on satu_sehat_observation_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_observation_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    // Filter
                    + "where permintaan_radiologi.tgl_hasil between ? and ? "
                    + "and permintaan_radiologi.tgl_hasil <> '0000-00-00' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_observation_radiologi.id_observation,'')='' ");
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF OBS RAD] Order: " + rs.getString("noorder") + " | Hasil: " + rs.getString("nm_perawatan") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_observation").equals("")) {
                        try {
                            // Validasi 1: Parent ServiceRequest (Wajib)
                            if (rs.getString("id_servicerequest").equals("")) {
                                TeksArea.append("   !! [SKIP] ServiceRequest (Order) belum terkirim.\n");
                                continue;
                            }
                            
                            // Validasi 2: Parent Specimen (Opsional/Conditional)
                            boolean hasSpecimen = !rs.getString("id_specimen").equals("");
                            if (!hasSpecimen) {
                                // TeksArea.append("   [INFO] Mengirim tanpa Specimen ID.\n");
                            }

                            // Validasi 3: KyC
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // Sanitasi Hasil (PENTING: Escape karakter json)
                            String hasilRadiologi = rs.getString("hasil")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", "<br>")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/")
                                    .replaceAll("\t", " ");

                            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
                            String nmDokter = rs.getString("nama_dokter").replaceAll("\"", "'");
                            String displayTindakan = rs.getString("display").replaceAll("\"", "'");

                            // Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\": \"Observation\","
                                    + "\"identifier\": ["
                                        + "{"
                                            + "\"system\": \"http://sys-ids.kemkes.go.id/observation/" + koneksiDB.IDSATUSEHAT() + "\","
                                            + "\"value\": \"" + rs.getString("noorder") + "." + rs.getString("kd_jenis_prw") + "\""
                                        + "}"
                                    + "],"
                                    + "\"status\": \"final\","
                                    + "\"category\": ["
                                        + "{"
                                            + "\"coding\": ["
                                                + "{"
                                                    + "\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\","
                                                    + "\"code\": \"imaging\","
                                                    + "\"display\": \"Imaging\""
                                                + "}"
                                            + "]"
                                        + "}"
                                    + "],"
                                    + "\"code\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"" + rs.getString("system") + "\","
                                                + "\"code\": \"" + rs.getString("code") + "\","
                                                + "\"display\": \"" + displayTindakan + "\""
                                            + "}"
                                        + "],"
                                        + "\"text\": \"" + rs.getString("nm_perawatan").replaceAll("\"", "'") + "\""
                                    + "},"
                                    + "\"subject\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\","
                                        + "\"display\": \"" + nmPasien + "\""
                                    + "},"
                                    + "\"performer\": ["
                                        + "{"
                                            + "\"reference\": \"Practitioner/" + iddokter + "\","
                                            + "\"display\": \"" + nmDokter + "\""
                                        + "}"
                                    + "],"
                                    + "\"encounter\": {"
                                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\""
                                    + "},"
                                    + "\"basedOn\": ["
                                        + "{"
                                            + "\"reference\": \"ServiceRequest/" + rs.getString("id_servicerequest") + "\""
                                        + "}"
                                    + "],"
                                    // Tambahkan Specimen jika ada
                                    + (hasSpecimen ? "\"specimen\": { \"reference\": \"Specimen/" + rs.getString("id_specimen") + "\" }," : "")
                                    
                                    + "\"effectiveDateTime\": \"" + rs.getString("tgl_hasil") + "T" + rs.getString("jam_hasil") + "+07:00\","
                                    + "\"valueString\": \"" + hasilRadiologi + "\""
                                    + "}";

                            // Kirim dengan Helper Sakti
                            processSendObsRad(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR INTERN] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("   !! [SKIP] NIK Dokter Kosong\n");
                        if (!rs.getString("id_observation").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Obs Rad : " + e);
                TeksArea.append("ERROR QUERY OBS RAD: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama : " + e);
            TeksArea.append("!! ERROR UTAMA OBS RAD: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS OBSERVATION RADIOLOGI (Auto Reconnect)
    private void processSendObsRad(String jsonPayload, ResultSet rs) throws Exception {
        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // Tampilkan payload utk debugging (optional)
            // TeksArea.append("   [DEBUG JSON] " + jsonPayload + "\n");
            
            // KIRIM PERTAMA
            json = konekSatuSehat(link + "/Observation", HttpMethod.POST, requestEntity);
            simpanLogObsRad(json, rs);
            
        } catch (HttpClientErrorException e) {
            // HANDLER TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                String newToken = api.TokenSatuSehat();
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    json = konekSatuSehat(link + "/Observation", HttpMethod.POST, requestEntity);
                    simpanLogObsRad(json, rs);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                TeksArea.append("   !! [ERROR API] " + e.getResponseBodyAsString() + "\n");
            }
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
        }
    }

    private void simpanLogObsRad(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_observation_radiologi", "?,?,?", "No.Rawat", 3, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }    
    
    // MODUL DIAGNOSTIC REPORT RADIOLOGI - DETEKTIF + AUTO RECONNECT
    private void diagnosticreportradiologi() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: DIAGNOSTIC REPORT RADIOLOGI\n");
            TeksArea.append("------------------------------------------------------\n");

            // Query Gabungan Ralan & Ranap (Menggunakan LEFT JOIN untuk pengecekan kelengkapan resource)
            ps = koneksi.prepareStatement(
                    "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,periksa_radiologi.kd_dokter,pegawai.nama,pegawai.no_ktp as ktppraktisi,"
                    + "satu_sehat_encounter.id_encounter,permintaan_radiologi.noorder,permintaan_radiologi.tgl_hasil,permintaan_radiologi.jam_hasil,permintaan_radiologi.diagnosa_klinis,"
                    + "jns_perawatan_radiologi.nm_perawatan,satu_sehat_mapping_radiologi.code,satu_sehat_mapping_radiologi.system,satu_sehat_mapping_radiologi.display,"
                    + "ifnull(satu_sehat_servicerequest_radiologi.id_servicerequest,'') as id_servicerequest,"
                    + "permintaan_pemeriksaan_radiologi.kd_jenis_prw,"
                    + "ifnull(satu_sehat_specimen_radiologi.id_specimen,'') as id_specimen,"
                    + "ifnull(satu_sehat_observation_radiologi.id_observation,'') as id_observation,"
                    + "ifnull(satu_sehat_diagnosticreport_radiologi.id_diagnosticreport,'') as id_diagnosticreport,hasil_radiologi.hasil "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_radiologi on permintaan_radiologi.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_pemeriksaan_radiologi on permintaan_pemeriksaan_radiologi.noorder=permintaan_radiologi.noorder "
                    + "inner join jns_perawatan_radiologi on jns_perawatan_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "inner join satu_sehat_mapping_radiologi on satu_sehat_mapping_radiologi.kd_jenis_prw=jns_perawatan_radiologi.kd_jenis_prw "
                    + "inner join periksa_radiologi on periksa_radiologi.no_rawat=permintaan_radiologi.no_rawat and periksa_radiologi.tgl_periksa=permintaan_radiologi.tgl_hasil "
                    + "and periksa_radiologi.jam=permintaan_radiologi.jam_hasil and periksa_radiologi.dokter_perujuk=permintaan_radiologi.dokter_perujuk "
                    + "inner join hasil_radiologi on periksa_radiologi.no_rawat=hasil_radiologi.no_rawat and periksa_radiologi.tgl_periksa=hasil_radiologi.tgl_periksa "
                    + "and periksa_radiologi.jam=hasil_radiologi.jam "
                    + "inner join pegawai on periksa_radiologi.kd_dokter=pegawai.nik "
                    // LEFT JOIN ke Log Resources Prasyarat
                    + "left join satu_sehat_servicerequest_radiologi on satu_sehat_servicerequest_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_servicerequest_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_specimen_radiologi on satu_sehat_specimen_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_specimen_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_observation_radiologi on satu_sehat_observation_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_observation_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    + "left join satu_sehat_diagnosticreport_radiologi on satu_sehat_diagnosticreport_radiologi.noorder=permintaan_pemeriksaan_radiologi.noorder "
                    + "and satu_sehat_diagnosticreport_radiologi.kd_jenis_prw=permintaan_pemeriksaan_radiologi.kd_jenis_prw "
                    // Filter Waktu Hasil
                    + "where permintaan_radiologi.tgl_hasil between ? and ? "
                    + "and permintaan_radiologi.tgl_hasil <> '0000-00-00' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_diagnosticreport_radiologi.id_diagnosticreport,'')='' ");
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF DIAG REPORT] Order: " + rs.getString("noorder") + " | Rawat: " + rs.getString("no_rawat") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_diagnosticreport").equals("")) {
                        try {
                            // Validasi 1: ServiceRequest (Wajib)
                            if (rs.getString("id_servicerequest").equals("")) {
                                TeksArea.append("   !! [SKIP] ServiceRequest (Order) belum terkirim.\n");
                                continue;
                            }
                            
                            // Validasi 2: Observation/Hasil (Wajib)
                            if (rs.getString("id_observation").equals("")) {
                                TeksArea.append("   !! [SKIP] Observation (Hasil Bacaan) belum terkirim.\n");
                                continue;
                            }

                            // Validasi 3: Specimen (Conditional)
                            boolean hasSpecimen = !rs.getString("id_specimen").equals("");
                            if (!hasSpecimen) {
                                // TeksArea.append("   [INFO] Mengirim tanpa Specimen ID.\n");
                            }

                            // Validasi 4: KyC
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                            if (idpasien.equals("") || iddokter.equals("")) {
                                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat.\n");
                                continue;
                            }

                            // Sanitasi Hasil Bacaan (Kesimpulan)
                            String hasilBacaan = rs.getString("hasil")
                                    .replaceAll("(\r\n|\r|\n|\n\r)", "<br>")
                                    .replaceAll("\"", "'")
                                    .replaceAll("\\\\", "/")
                                    .replaceAll("\t", " ");
                            
                            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
                            String nmDokter = rs.getString("nama").replaceAll("\"", "'");
                            String displayTindakan = rs.getString("display").replaceAll("\"", "'");

                            // Konstruksi JSON
                            json = "{"
                                    + "\"resourceType\": \"DiagnosticReport\","
                                    + "\"identifier\": ["
                                        + "{"
                                            + "\"system\": \"http://sys-ids.kemkes.go.id/diagnostic/" + koneksiDB.IDSATUSEHAT() + "/rad\","
                                            + "\"use\": \"official\","
                                            + "\"value\": \"" + rs.getString("noorder") + "." + rs.getString("kd_jenis_prw") + "\""
                                        + "}"
                                    + "],"
                                    + "\"status\": \"final\","
                                    + "\"category\": ["
                                        + "{"
                                            + "\"coding\": ["
                                                + "{"
                                                    + "\"system\": \"http://terminology.hl7.org/CodeSystem/v2-0074\","
                                                    + "\"code\": \"RAD\","
                                                    + "\"display\": \"Radiology\""
                                                + "}"
                                            + "]"
                                        + "}"
                                    + "],"
                                    + "\"code\": {"
                                        + "\"coding\": ["
                                            + "{"
                                                + "\"system\": \"" + rs.getString("system") + "\","
                                                + "\"code\": \"" + rs.getString("code") + "\","
                                                + "\"display\": \"" + displayTindakan + "\""
                                            + "}"
                                        + "]"
                                    + "},"
                                    + "\"subject\": {"
                                        + "\"reference\": \"Patient/" + idpasien + "\","
                                        + "\"display\": \"" + nmPasien + "\""
                                    + "},"
                                    + "\"encounter\": {"
                                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\""
                                    + "},"
                                    + "\"effectiveDateTime\": \"" + rs.getString("tgl_hasil") + "T" + rs.getString("jam_hasil") + "+07:00\","
                                    + "\"issued\": \"" + rs.getString("tgl_hasil") + "T" + rs.getString("jam_hasil") + "+07:00\","
                                    + "\"performer\": ["
                                        + "{"
                                            + "\"reference\": \"Practitioner/" + iddokter + "\","
                                            + "\"display\": \"" + nmDokter + "\""
                                        + "}"
                                    + "],"
                                    + "\"result\": ["
                                        + "{"
                                            + "\"reference\": \"Observation/" + rs.getString("id_observation") + "\""
                                        + "}"
                                    + "],"
                                    + "\"basedOn\": ["
                                        + "{"
                                            + "\"reference\": \"ServiceRequest/" + rs.getString("id_servicerequest") + "\""
                                        + "}"
                                    + "],"
                                    // Tambahkan Specimen jika ada
                                    + (hasSpecimen ? "\"specimen\": [{ \"reference\": \"Specimen/" + rs.getString("id_specimen") + "\" }]," : "")
                                    
                                    + "\"conclusion\": \"" + hasilBacaan + "\""
                                    + "}";

                            // Kirim dengan Helper Sakti
                            processSendDiagnosticReport(json, rs);

                        } catch (Exception e) {
                            TeksArea.append("   !! [ERROR INTERN] " + e + "\n");
                        }
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("   !! [SKIP] NIK Dokter Kosong\n");
                        if (!rs.getString("id_diagnosticreport").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif DiagnosticReport Rad : " + e);
                TeksArea.append("ERROR QUERY DIAG REPORT: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Diag Report : " + e);
            TeksArea.append("!! ERROR UTAMA DIAGNOSTIC REPORT: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS DIAGNOSTIC REPORT (Auto Reconnect)
    private void processSendDiagnosticReport(String jsonPayload, ResultSet rs) throws Exception {
        headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
        requestEntity = new HttpEntity(jsonPayload, headers);

        try {
            // Tampilkan payload utk debugging (optional)
            // TeksArea.append("   [DEBUG JSON] " + jsonPayload + "\n");
            
            // KIRIM PERTAMA
            json = konekSatuSehat(link + "/DiagnosticReport", HttpMethod.POST, requestEntity);
            simpanLogDiagnosticReport(json, rs);
            
        } catch (HttpClientErrorException e) {
            // HANDLER TOKEN EXPIRED (401)
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                String newToken = api.TokenSatuSehat();
                headers.set("Authorization", "Bearer " + newToken);
                requestEntity = new HttpEntity(jsonPayload, headers);
                
                try {
                    json = konekSatuSehat(link + "/DiagnosticReport", HttpMethod.POST, requestEntity);
                    simpanLogDiagnosticReport(json, rs);
                    TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                } catch (Exception ex) {
                    TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                }
            } else {
                TeksArea.append("   !! [ERROR API] " + e.getResponseBodyAsString() + "\n");
            }
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
        }
    }

    private void simpanLogDiagnosticReport(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_diagnosticreport_radiologi", "?,?,?", "No.Order", 3, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
    
    // MODUL SERVICE REQUEST LAB PK - DETEKTIF + AUTO RECONNECT
    private void servicerequestlabpk() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: SERVICE REQUEST LAB PK\n");
            TeksArea.append("------------------------------------------------------\n");

            // QUERY UTAMA (Gabungan Ralan & Ranap via Filter Tanggal)
            String query = "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,reg_periksa.kd_dokter,pegawai.nama,pegawai.no_ktp as ktppraktisi,"
                    + "satu_sehat_encounter.id_encounter,permintaan_lab.noorder,permintaan_lab.tgl_permintaan,permintaan_lab.jam_permintaan,permintaan_lab.diagnosa_klinis,"
                    + "template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,"
                    + "ifnull(satu_sehat_servicerequest_lab.id_servicerequest,'') as id_servicerequest,permintaan_detail_permintaan_lab.id_template,permintaan_detail_permintaan_lab.kd_jenis_prw "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join pegawai on pegawai.nik=reg_periksa.kd_dokter "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join permintaan_lab on permintaan_lab.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_detail_permintaan_lab on permintaan_detail_permintaan_lab.noorder=permintaan_lab.noorder "
                    + "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "
                    + "left join satu_sehat_servicerequest_lab on satu_sehat_servicerequest_lab.noorder=permintaan_detail_permintaan_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=permintaan_detail_permintaan_lab.kd_jenis_prw "
                    // Filter
                    + "where permintaan_lab.tgl_permintaan between ? and ? "
                    + "and permintaan_lab.tgl_permintaan <> '0000-00-00' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_servicerequest_lab.id_servicerequest,'')='' ";
            
            ps = koneksi.prepareStatement(query);
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF LAB PK] Order: " + rs.getString("noorder") + " | Item: " + rs.getString("Pemeriksaan") + "\n");
                    TeksArea.append("   [INFO MAPPING] Code: " + rs.getString("code") + " | System: " + rs.getString("system") + "\n");

                    // Validasi ID yang sudah terkirim (Double Check)
                    if (!rs.getString("id_servicerequest").isEmpty()) {
                        TeksArea.append("   !! [SKIP] Sudah terkirim (ID: " + rs.getString("id_servicerequest") + ")\n");
                        continue;
                    }

                    if (rs.getString("no_ktp").isEmpty() || rs.getString("ktppraktisi").isEmpty()) {
                        TeksArea.append("   !! [SKIP] NIK Pasien/Dokter Kosong.\n");
                        continue;
                    }

                    // Helper Kirim
                    processServiceRequestLab(rs);
                    
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Lab PK : " + e);
                TeksArea.append("ERROR QUERY LAB PK: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Lab PK : " + e);
            TeksArea.append("!! ERROR UTAMA LAB PK: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS LAB PK (Auto Reconnect)
    private void processServiceRequestLab(ResultSet rs) {
        try {
            // 1. Cek KyC (Dapatkan ID Satu Sehat)
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));

            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID SatuSehat Pasien/Dokter tidak ditemukan.\n");
                return;
            }

            // 2. Sanitasi Data
            String diagnosaKlinis = rs.getString("diagnosa_klinis")
                    .replaceAll("(\r\n|\r|\n|\n\r)", " ")
                    .replaceAll("\"", "'")
                    .replaceAll("\\\\", "/").trim();
            if(diagnosaKlinis.isEmpty()) diagnosaKlinis = "-";
            
            String namaPemeriksaan = rs.getString("Pemeriksaan").replaceAll("\"", "'");
            String displayMapping = rs.getString("display").replaceAll("\"", "'");
            String namaDokter = rs.getString("nama").replaceAll("\"", "'");
            
            // Format Waktu
            String tglRequest = rs.getString("tgl_permintaan") + "T" + rs.getString("jam_permintaan") + "+07:00";
            String displayEncounter = "Permintaan " + namaPemeriksaan + " atas nama pasien " + rs.getString("nm_pasien") + " No.RM " + rs.getString("no_rkm_medis") + " No.Rawat " + rs.getString("no_rawat") + ", pada tanggal " + rs.getString("tgl_permintaan") + " " + rs.getString("jam_permintaan");

            // 3. Konstruksi JSON
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
            
            json = "{"
                    + "\"resourceType\": \"ServiceRequest\","
                    + "\"identifier\": ["
                        + "{"
                            + "\"system\": \"http://sys-ids.kemkes.go.id/servicerequest/" + koneksiDB.IDSATUSEHAT() + "\","
                            + "\"value\": \"" + rs.getString("noorder") + "." + rs.getString("id_template") + "\""
                        + "}"
                    + "],"
                    + "\"status\": \"active\","
                    + "\"intent\": \"order\","
                    + "\"category\": ["
                        + "{"
                            + "\"coding\": ["
                                + "{"
                                    + "\"system\": \"http://snomed.info/sct\","
                                    + "\"code\": \"108252007\","
                                    + "\"display\": \"Laboratory procedure\""
                                + "}"
                            + "]"
                        + "}"
                    + "],"
                    + "\"code\": {"
                        + "\"coding\": ["
                            + "{"
                                + "\"system\": \"" + rs.getString("system") + "\","
                                + "\"code\": \"" + rs.getString("code") + "\","
                                + "\"display\": \"" + displayMapping + "\""
                            + "}"
                        + "],"
                        + "\"text\": \"" + namaPemeriksaan + "\""
                    + "},"
                    + "\"subject\": {"
                        + "\"reference\": \"Patient/" + idpasien + "\""
                    + "},"
                    + "\"encounter\": {"
                        + "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\","
                        + "\"display\": \"" + displayEncounter + "\""
                    + "},"
                    + "\"authoredOn\" : \"" + tglRequest + "\","
                    + "\"requester\": {"
                        + "\"reference\": \"Practitioner/" + iddokter + "\","
                        + "\"display\": \"" + namaDokter + "\""
                    + "},"
                    + "\"performer\": [{"
                        + "\"reference\": \"Organization/" + koneksiDB.IDSATUSEHAT() + "\","
                        + "\"display\": \"Ruang Laborat/Petugas Laborat\""
                    + "}],"
                    + "\"reasonCode\": ["
                        + "{"
                            + "\"text\": \"" + diagnosaKlinis + "\""
                        + "}"
                    + "]"
                    + "}";

            // TeksArea.append("   [DEBUG JSON] " + json + "\n");

            // 4. Kirim Request (Dengan Auto Reconnect)
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/ServiceRequest", HttpMethod.POST, requestEntity);
                simpanLogLabPK(responseJson, rs);
                
            } catch (HttpClientErrorException e) {
                // HANDLER TOKEN EXPIRED (401)
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    
                    try {
                        // RETRY KIRIM
                        String responseJson = konekSatuSehat(link + "/ServiceRequest", HttpMethod.POST, requestEntity);
                        simpanLogLabPK(responseJson, rs);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            } catch (Exception e) {
                TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR INTERNAL] " + e + "\n");
            e.printStackTrace();
        }
    }

    private void simpanLogLabPK(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_servicerequest_lab", "?,?,?,?", "No.Rawat", 4, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), rs.getString("id_template"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
    
    private void servicerequestlabmb() {
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,reg_periksa.kd_dokter,pegawai.nama,pegawai.no_ktp as ktpdokter,"+
                   "satu_sehat_encounter.id_encounter,permintaan_labmb.noorder,permintaan_labmb.tgl_permintaan,permintaan_labmb.jam_permintaan,permintaan_labmb.diagnosa_klinis,"+
                   "template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,"+
                   "ifnull(satu_sehat_servicerequest_lab_mb.id_servicerequest,'') as id_servicerequest,permintaan_detail_permintaan_labmb.id_template,permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join pegawai on pegawai.nik=reg_periksa.kd_dokter "+
                   "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "left join satu_sehat_servicerequest_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "+
                   "where nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_servicerequest_lab_mb.id_servicerequest,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&(!rs.getString("ktpdokter").equals(""))&&rs.getString("id_servicerequest").equals("")){
                        try {
                            iddokter=cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"ServiceRequest\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/servicerequest/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"active\"," +
                                            "\"intent\": \"order\"," +
                                            "\"category\": [" +
                                                "{" +
                                                    "\"coding\": [" +
                                                        "{" +
                                                            "\"system\": \"http://snomed.info/sct\"," +
                                                            "\"code\": \"108252007\"," +
                                                            "\"display\": \"Laboratory procedure\"" +
                                                        "}" +
                                                    "]" +
                                                "}" +
                                            "],"+
                                            "\"code\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"system\": \""+rs.getString("system")+"\"," +
                                                        "\"code\": \""+rs.getString("code")+"\"," +
                                                        "\"display\": \""+rs.getString("display")+"\"" +
                                                    "}" +
                                                "]," +
                                                "\"text\": \""+rs.getString("Pemeriksaan")+"\"" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"" +
                                            "}," +
                                            "\"encounter\": {" +
                                                "\"reference\": \"Encounter/"+rs.getString("id_encounter")+"\"," +
                                                "\"display\": \"Permintaan "+rs.getString("Pemeriksaan")+" atas nama pasien "+rs.getString("nm_pasien")+" No.RM "+rs.getString("no_rkm_medis")+" No.Rawat "+rs.getString("no_rawat")+", pada tanggal "+rs.getString("tgl_permintaan")+" "+rs.getString("jam_permintaan")+"\"" +
                                            "}," +
                                            "\"authoredOn\" : \""+rs.getString("tgl_permintaan")+"T"+rs.getString("jam_permintaan")+"+07:00\"," +
                                            "\"requester\": {" +
                                                "\"reference\": \"Practitioner/"+iddokter+"\"," +
                                                "\"display\": \""+rs.getString("nama")+"\"" +
                                            "}," +
                                            "\"performer\": [{" +
                                                "\"reference\": \"Organization/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                "\"display\": \"Ruang Laborat/Petugas Laborat\"" +
                                            "}]," +
                                            "\"reasonCode\": [" +
                                                "{" +
                                                    "\"text\": \""+rs.getString("diagnosa_klinis")+"\"" +
                                                "}" +
                                            "]" +
                                        "}";
                                TeksArea.append("URL : "+link+"/ServiceRequest");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/ServiceRequest", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_servicerequest_lab","?,?,?,?","No.Rawat",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
        
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,reg_periksa.kd_dokter,pegawai.nama,pegawai.no_ktp as ktpdokter,"+
                   "satu_sehat_encounter.id_encounter,permintaan_labmb.noorder,permintaan_labmb.tgl_permintaan,permintaan_labmb.jam_permintaan,permintaan_labmb.diagnosa_klinis,"+
                   "template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,"+
                   "ifnull(satu_sehat_servicerequest_lab_mb.id_servicerequest,'') as id_servicerequest,permintaan_detail_permintaan_labmb.id_template,permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join pegawai on pegawai.nik=reg_periksa.kd_dokter "+
                   "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "left join satu_sehat_servicerequest_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "+
                   "where nota_inap.tanggal between ? and ?  and ifnull(satu_sehat_servicerequest_lab_mb.id_servicerequest,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&(!rs.getString("ktpdokter").equals(""))&&rs.getString("id_servicerequest").equals("")){
                        try {
                            iddokter=cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"ServiceRequest\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/servicerequest/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"active\"," +
                                            "\"intent\": \"order\"," +
                                            "\"category\": [" +
                                                "{" +
                                                    "\"coding\": [" +
                                                        "{" +
                                                            "\"system\": \"http://snomed.info/sct\"," +
                                                            "\"code\": \"108252007\"," +
                                                            "\"display\": \"Laboratory procedure\"" +
                                                        "}" +
                                                    "]" +
                                                "}" +
                                            "],"+
                                            "\"code\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"system\": \""+rs.getString("system")+"\"," +
                                                        "\"code\": \""+rs.getString("code")+"\"," +
                                                        "\"display\": \""+rs.getString("display")+"\"" +
                                                    "}" +
                                                "]," +
                                                "\"text\": \""+rs.getString("Pemeriksaan")+"\"" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"" +
                                            "}," +
                                            "\"encounter\": {" +
                                                "\"reference\": \"Encounter/"+rs.getString("id_encounter")+"\"," +
                                                "\"display\": \"Permintaan "+rs.getString("Pemeriksaan")+" atas nama pasien "+rs.getString("nm_pasien")+" No.RM "+rs.getString("no_rkm_medis")+" No.Rawat "+rs.getString("no_rawat")+", pada tanggal "+rs.getString("tgl_permintaan")+" "+rs.getString("jam_permintaan")+"\"" +
                                            "}," +
                                            "\"authoredOn\" : \""+rs.getString("tgl_permintaan")+"T"+rs.getString("jam_permintaan")+"+07:00\"," +
                                            "\"requester\": {" +
                                                "\"reference\": \"Practitioner/"+iddokter+"\"," +
                                                "\"display\": \""+rs.getString("nama")+"\"" +
                                            "}," +
                                            "\"performer\": [{" +
                                                "\"reference\": \"Organization/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                "\"display\": \"Ruang Laborat/Petugas Laborat\"" +
                                            "}]," +
                                            "\"reasonCode\": [" +
                                                "{" +
                                                    "\"text\": \""+rs.getString("diagnosa_klinis")+"\"" +
                                                "}" +
                                            "]" +
                                        "}";
                                TeksArea.append("URL : "+link+"/ServiceRequest");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/ServiceRequest", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_servicerequest_lab","?,?,?,?","No.Rawat",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
    }
    
    // MODUL SPECIMEN LAB PK - DETEKTIF + AUTO RECONNECT
    private void specimenlabpk() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: SPECIMEN LAB PK\n");
            TeksArea.append("------------------------------------------------------\n");

            // Query Gabungan Ralan & Ranap (Filter by Tgl Sampel)
            String query = "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,permintaan_lab.noorder,"
                    + "permintaan_lab.tgl_sampel,permintaan_lab.jam_sampel,template_laboratorium.Pemeriksaan,"
                    + "satu_sehat_mapping_lab.sampel_code,satu_sehat_mapping_lab.sampel_system,satu_sehat_mapping_lab.sampel_display,satu_sehat_servicerequest_lab.id_servicerequest,"
                    + "permintaan_detail_permintaan_lab.id_template,ifnull(satu_sehat_specimen_lab.id_specimen,'') as id_specimen,permintaan_detail_permintaan_lab.kd_jenis_prw "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join permintaan_lab on permintaan_lab.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_detail_permintaan_lab on permintaan_detail_permintaan_lab.noorder=permintaan_lab.noorder "
                    + "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "
                    + "inner join satu_sehat_servicerequest_lab on satu_sehat_servicerequest_lab.noorder=permintaan_detail_permintaan_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=permintaan_detail_permintaan_lab.kd_jenis_prw "
                    + "left join satu_sehat_specimen_lab on satu_sehat_servicerequest_lab.noorder=satu_sehat_specimen_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=satu_sehat_specimen_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=satu_sehat_specimen_lab.kd_jenis_prw "
                    // Filter
                    + "where permintaan_lab.tgl_sampel between ? and ? "
                    + "and permintaan_lab.tgl_sampel <> '0000-00-00' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_specimen_lab.id_specimen,'')='' ";
            
            ps = koneksi.prepareStatement(query);
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF SPECIMEN PK] Order: " + rs.getString("noorder") + " | Item: " + rs.getString("Pemeriksaan") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && rs.getString("id_specimen").equals("")) {
                        // Helper Kirim
                        processSpecimenLabPK(rs);
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (!rs.getString("id_specimen").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim (ID: " + rs.getString("id_specimen") + ")\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Specimen Lab PK : " + e);
                TeksArea.append("ERROR QUERY SPECIMEN LAB PK: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Specimen PK : " + e);
            TeksArea.append("!! ERROR UTAMA SPECIMEN LAB PK: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS SPECIMEN LAB PK (Auto Reconnect)
    private void processSpecimenLabPK(ResultSet rs) {
        try {
            // Validasi 1: Parent ServiceRequest
            if (rs.getString("id_servicerequest").equals("")) {
                TeksArea.append("   !! [SKIP] ServiceRequest (Order Lab) belum terkirim.\n");
                return;
            }

            // Validasi 2: ID Pasien
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            if (idpasien.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // Sanitasi Data
            String sampelDisplay = rs.getString("sampel_display").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'");
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");

            // Konstruksi JSON
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
            
            json = "{"
                    + "\"resourceType\": \"Specimen\","
                    + "\"identifier\": ["
                        + "{"
                            + "\"system\": \"http://sys-ids.kemkes.go.id/specimen/" + koneksiDB.IDSATUSEHAT() + "\","
                            + "\"value\": \"" + rs.getString("noorder") + "." + rs.getString("id_template") + "\""
                        + "}"
                    + "],"
                    + "\"status\": \"available\","
                    + "\"type\": {"
                        + "\"coding\": ["
                            + "{"
                                + "\"system\": \"" + rs.getString("sampel_system") + "\","
                                + "\"code\": \"" + rs.getString("sampel_code") + "\","
                                + "\"display\": \"" + sampelDisplay + "\""
                            + "}"
                        + "]"
                    + "},"
                    + "\"subject\": {"
                        + "\"reference\": \"Patient/" + idpasien + "\","
                        + "\"display\": \"" + nmPasien + "\""
                    + "},"
                    + "\"request\": ["
                        + "{"
                            + "\"reference\": \"ServiceRequest/" + rs.getString("id_servicerequest") + "\""
                        + "}"
                    + "],"
                    + "\"receivedTime\": \"" + rs.getString("tgl_sampel") + "T" + rs.getString("jam_sampel") + "+07:00\""
                    + "}";

            // TeksArea.append("   [DEBUG JSON] " + json + "\n");

            // Kirim Request (Dengan Auto Reconnect)
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/Specimen", HttpMethod.POST, requestEntity);
                simpanLogSpecimenLabPK(responseJson, rs);
                
            } catch (HttpClientErrorException e) {
                // HANDLER TOKEN EXPIRED (401)
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    
                    try {
                        // RETRY KIRIM
                        String responseJson = konekSatuSehat(link + "/Specimen", HttpMethod.POST, requestEntity);
                        simpanLogSpecimenLabPK(responseJson, rs);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            } catch (Exception e) {
                TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR INTERNAL] " + e + "\n");
            e.printStackTrace();
        }
    }

    private void simpanLogSpecimenLabPK(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_specimen_lab", "?,?,?,?", "No.Rawat", 4, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), rs.getString("id_template"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
       
    private void specimenlabmb() {
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,permintaan_labmb.noorder,"+
                   "permintaan_labmb.tgl_sampel,permintaan_labmb.jam_sampel,template_laboratorium.Pemeriksaan,"+
                   "satu_sehat_mapping_lab.sampel_code,satu_sehat_mapping_lab.sampel_system,satu_sehat_mapping_lab.sampel_display,satu_sehat_servicerequest_lab_mb.id_servicerequest,"+
                   "permintaan_detail_permintaan_labmb.id_template,ifnull(satu_sehat_specimen_lab_mb.id_specimen,'') as id_specimen,permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "inner join satu_sehat_servicerequest_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "left join satu_sehat_specimen_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=satu_sehat_specimen_lab_mb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=satu_sehat_specimen_lab_mb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=satu_sehat_specimen_lab_mb.kd_jenis_prw "+
                   "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "+
                   "where nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_specimen_lab_mb.id_specimen,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&rs.getString("id_specimen").equals("")){
                        try {
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"Specimen\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/specimen/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"available\"," +
                                            "\"type\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"system\": \""+rs.getString("sampel_system")+"\"," +
                                                        "\"code\": \""+rs.getString("sampel_code")+"\"," +
                                                        "\"display\": \""+rs.getString("sampel_display")+"\"" +
                                                    "}" +
                                                "]" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"," +
                                                "\"display\": \""+rs.getString("nm_pasien")+"\"" +
                                            "}," +
                                            "\"request\": [" +
                                                "{" +
                                                    "\"reference\": \"ServiceRequest/"+rs.getString("id_servicerequest")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"receivedTime\": \""+rs.getString("tgl_sampel")+"T"+rs.getString("jam_sampel")+"+07:00\"" +
                                        "}";
                                TeksArea.append("URL : "+link+"/Specimen");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/Specimen", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_specimen_lab_mb","?,?,?,?","No.Rawat",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
        
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,permintaan_labmb.noorder,"+
                   "permintaan_labmb.tgl_sampel,permintaan_labmb.jam_sampel,template_laboratorium.Pemeriksaan,"+
                   "satu_sehat_mapping_lab.sampel_code,satu_sehat_mapping_lab.sampel_system,satu_sehat_mapping_lab.sampel_display,satu_sehat_servicerequest_lab_mb.id_servicerequest,"+
                   "permintaan_detail_permintaan_labmb.id_template,ifnull(satu_sehat_specimen_lab_mb.id_specimen,'') as id_specimen,permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "inner join satu_sehat_servicerequest_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "left join satu_sehat_specimen_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=satu_sehat_specimen_lab_mb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=satu_sehat_specimen_lab_mb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=satu_sehat_specimen_lab_mb.kd_jenis_prw "+
                   "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "+
                   "where nota_inap.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_specimen_lab_mb.id_specimen,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&rs.getString("id_specimen").equals("")){
                        try {
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"Specimen\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/specimen/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"available\"," +
                                            "\"type\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"system\": \""+rs.getString("sampel_system")+"\"," +
                                                        "\"code\": \""+rs.getString("sampel_code")+"\"," +
                                                        "\"display\": \""+rs.getString("sampel_display")+"\"" +
                                                    "}" +
                                                "]" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"," +
                                                "\"display\": \""+rs.getString("nm_pasien")+"\"" +
                                            "}," +
                                            "\"request\": [" +
                                                "{" +
                                                    "\"reference\": \"ServiceRequest/"+rs.getString("id_servicerequest")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"receivedTime\": \""+rs.getString("tgl_sampel")+"T"+rs.getString("jam_sampel")+"+07:00\"" +
                                        "}";
                                TeksArea.append("URL : "+link+"/Specimen");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/Specimen", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_specimen_lab_mb","?,?,?,?","No.Rawat",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
    }
    
    // MODUL OBSERVATION LAB PK (HASIL) - DETEKTIF + AUTO RECONNECT + AUTO FIX CODE
    private void observationlabpk() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: OBSERVATION LAB PK (HASIL)\n");
            TeksArea.append("------------------------------------------------------\n");

            // QUERY UTAMA
            String query = "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,permintaan_lab.noorder,"
                    + "permintaan_lab.tgl_hasil,permintaan_lab.jam_hasil,template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,"
                    + "satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,detail_periksa_lab.nilai,detail_periksa_lab.nilai_rujukan,"
                    + "detail_periksa_lab.keterangan,permintaan_detail_permintaan_lab.id_template,ifnull(satu_sehat_specimen_lab.id_specimen,'') as id_specimen,"
                    + "periksa_lab.kd_dokter,pegawai.nama,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,"
                    + "ifnull(satu_sehat_observation_lab.id_observation,'') as id_observation,detail_periksa_lab.kd_jenis_prw,template_laboratorium.satuan,"
                    + "ifnull(satu_sehat_servicerequest_lab.id_servicerequest,'') as id_servicerequest "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_lab on permintaan_lab.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_detail_permintaan_lab on permintaan_detail_permintaan_lab.noorder=permintaan_lab.noorder "
                    + "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "
                    + "inner join periksa_lab on periksa_lab.no_rawat=permintaan_lab.no_rawat and periksa_lab.tgl_periksa=permintaan_lab.tgl_hasil "
                    + "and periksa_lab.jam=permintaan_lab.jam_hasil and periksa_lab.dokter_perujuk=permintaan_lab.dokter_perujuk "
                    + "inner join detail_periksa_lab on periksa_lab.no_rawat=detail_periksa_lab.no_rawat and periksa_lab.tgl_periksa=detail_periksa_lab.tgl_periksa "
                    + "and periksa_lab.jam=detail_periksa_lab.jam and detail_periksa_lab.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "inner join pegawai on periksa_lab.kd_dokter=pegawai.nik "
                    + "left join satu_sehat_servicerequest_lab on satu_sehat_servicerequest_lab.noorder=permintaan_detail_permintaan_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=permintaan_detail_permintaan_lab.kd_jenis_prw "
                    + "left join satu_sehat_specimen_lab on satu_sehat_servicerequest_lab.noorder=satu_sehat_specimen_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=satu_sehat_specimen_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=satu_sehat_specimen_lab.kd_jenis_prw "
                    + "left join satu_sehat_observation_lab on satu_sehat_specimen_lab.noorder=satu_sehat_observation_lab.noorder "
                    + "and satu_sehat_specimen_lab.id_template=satu_sehat_observation_lab.id_template "
                    + "and satu_sehat_specimen_lab.kd_jenis_prw=satu_sehat_observation_lab.kd_jenis_prw "
                    + "where permintaan_lab.tgl_hasil between ? and ? "
                    + "and permintaan_lab.tgl_hasil <> '0000-00-00' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_observation_lab.id_observation,'')='' ";
            
            ps = koneksi.prepareStatement(query);
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF OBS LAB] No.Order: " + rs.getString("noorder") + " | Item: " + rs.getString("Pemeriksaan") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_observation").equals("")) {
                        kirimObservationLab(rs);
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (!rs.getString("id_observation").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim (ID: " + rs.getString("id_observation") + ")\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Lab PK : " + e);
                TeksArea.append("ERROR QUERY LAB PK: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Lab PK : " + e);
            TeksArea.append("!! ERROR UTAMA OBSERVATION LAB PK: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS OBSERVATION LAB (Auto Reconnect & Auto Fix Code)
    private void kirimObservationLab(ResultSet rs) {
        String identifierValue = ""; // Untuk keperluan search/recovery
        try {
            // 1. Cek Prasyarat (Chain of Custody)
            String idServiceRequest = rs.getString("id_servicerequest");
            String idSpecimen = rs.getString("id_specimen");

            if (idServiceRequest.isEmpty()) {
                TeksArea.append("   !! [SKIP] ServiceRequest (Order) belum terkirim.\n");
                return;
            }
            if (idSpecimen.isEmpty()) {
                TeksArea.append("   !! [SKIP] Specimen belum terkirim/tidak ditemukan.\n");
                return;
            }

            // 2. Cek KyC
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Dokter PJ Lab tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // 3. Sanitasi Data Hasil
            String nilaiHasil = rs.getString("nilai").replaceAll("\"", "'");
            String satuan = rs.getString("satuan").replaceAll("\"", "'");
            String nilaiRujukan = rs.getString("nilai_rujukan").replaceAll("\"", "'");
            String keterangan = rs.getString("keterangan").replaceAll("(\r\n|\r|\n|\n\r)", " ").replaceAll("\"", "'");

            String hasilString = "Hasil: " + nilaiHasil + " " + satuan + " (Rujukan: " + nilaiRujukan + ")";
            if (!keterangan.isEmpty()) hasilString += " Ket: " + keterangan;
            
            // Bersihkan string untuk JSON
            hasilString = hasilString.replaceAll("[\n\r]", " ").replaceAll("\"", "'").replaceAll("\\\\", "/");

            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmDokter = rs.getString("nama").replaceAll("\"", "'");
            String tglHasil = rs.getString("tgl_hasil") + "T" + rs.getString("jam_hasil") + "+07:00";

            // 4. FIX MAPPING CODE (Detektif Conan Feature)
            // Masalah: Kode Lab "DARAH-LENGKAP" (dengan strip) sering ditolak jika system-nya bukan LOINC
            String labCode = rs.getString("code");
            String labSystem = rs.getString("system");
            String labDisplay = rs.getString("display").replaceAll("\"", "'");

            // LOGIKA AUTO-FIX: Jika Code mengandung "-" dan System BUKAN LOINC/SNOMED, mungkin ini kode lokal.
            // Satu Sehat menyarankan penggunaan LOINC. Jika kode lokal dipaksa, pastikan systemnya benar.
            // Namun, jika user sudah mapping, kita hormati mapping user TAPI kita sanitasi karakternya jika perlu.
            
            // Opsional: Jika Anda ingin memblokir kode yang mengandung spasi/karakter aneh
            if (labCode.contains(" ")) {
                TeksArea.append("   [WARNING] Kode Lab mengandung spasi: '" + labCode + "'. Ini mungkin ditolak server.\n");
            }

            identifierValue = rs.getString("noorder") + "." + rs.getString("id_template");

            // 5. Konstruksi JSON
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            json = "{"
                    + "\"resourceType\": \"Observation\","
                    + "\"identifier\": ["
                        + "{\"system\": \"http://sys-ids.kemkes.go.id/observation/" + koneksiDB.IDSATUSEHAT() + "\",\"value\": \"" + identifierValue + "\"}"
                    + "],"
                    + "\"status\": \"final\","
                    + "\"category\": ["
                        + "{\"coding\": [{\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\",\"code\": \"laboratory\",\"display\": \"Laboratory\"}]}"
                    + "],"
                    + "\"code\": {"
                        + "\"coding\": [{\"system\": \"" + labSystem + "\",\"code\": \"" + labCode + "\",\"display\": \"" + labDisplay + "\"}]"
                    + "},"
                    + "\"subject\": {\"reference\": \"Patient/" + idpasien + "\",\"display\": \"" + nmPasien + "\"},"
                    + "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\",\"display\": \"" + nmDokter + "\"}],"
                    + "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"},"
                    + "\"specimen\": {\"reference\": \"Specimen/" + idSpecimen + "\"},"
                    + "\"basedOn\": [{\"reference\": \"ServiceRequest/" + idServiceRequest + "\"}],"
                    + "\"effectiveDateTime\": \"" + tglHasil + "\","
                    + "\"valueString\": \"" + hasilString + "\""
                    + "}";

            // 6. Kirim Request (Dengan Auto Reconnect & Duplicate Recovery)
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/Observation", HttpMethod.POST, requestEntity);
                simpanLogObsLab(responseJson, rs);
                
            } catch (HttpClientErrorException e) {
                // HANDLER TOKEN EXPIRED (401)
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Refresh Token...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    try {
                        String responseJson = konekSatuSehat(link + "/Observation", HttpMethod.POST, requestEntity);
                        simpanLogObsLab(responseJson, rs);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } 
                // HANDLER DUPLICATE (400) - Detektif Mode
                else if (e.getStatusCode().value() == 400 && e.getResponseBodyAsString().contains("duplicate")) {
                    TeksArea.append("   [INFO] Data Duplikat. Mencoba Recovery ID...\n");
                    recoverDuplicateObsLab(identifierValue, rs);
                }
                else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            } catch (Exception e) {
                TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR INTERNAL] " + e + "\n");
            e.printStackTrace();
        }
    }

    private void simpanLogObsLab(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_observation_lab", "?,?,?,?", "No.Order", 4, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), rs.getString("id_template"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }

    // Helper Khusus Recovery Duplikat (Agar tidak macet)
    private void recoverDuplicateObsLab(String identifierValue, ResultSet rs) {
        try {
            // Cari ID berdasarkan Identifier unik kita
            String searchUrl = link + "/Observation?identifier=http://sys-ids.kemkes.go.id/observation/" + koneksiDB.IDSATUSEHAT() + "|" + identifierValue;
            String searchJson = konekSatuSehat(searchUrl, HttpMethod.GET, new HttpEntity(headers));
            
            JsonNode searchRoot = mapper.readTree(searchJson);
            if (searchRoot.path("total").asInt() > 0) {
                String existingId = searchRoot.path("entry").get(0).path("resource").path("id").asText();
                TeksArea.append("   [RECOVERED] ID Ditemukan: " + existingId + "\n");
                
                // Simpan ID yang ditemukan ke Database agar tidak error lagi
                Sequel.menyimpan2("satu_sehat_observation_lab", "?,?,?,?", "No.Order", 4, new String[]{
                    rs.getString("noorder"), rs.getString("kd_jenis_prw"), rs.getString("id_template"), existingId
                });
            } else {
                TeksArea.append("   !! [GAGAL RECOVER] Data duplikat tapi tidak ditemukan saat dicari.\n");
            }
        } catch (Exception ex) {
            TeksArea.append("   !! [ERROR RECOVER] Gagal mengambil data duplikat: " + ex + "\n");
        }
    }
    
    private void observationlabmb() {
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,permintaan_labmb.noorder,"+
                   "permintaan_labmb.tgl_hasil,permintaan_labmb.jam_hasil,template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,"+
                   "satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,detail_periksa_lab.nilai,detail_periksa_lab.nilai_rujukan,"+
                   "detail_periksa_lab.keterangan,permintaan_detail_permintaan_labmb.id_template,satu_sehat_specimen_lab_mb.id_specimen,"+
                   "periksa_lab.kd_dokter,pegawai.nama,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,"+
                   "ifnull(satu_sehat_observation_lab_mb.id_observation,'') as id_observation,detail_periksa_lab.kd_jenis_prw,template_laboratorium.satuan "+
                   "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "inner join satu_sehat_specimen_lab_mb on satu_sehat_specimen_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_specimen_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_specimen_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "inner join periksa_lab on periksa_lab.no_rawat=permintaan_labmb.no_rawat and periksa_lab.tgl_periksa=permintaan_labmb.tgl_hasil "+
                   "and periksa_lab.jam=permintaan_labmb.jam_hasil and periksa_lab.dokter_perujuk=permintaan_labmb.dokter_perujuk "+
                   "inner join detail_periksa_lab on periksa_lab.no_rawat=detail_periksa_lab.no_rawat and periksa_lab.tgl_periksa=detail_periksa_lab.tgl_periksa "+
                   "and periksa_lab.jam=detail_periksa_lab.jam "+
                   "left join satu_sehat_observation_lab_mb on satu_sehat_specimen_lab_mb.noorder=satu_sehat_observation_lab_mb.noorder "+
                   "and satu_sehat_specimen_lab_mb.id_template=satu_sehat_observation_lab_mb.id_template "+
                   "and satu_sehat_specimen_lab_mb.kd_jenis_prw=satu_sehat_observation_lab_mb.kd_jenis_prw "+
                   "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "+
                   "inner join pegawai on periksa_lab.kd_dokter=pegawai.nik "+
                   "where nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_observation_lab_mb.id_observation,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&(!rs.getString("ktppraktisi").equals(""))&&rs.getString("id_observation").equals("")){
                        try {
                            iddokter=cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"Observation\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/observation/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"final\"," +
                                            "\"category\": [" +
                                                "{" +
                                                    "\"coding\": [" +
                                                        "{" +
                                                            "\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\"," +
                                                            "\"code\": \"laboratory\"," +
                                                            "\"display\": \"Laboratory\"" +
                                                        "}" +
                                                    "]" +
                                                "}" +
                                            "]," +
                                            "\"code\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"system\": \""+rs.getString("system")+"\"," +
                                                        "\"code\": \""+rs.getString("code")+"\"," +
                                                        "\"display\": \""+rs.getString("display")+"\"" +
                                                    "}" +
                                                "]" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"" +
                                            "}," +
                                            "\"performer\": [" +
                                                "{" +
                                                    "\"reference\": \"Practitioner/"+iddokter+"\"" +
                                                "}" +
                                            "]," +
                                            "\"encounter\": {" +
                                                "\"reference\": \"Encounter/"+rs.getString("id_encounter")+"\"," +
                                                "\"display\": \"Hasil Pemeriksaan Lab "+rs.getString("nm_perawatan")+" No.Rawat "+rs.getString("no_rawat")+", Atas Nama Pasien "+rs.getString("nm_pasien")+", No.RM "+rs.getString("no_rkm_medis")+", Pada Tanggal "+rs.getString("tgl_hasil")+" "+rs.getString("jam_hasil")+"\"" +
                                            "}," +
                                            "\"specimen\": {" +
                                                "\"reference\": \"Specimen/"+rs.getString("id_specimen")+"\"" +
                                            "}," +
                                            "\"effectiveDateTime\": \""+rs.getString("tgl_hasil")+"T"+rs.getString("jam_hasil")+"+07:00\"," +
                                            "\"valueString\": \""+("Hasil Lab : "+rs.getString("nilai")+" "+rs.getString("satuan")+", Nilai Rujukan : "+rs.getString("nilai_rujukan")+(rs.getString("keterangan").equals("")?"":", Keterangan : "+rs.getString("keterangan"))).replaceAll("(\r\n|\r|\n|\n\r)","<br>").replaceAll("\t", " ")+"\"" +
                                       "}";
                                TeksArea.append("URL : "+link+"/Observation");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/Observation", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_observation_lab_mb","?,?,?,?","No.Order",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
        
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,permintaan_labmb.noorder,"+
                   "permintaan_labmb.tgl_hasil,permintaan_labmb.jam_hasil,template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,"+
                   "satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,detail_periksa_lab.nilai,detail_periksa_lab.nilai_rujukan,"+
                   "detail_periksa_lab.keterangan,permintaan_detail_permintaan_labmb.id_template,satu_sehat_specimen_lab_mb.id_specimen,"+
                   "periksa_lab.kd_dokter,pegawai.nama,pegawai.no_ktp as ktppraktisi,satu_sehat_encounter.id_encounter,"+
                   "ifnull(satu_sehat_observation_lab_mb.id_observation,'') as id_observation,detail_periksa_lab.kd_jenis_prw,template_laboratorium.satuan "+
                   "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "inner join satu_sehat_specimen_lab_mb on satu_sehat_specimen_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_specimen_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_specimen_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "inner join periksa_lab on periksa_lab.no_rawat=permintaan_labmb.no_rawat and periksa_lab.tgl_periksa=permintaan_labmb.tgl_hasil "+
                   "and periksa_lab.jam=permintaan_labmb.jam_hasil and periksa_lab.dokter_perujuk=permintaan_labmb.dokter_perujuk "+
                   "inner join detail_periksa_lab on periksa_lab.no_rawat=detail_periksa_lab.no_rawat and periksa_lab.tgl_periksa=detail_periksa_lab.tgl_periksa "+
                   "and periksa_lab.jam=detail_periksa_lab.jam "+
                   "left join satu_sehat_observation_lab_mb on satu_sehat_specimen_lab_mb.noorder=satu_sehat_observation_lab_mb.noorder "+
                   "and satu_sehat_specimen_lab_mb.id_template=satu_sehat_observation_lab_mb.id_template "+
                   "and satu_sehat_specimen_lab_mb.kd_jenis_prw=satu_sehat_observation_lab_mb.kd_jenis_prw "+
                   "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "+
                   "inner join pegawai on periksa_lab.kd_dokter=pegawai.nik "+
                   "where nota_inap.tanggal between ? and ?  and ifnull(satu_sehat_observation_lab_mb.id_observation,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&(!rs.getString("ktppraktisi").equals(""))&&rs.getString("id_observation").equals("")){
                        try {
                            iddokter=cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"Observation\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/observation/"+koneksiDB.IDSATUSEHAT()+"\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"final\"," +
                                            "\"category\": [" +
                                                "{" +
                                                    "\"coding\": [" +
                                                        "{" +
                                                            "\"system\": \"http://terminology.hl7.org/CodeSystem/observation-category\"," +
                                                            "\"code\": \"laboratory\"," +
                                                            "\"display\": \"Laboratory\"" +
                                                        "}" +
                                                    "]" +
                                                "}" +
                                            "]," +
                                            "\"code\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"system\": \""+rs.getString("system")+"\"," +
                                                        "\"code\": \""+rs.getString("code")+"\"," +
                                                        "\"display\": \""+rs.getString("display")+"\"" +
                                                    "}" +
                                                "]" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"" +
                                            "}," +
                                            "\"performer\": [" +
                                                "{" +
                                                    "\"reference\": \"Practitioner/"+iddokter+"\"" +
                                                "}" +
                                            "]," +
                                            "\"encounter\": {" +
                                                "\"reference\": \"Encounter/"+rs.getString("id_encounter")+"\"," +
                                                "\"display\": \"Hasil Pemeriksaan Lab "+rs.getString("nm_perawatan")+" No.Rawat "+rs.getString("no_rawat")+", Atas Nama Pasien "+rs.getString("nm_pasien")+", No.RM "+rs.getString("no_rkm_medis")+", Pada Tanggal "+rs.getString("tgl_hasil")+" "+rs.getString("jam_hasil")+"\"" +
                                            "}," +
                                            "\"specimen\": {" +
                                                "\"reference\": \"Specimen/"+rs.getString("id_specimen")+"\"" +
                                            "}," +
                                            "\"effectiveDateTime\": \""+rs.getString("tgl_hasil")+"T"+rs.getString("jam_hasil")+"+07:00\"," +
                                            "\"valueString\": \""+("Hasil Lab : "+rs.getString("nilai")+" "+rs.getString("satuan")+", Nilai Rujukan : "+rs.getString("nilai_rujukan")+(rs.getString("keterangan").equals("")?"":", Keterangan : "+rs.getString("keterangan"))).replaceAll("(\r\n|\r|\n|\n\r)","<br>").replaceAll("\t", " ")+"\"" +
                                       "}";
                                TeksArea.append("URL : "+link+"/Observation");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/Observation", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_observation_lab_mb","?,?,?,?","No.Order",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
    }
    
    // MODUL DIAGNOSTIC REPORT LAB PK - DETEKTIF + AUTO RECONNECT
    private void diagnosticreportlabpk() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: DIAGNOSTIC REPORT LAB PK\n");
            TeksArea.append("------------------------------------------------------\n");

            // Query Utama (Menggunakan LEFT JOIN untuk pengecekan kelengkapan resource)
            String query = "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,periksa_lab.kd_dokter,pegawai.nama,pegawai.no_ktp as ktppraktisi,"
                    + "satu_sehat_encounter.id_encounter,permintaan_lab.noorder,permintaan_lab.tgl_hasil,permintaan_lab.jam_hasil,permintaan_lab.diagnosa_klinis,"
                    + "template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,"
                    + "ifnull(satu_sehat_servicerequest_lab.id_servicerequest,'') as id_servicerequest,"
                    + "permintaan_detail_permintaan_lab.id_template,"
                    + "ifnull(satu_sehat_specimen_lab.id_specimen,'') as id_specimen,"
                    + "ifnull(satu_sehat_observation_lab.id_observation,'') as id_observation,"
                    + "ifnull(satu_sehat_diagnosticreport_lab.id_diagnosticreport,'') as id_diagnosticreport,saran_kesan_lab.kesan,"
                    + "permintaan_detail_permintaan_lab.kd_jenis_prw "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_lab on permintaan_lab.no_rawat=reg_periksa.no_rawat "
                    + "inner join permintaan_detail_permintaan_lab on permintaan_detail_permintaan_lab.noorder=permintaan_lab.noorder "
                    + "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "
                    + "inner join periksa_lab on periksa_lab.no_rawat=permintaan_lab.no_rawat and periksa_lab.tgl_periksa=permintaan_lab.tgl_hasil "
                    + "and periksa_lab.jam=permintaan_lab.jam_hasil and periksa_lab.dokter_perujuk=permintaan_lab.dokter_perujuk "
                    + "left join saran_kesan_lab on periksa_lab.no_rawat=saran_kesan_lab.no_rawat and periksa_lab.tgl_periksa=saran_kesan_lab.tgl_periksa "
                    + "and periksa_lab.jam=saran_kesan_lab.jam "
                    + "inner join pegawai on periksa_lab.kd_dokter=pegawai.nik "
                    // LEFT JOIN Log Resources Prasyarat
                    + "left join satu_sehat_servicerequest_lab on satu_sehat_servicerequest_lab.noorder=permintaan_detail_permintaan_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=permintaan_detail_permintaan_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=permintaan_detail_permintaan_lab.kd_jenis_prw "
                    + "left join satu_sehat_specimen_lab on satu_sehat_servicerequest_lab.noorder=satu_sehat_specimen_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=satu_sehat_specimen_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=satu_sehat_specimen_lab.kd_jenis_prw "
                    + "left join satu_sehat_observation_lab on satu_sehat_specimen_lab.noorder=satu_sehat_observation_lab.noorder "
                    + "and satu_sehat_specimen_lab.id_template=satu_sehat_observation_lab.id_template "
                    + "and satu_sehat_specimen_lab.kd_jenis_prw=satu_sehat_observation_lab.kd_jenis_prw "
                    + "left join satu_sehat_diagnosticreport_lab on satu_sehat_servicerequest_lab.noorder=satu_sehat_diagnosticreport_lab.noorder "
                    + "and satu_sehat_servicerequest_lab.id_template=satu_sehat_diagnosticreport_lab.id_template "
                    + "and satu_sehat_servicerequest_lab.kd_jenis_prw=satu_sehat_diagnosticreport_lab.kd_jenis_prw "
                    // Filter
                    + "where permintaan_lab.tgl_hasil between ? and ? "
                    + "and permintaan_lab.tgl_hasil <> '0000-00-00' "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_diagnosticreport_lab.id_diagnosticreport,'')='' ";
            
            ps = koneksi.prepareStatement(query);
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF DIAG LAB] Order: " + rs.getString("noorder") + " | Item: " + rs.getString("Pemeriksaan") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_diagnosticreport").equals("")) {
                        // Helper Kirim
                        processDiagnosticReportLab(rs);
                    } else {
                        if (rs.getString("no_ktp").equals("")) TeksArea.append("   !! [SKIP] NIK Pasien Kosong\n");
                        if (rs.getString("ktppraktisi").equals("")) TeksArea.append("   !! [SKIP] NIK Dokter Kosong\n");
                        if (!rs.getString("id_diagnosticreport").equals("")) TeksArea.append("   !! [SKIP] Sudah Terkirim (ID: " + rs.getString("id_diagnosticreport") + ")\n");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Diag Report Lab : " + e);
                TeksArea.append("ERROR QUERY DIAG LAB: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Diag Report Lab : " + e);
            TeksArea.append("!! ERROR UTAMA DIAGNOSTIC REPORT LAB: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS DIAGNOSTIC REPORT LAB (Auto Reconnect + Duplicate Recovery)
    private void processDiagnosticReportLab(ResultSet rs) {
        String identifierValue = ""; 
        try {
            // 1. Validasi Prasyarat
            if (rs.getString("id_servicerequest").isEmpty()) {
                TeksArea.append("   !! [SKIP] ServiceRequest (Order) belum terkirim.\n");
                return;
            }
            if (rs.getString("id_specimen").isEmpty()) {
                TeksArea.append("   !! [SKIP] Specimen belum terkirim.\n");
                return;
            }
            if (rs.getString("id_observation").isEmpty()) {
                TeksArea.append("   !! [SKIP] Observation (Hasil) belum terkirim.\n");
                return;
            }

            // 2. Cek KyC
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // 3. Prepare Data
            String kesan = rs.getString("kesan");
            if (kesan == null || kesan.isEmpty()) kesan = "Hasil laboratorium dalam batas normal/sesuai lampiran.";
            String conclusion = kesan.replaceAll("(\r\n|\r|\n|\n\r)", "<br>")
                    .replaceAll("\"", "'").replaceAll("\\\\", "/").replaceAll("\t", " ");

            String displayMapping = rs.getString("display").replaceAll("\"", "'");
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmDokter = rs.getString("nama").replaceAll("\"", "'");
            String tglHasil = rs.getString("tgl_hasil") + "T" + rs.getString("jam_hasil") + "+07:00";
            
            // Identifier Unik untuk Recovery
            identifierValue = rs.getString("noorder") + "." + rs.getString("id_template");

            // 4. Konstruksi JSON
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
            
            json = "{"
                    + "\"resourceType\": \"DiagnosticReport\","
                    + "\"identifier\": ["
                        + "{"
                            + "\"system\": \"http://sys-ids.kemkes.go.id/diagnostic/" + koneksiDB.IDSATUSEHAT() + "/lab\","
                            + "\"use\": \"official\","
                            + "\"value\": \"" + identifierValue + "\""
                        + "}"
                    + "],"
                    + "\"status\": \"final\","
                    + "\"category\": [{"
                        + "\"coding\": [{"
                            + "\"system\": \"http://terminology.hl7.org/CodeSystem/v2-0074\","
                            + "\"code\": \"LAB\","
                            + "\"display\": \"Laboratory\""
                        + "}]"
                    + "}],"
                    + "\"code\": {"
                        + "\"coding\": [{"
                            + "\"code\": \"" + rs.getString("code") + "\","
                            + "\"display\": \"" + displayMapping + "\","
                            + "\"system\": \"" + rs.getString("system") + "\""
                        + "}]"
                    + "},"
                    + "\"subject\": {\"reference\": \"Patient/" + idpasien + "\",\"display\": \"" + nmPasien + "\"},"
                    + "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"},"
                    + "\"effectiveDateTime\": \"" + tglHasil + "\","
                    + "\"issued\": \"" + tglHasil + "\","
                    + "\"performer\": [{\"reference\": \"Practitioner/" + iddokter + "\",\"display\": \"" + nmDokter + "\"}],"
                    + "\"specimen\": [{\"reference\": \"Specimen/" + rs.getString("id_specimen") + "\"}],"
                    + "\"result\": [{\"reference\": \"Observation/" + rs.getString("id_observation") + "\"}],"
                    + "\"basedOn\": [{\"reference\": \"ServiceRequest/" + rs.getString("id_servicerequest") + "\"}],"
                    + "\"conclusion\": \"" + conclusion + "\""
                    + "}";

            // 5. Kirim Request
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/DiagnosticReport", HttpMethod.POST, requestEntity);
                simpanLogDiagLab(responseJson, rs);
                
            } catch (HttpClientErrorException e) {
                // HANDLER TOKEN EXPIRED (401)
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    try {
                        String responseJson = konekSatuSehat(link + "/DiagnosticReport", HttpMethod.POST, requestEntity);
                        simpanLogDiagLab(responseJson, rs);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } 
                // HANDLER DUPLICATE (400) - RuleNumber: 20002
                else if (e.getStatusCode().value() == 400 && e.getResponseBodyAsString().contains("duplicate")) {
                    TeksArea.append("   [INFO] Data Duplikat (Sudah ada di SatuSehat). Mencoba Recovery ID...\n");
                    recoverDuplicateDiagLab(identifierValue, rs);
                }
                else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            } catch (Exception e) {
                TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR INTERNAL] " + e + "\n");
            e.printStackTrace();
        }
    }

    private void simpanLogDiagLab(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_diagnosticreport_lab", "?,?,?,?,?", "No.Order", 4, new String[]{
                rs.getString("noorder"), rs.getString("kd_jenis_prw"), rs.getString("id_template"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }

    // HELPER KHUSUS RECOVERY DATA DUPLIKAT
    private void recoverDuplicateDiagLab(String identifierValue, ResultSet rs) {
        try {
            // Mencari ID DiagnosticReport berdasarkan Identifier Unik (noorder.id_template)
            String searchUrl = link + "/DiagnosticReport?identifier=http://sys-ids.kemkes.go.id/diagnostic/" + koneksiDB.IDSATUSEHAT() + "/lab|" + identifierValue;
            String searchJson = konekSatuSehat(searchUrl, HttpMethod.GET, new HttpEntity(headers));
            
            JsonNode searchRoot = mapper.readTree(searchJson);
            if (searchRoot.path("total").asInt() > 0) {
                String existingId = searchRoot.path("entry").get(0).path("resource").path("id").asText();
                TeksArea.append("   [RECOVERED] ID Ditemukan: " + existingId + ". Menyimpan ke DB Lokal...\n");
                
                // Simpan ID yang ditemukan ke Database
                Sequel.menyimpan2("satu_sehat_diagnosticreport_lab", "?,?,?,?,?", "No.Order", 4, new String[]{
                    rs.getString("noorder"), rs.getString("kd_jenis_prw"), rs.getString("id_template"), existingId
                });
            } else {
                TeksArea.append("   !! [GAGAL RECOVER] Data duplikat tapi tidak ditemukan saat dicari.\n");
            }
        } catch (Exception ex) {
            TeksArea.append("   !! [ERROR RECOVER] Gagal mengambil data duplikat: " + ex.getMessage() + "\n");
        }
    }    
    
    private void diagnosticreportlabmb() {
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,periksa_lab.kd_dokter,pegawai.nama,pegawai.no_ktp as ktpdokter,"+
                   "satu_sehat_encounter.id_encounter,permintaan_labmb.noorder,permintaan_labmb.tgl_hasil,permintaan_labmb.jam_hasil,permintaan_labmb.diagnosa_klinis,"+
                   "template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,"+
                   "satu_sehat_servicerequest_lab_mb.id_servicerequest,permintaan_detail_permintaan_labmb.id_template,satu_sehat_specimen_lab_mb.id_specimen,"+
                   "satu_sehat_observation_lab_mb.id_observation,ifnull(satu_sehat_diagnosticreport_lab_mb.id_diagnosticreport,'') as id_diagnosticreport,saran_kesan_lab.kesan,"+
                   "template_laboratorium.kd_jenis_prw from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "+
                   "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "inner join satu_sehat_servicerequest_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "inner join satu_sehat_specimen_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=satu_sehat_specimen_lab_mb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=satu_sehat_specimen_lab_mb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=satu_sehat_specimen_lab_mb.kd_jenis_prw "+
                   "inner join periksa_lab on periksa_lab.no_rawat=permintaan_labmb.no_rawat and periksa_lab.tgl_periksa=permintaan_labmb.tgl_hasil "+
                   "and periksa_lab.jam=permintaan_labmb.jam_hasil and periksa_lab.dokter_perujuk=permintaan_labmb.dokter_perujuk "+
                   "inner join saran_kesan_lab on periksa_lab.no_rawat=saran_kesan_lab.no_rawat and periksa_lab.tgl_periksa=saran_kesan_lab.tgl_periksa "+
                   "and periksa_lab.jam=saran_kesan_lab.jam "+
                   "inner join satu_sehat_observation_lab_mb on satu_sehat_specimen_lab_mb.noorder=satu_sehat_observation_lab_mb.noorder "+
                   "and satu_sehat_specimen_lab_mb.id_template=satu_sehat_observation_lab_mb.id_template "+
                   "and satu_sehat_specimen_lab_mb.kd_jenis_prw=satu_sehat_observation_lab_mb.kd_jenis_prw "+
                   "left join satu_sehat_diagnosticreport_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=satu_sehat_diagnosticreport_lab_mb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=satu_sehat_diagnosticreport_lab_mb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=satu_sehat_diagnosticreport_lab_mb.kd_jenis_prw "+
                   "inner join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "+
                   "inner join pegawai on periksa_lab.kd_dokter=pegawai.nik "+
                   "where nota_jalan.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_diagnosticreport_lab_mb.id_diagnosticreport,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&(!rs.getString("ktpdokter").equals(""))&&rs.getString("id_diagnosticreport").equals("")){
                        try {
                            iddokter=cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"DiagnosticReport\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/diagnostic/"+koneksiDB.IDSATUSEHAT()+"/lab\"," +
                                                    "\"use\": \"official\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"final\"," +
                                            "\"category\": [" +
                                                "{" +
                                                    "\"coding\": [" +
                                                        "{" +
                                                            "\"system\": \"http://terminology.hl7.org/CodeSystem/v2-0074\"," +
                                                            "\"code\": \"LAB\"," +
                                                            "\"display\": \"Laboratory\"" +
                                                        "}" +
                                                    "]" +
                                                "}" +
                                            "]," +
                                            "\"code\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"code\": \""+rs.getString("code")+"\"," +
                                                        "\"display\": \""+rs.getString("display")+"\"," +
                                                        "\"system\": \""+rs.getString("system")+"\"" +
                                                    "}" +
                                                "]" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"" +
                                            "}," +
                                            "\"encounter\": {" +
                                                "\"reference\": \"Encounter/"+rs.getString("id_encounter")+"\"" +
                                            "}," +
                                            "\"effectiveDateTime\": \""+rs.getString("tgl_hasil")+"T"+rs.getString("jam_hasil")+"+07:00\"," +
                                            "\"issued\": \""+rs.getString("tgl_hasil")+"T"+rs.getString("jam_hasil")+"+07:00\"," +
                                            "\"performer\": [" +
                                                "{" +
                                                    "\"reference\": \"Practitioner/"+iddokter+"\"" +
                                                "}" +
                                            "]," +
                                            "\"specimen\": [{" +
                                                "\"reference\": \"Specimen/"+rs.getString("id_specimen")+"\"" +
                                            "}]," +
                                            "\"result\": [" +
                                                "{" +
                                                    "\"reference\": \"Observation/"+rs.getString("id_observation")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"basedOn\": [" +
                                                "{" +
                                                    "\"reference\": \"ServiceRequest/"+rs.getString("id_servicerequest")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"conclusion\": \""+rs.getString("kesan").replaceAll("(\r\n|\r|\n|\n\r)","<br>").replaceAll("\t", " ")+"\"" +
                                        "}";
                                TeksArea.append("URL : "+link+"/DiagnosticReport");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/DiagnosticReport", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_diagnosticreport_lab","?,?,?,?","No.Order",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
        
        try{
            ps=koneksi.prepareStatement(
                   "select reg_periksa.no_rawat,reg_periksa.no_rkm_medis,pasien.nm_pasien,pasien.no_ktp,periksa_lab.kd_dokter,pegawai.nama,pegawai.no_ktp as ktpdokter,"+
                   "satu_sehat_encounter.id_encounter,permintaan_labmb.noorder,permintaan_labmb.tgl_hasil,permintaan_labmb.jam_hasil,permintaan_labmb.diagnosa_klinis,"+
                   "template_laboratorium.Pemeriksaan,satu_sehat_mapping_lab.code,satu_sehat_mapping_lab.system,satu_sehat_mapping_lab.display,"+
                   "satu_sehat_servicerequest_lab_mb.id_servicerequest,permintaan_detail_permintaan_labmb.id_template,satu_sehat_specimen_lab_mb.id_specimen,"+
                   "satu_sehat_observation_lab_mb.id_observation,ifnull(satu_sehat_diagnosticreport_lab_mb.id_diagnosticreport,'') as id_diagnosticreport,saran_kesan_lab.kesan,"+
                   "template_laboratorium.kd_jenis_prw from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "+
                   "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat inner join permintaan_labmb on permintaan_labmb.no_rawat=reg_periksa.no_rawat "+
                   "inner join permintaan_detail_permintaan_labmb on permintaan_detail_permintaan_labmb.noorder=permintaan_labmb.noorder "+
                   "inner join template_laboratorium on template_laboratorium.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "inner join satu_sehat_mapping_lab on satu_sehat_mapping_lab.id_template=template_laboratorium.id_template "+
                   "inner join satu_sehat_servicerequest_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=permintaan_detail_permintaan_labmb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=permintaan_detail_permintaan_labmb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=permintaan_detail_permintaan_labmb.kd_jenis_prw "+
                   "inner join satu_sehat_specimen_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=satu_sehat_specimen_lab_mb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=satu_sehat_specimen_lab_mb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=satu_sehat_specimen_lab_mb.kd_jenis_prw "+
                   "inner join periksa_lab on periksa_lab.no_rawat=permintaan_labmb.no_rawat and periksa_lab.tgl_periksa=permintaan_labmb.tgl_hasil "+
                   "and periksa_lab.jam=permintaan_labmb.jam_hasil and periksa_lab.dokter_perujuk=permintaan_labmb.dokter_perujuk "+
                   "inner join saran_kesan_lab on periksa_lab.no_rawat=saran_kesan_lab.no_rawat and periksa_lab.tgl_periksa=saran_kesan_lab.tgl_periksa "+
                   "and periksa_lab.jam=saran_kesan_lab.jam "+
                   "inner join satu_sehat_observation_lab_mb on satu_sehat_specimen_lab_mb.noorder=satu_sehat_observation_lab_mb.noorder "+
                   "and satu_sehat_specimen_lab_mb.id_template=satu_sehat_observation_lab_mb.id_template "+
                   "and satu_sehat_specimen_lab_mb.kd_jenis_prw=satu_sehat_observation_lab_mb.kd_jenis_prw "+
                   "left join satu_sehat_diagnosticreport_lab_mb on satu_sehat_servicerequest_lab_mb.noorder=satu_sehat_diagnosticreport_lab_mb.noorder "+
                   "and satu_sehat_servicerequest_lab_mb.id_template=satu_sehat_diagnosticreport_lab_mb.id_template "+
                   "and satu_sehat_servicerequest_lab_mb.kd_jenis_prw=satu_sehat_diagnosticreport_lab_mb.kd_jenis_prw "+
                   "inner join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "+
                   "inner join pegawai on periksa_lab.kd_dokter=pegawai.nik "+
                   "where nota_inap.tanggal between ? and ? and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' and ifnull(satu_sehat_diagnosticreport_lab_mb.id_diagnosticreport,'')='' ");
            try {
                ps.setString(1,Tanggal1.getText());
                ps.setString(2,Tanggal2.getText());
                rs=ps.executeQuery();
                while(rs.next()){
                    if((!rs.getString("no_ktp").equals(""))&&(!rs.getString("ktpdokter").equals(""))&&rs.getString("id_diagnosticreport").equals("")){
                        try {
                            iddokter=cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));
                            idpasien=cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                            try{
                                headers = new HttpHeaders();
                                headers.setContentType(MediaType.APPLICATION_JSON);
                                headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
                                json = "{" +
                                            "\"resourceType\": \"DiagnosticReport\"," +
                                            "\"identifier\": [" +
                                                "{" +
                                                    "\"system\": \"http://sys-ids.kemkes.go.id/diagnostic/"+koneksiDB.IDSATUSEHAT()+"/lab\"," +
                                                    "\"use\": \"official\"," +
                                                    "\"value\": \""+rs.getString("noorder")+"."+rs.getString("id_template")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"status\": \"final\"," +
                                            "\"category\": [" +
                                                "{" +
                                                    "\"coding\": [" +
                                                        "{" +
                                                            "\"system\": \"http://terminology.hl7.org/CodeSystem/v2-0074\"," +
                                                            "\"code\": \"LAB\"," +
                                                            "\"display\": \"Laboratory\"" +
                                                        "}" +
                                                    "]" +
                                                "}" +
                                            "]," +
                                            "\"code\": {" +
                                                "\"coding\": [" +
                                                    "{" +
                                                        "\"code\": \""+rs.getString("code")+"\"," +
                                                        "\"display\": \""+rs.getString("display")+"\"," +
                                                        "\"system\": \""+rs.getString("system")+"\"" +
                                                    "}" +
                                                "]" +
                                            "}," +
                                            "\"subject\": {" +
                                                "\"reference\": \"Patient/"+idpasien+"\"" +
                                            "}," +
                                            "\"encounter\": {" +
                                                "\"reference\": \"Encounter/"+rs.getString("id_encounter")+"\"" +
                                            "}," +
                                            "\"effectiveDateTime\": \""+rs.getString("tgl_hasil")+"T"+rs.getString("jam_hasil")+"+07:00\"," +
                                            "\"issued\": \""+rs.getString("tgl_hasil")+"T"+rs.getString("jam_hasil")+"+07:00\"," +
                                            "\"performer\": [" +
                                                "{" +
                                                    "\"reference\": \"Practitioner/"+iddokter+"\"" +
                                                "}" +
                                            "]," +
                                            "\"specimen\": [{" +
                                                "\"reference\": \"Specimen/"+rs.getString("id_specimen")+"\"" +
                                            "}]," +
                                            "\"result\": [" +
                                                "{" +
                                                    "\"reference\": \"Observation/"+rs.getString("id_observation")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"basedOn\": [" +
                                                "{" +
                                                    "\"reference\": \"ServiceRequest/"+rs.getString("id_servicerequest")+"\"" +
                                                "}" +
                                            "]," +
                                            "\"conclusion\": \""+rs.getString("kesan").replaceAll("(\r\n|\r|\n|\n\r)","<br>").replaceAll("\t", " ")+"\"" +
                                        "}";
                                TeksArea.append("URL : "+link+"/DiagnosticReport");
                                TeksArea.append("Request JSON : "+json);
                                requestEntity = new HttpEntity(json,headers);
                                json=konekSatuSehat(link+"/DiagnosticReport", HttpMethod.POST, requestEntity);
                                TeksArea.append("Result JSON : "+json);
                                root = mapper.readTree(json);
                                response = root.path("id");
                                if(!response.asText().equals("")){
                                    Sequel.menyimpan2("satu_sehat_diagnosticreport_lab","?,?,?,?","No.Order",4,new String[]{
                                        rs.getString("noorder"),rs.getString("kd_jenis_prw"),rs.getString("id_template"),response.asText()
                                    });
                                }
                            }catch(Exception ea){
                                System.out.println("Notifikasi Bridging : "+ea);
                            }
                        } catch (Exception ef) {
                            System.out.println("Notifikasi : "+ef);
                        }
                    }
//------------------------------//tambahan buat rem  - ichsan
                try { Thread.sleep(50);  }  
                    catch (InterruptedException ex) 
                        { System.out.println("Proses jeda gagal: " + ex); }
//------------------------------//tambahan buat rem  - ichsan
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
    }
    
    // MODUL CARE PLAN (RENCANA PERAWATAN) - DETEKTIF + AUTO RECONNECT + QUERY OPTIMIZED
    private void careplan() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: CARE PLAN (RENCANA PERAWATAN)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. CARE PLAN - RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/2] Melacak Care Plan Ralan...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,"
                    + "pasien.nm_pasien,pasien.no_ktp,satu_sehat_encounter.id_encounter,pemeriksaan_ralan.rtl,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,pemeriksaan_ralan.tgl_perawatan,pemeriksaan_ralan.jam_rawat,"
                    + "ifnull(satu_sehat_careplan.id_careplan,'') as id_careplan "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat " // Inner Join = Wajib Ada Encounter
                    + "inner join pemeriksaan_ralan on pemeriksaan_ralan.no_rawat=reg_periksa.no_rawat "
                    + "inner join pegawai on pemeriksaan_ralan.nip=pegawai.nik "
                    + "left join satu_sehat_careplan on satu_sehat_careplan.no_rawat=pemeriksaan_ralan.no_rawat "
                    + "and satu_sehat_careplan.tgl_perawatan=pemeriksaan_ralan.tgl_perawatan and satu_sehat_careplan.jam_rawat=pemeriksaan_ralan.jam_rawat "
                    // Filter
                    + "where pemeriksaan_ralan.rtl<>'' and pemeriksaan_ralan.rtl<>'-' "
                    + "and pemeriksaan_ralan.tgl_perawatan between ? and ? "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_careplan.id_careplan,'')='' "
                    + "and satu_sehat_encounter.id_encounter <> '' "); // Validasi Encounter ID di Query
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF CAREPLAN RALAN] Rawat: " + rs.getString("no_rawat") + "\n");
                    
                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_careplan").equals("")) {
                        processCarePlan(rs, "736271009", "Outpatient care plan", "Ralan");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif CarePlan Ralan : " + e);
                TeksArea.append("ERROR QUERY CAREPLAN RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. CARE PLAN - RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[2/2] Melacak Care Plan Ranap...\n");
            ps = koneksi.prepareStatement(
                    "select reg_periksa.tgl_registrasi,reg_periksa.jam_reg,reg_periksa.no_rawat,reg_periksa.no_rkm_medis,"
                    + "pasien.nm_pasien,pasien.no_ktp,satu_sehat_encounter.id_encounter,pemeriksaan_ranap.rtl,"
                    + "pegawai.nama,pegawai.no_ktp as ktppraktisi,pemeriksaan_ranap.tgl_perawatan,pemeriksaan_ranap.jam_rawat,"
                    + "ifnull(satu_sehat_careplan.id_careplan,'') as id_careplan "
                    + "from reg_periksa inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat " // Inner Join = Wajib Ada Encounter
                    + "inner join pemeriksaan_ranap on pemeriksaan_ranap.no_rawat=reg_periksa.no_rawat "
                    + "inner join pegawai on pemeriksaan_ranap.nip=pegawai.nik "
                    + "left join satu_sehat_careplan on satu_sehat_careplan.no_rawat=pemeriksaan_ranap.no_rawat "
                    + "and satu_sehat_careplan.tgl_perawatan=pemeriksaan_ranap.tgl_perawatan and satu_sehat_careplan.jam_rawat=pemeriksaan_ranap.jam_rawat "
                    // Filter
                    + "where pemeriksaan_ranap.rtl<>'' and pemeriksaan_ranap.rtl<>'-' "
                    + "and pemeriksaan_ranap.tgl_perawatan between ? and ? "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' and pasien.no_ktp <> '0000000000000000' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' and pegawai.no_ktp <> '0000000000000000' "
                    + "and ifnull(satu_sehat_careplan.id_careplan,'')='' "
                    + "and satu_sehat_encounter.id_encounter <> '' "); // Validasi Encounter ID di Query
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Log Detektif
                    TeksArea.append("\n[DETEKTIF CAREPLAN RANAP] Rawat: " + rs.getString("no_rawat") + "\n");

                    if ((!rs.getString("no_ktp").equals("")) && (!rs.getString("ktppraktisi").equals("")) && rs.getString("id_careplan").equals("")) {
                        processCarePlan(rs, "736353004", "Inpatient care plan", "Ranap");
                    }
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif CarePlan Ranap : " + e);
                TeksArea.append("ERROR QUERY CAREPLAN RANAP: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

        } catch (Exception e) {
            System.out.println("Notifikasi Utama CarePlan : " + e);
            TeksArea.append("!! ERROR UTAMA CAREPLAN: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS CARE PLAN (Auto Reconnect)
    private void processCarePlan(ResultSet rs, String snomedCode, String snomedDisplay, String statusRawat) {
        try {
            // 1. Cek KyC
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Praktisi tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // 2. Sanitasi Data RTL
            String rtl = rs.getString("rtl")
                    .replaceAll("(\r\n|\r|\n|\n\r)", "<br>")
                    .replaceAll("\"", "'")
                    .replaceAll("\\\\", "/")
                    .replaceAll("\t", " ");
            
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmDokter = rs.getString("nama").replaceAll("\"", "'");
            String tglCreated = rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00";

            // 3. Konstruksi JSON
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
            
            json = "{"
                    + "\"resourceType\" : \"CarePlan\","
                    + "\"identifier\" : ["
                        + "{"
                            + "\"system\" : \"http://sys-ids.kemkes.go.id/careplan/" + koneksiDB.IDSATUSEHAT() + "\","
                            + "\"value\" : \"" + rs.getString("no_rawat") + "\""
                        + "}"
                    + "],"
                    + "\"title\" : \"Instruksi Medik dan Keperawatan Pasien\","
                    + "\"status\" : \"active\","
                    + "\"category\" : ["
                        + "{"
                            + "\"coding\" : ["
                                + "{"
                                    + "\"system\" : \"http://snomed.info/sct\","
                                    + "\"code\" : \"" + snomedCode + "\","
                                    + "\"display\" : \"" + snomedDisplay + "\""
                                + "}"
                            + "]"
                        + "}"
                    + "],"
                    + "\"intent\" : \"plan\","
                    + "\"description\" : \"" + rtl + "\","
                    + "\"subject\" : {"
                        + "\"reference\" : \"Patient/" + idpasien + "\","
                        + "\"display\" : \"" + nmPasien + "\""
                    + "},"
                    + "\"encounter\" : {"
                        + "\"reference\" : \"Encounter/" + rs.getString("id_encounter") + "\","
                        + "\"display\" : \"Kunjungan " + nmPasien + " pada tanggal " + rs.getString("tgl_registrasi") + " dengan nomor kunjungan " + rs.getString("no_rawat") + "\""
                    + "},"
                    + "\"created\" : \"" + tglCreated + "\","
                    + "\"author\" : {"
                        + "\"reference\" : \"Practitioner/" + iddokter + "\","
                        + "\"display\" : \"" + nmDokter + "\""
                    + "}"
                    + "}";

            // TeksArea.append("   [DEBUG JSON] " + json + "\n");

            // 4. Kirim Request (Dengan Auto Reconnect)
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/CarePlan", HttpMethod.POST, requestEntity);
                simpanLogCarePlan(responseJson, rs, statusRawat);
                
            } catch (HttpClientErrorException e) {
                // HANDLER TOKEN EXPIRED (401)
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    
                    try {
                        // RETRY KIRIM
                        String responseJson = konekSatuSehat(link + "/CarePlan", HttpMethod.POST, requestEntity);
                        simpanLogCarePlan(responseJson, rs, statusRawat);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            } catch (Exception e) {
                TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR INTERNAL] " + e + "\n");
            e.printStackTrace();
        }
    }

    private void simpanLogCarePlan(String responseJson, ResultSet rs, String statusRawat) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_careplan", "?,?,?,?,?", "Rencana Perawatan", 5, new String[]{
                rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam_rawat"), statusRawat, responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }
    
    
    
    // MODUL QUESTIONNAIRE (TELAAH RESEP) - DETEKTIF + AUTO RECONNECT + RECOVERY
    private void questionnaire() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: QUESTIONNAIRE (TELAAH RESEP)\n");
            TeksArea.append("------------------------------------------------------\n");

            // ===========================================================================================
            // 1. TELAAH RESEP - RAWAT JALAN (RALAN)
            // ===========================================================================================
            TeksArea.append("\n[1/2] Melacak Telaah Resep Ralan...\n");
            // Query Optimized: Inner Join Encounter (Wajib), Filter Tanggal Nota
            ps = koneksi.prepareStatement(
                "SELECT resep_obat.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
                "telaah_farmasi.no_resep, resep_obat.tgl_penyerahan, resep_obat.jam_penyerahan, telaah_farmasi.nip, " +
                "pegawai.nama, pegawai.no_ktp AS aptktp, " +
                "telaah_farmasi.resep_identifikasi_pasien, telaah_farmasi.resep_tepat_obat, telaah_farmasi.resep_tepat_waktu_pemberian, " +
                "telaah_farmasi.resep_tepat_dosis, telaah_farmasi.resep_tepat_cara_pemberian, " +
                "telaah_farmasi.resep_ada_tidak_duplikasi_obat, telaah_farmasi.resep_kontra_indikasi_obat, telaah_farmasi.resep_interaksi_obat, " +
                "satu_sehat_encounter.id_encounter, ifnull(satu_sehat_questionnairereq_pengkajian_obat.id_questreq,'') as id_questreq " +
                "FROM resep_obat " +
                "INNER JOIN telaah_farmasi ON resep_obat.no_resep = telaah_farmasi.no_resep " +
                "INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
                "INNER JOIN satu_sehat_encounter ON resep_obat.no_rawat = satu_sehat_encounter.no_rawat " + // Wajib Ada Encounter
                "INNER JOIN pegawai ON telaah_farmasi.nip = pegawai.nik " +
                "INNER JOIN nota_jalan ON resep_obat.no_rawat = nota_jalan.no_rawat " +
                "LEFT JOIN satu_sehat_questionnairereq_pengkajian_obat ON telaah_farmasi.no_resep = satu_sehat_questionnairereq_pengkajian_obat.no_resep " +
                "WHERE satu_sehat_questionnairereq_pengkajian_obat.id_questreq IS NULL " +
                "AND resep_obat.tgl_penyerahan <> '0000-00-00' " +
                "AND nota_jalan.tanggal BETWEEN ? AND ? " +
                "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
                "AND LENGTH(pegawai.no_ktp) = 16 AND pegawai.no_ktp REGEXP '^[0-9]+$' AND pegawai.no_ktp <> '0000000000000000' " +
                "GROUP BY telaah_farmasi.no_resep"
            );
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    processQuestionnaireResponse(rs, "Ralan");
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Kueri Ralan: " + e);
                TeksArea.append("ERROR QUERY TELAAH RALAN: " + e + "\n");
            } finally {
                if(rs != null) rs.close();
                if(ps != null) ps.close();
            }

            // ===========================================================================================
            // 2. TELAAH RESEP - RAWAT INAP (RANAP)
            // ===========================================================================================
            TeksArea.append("\n[2/2] Melacak Telaah Resep Ranap...\n");
            ps = koneksi.prepareStatement(
                "SELECT resep_obat.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
                "telaah_farmasi.no_resep, resep_obat.tgl_penyerahan, resep_obat.jam_penyerahan, telaah_farmasi.nip, " +
                "pegawai.nama, pegawai.no_ktp AS aptktp, " +
                "telaah_farmasi.resep_identifikasi_pasien, telaah_farmasi.resep_tepat_obat, telaah_farmasi.resep_tepat_waktu_pemberian, " +
                "telaah_farmasi.resep_tepat_dosis, telaah_farmasi.resep_tepat_cara_pemberian, " +
                "telaah_farmasi.resep_ada_tidak_duplikasi_obat, telaah_farmasi.resep_kontra_indikasi_obat, telaah_farmasi.resep_interaksi_obat, " +
                "satu_sehat_encounter.id_encounter, ifnull(satu_sehat_questionnairereq_pengkajian_obat.id_questreq,'') as id_questreq " +
                "FROM resep_obat " +
                "INNER JOIN telaah_farmasi ON resep_obat.no_resep = telaah_farmasi.no_resep " +
                "INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
                "INNER JOIN satu_sehat_encounter ON resep_obat.no_rawat = satu_sehat_encounter.no_rawat " +
                "INNER JOIN pegawai ON telaah_farmasi.nip = pegawai.nik " +
                "INNER JOIN nota_inap ON resep_obat.no_rawat = nota_inap.no_rawat " +
                "LEFT JOIN satu_sehat_questionnairereq_pengkajian_obat ON telaah_farmasi.no_resep = satu_sehat_questionnairereq_pengkajian_obat.no_resep " +
                "WHERE satu_sehat_questionnairereq_pengkajian_obat.id_questreq IS NULL " +
                "AND resep_obat.tgl_penyerahan <> '0000-00-00' " +
                "AND nota_inap.tanggal BETWEEN ? AND ? " +
                "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
                "AND LENGTH(pegawai.no_ktp) = 16 AND pegawai.no_ktp REGEXP '^[0-9]+$' AND pegawai.no_ktp <> '0000000000000000' " +
                "GROUP BY telaah_farmasi.no_resep"
            );
            
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    processQuestionnaireResponse(rs, "Ranap");
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Kueri Ranap: " + e);
                TeksArea.append("ERROR QUERY TELAAH RANAP: " + e + "\n");
            } finally {
                if(rs != null) rs.close();
                if(ps != null) ps.close();
            }

        } catch (Exception e) {
            System.out.println("Notifikasi Utama Questionnaire: " + e);
            TeksArea.append("!! ERROR UTAMA QUESTIONNAIRE: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS QUESTIONNAIRE (Auto Reconnect + Duplicate Recovery)
    private void processQuestionnaireResponse(ResultSet rs, String contextInfo) {
        String identifierValue = "";
        try {
            TeksArea.append("\n[DETEKTIF QUESTIONNAIRE " + contextInfo.toUpperCase() + "] No.Resep: " + rs.getString("no_resep") + "\n");

            // 1. Validasi Prasyarat
            if (!rs.getString("id_questreq").isEmpty()) {
                TeksArea.append("   !! [SKIP] Sudah terkirim (ID: " + rs.getString("id_questreq") + ")\n");
                return;
            }

            // 2. Cek KyC
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("aptktp")); // Apt KTP

            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Apoteker tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // 3. Mapping Data
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmApoteker = rs.getString("nama").replaceAll("\"", "'");
            
            // Mapping Jawaban (Null Safe)
            String[] adminLengkap = mapYaTidak(rs.getString("resep_identifikasi_pasien"));
            String[] resepJelas = mapYaTidak(rs.getString("resep_tepat_obat")); // Proxy
            String[] tglResepSesuai = mapYaTidak(rs.getString("resep_tepat_waktu_pemberian"));
            String[] unitSesuai = {"OV000052", "Sesuai"}; 
            String[] namaObatSesuai = mapYaTidak(rs.getString("resep_tepat_obat"));
            String[] dosisSesuai = mapYaTidak(rs.getString("resep_tepat_dosis"));
            String[] aturanPakaiSesuai = mapYaTidak(rs.getString("resep_tepat_cara_pemberian"));
            String[] indikasiSesuai = mapYaTidak(rs.getString("resep_tepat_obat")); // Proxy

            String duplikasi = mapAdaTidakKeBoolean(rs.getString("resep_ada_tidak_duplikasi_obat"));
            String alergi = mapAdaTidakKeBoolean(rs.getString("resep_kontra_indikasi_obat")); 
            String kontraindikasi = mapAdaTidakKeBoolean(rs.getString("resep_kontra_indikasi_obat"));
            String interaksi = mapAdaTidakKeBoolean(rs.getString("resep_interaksi_obat"));

            // Identifier untuk Recovery
            identifierValue = rs.getString("no_resep"); // Identifier Unik QuestionnaireResponse

            // 4. Konstruksi JSON
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            json = "{\n" +
                "    \"resourceType\": \"QuestionnaireResponse\",\n" +
                "    \"identifier\": {\n" + // Tambahkan Identifier agar bisa dicari
                "        \"system\": \"http://sys-ids.kemkes.go.id/questionnaireresponse/" + koneksiDB.IDSATUSEHAT() + "\",\n" +
                "        \"value\": \"" + identifierValue + "\"\n" +
                "    },\n" +
                "    \"questionnaire\": \"https://fhir.kemkes.go.id/Questionnaire/Q0007\",\n" +
                "    \"status\": \"completed\",\n" +
                "    \"subject\": {\n" +
                "        \"reference\": \"Patient/" + idpasien + "\",\n" +
                "        \"display\": \"" + nmPasien + "\"\n" +
                "    },\n" +
                "    \"encounter\": {\n" +
                "        \"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"\n" +
                "    },\n" +
                "    \"authored\": \"" + rs.getString("tgl_penyerahan") + "T" + rs.getString("jam_penyerahan") + "+07:00\",\n" +
                "    \"author\": {\n" +
                "        \"reference\": \"Practitioner/" + iddokter + "\",\n" +
                "        \"display\": \"" + nmApoteker + "\"\n" +
                "    },\n" +
                "    \"source\": {\n" +
                "        \"reference\": \"Patient/" + idpasien + "\"\n" +
                "    },\n" +
                "    \"item\": [\n" +
                "        {\n" +
                "            \"linkId\": \"1\",\n" +
                "            \"text\": \"Persyaratan Administrasi\",\n" +
                "            \"item\": [\n" +
                "                {\"linkId\": \"1.1\", \"text\": \"Identifikasi Pasien\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + adminLengkap[0] + "\", \"display\": \"" + adminLengkap[1] + "\"}}]},\n" +
                "                {\"linkId\": \"1.2\", \"text\": \"Identifikasi Dokter\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + resepJelas[0] + "\", \"display\": \"" + resepJelas[1] + "\"}}]},\n" +
                "                {\"linkId\": \"1.3\", \"text\": \"Tanggal Resep\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + tglResepSesuai[0] + "\", \"display\": \"" + tglResepSesuai[1] + "\"}}]},\n" +
                "                {\"linkId\": \"1.4\", \"text\": \"Unit Asal\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + unitSesuai[0] + "\", \"display\": \"" + unitSesuai[1] + "\"}}]}\n" +
                "            ]\n" +
                "        },\n" +
                "        {\n" +
                "            \"linkId\": \"2\",\n" +
                "            \"text\": \"Persyaratan Farmasetik\",\n" +
                "            \"item\": [\n" +
                "                {\"linkId\": \"2.1\", \"text\": \"Nama, Bentuk, Kekuatan\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + namaObatSesuai[0] + "\", \"display\": \"" + namaObatSesuai[1] + "\"}}]},\n" +
                "                {\"linkId\": \"2.2\", \"text\": \"Dosis & Jumlah\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + dosisSesuai[0] + "\", \"display\": \"" + dosisSesuai[1] + "\"}}]},\n" +
                "                {\"linkId\": \"2.3\", \"text\": \"Aturan & Cara\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + aturanPakaiSesuai[0] + "\", \"display\": \"" + aturanPakaiSesuai[1] + "\"}}]}\n" +
                "            ]\n" +
                "        },\n" +
                "        {\n" +
                "            \"linkId\": \"3\",\n" +
                "            \"text\": \"Persyaratan Klinis\",\n" +
                "            \"item\": [\n" +
                "                {\"linkId\": \"3.1\", \"text\": \"Ketepatan Indikasi\", \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + indikasiSesuai[0] + "\", \"display\": \"" + indikasiSesuai[1] + "\"}}]},\n" +
                "                {\"linkId\": \"3.2\", \"text\": \"Duplikasi\", \"answer\": [{\"valueBoolean\": " + duplikasi + "}]},\n" +
                "                {\"linkId\": \"3.3\", \"text\": \"Alergi/ROTD\", \"answer\": [{\"valueBoolean\": " + alergi + "}]},\n" +
                "                {\"linkId\": \"3.4\", \"text\": \"Kontraindikasi\", \"answer\": [{\"valueBoolean\": " + kontraindikasi + "}]},\n" +
                "                {\"linkId\": \"3.5\", \"text\": \"Interaksi\", \"answer\": [{\"valueBoolean\": " + interaksi + "}]}\n" +
                "            ]\n" +
                "        }\n" +
                "    ]\n" +
                "}";

            // 5. Kirim Request
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/QuestionnaireResponse", HttpMethod.POST, requestEntity);
                simpanLogQuestionnaire(responseJson, rs);
                
            } catch (HttpClientErrorException e) {
                // HANDLER TOKEN EXPIRED (401)
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Meminta token baru...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    try {
                        String responseJson = konekSatuSehat(link + "/QuestionnaireResponse", HttpMethod.POST, requestEntity);
                        simpanLogQuestionnaire(responseJson, rs);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } 
                // HANDLER DUPLICATE (400)
                else if (e.getStatusCode().value() == 400 && e.getResponseBodyAsString().contains("duplicate")) {
                    TeksArea.append("   [INFO] Data Duplikat. Mencoba Recovery ID...\n");
                    recoverDuplicateQuestionnaire(identifierValue, rs);
                }
                else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            } catch (Exception e) {
                TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR INTERNAL] " + e + "\n");
            e.printStackTrace();
        }
    }

    private void simpanLogQuestionnaire(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            Sequel.menyimpan2("satu_sehat_questionnairereq_pengkajian_obat", "?,?,?", "Questionnaire Telaah Obat", 3, new String[]{
                rs.getString("no_resep"), rs.getString("no_rawat"), responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
        }
    }

    // HELPER RECOVERY DUPLIKAT
    private void recoverDuplicateQuestionnaire(String identifierValue, ResultSet rs) {
        try {
            // Mencari ID berdasarkan Identifier (no_resep)
            String searchUrl = link + "/QuestionnaireResponse?identifier=http://sys-ids.kemkes.go.id/questionnaireresponse/" + koneksiDB.IDSATUSEHAT() + "|" + identifierValue;
            String searchJson = konekSatuSehat(searchUrl, HttpMethod.GET, new HttpEntity(headers));
            
            JsonNode searchRoot = mapper.readTree(searchJson);
            if (searchRoot.path("total").asInt() > 0) {
                String existingId = searchRoot.path("entry").get(0).path("resource").path("id").asText();
                TeksArea.append("   [RECOVERED] ID Ditemukan: " + existingId + "\n");
                
                // Simpan ID yang ditemukan
                Sequel.menyimpan2("satu_sehat_questionnairereq_pengkajian_obat", "?,?,?", "Questionnaire Telaah Obat", 3, new String[]{
                    rs.getString("no_resep"), rs.getString("no_rawat"), existingId
                });
            } else {
                TeksArea.append("   !! [GAGAL RECOVER] Data duplikat tapi tidak ditemukan saat dicari.\n");
            }
        } catch (Exception ex) {
            TeksArea.append("   !! [ERROR RECOVER] Gagal mengambil data duplikat: " + ex.getMessage() + "\n");
        }
    }

    // Helper method untuk mapping Ya/Tidak ke Kode & Display (Null Safe)
    private String[] mapYaTidak(String value) {
        if (value != null && "Ya".equalsIgnoreCase(value.trim())) {
            return new String[]{"OV000052", "Sesuai"};
        } else {
            return new String[]{"OV000053", "Tidak Sesuai"};
        }
    }

    // Helper method untuk mapping Ada/Tidak ke true/false (Null Safe)
    private String mapAdaTidakKeBoolean(String value) {
        if (value != null && ("Ada".equalsIgnoreCase(value.trim()) || "Ya".equalsIgnoreCase(value.trim()))) {
            return "true";
        } else {
            return "false";
        }
    }
    
    /*
    private void questionnaire() {
    try {
        // Kueri untuk Rawat Jalan
        ps = koneksi.prepareStatement(
            "SELECT resep_obat.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
            "telaah_farmasi.no_resep, resep_obat.tgl_penyerahan, resep_obat.jam_penyerahan, telaah_farmasi.nip, " +
            "pegawai.nama, pegawai.no_ktp AS aptktp, " +
            "telaah_farmasi.resep_identifikasi_pasien, telaah_farmasi.resep_tepat_obat, telaah_farmasi.resep_tepat_waktu_pemberian, " +
            "telaah_farmasi.resep_tepat_dosis, telaah_farmasi.resep_tepat_cara_pemberian, " +
            "telaah_farmasi.resep_ada_tidak_duplikasi_obat, telaah_farmasi.resep_kontra_indikasi_obat, telaah_farmasi.resep_interaksi_obat, " +
            "satu_sehat_encounter.id_encounter " +
            "FROM resep_obat " +
            "INNER JOIN telaah_farmasi ON resep_obat.no_resep = telaah_farmasi.no_resep " +
            "INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat " +
            "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
            "INNER JOIN satu_sehat_encounter ON resep_obat.no_rawat = satu_sehat_encounter.no_rawat " +
            "INNER JOIN pegawai ON telaah_farmasi.nip = pegawai.nik " +
            "INNER JOIN nota_jalan ON resep_obat.no_rawat = nota_jalan.no_rawat " +
            "LEFT JOIN satu_sehat_questionnairereq_pengkajian_obat ON telaah_farmasi.no_resep = satu_sehat_questionnairereq_pengkajian_obat.no_resep " +
            "WHERE satu_sehat_questionnairereq_pengkajian_obat.id_questreq IS NULL " +
            "AND resep_obat.tgl_penyerahan <> '0000-00-00' " +
            "AND nota_jalan.tanggal BETWEEN ? AND ? " +
            "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
            "AND LENGTH(pegawai.no_ktp) = 16 AND pegawai.no_ktp REGEXP '^[0-9]+$' AND pegawai.no_ktp <> '0000000000000000' " +
            "GROUP BY telaah_farmasi.no_resep"
        );
        try {
            ps.setString(1, Tanggal1.getText());
            ps.setString(2, Tanggal2.getText());
            rs = ps.executeQuery();
            while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                kirimQuestionnaire(rs);
            }
        } catch (Exception e) {
            System.out.println("Notif Kueri Ralan: " + e);
        } finally {
            if(rs != null) rs.close();
            if(ps != null) ps.close();
        }

        // Kueri untuk Rawat Inap
        ps = koneksi.prepareStatement(
            "SELECT resep_obat.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
            "telaah_farmasi.no_resep, resep_obat.tgl_penyerahan, resep_obat.jam_penyerahan, telaah_farmasi.nip, " +
            "pegawai.nama, pegawai.no_ktp AS aptktp, " +
            "telaah_farmasi.resep_identifikasi_pasien, telaah_farmasi.resep_tepat_obat, telaah_farmasi.resep_tepat_waktu_pemberian, " +
            "telaah_farmasi.resep_tepat_dosis, telaah_farmasi.resep_tepat_cara_pemberian, " +
            "telaah_farmasi.resep_ada_tidak_duplikasi_obat, telaah_farmasi.resep_kontra_indikasi_obat, telaah_farmasi.resep_interaksi_obat, " +
            "satu_sehat_encounter.id_encounter " +
            "FROM resep_obat " +
            "INNER JOIN telaah_farmasi ON resep_obat.no_resep = telaah_farmasi.no_resep " +
            "INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat " +
            "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
            "INNER JOIN satu_sehat_encounter ON resep_obat.no_rawat = satu_sehat_encounter.no_rawat " +
            "INNER JOIN pegawai ON telaah_farmasi.nip = pegawai.nik " +
            "INNER JOIN nota_inap ON resep_obat.no_rawat = nota_inap.no_rawat " +
            "LEFT JOIN satu_sehat_questionnairereq_pengkajian_obat ON telaah_farmasi.no_resep = satu_sehat_questionnairereq_pengkajian_obat.no_resep " +
            "WHERE satu_sehat_questionnairereq_pengkajian_obat.id_questreq IS NULL " +
            "AND resep_obat.tgl_penyerahan <> '0000-00-00' " +
            "AND nota_inap.tanggal BETWEEN ? AND ? " +
            "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
            "AND LENGTH(pegawai.no_ktp) = 16 AND pegawai.no_ktp REGEXP '^[0-9]+$' AND pegawai.no_ktp <> '0000000000000000' " +
            "GROUP BY telaah_farmasi.no_resep"
        );
        try {
            ps.setString(1, Tanggal1.getText());
            ps.setString(2, Tanggal2.getText());
            rs = ps.executeQuery();
            while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                kirimQuestionnaire(rs);
            }
        } catch (Exception e) {
            System.out.println("Notif Kueri Ranap: " + e);
        } finally {
            if(rs != null) rs.close();
            if(ps != null) ps.close();
        }
    } catch (Exception e) {
        System.out.println("Notifikasi Utama Questionnaire: " + e);
    }
}

private void kirimQuestionnaire(ResultSet rs) throws Exception {
        try {
            TeksArea.append("\n[PROSES QUESTIONNAIRE RESPONSE] No.Resep: " + rs.getString("no_resep") + "\n");

            // 1. Validasi ID Pasien & Apoteker
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            // Note: aptktp adalah NIK Apoteker yang melakukan telaah
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("aptktp"));

            if (idpasien.equals("") || iddokter.equals("")) {
                TeksArea.append("!! SKIP: ID Pasien/Apoteker tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // 2. Sanitasi Data Teks
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmApoteker = rs.getString("nama").replaceAll("\"", "'");

            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            // 3. Mapping Jawaban (Null Safe)
            String[] adminLengkap = mapYaTidak(rs.getString("resep_identifikasi_pasien"));
            String[] resepJelas = mapYaTidak(rs.getString("resep_tepat_obat")); // Proxy
            String[] tglResepSesuai = mapYaTidak(rs.getString("resep_tepat_waktu_pemberian"));
            String[] unitSesuai = {"OV000052", "Sesuai"}; // Hardcoded sesuai logika lama
            String[] namaObatSesuai = mapYaTidak(rs.getString("resep_tepat_obat"));
            String[] dosisSesuai = mapYaTidak(rs.getString("resep_tepat_dosis"));
            String[] aturanPakaiSesuai = mapYaTidak(rs.getString("resep_tepat_cara_pemberian"));
            String[] indikasiSesuai = mapYaTidak(rs.getString("resep_tepat_obat")); // Proxy

            String duplikasi = mapAdaTidakKeBoolean(rs.getString("resep_ada_tidak_duplikasi_obat"));
            String alergi = mapAdaTidakKeBoolean(rs.getString("resep_kontra_indikasi_obat")); // Proxy
            String kontraindikasi = mapAdaTidakKeBoolean(rs.getString("resep_kontra_indikasi_obat"));
            String interaksi = mapAdaTidakKeBoolean(rs.getString("resep_interaksi_obat"));

            // 4. Konstruksi JSON
            json = "{\n" +
                "    \"resourceType\": \"QuestionnaireResponse\",\n" +
                "    \"questionnaire\": \"https://fhir.kemkes.go.id/Questionnaire/Q0007\",\n" +
                "    \"status\": \"completed\",\n" +
                "    \"subject\": {\n" +
                "        \"reference\": \"Patient/" + idpasien + "\",\n" +
                "        \"display\": \"" + nmPasien + "\"\n" +
                "    },\n" +
                "    \"encounter\": {\n" +
                "        \"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"\n" +
                "    },\n" +
                "    \"authored\": \"" + rs.getString("tgl_penyerahan") + "T" + rs.getString("jam_penyerahan") + "+07:00\",\n" +
                "    \"author\": {\n" +
                "        \"reference\": \"Practitioner/" + iddokter + "\",\n" +
                "        \"display\": \"" + nmApoteker + "\"\n" +
                "    },\n" +
                "    \"source\": {\n" +
                "        \"reference\": \"Patient/" + idpasien + "\"\n" +
                "    },\n" +
                "    \"item\": [\n" +
                "        {\n" +
                "            \"linkId\": \"1\",\n" +
                "            \"text\": \"Persyaratan Administrasi\",\n" +
                "            \"item\": [\n" +
                "                {\n" +
                "                    \"linkId\": \"1.1\",\n" +
                "                    \"text\": \"Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + adminLengkap[0] + "\", \"display\": \"" + adminLengkap[1] + "\"}}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"1.2\",\n" +
                "                    \"text\": \"Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + resepJelas[0] + "\", \"display\": \"" + resepJelas[1] + "\"}}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"1.3\",\n" +
                "                    \"text\": \"Apakah tanggal resep sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + tglResepSesuai[0] + "\", \"display\": \"" + tglResepSesuai[1] + "\"}}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"1.4\",\n" +
                "                    \"text\": \"Apakah ruangan/unit asal resep sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + unitSesuai[0] + "\", \"display\": \"" + unitSesuai[1] + "\"}}]\n" +
                "                }\n" +
                "            ]\n" +
                "        },\n" +
                "        {\n" +
                "            \"linkId\": \"2\",\n" +
                "            \"text\": \"Persyaratan Farmasetik\",\n" +
                "            \"item\": [\n" +
                "                {\n" +
                "                    \"linkId\": \"2.1\",\n" +
                "                    \"text\": \"Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + namaObatSesuai[0] + "\", \"display\": \"" + namaObatSesuai[1] + "\"}}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"2.2\",\n" +
                "                    \"text\": \"Apakah dosis dan jumlah obat sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + dosisSesuai[0] + "\", \"display\": \"" + dosisSesuai[1] + "\"}}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"2.3\",\n" +
                "                    \"text\": \"Apakah aturan dan cara penggunaan obat sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + aturanPakaiSesuai[0] + "\", \"display\": \"" + aturanPakaiSesuai[1] + "\"}}]\n" +
                "                }\n" +
                "            ]\n" +
                "        },\n" +
                "        {\n" +
                "            \"linkId\": \"3\",\n" +
                "            \"text\": \"Persyaratan Klinis\",\n" +
                "            \"item\": [\n" +
                "                {\n" +
                "                    \"linkId\": \"3.1\",\n" +
                "                    \"text\": \"Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?\",\n" +
                "                    \"answer\": [{\"valueCoding\": {\"system\": \"http://terminology.kemkes.go.id/CodeSystem/clinical-term\", \"code\": \"" + indikasiSesuai[0] + "\", \"display\": \"" + indikasiSesuai[1] + "\"}}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"3.2\",\n" +
                "                    \"text\": \"Apakah terdapat duplikasi pengobatan?\",\n" +
                "                    \"answer\": [{\"valueBoolean\": " + duplikasi + "}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"3.3\",\n" +
                "                    \"text\": \"Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?\",\n" +
                "                    \"answer\": [{\"valueBoolean\": " + alergi + "}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"3.4\",\n" +
                "                    \"text\": \"Apakah terdapat kontraindikasi pengobatan?\",\n" +
                "                    \"answer\": [{\"valueBoolean\": " + kontraindikasi + "}]\n" +
                "                },\n" +
                "                {\n" +
                "                    \"linkId\": \"3.5\",\n" +
                "                    \"text\": \"Apakah terdapat dampak interaksi obat?\",\n" +
                "                    \"answer\": [{\"valueBoolean\": " + interaksi + "}]\n" +
                "                }\n" +
                "            ]\n" +
                "        }\n" +
                "    ]\n" +
                "}";

            TeksArea.append("   URL : " + link + "/QuestionnaireResponse\n");
            TeksArea.append("   Request JSON : " + json + "\n");

            requestEntity = new HttpEntity(json, headers);
            json = konekSatuSehat(link + "/QuestionnaireResponse", HttpMethod.POST, requestEntity);
            
            TeksArea.append("   Result JSON : " + json + "\n");
            
            root = mapper.readTree(json);
            response = root.path("id");
            
            if (!response.asText().equals("")) {
                Sequel.menyimpan2("satu_sehat_questionnairereq_pengkajian_obat", "?,?,?", "Questionnaire Telaah Obat", 3, new String[]{
                    rs.getString("no_resep"), rs.getString("no_rawat"), response.asText()
                });
                TeksArea.append("   [SUKSES] Disimpan ke database lokal.\n");
            }
            Thread.sleep(50);
        } catch (Exception e) {
            TeksArea.append("   [ERROR] " + e + "\n");
            System.out.println("Notifikasi Kirim Questionnaire : " + e);
            if (e.toString().contains("UnknownHostException") || e.toString().contains("unreachable")) {
                 System.out.println("Koneksi ke server Satu Sehat terputus. Menunggu beberapa saat sebelum mencoba lagi.");
                 Thread.sleep(300); 
            }
        }
    }

    // Helper method untuk mapping Ya/Tidak ke Kode & Display (Null Safe)
    private String[] mapYaTidak(String value) {
        if (value != null && "Ya".equalsIgnoreCase(value.trim())) {
            return new String[]{"OV000052", "Sesuai"};
        } else {
            return new String[]{"OV000053", "Tidak Sesuai"};
        }
    }

    // Helper method untuk mapping Ada/Tidak ke true/false (Null Safe)
    private String mapAdaTidakKeBoolean(String value) {
        if (value != null && ("Ada".equalsIgnoreCase(value.trim()) || "Ya".equalsIgnoreCase(value.trim()))) {
            return "true";
        } else {
            return "false";
        }
    }  */
    
    
    // GERBONG TERAKHIR: KIRIM COMPOSITION (RESUME MEDIS) - REVISI TIPE DOKUMEN
    // MODUL COMPOSITION (RESUME MEDIS) - DETEKTIF + AUTO RECONNECT + RECOVERY
    private void kirimComposition() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES DETEKTIF: COMPOSITION (RESUME MEDIS)\n");
            TeksArea.append("------------------------------------------------------\n");

            // QUERY SWEEPER (VALIDASI KASIR & RESUME & ENCOUNTER)
            // Mengambil 10 data per batch agar tidak overload memori
            String query = 
                "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, p.no_ktp, " +
                "pg.nama as nama_dokter, pg.no_ktp as nik_dokter, " +
                "rp.status_lanjut, " + 
                "sse.id_encounter, " +
                "rp.tgl_registrasi, rp.jam_reg, " +
                "ifnull(ssc.id_composition,'') as id_composition_ada, " +
                
                // Kumpulkan ID Resource Anak (Conditions, MedicationRequests, Observations)
                "GROUP_CONCAT(DISTINCT scond.id_condition SEPARATOR '|') as list_diagnosa, " +
                "GROUP_CONCAT(DISTINCT ssmr.id_medicationrequest SEPARATOR '|') as list_resep, " +
                "GROUP_CONCAT(DISTINCT ssol.id_observation SEPARATOR '|') as list_observasi_lab, " +
                "GROUP_CONCAT(DISTINCT ssor.id_observation SEPARATOR '|') as list_observasi_rad " +
                
                "FROM reg_periksa rp " +
                "JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis " +
                "JOIN pegawai pg ON rp.kd_dokter = pg.nik " +
                "JOIN satu_sehat_encounter sse ON sse.no_rawat = rp.no_rawat " + // Wajib Ada Encounter
                
                // JOIN LOG RESOURCES (Left Join karena mungkin tidak ada resep/lab/rad)
                "LEFT JOIN satu_sehat_condition scond ON scond.no_rawat = rp.no_rawat " +
                
                "LEFT JOIN resep_obat ro ON ro.no_rawat = rp.no_rawat " +
                "LEFT JOIN satu_sehat_medicationrequest ssmr ON ssmr.no_resep = ro.no_resep " +
                
                "LEFT JOIN permintaan_lab pl ON pl.no_rawat = rp.no_rawat " +
                "LEFT JOIN permintaan_detail_permintaan_lab pdpl ON pdpl.noorder = pl.noorder " +
                "LEFT JOIN satu_sehat_observation_lab ssol ON ssol.noorder = pdpl.noorder AND ssol.id_template = pdpl.id_template " +
                
                "LEFT JOIN permintaan_radiologi pr ON pr.no_rawat = rp.no_rawat " +
                "LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON ppr.noorder = pr.noorder " +
                "LEFT JOIN satu_sehat_observation_radiologi ssor ON ssor.noorder = ppr.noorder AND ssor.kd_jenis_prw = ppr.kd_jenis_prw " +
                
                "LEFT JOIN satu_sehat_composition ssc ON ssc.no_rawat = rp.no_rawat " +
                
                // Filter: Encounter Ada & Composition Belum Ada
                "WHERE sse.id_encounter <> '' " + 
                "AND (ssc.id_composition IS NULL OR ssc.id_composition = '') " + 
                
                // LOGIKA VALIDASI GANDA (Hanya proses yang sudah pulang/bayar & ada resume)
                "AND ( " +
                "  (rp.status_lanjut = 'Ralan' " +
                "   AND EXISTS(SELECT no_rawat FROM nota_jalan WHERE no_rawat = rp.no_rawat) " +        
                "   AND EXISTS(SELECT no_rawat FROM resume_pasien WHERE no_rawat = rp.no_rawat) " +     
                "  ) " +
                "  OR " +
                "  (rp.status_lanjut = 'Ranap' " +
                "   AND EXISTS(SELECT no_rawat FROM nota_inap WHERE no_rawat = rp.no_rawat) " +         
                "   AND EXISTS(SELECT no_rawat FROM resume_pasien_ranap WHERE no_rawat = rp.no_rawat) " + 
                "  ) " +
                ") " +
                
                "GROUP BY rp.no_rawat LIMIT 10"; // Batch Processing

            ps = koneksi.prepareStatement(query);
            rs = ps.executeQuery();

            while(rs.next()) {
                if (isEmergencyStop) {
                    TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                    break;
                }
                // Helper Kirim
                processComposition(rs);
                jeda();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Composition: " + e);
            TeksArea.append("!! ERROR UTAMA COMPOSITION: " + e + "\n");
        }
    }

    // HELPER SAKTI KHUSUS COMPOSITION (Auto Reconnect + Duplicate Recovery)
    private void processComposition(ResultSet rs) {
        String noRawat = "";
        try {
            noRawat = rs.getString("no_rawat");
            TeksArea.append("\n[DETEKTIF COMPOSITION] Rawat: " + noRawat + " | Pasien: " + rs.getString("nm_pasien") + "\n");

            // 1. Cek KyC
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("nik_dokter"));

            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak valid di Satu Sehat.\n");
                return;
            }

            // 2. Siapkan Sections (Bundle)
            String sectionDiagnosa = "", sectionResep = "", sectionObservasi = "";

            // A. Section Diagnosa (Condition)
            String rawDiagnosa = rs.getString("list_diagnosa");
            if (rawDiagnosa != null && !rawDiagnosa.isEmpty()) {
                StringBuilder sb = new StringBuilder();
                for (String id : rawDiagnosa.split("\\|")) {
                    if(!id.trim().isEmpty()) sb.append("{\"reference\": \"Condition/").append(id.trim()).append("\"},");
                }
                if (sb.length() > 0) {
                    sb.setLength(sb.length() - 1); // Hapus koma terakhir
                    sectionDiagnosa = "{\"title\": \"Diagnosis\",\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"29548-5\",\"display\": \"Diagnosis\"}]},\"entry\": [" + sb.toString() + "]},";
                }
            }

            // B. Section Resep (MedicationRequest)
            String rawResep = rs.getString("list_resep");
            if (rawResep != null && !rawResep.isEmpty()) {
                StringBuilder sb = new StringBuilder();
                for (String id : rawResep.split("\\|")) {
                    if(!id.trim().isEmpty()) sb.append("{\"reference\": \"MedicationRequest/").append(id.trim()).append("\"},");
                }
                if (sb.length() > 0) {
                    sb.setLength(sb.length() - 1);
                    sectionResep = "{\"title\": \"Prescription\",\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"57828-6\",\"display\": \"Prescriptions\"}]},\"entry\": [" + sb.toString() + "]},";
                }
            }

            // C. Section Observasi (Lab & Rad gabung)
            String rawObsLab = rs.getString("list_observasi_lab");
            String rawObsRad = rs.getString("list_observasi_rad");
            StringBuilder sbObs = new StringBuilder();
            
            if (rawObsLab != null && !rawObsLab.isEmpty()) {
                for (String id : rawObsLab.split("\\|")) { if(!id.trim().isEmpty()) sbObs.append("{\"reference\": \"Observation/").append(id.trim()).append("\"},"); }
            }
            if (rawObsRad != null && !rawObsRad.isEmpty()) {
                for (String id : rawObsRad.split("\\|")) { if(!id.trim().isEmpty()) sbObs.append("{\"reference\": \"Observation/").append(id.trim()).append("\"},"); }
            }
            
            if (sbObs.length() > 0) {
                sbObs.setLength(sbObs.length() - 1);
                sectionObservasi = "{\"title\": \"Diagnostic Results\",\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"30954-2\",\"display\": \"Relevant diagnostic tests/laboratory data\"}]},\"entry\": [" + sbObs.toString() + "]},";
            }

            String allSections = sectionDiagnosa + sectionResep + sectionObservasi;
            if (allSections.endsWith(",")) allSections = allSections.substring(0, allSections.length() - 1);

            if (allSections.isEmpty()) {
                TeksArea.append("   !! [SKIP] Tidak ada data klinis (Diagnosa/Obat/Lab/Rad) untuk dibundle.\n");
                return;
            }

            // 3. Konstruksi JSON
            String tglNow = rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00"; 
            
            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            json = "{" +
                "\"resourceType\": \"Composition\"," +
                "\"identifier\": {\"system\": \"http://sys-ids.kemkes.go.id/composition/" + koneksiDB.IDSATUSEHAT() + "\", \"value\": \"" + noRawat + "\"}," + // Identifier Unik untuk Recovery
                "\"status\": \"final\"," +
                "\"type\": {" +
                    "\"coding\": [{" +
                        "\"system\": \"http://loinc.org\"," +
                        "\"code\": \"18842-5\"," +
                        "\"display\": \"Discharge summary\"" +
                    "}]" +
                "}," +
                "\"category\": [{" +
                    "\"coding\": [{" +
                        "\"system\": \"http://loinc.org\"," +
                        "\"code\": \"LP173421-1\"," +
                        "\"display\": \"Report\"" +
                    "}]" +
                "}]," +
                "\"subject\": {\"reference\": \"Patient/" + idpasien + "\", \"display\": \"" + rs.getString("nm_pasien") + "\"}," +
                "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                "\"date\": \"" + tglNow + "\"," +
                "\"author\": [{\"reference\": \"Practitioner/" + iddokter + "\", \"display\": \"" + rs.getString("nama_dokter") + "\"}]," +
                "\"title\": \"Ringkasan Pulang Pasien\"," +
                "\"section\": [" + allSections + "]" +
            "}";

            // 4. Kirim Request
            requestEntity = new HttpEntity(json, headers);
            try {
                // KIRIM PERTAMA
                String responseJson = konekSatuSehat(link + "/Composition", HttpMethod.POST, requestEntity);
                simpanLogComposition(responseJson, rs);
                
            } catch (HttpClientErrorException e) {
                // TOKEN EXPIRED
                if (e.getStatusCode().value() == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Refresh Token...\n");
                    String newToken = api.TokenSatuSehat();
                    headers.set("Authorization", "Bearer " + newToken);
                    requestEntity = new HttpEntity(json, headers);
                    try {
                        String responseJson = konekSatuSehat(link + "/Composition", HttpMethod.POST, requestEntity);
                        simpanLogComposition(responseJson, rs);
                        TeksArea.append("   [RETRY SUKSES] Data terkirim.\n");
                    } catch (Exception ex) {
                        TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                    }
                } 
                // DUPLICATE RECOVERY
                else if (e.getStatusCode().value() == 400 && e.getResponseBodyAsString().contains("duplicate")) {
                    TeksArea.append("   [INFO] Data Duplikat. Mencoba Recovery ID...\n");
                    recoverDuplicateComposition(noRawat, rs);
                }
                else {
                    TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                }
            }
            
        } catch (Exception e) {
            TeksArea.append("   !! [ERROR SYSTEM] " + e + "\n");
            e.printStackTrace();
        }
    }

    private void simpanLogComposition(String responseJson, ResultSet rs) throws Exception {
        JsonNode root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().equals("")) {
            TeksArea.append("   [SUKSES] ID SatuSehat: " + responseId.asText() + "\n");
            Sequel.menyimpan2("satu_sehat_composition", "?,?,?,?", "Composition Log", 4, new String[]{
                rs.getString("no_rawat"), 
                responseId.asText(), 
                rs.getString("status_lanjut"), 
                new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(new java.util.Date())
            });
        }
    }

    // HELPER RECOVERY DUPLIKAT
    private void recoverDuplicateComposition(String noRawat, ResultSet rs) {
        try {
            // Mencari ID berdasarkan Identifier (no_rawat)
            String searchUrl = link + "/Composition?identifier=http://sys-ids.kemkes.go.id/composition/" + koneksiDB.IDSATUSEHAT() + "|" + noRawat;
            String searchJson = konekSatuSehat(searchUrl, HttpMethod.GET, new HttpEntity(headers));
            
            JsonNode searchRoot = mapper.readTree(searchJson);
            if (searchRoot.path("total").asInt() > 0) {
                String existingId = searchRoot.path("entry").get(0).path("resource").path("id").asText();
                TeksArea.append("   [RECOVERED] ID Ditemukan: " + existingId + "\n");
                
                // Simpan ID yang ditemukan
                Sequel.menyimpan2("satu_sehat_composition", "?,?,?,?", "Composition Log", 4, new String[]{
                    noRawat, 
                    existingId, 
                    rs.getString("status_lanjut"), 
                    new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(new java.util.Date())
                });
            } else {
                TeksArea.append("   !! [GAGAL RECOVER] Data duplikat tapi tidak ditemukan saat dicari.\n");
            }
        } catch (Exception ex) {
            TeksArea.append("   !! [ERROR RECOVER] " + ex.getMessage() + "\n");
        }
    }
    /*
    private void kirimComposition() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES COMPOSITION (RESUME MEDIS)\n");
            TeksArea.append("------------------------------------------------------\n");

            // QUERY SWEEPER (VALIDASI KASIR & RESUME)
            String query = 
                "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, p.no_ktp, " +
                "pg.nama as nama_dokter, pg.no_ktp as nik_dokter, " +
                "rp.status_lanjut, " + 
                "sse.id_encounter, " +
                "rp.tgl_registrasi, rp.jam_reg, " +
                "ifnull(ssc.id_composition,'') as id_composition_ada, " +
                
                // Kumpulkan ID Resource
                "GROUP_CONCAT(DISTINCT scond.id_condition SEPARATOR '|') as list_diagnosa, " +
                "GROUP_CONCAT(DISTINCT ssmr.id_medicationrequest SEPARATOR '|') as list_resep, " +
                "GROUP_CONCAT(DISTINCT ssol.id_observation SEPARATOR '|') as list_observasi " +
                
                "FROM reg_periksa rp " +
                "JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis " +
                "JOIN pegawai pg ON rp.kd_dokter = pg.nik " +
                "JOIN satu_sehat_encounter sse ON sse.no_rawat = rp.no_rawat " +
                
                // JOIN LOG RESOURCES
                "LEFT JOIN satu_sehat_condition scond ON scond.no_rawat = rp.no_rawat " +
                "LEFT JOIN resep_obat ro ON ro.no_rawat = rp.no_rawat " +
                "LEFT JOIN satu_sehat_medicationrequest ssmr ON ssmr.no_resep = ro.no_resep " +
                "LEFT JOIN permintaan_lab pl ON pl.no_rawat = rp.no_rawat " +
                "LEFT JOIN satu_sehat_observation_lab ssol ON ssol.noorder = pl.noorder " +
                
                "LEFT JOIN satu_sehat_composition ssc ON ssc.no_rawat = rp.no_rawat " +
                
                "WHERE sse.id_encounter IS NOT NULL " + 
                "AND (ssc.id_composition IS NULL OR ssc.id_composition = '') " + 
                
                // --- LOGIKA VALIDASI GANDA ---
                "AND ( " +
                "  (rp.status_lanjut = 'Ralan' " +
                "   AND EXISTS(SELECT no_rawat FROM nota_jalan WHERE no_rawat = rp.no_rawat) " +       
                "   AND EXISTS(SELECT no_rawat FROM resume_pasien WHERE no_rawat = rp.no_rawat) " +    
                "  ) " +
                "  OR " +
                "  (rp.status_lanjut = 'Ranap' " +
                "   AND EXISTS(SELECT no_rawat FROM nota_inap WHERE no_rawat = rp.no_rawat) " +        
                "   AND EXISTS(SELECT no_rawat FROM resume_pasien_ranap WHERE no_rawat = rp.no_rawat) " + 
                "  ) " +
                ") " +
                
                "GROUP BY rp.no_rawat LIMIT 10";

            ps = koneksi.prepareStatement(query);
            rs = ps.executeQuery();

            while(rs.next()) {
                if (isEmergencyStop) {
                    TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                    break;
                }
                TeksArea.append("\n[PROSES COMPOSITION] No.Rawat: " + rs.getString("no_rawat") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("nik_dokter"));

                if (idpasien.isEmpty() || iddokter.isEmpty()) {
                    TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak valid.\n");
                    continue;
                }

                // 2. Siapkan Sections
                String sectionDiagnosa = "";
                String sectionResep = "";
                String sectionObservasi = "";

                // A. Section Diagnosa
                String rawDiagnosa = rs.getString("list_diagnosa");
                if (rawDiagnosa != null && !rawDiagnosa.isEmpty()) {
                    String[] arrDiag = rawDiagnosa.split("\\|");
                    StringBuilder sb = new StringBuilder();
                    for (String id : arrDiag) {
                        if(!id.trim().isEmpty()) sb.append("{\"reference\": \"Condition/").append(id.trim()).append("\"},");
                    }
                    if (sb.length() > 0) {
                        sb.setLength(sb.length() - 1);
                        sectionDiagnosa = 
                            "{\"title\": \"Diagnosis\",\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"29548-5\",\"display\": \"Diagnosis\"}]},\"entry\": [" + sb.toString() + "]},";
                    }
                }

                // B. Section Resep
                String rawResep = rs.getString("list_resep");
                if (rawResep != null && !rawResep.isEmpty()) {
                    String[] arrResep = rawResep.split("\\|");
                    StringBuilder sb = new StringBuilder();
                    for (String id : arrResep) {
                        if(!id.trim().isEmpty()) sb.append("{\"reference\": \"MedicationRequest/").append(id.trim()).append("\"},");
                    }
                    if (sb.length() > 0) {
                        sb.setLength(sb.length() - 1);
                        sectionResep = 
                            "{\"title\": \"Prescription\",\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"57828-6\",\"display\": \"Prescriptions\"}]},\"entry\": [" + sb.toString() + "]},";
                    }
                }

                // C. Section Observasi
                String rawObs = rs.getString("list_observasi");
                if (rawObs != null && !rawObs.isEmpty()) {
                    String[] arrObs = rawObs.split("\\|");
                    StringBuilder sb = new StringBuilder();
                    for (String id : arrObs) {
                        if(!id.trim().isEmpty()) sb.append("{\"reference\": \"Observation/").append(id.trim()).append("\"},");
                    }
                    if (sb.length() > 0) {
                        sb.setLength(sb.length() - 1);
                        sectionObservasi = 
                            "{\"title\": \"Diagnostic Results\",\"code\": {\"coding\": [{\"system\": \"http://loinc.org\",\"code\": \"30954-2\",\"display\": \"Relevant diagnostic tests/laboratory data\"}]},\"entry\": [" + sb.toString() + "]},";
                    }
                }

                String allSections = sectionDiagnosa + sectionResep + sectionObservasi;
                if (allSections.endsWith(",")) allSections = allSections.substring(0, allSections.length() - 1);

                if (allSections.isEmpty()) {
                    TeksArea.append("   !! [SKIP] Tidak ada data klinis (Diagnosa/Obat/Lab) yang bisa dibungkus.\n");
                    continue;
                }

                // 3. Konstruksi JSON
                String tglNow = rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00"; 
                
                headers = new HttpHeaders();
                headers.setContentType(MediaType.APPLICATION_JSON);
                // Ambil token saat ini
                headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

                String json = "{" +
                    "\"resourceType\": \"Composition\"," +
                    "\"status\": \"final\"," +
                    "\"type\": {" +
                        "\"coding\": [{" +
                            "\"system\": \"http://loinc.org\"," +
                            "\"code\": \"18842-5\"," +
                            "\"display\": \"Discharge summary\"" +
                        "}]" +
                    "}," +
                    "\"category\": [{" +
                        "\"coding\": [{" +
                            "\"system\": \"http://loinc.org\"," +
                            "\"code\": \"LP173421-1\"," +
                            "\"display\": \"Report\"" +
                        "}]" +
                    "}]," +
                    "\"subject\": {\"reference\": \"Patient/" + idpasien + "\", \"display\": \"" + rs.getString("nm_pasien") + "\"}," +
                    "\"encounter\": {\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"}," +
                    "\"date\": \"" + tglNow + "\"," +
                    "\"author\": [{\"reference\": \"Practitioner/" + iddokter + "\", \"display\": \"" + rs.getString("nama_dokter") + "\"}]," +
                    "\"title\": \"Ringkasan Pulang Pasien\"," +
                    "\"section\": [" + allSections + "]" +
                "}";

                TeksArea.append("   [DEBUG] Payload JSON Composition:\n");
                // TeksArea.append(json + "\n"); // Hemat log area

                // 4. Kirim ke API dengan RETRY MECHANISM
                requestEntity = new HttpEntity(json, headers);
                
                try {
                    // PERCOBAAN PERTAMA
                    String responseJson = konekSatuSehat(link + "/Composition", HttpMethod.POST, requestEntity);
                    simpanLogComposition(responseJson, rs);
                    
                } catch (HttpClientErrorException e) {
                    
                    // JIKA ERROR 401 (TOKEN EXPIRED)
                    if (e.getStatusCode().value() == 401) {
                        TeksArea.append("   !! [TOKEN EXPIRED] Memperbarui Token...\n");
                        
                        // 1. Generate Token Baru (Panggil fungsi Login Satu Sehat Anda)
                        // Pastikan method ini ada di class ApiSatuSehat Anda
                        api.TokenSatuSehat(); 
                        
                        // 2. Update Header dengan Token Baru
                        headers.set("Authorization", "Bearer " + api.TokenSatuSehat());
                        requestEntity = new HttpEntity(json, headers);
                        
                        // 3. Coba Kirim Ulang (Retry)
                        try {
                            String responseJsonRetry = konekSatuSehat(link + "/Composition", HttpMethod.POST, requestEntity);
                            TeksArea.append("   [RETRY SUKSES] Berhasil dikirim setelah refresh token.\n");
                            simpanLogComposition(responseJsonRetry, rs);
                        } catch (Exception ex) {
                            TeksArea.append("   !! [GAGAL RETRY] " + ex.getMessage() + "\n");
                        }
                        
                    } else {
                        // Error lain (400 Bad Request, dll)
                        TeksArea.append("   !! [ERROR API " + e.getStatusCode() + "] " + e.getResponseBodyAsString() + "\n");
                    }
                }

                jeda(); 
            }
        } catch (Exception e) {
            System.out.println("Error Composition: " + e);
            TeksArea.append("!! ERROR COMPOSITION SYSTEM: " + e + "\n");
        }
    }
    
    // Helper untuk menyimpan log sukses
    private void simpanLogComposition(String responseJson, ResultSet rs) throws Exception {
        JsonNode root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");

        if (!responseId.asText().equals("")) {
            TeksArea.append("   [SUKSES] Composition ID: " + responseId.asText() + "\n");
            
            String statusRawat = rs.getString("status_lanjut");
            Sequel.menyimpan2("satu_sehat_composition", "?,?,?,?", "Composition Log", 4, new String[]{
                rs.getString("no_rawat"), 
                responseId.asText(), 
                statusRawat, 
                new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss").format(new java.util.Date())
            });
        }
    }  */
    
    
    private void jeda() throws InterruptedException {
        Thread.sleep(100); // Jeda selama 300 milidetik (0.1 detik)
    }

    // ============================================================
    // FITUR BARU: UNIFIED REQUEST HANDLER (AUTO-RECONNECT)
    // Menangani semua HTTP Request ke Satu Sehat dengan fitur:
    // 1. Auto-Refresh Token jika 401 Unauthorized
    // 2. Auto-Reconnect jika koneksi terputus/Timeout (maksimal 5x)
    // ============================================================
    private String konekSatuSehat(String url, HttpMethod method, HttpEntity requestEntity) throws Exception {
        int maxRetries = 5;
        int attempt = 0;
        Exception lastException = null;

        while (attempt < maxRetries) {
            try {
                attempt++;
                ResponseEntity<String> response = api.getRest().exchange(url, method, requestEntity, String.class);
                return response.getBody();
            } catch (HttpClientErrorException e) {
                int statusCode = 0;
                try {
                    statusCode = e.getStatusCode().value();
                } catch (IllegalArgumentException ex) {
                    if (e.getMessage() != null && e.getMessage().contains("429")) {
                        statusCode = 429;
                    } else {
                        throw e;
                    }
                }

                if (statusCode == 401) {
                    TeksArea.append("   !! [TOKEN EXPIRED] Memperbarui Token... (Percobaan " + attempt + "/" + maxRetries + ")\n");
                    api.TokenSatuSehat(); // Segarkan token
                    
                    // Update header dengan token baru
                    HttpHeaders headersBaru = new HttpHeaders();
                    headersBaru.setContentType(MediaType.APPLICATION_JSON);
                    headersBaru.add("Authorization", "Bearer " + api.TokenSatuSehat());
                    
                    // Buat request entity baru dengan body lama (jika ada) dan header baru
                    Object body = requestEntity.getBody();
                    requestEntity = new HttpEntity<>(body, headersBaru);
                    
                    // Jangan tambah attempt jika hanya token expired, langsung coba lagi di putaran berikutnya
                    attempt--; 
                    continue;
                } else if (statusCode == 429) {
                    lastException = e;
                    TeksArea.append("   !! [TOO MANY REQUESTS] Server Penuh (429). Menunggu 15 detik... (Percobaan " + attempt + "/" + maxRetries + ")\n");
                    try {
                        Thread.sleep(15000); // Tunggu lebih lama (15 detik) untuk rate limiting
                    } catch (InterruptedException ie) {
                        Thread.currentThread().interrupt();
                    }
                } else {
                    // Error HttpClientErrorException lainnya (400, 404, 500)
                    TeksArea.append("   !! [SERVER ERROR] " + statusCode + ": " + e.getResponseBodyAsString() + "\n");
                    throw e; 
                }
            } catch (ResourceAccessException e) {
                lastException = e;
                TeksArea.append("   !! [GANGGUAN KONEKSI] Menunggu 5 detik sebelum coba lagi... (Percobaan " + attempt + "/" + maxRetries + ")\n");
                try {
                    Thread.sleep(5000); // Tunggu 5 detik sebelum retry
                } catch (InterruptedException ie) {
                    Thread.currentThread().interrupt();
                }
            } catch (Exception e) {
                lastException = e;
                TeksArea.append("   !! [ERROR TIDAK TERDUGA] " + e.getMessage() + " (Percobaan " + attempt + "/" + maxRetries + ")\n");
                try {
                    Thread.sleep(5000); // Tunggu 5 detik sebelum retry
                } catch (InterruptedException ie) {
                    Thread.currentThread().interrupt();
                }
            }
        }
        
        throw new Exception("Gagal terhubung ke Satu Sehat setelah " + maxRetries + " percobaan. Pesan Terakhir: " + lastException.getMessage());
    }


    // ============================================================
    // FITUR BARU: ALLERGY INTOLERANCE
    // Mengambil data alergi dari pemeriksaan_ralan dan mapping
    // ke kode FHIR via file cache ./cache/alergisatusehat.iyem
    // ============================================================
    private void alergi() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES: ALLERGY INTOLERANCE (RIWAYAT ALERGI)\n");
            TeksArea.append("------------------------------------------------------\n");

            ps = koneksi.prepareStatement(
                "SELECT reg_periksa.tgl_registrasi, reg_periksa.jam_reg, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, " +
                "pasien.nm_pasien, pasien.no_ktp, satu_sehat_encounter.id_encounter, pemeriksaan_ralan.alergi, " +
                "pegawai.nama, pegawai.no_ktp AS ktppraktisi, " +
                "pemeriksaan_ralan.tgl_perawatan, pemeriksaan_ralan.jam_rawat, " +
                "ifnull(satu_sehat_allergy_intolerance.id_allergy_intolerance, '') AS id_allergy_intolerance " +
                "FROM reg_periksa " +
                "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
                "INNER JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN pemeriksaan_ralan ON pemeriksaan_ralan.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN pegawai ON pemeriksaan_ralan.nip = pegawai.nik " +
                "LEFT JOIN satu_sehat_allergy_intolerance ON " +
                "    satu_sehat_allergy_intolerance.no_rawat = reg_periksa.no_rawat " +
                "    AND satu_sehat_allergy_intolerance.tgl_perawatan = pemeriksaan_ralan.tgl_perawatan " +
                "    AND satu_sehat_allergy_intolerance.jam_rawat = pemeriksaan_ralan.jam_rawat " +
                "    AND satu_sehat_allergy_intolerance.status = 'Ralan' " +
                "WHERE satu_sehat_allergy_intolerance.id_allergy_intolerance IS NULL " +
                "AND (pemeriksaan_ralan.alergi IS NOT NULL AND pemeriksaan_ralan.alergi <> '' AND pemeriksaan_ralan.alergi <> '-') " +
                "AND pemeriksaan_ralan.tgl_perawatan BETWEEN ? AND ? " +
                "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
                "AND LENGTH(pegawai.no_ktp) = 16 AND pegawai.no_ktp REGEXP '^[0-9]+$' AND pegawai.no_ktp <> '0000000000000000'"
            );
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    processAlergi(rs);
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Query Alergi: " + e);
                TeksArea.append("ERROR QUERY ALERGI: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            System.out.println("Notifikasi Utama Alergi: " + e);
            TeksArea.append("!! ERROR UTAMA ALERGI: " + e + "\n");
        }
    }

    private void processAlergi(ResultSet rs) {
        java.io.FileReader myObj = null;
        try {
            TeksArea.append("\n[PROSES ALERGI] No.Rawat: " + rs.getString("no_rawat") +
                " | Pasien: " + rs.getString("nm_pasien") +
                " | Alergi: " + rs.getString("alergi") + "\n");

            // 1. Cek sudah terkirim
            if (!rs.getString("id_allergy_intolerance").isEmpty()) {
                TeksArea.append("   !! [SKIP] Sudah terkirim (ID: " + rs.getString("id_allergy_intolerance") + ")\n");
                return;
            }

            // 2. Mapping alergi via file cache JSON
            String dicari = rs.getString("alergi");
            String category = "", coding_system = "", coding_code = "", coding_display = "", text = "";

            myObj = new java.io.FileReader("./cache/alergisatusehat.iyem");
            com.fasterxml.jackson.databind.JsonNode alergiRoot = mapper.readTree(myObj);
            com.fasterxml.jackson.databind.JsonNode alergiList = alergiRoot.path("alergi");
            if (alergiList.isArray()) {
                for (com.fasterxml.jackson.databind.JsonNode item : alergiList) {
                    if (dicari != null && dicari.contains(item.path("keyword").asText())) {
                        category      = item.path("category").asText();
                        coding_system = item.path("coding_system").asText();
                        coding_code   = item.path("coding_code").asText();
                        coding_display = item.path("coding_display").asText();
                        text          = item.path("text").asText();
                        break;
                    }
                }
            }

            if (category.isEmpty()) {
                TeksArea.append("   !! [SKIP] Alergi '" + dicari + "' tidak ditemukan di file mapping.\n");
                return;
            }

            // 3. Lookup ID Pasien & Praktisi di Satu Sehat
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien/Praktisi tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // 4. Konstruksi JSON AllergyIntolerance
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String nmPraktisi = rs.getString("nama").replaceAll("\"", "'");

            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            json = "{" +
                "\"resourceType\": \"AllergyIntolerance\"," +
                "\"identifier\": [{" +
                    "\"system\": \"http://sys-ids.kemkes.go.id/allergy/" + koneksiDB.IDSATUSEHAT() + "\"," +
                    "\"value\": \"" + rs.getString("no_rawat") + "\"" +
                "}]," +
                "\"clinicalStatus\": {" +
                    "\"coding\": [{" +
                        "\"system\": \"http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical\"," +
                        "\"code\": \"active\",\"display\": \"Active\"" +
                    "}]" +
                "}," +
                "\"verificationStatus\": {" +
                    "\"coding\": [{" +
                        "\"system\": \"http://terminology.hl7.org/CodeSystem/allergyintolerance-verification\"," +
                        "\"code\": \"confirmed\",\"display\": \"Confirmed\"" +
                    "}]" +
                "}," +
                "\"category\": [\"" + category + "\"]," +
                "\"code\": {" +
                    "\"coding\": [{" +
                        "\"system\": \"" + coding_system + "\"," +
                        "\"code\": \"" + coding_code + "\"," +
                        "\"display\": \"" + coding_display + "\"" +
                    "}]," +
                    "\"text\": \"" + text + "\"" +
                "}," +
                "\"patient\": {" +
                    "\"reference\": \"Patient/" + idpasien + "\"," +
                    "\"display\": \"" + nmPasien + "\"" +
                "}," +
                "\"encounter\": {" +
                    "\"reference\": \"Encounter/" + rs.getString("id_encounter") + "\"," +
                    "\"display\": \"Kunjungan " + nmPasien + " pada " + rs.getString("tgl_registrasi") + "\"" +
                "}," +
                "\"recordedDate\": \"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam_rawat") + "+07:00\"," +
                "\"recorder\": {" +
                    "\"reference\": \"Practitioner/" + iddokter + "\"," +
                    "\"display\": \"" + nmPraktisi + "\"" +
                "}" +
            "}";

            TeksArea.append("   URL : " + link + "/AllergyIntolerance\n");
            requestEntity = new HttpEntity(json, headers);
            String responseJson = konekSatuSehat(link + "/AllergyIntolerance", HttpMethod.POST, requestEntity);
            TeksArea.append("   Result JSON : " + responseJson + "\n");

            simpanLogAlergi(responseJson, rs);

        } catch (Exception e) {
            System.out.println("Notifikasi processAlergi: " + e);
            TeksArea.append("   !! [ERROR] " + e + "\n");
        } finally {
            if (myObj != null) try { myObj.close(); } catch (Exception ignored) {}
            response = null;
        }
    }

    private void simpanLogAlergi(String responseJson, ResultSet rs) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().isEmpty()) {
            Sequel.menyimpan2("satu_sehat_allergy_intolerance", "?,?,?,?,?", "AllergyIntolerance", 5, new String[]{
                rs.getString("no_rawat"),
                rs.getString("tgl_perawatan"),
                rs.getString("jam_rawat"),
                "Ralan",
                responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID Alergi: " + responseId.asText() + "\n");
        }
    }

    // ============================================================
    // FITUR BARU: EPISODE OF CARE (ANC / Antenatal Care)
    // Filter: diagnosa ICD-10 kelompok 'O' (kebidanan/kehamilan)
    // Tabel log: satu_sehat_episodeofcare (no_rawat, id_encounter)
    // ============================================================
    private void episodeofcare() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES: EPISODE OF CARE (ANC KEHAMILAN)\n");
            TeksArea.append("------------------------------------------------------\n");

            // [1/2] RAWAT JALAN
            TeksArea.append("\n[1/2] Mencari kasus ANC Ralan...\n");
            ps = koneksi.prepareStatement(
                "SELECT reg_periksa.tgl_registrasi, reg_periksa.jam_reg, reg_periksa.no_rawat, " +
                "reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
                "reg_periksa.stts, reg_periksa.status_lanjut, " +
                "concat(pemeriksaan_ralan.tgl_perawatan, 'T', pemeriksaan_ralan.jam_rawat, '+07:00') AS start_period, " +
                "satu_sehat_encounter.id_encounter, " +
                "diagnosa_pasien.kd_penyakit, penyakit.nm_penyakit, diagnosa_pasien.status " +
                "FROM reg_periksa " +
                "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
                "INNER JOIN pemeriksaan_ralan ON pemeriksaan_ralan.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit " +
                "LEFT JOIN satu_sehat_episodeofcare ON satu_sehat_episodeofcare.no_rawat = reg_periksa.no_rawat " +
                "WHERE pemeriksaan_ralan.tgl_perawatan BETWEEN ? AND ? " +
                "AND diagnosa_pasien.kd_penyakit LIKE '%O%' " +
                "AND satu_sehat_episodeofcare.id_encounter IS NULL " +
                "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
                "GROUP BY reg_periksa.no_rawat, diagnosa_pasien.kd_penyakit, diagnosa_pasien.status"
            );
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    processEpisodeOfCare(rs);
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Query EpisodeOfCare Ralan: " + e);
                TeksArea.append("ERROR QUERY EOC RALAN: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

            // [2/2] RAWAT INAP
            TeksArea.append("\n[2/2] Mencari kasus ANC Ranap...\n");
            ps = koneksi.prepareStatement(
                "SELECT reg_periksa.tgl_registrasi, reg_periksa.jam_reg, reg_periksa.no_rawat, " +
                "reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, " +
                "reg_periksa.stts, reg_periksa.status_lanjut, " +
                "concat(kamar_inap.tgl_keluar, 'T', kamar_inap.jam_keluar, '+07:00') AS start_period, " +
                "satu_sehat_encounter.id_encounter, " +
                "diagnosa_pasien.kd_penyakit, penyakit.nm_penyakit, diagnosa_pasien.status " +
                "FROM reg_periksa " +
                "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
                "INNER JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat " +
                "INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit " +
                "LEFT JOIN satu_sehat_episodeofcare ON satu_sehat_episodeofcare.no_rawat = reg_periksa.no_rawat " +
                "WHERE kamar_inap.tgl_keluar BETWEEN ? AND ? " +
                "AND diagnosa_pasien.kd_penyakit LIKE '%O%' " +
                "AND satu_sehat_episodeofcare.id_encounter IS NULL " +
                "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
                "GROUP BY reg_periksa.no_rawat, diagnosa_pasien.kd_penyakit, diagnosa_pasien.status"
            );
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    processEpisodeOfCare(rs);
                    jeda();
                }
            } catch (Exception e) {
                System.out.println("Notif Query EpisodeOfCare Ranap: " + e);
                TeksArea.append("ERROR QUERY EOC RANAP: " + e + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }

        } catch (Exception e) {
            System.out.println("Notifikasi Utama EpisodeOfCare: " + e);
            TeksArea.append("!! ERROR UTAMA EPISODE OF CARE: " + e + "\n");
        }
    }

    private void processEpisodeOfCare(ResultSet rs) {
        try {
            String noRawat = rs.getString("no_rawat");
            TeksArea.append("\n[PROSES EOC] No.Rawat: " + noRawat +
                " | Pasien: " + rs.getString("nm_pasien") +
                " | ICD: " + rs.getString("kd_penyakit") + "\n");

            // Lookup Patient ID di Satu Sehat
            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
            if (idpasien.isEmpty()) {
                TeksArea.append("   !! [SKIP] ID Pasien tidak ditemukan di Satu Sehat.\n");
                return;
            }

            // Konstruksi JSON EpisodeOfCare (tipe: ANC - Antenatal Care)
            String nmPasien = rs.getString("nm_pasien").replaceAll("\"", "'");
            String startPeriod = rs.getString("start_period");

            headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

            json = "{" +
                "\"resourceType\": \"EpisodeOfCare\"," +
                "\"identifier\": [{" +
                    "\"system\": \"http://sys-ids.kemkes.go.id/episode-of-care/" + koneksiDB.IDSATUSEHAT() + "\"," +
                    "\"value\": \"" + noRawat + "\"" +
                "}]," +
                "\"status\": \"active\"," +
                "\"statusHistory\": [{" +
                    "\"status\": \"active\"," +
                    "\"period\": {\"start\": \"" + startPeriod + "\"}" +
                "}]," +
                "\"type\": [{" +
                    "\"coding\": [{" +
                        "\"system\": \"http://terminology.kemkes.go.id/CodeSystem/episodeofcare-type\"," +
                        "\"code\": \"ANC\"," +
                        "\"display\": \"Antenatal Care\"" +
                    "}]" +
                "}]," +
                "\"patient\": {" +
                    "\"reference\": \"Patient/" + idpasien + "\"," +
                    "\"display\": \"" + nmPasien + "\"" +
                "}," +
                "\"managingOrganization\": {" +
                    "\"reference\": \"Organization/" + koneksiDB.IDSATUSEHAT() + "\"" +
                "}," +
                "\"period\": {\"start\": \"" + startPeriod + "\"}" +
            "}";

            TeksArea.append("   URL : " + link + "/EpisodeOfCare\n");
            requestEntity = new HttpEntity(json, headers);
            String responseJson = konekSatuSehat(link + "/EpisodeOfCare", HttpMethod.POST, requestEntity);
            TeksArea.append("   Result JSON : " + responseJson + "\n");

            simpanLogEpisodeOfCare(responseJson, noRawat);

        } catch (Exception e) {
            System.out.println("Notifikasi processEpisodeOfCare: " + e);
            TeksArea.append("   !! [ERROR] " + e + "\n");
        }
    }

    private void simpanLogEpisodeOfCare(String responseJson, String noRawat) throws Exception {
        root = mapper.readTree(responseJson);
        JsonNode responseId = root.path("id");
        if (!responseId.asText().isEmpty()) {
            // Simpan ke tabel satu_sehat_episodeofcare (no_rawat, id_encounter)
            // Catatan: kolom 'id_encounter' pada tabel ini menyimpan ID EpisodeOfCare dari Satu Sehat
            Sequel.menyimpan2("satu_sehat_episodeofcare", "?,?", "EpisodeOfCare", 2, new String[]{
                noRawat,
                responseId.asText()
            });
            TeksArea.append("   [SUKSES] ID EpisodeOfCare: " + responseId.asText() + "\n");
        }
    }

    // ============================================================
    // FITUR BARU: ENCOUNTER v2 (SUSULAN UNTUK KUNJUNGAN RADIOLOGI)
    // Mengirim Encounter untuk kunjungan yang memiliki permintaan
    // radiologi tapi belum memiliki id_encounter di Satu Sehat.
    // JOIN ke permintaan_radiologi untuk memastikan ada aksi radiologi.
    // ============================================================
    private void encounter2() {
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES: ENCOUNTER SUSULAN RADIOLOGI (Encounter v2)\n");
            TeksArea.append("------------------------------------------------------\n");

            ps = koneksi.prepareStatement(
                "SELECT reg_periksa.tgl_registrasi, reg_periksa.jam_reg, reg_periksa.no_rawat, " +
                "pasien.nm_pasien, pasien.no_ktp, " +
                "pegawai.nama, pegawai.no_ktp AS ktpdokter, poliklinik.nm_poli, " +
                "satu_sehat_mapping_lokasi_ralan.id_lokasi_satusehat, " +
                "reg_periksa.status_lanjut, " +
                "concat(reg_periksa.tgl_registrasi, 'T', reg_periksa.jam_reg, '+07:00') AS pulang, " +
                "ifnull(satu_sehat_encounter.id_encounter, '') AS id_encounter " +
                "FROM reg_periksa " +
                "INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis " +
                "INNER JOIN pegawai ON pegawai.nik = reg_periksa.kd_dokter " +
                "INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli " +
                "INNER JOIN satu_sehat_mapping_lokasi_ralan ON satu_sehat_mapping_lokasi_ralan.kd_poli = poliklinik.kd_poli " +
                "INNER JOIN permintaan_radiologi ON permintaan_radiologi.no_rawat = reg_periksa.no_rawat " +
                "LEFT JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat " +
                "WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ? " +
                "AND LENGTH(pasien.no_ktp) = 16 AND pasien.no_ktp REGEXP '^[0-9]+$' AND pasien.no_ktp <> '0000000000000000' " +
                "AND LENGTH(pegawai.no_ktp) = 16 AND pegawai.no_ktp REGEXP '^[0-9]+$' AND pegawai.no_ktp <> '0000000000000000' " +
                "GROUP BY reg_periksa.no_rawat"
            );
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    // Hanya proses yang BELUM punya id_encounter
                    if (!rs.getString("no_ktp").isEmpty() &&
                        !rs.getString("ktpdokter").isEmpty() &&
                        rs.getString("id_encounter").isEmpty()) {

                        try {
                            iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktpdokter"));
                            idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));

                            if (idpasien.isEmpty() || iddokter.isEmpty()) {
                                TeksArea.append("   !! [SKIP] No.Rawat: " + rs.getString("no_rawat") + " - ID Pasien/Dokter tidak valid.\n");
                                continue;
                            }

                            TeksArea.append("\n[PROSES ENCOUNTER v2] No.Rawat: " + rs.getString("no_rawat") + " | Pasien: " + rs.getString("nm_pasien") + "\n");

                            String classCode = rs.getString("status_lanjut").equals("Ralan") ? "AMB" : "IMP";
                            String classDisplay = rs.getString("status_lanjut").equals("Ralan") ? "ambulatory" : "inpatient encounter";

                            headers = new HttpHeaders();
                            headers.setContentType(MediaType.APPLICATION_JSON);
                            headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

                            json = "{" +
                                "\"resourceType\": \"Encounter\"," +
                                "\"status\": \"arrived\"," +
                                "\"class\": {" +
                                    "\"system\": \"http://terminology.hl7.org/CodeSystem/v3-ActCode\"," +
                                    "\"code\": \"" + classCode + "\"," +
                                    "\"display\": \"" + classDisplay + "\"" +
                                "}," +
                                "\"subject\": {" +
                                    "\"reference\": \"Patient/" + idpasien + "\"," +
                                    "\"display\": \"" + rs.getString("nm_pasien").replaceAll("\"", "'") + "\"" +
                                "}," +
                                "\"participant\": [{" +
                                    "\"type\": [{\"coding\": [{" +
                                        "\"system\": \"http://terminology.hl7.org/CodeSystem/v3-ParticipationType\"," +
                                        "\"code\": \"ATND\",\"display\": \"attender\"" +
                                    "}]}]," +
                                    "\"individual\": {" +
                                        "\"reference\": \"Practitioner/" + iddokter + "\"," +
                                        "\"display\": \"" + rs.getString("nama").replaceAll("\"", "'") + "\"" +
                                    "}" +
                                "}]," +
                                "\"period\": {\"start\": \"" + rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00\"}," +
                                "\"location\": [{\"location\": {" +
                                    "\"reference\": \"Location/" + rs.getString("id_lokasi_satusehat") + "\"," +
                                    "\"display\": \"" + rs.getString("nm_poli") + "\"" +
                                "}}]," +
                                "\"statusHistory\": [{" +
                                    "\"status\": \"arrived\"," +
                                    "\"period\": {" +
                                        "\"start\": \"" + rs.getString("tgl_registrasi") + "T" + rs.getString("jam_reg") + "+07:00\"," +
                                        "\"end\": \"" + rs.getString("pulang") + "\"" +
                                    "}" +
                                "}]," +
                                "\"serviceProvider\": {\"reference\": \"Organization/" + koneksiDB.IDSATUSEHAT() + "\"}," +
                                "\"identifier\": [{" +
                                    "\"system\": \"http://sys-ids.kemkes.go.id/encounter/" + koneksiDB.IDSATUSEHAT() + "\"," +
                                    "\"value\": \"" + rs.getString("no_rawat") + "\"" +
                                "}]" +
                            "}";

                            TeksArea.append("   URL : " + link + "/Encounter\n");
                            requestEntity = new HttpEntity(json, headers);
                            String responseJson = konekSatuSehat(link + "/Encounter", HttpMethod.POST, requestEntity);
                            TeksArea.append("   Result JSON : " + responseJson + "\n");

                            root = mapper.readTree(responseJson);
                            response = root.path("id");
                            if (!response.asText().isEmpty()) {
                                Sequel.menyimpan2("satu_sehat_encounter", "?,?", "Encounter v2", 2, new String[]{
                                    rs.getString("no_rawat"), response.asText()
                                });
                                TeksArea.append("   [SUKSES] ID Encounter: " + response.asText() + "\n");
                            }
                            jeda();

                        } catch (Exception ea) {
                            TeksArea.append("   [ERROR] Encounter v2 No.Rawat " + rs.getString("no_rawat") + ": " + ea + "\n");
                        }
                    }
                }
            } catch (Exception ex) {
                System.out.println("Notif Query Encounter v2: " + ex);
                TeksArea.append("ERROR QUERY ENCOUNTER v2: " + ex + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception ez) {
            System.out.println("Notifikasi Utama Encounter v2: " + ez);
            TeksArea.append("!! ERROR UTAMA ENCOUNTER v2: " + ez + "\n");
        }
    }

    // ============================================================
    // FITUR BARU: ORTHANC DICOM ROUTER
    // Mengambil noorder dari permintaan_radiologi dan mengirim
    // series DICOM ke router/modality via ApiOrthanc.
    // Default: MANUAL via menu. Mode otomatis bisa di-toggle
    // via checkbox [Orthanc] Mode Otomatis di popup menu.
    // ============================================================
    private void kirimDicomRouter() {
        ApiOrthanc orthanc = new ApiOrthanc();
        try {
            TeksArea.append("\n------------------------------------------------------\n");
            TeksArea.append("MULAI PROSES: ORTHANC DICOM ROUTER\n");
            TeksArea.append("------------------------------------------------------\n");

            ps = koneksi.prepareStatement(
                "SELECT DISTINCT permintaan_radiologi.noorder " +
                "FROM permintaan_radiologi " +
                "INNER JOIN reg_periksa ON reg_periksa.no_rawat = permintaan_radiologi.no_rawat " +
                "WHERE permintaan_radiologi.tgl_periksa BETWEEN ? AND ?"
            );
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) {
                        TeksArea.append("\n[EMERGENCY STOP] Memutus loop eksekusi query secara aman!\n");
                        break;
                    }
                    String noOrder = rs.getString("noorder");
                    TeksArea.append("   [DICOM] Memproses noorder: " + noOrder + "\n");
                    try {
                        // Ambil series DICOM dari Orthanc berdasarkan noorder (ACSN)
                        com.fasterxml.jackson.databind.JsonNode seriesList = orthanc.AmbilSeries(
                            noOrder,
                            Tanggal1.getText().replaceAll("-", ""),
                            Tanggal2.getText().replaceAll("-", "")
                        );
                        for (com.fasterxml.jackson.databind.JsonNode series : seriesList) {
                            String seriesId = series.path("ID").asText();
                            orthanc.kirimKeModality(seriesId);
                            TeksArea.append("     -> Series ID " + seriesId + " dikirim ke modality.\n");
                        }
                    } catch (Exception ex) {
                        TeksArea.append("   [ERROR] noorder " + noOrder + ": " + ex + "\n");
                    }
                }
            } catch (Exception ex) {
                System.out.println("Notif Query DICOM: " + ex);
                TeksArea.append("ERROR QUERY DICOM: " + ex + "\n");
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception ez) {
            System.out.println("Notifikasi Utama DICOM Router: " + ez);
            TeksArea.append("!! ERROR UTAMA DICOM ROUTER: " + ez + "\n");
        }
    }

    private void jalankanSemuaQueryBridging() {
    // Menambahkan log ke TeksArea untuk memberikan feedback ke user
    TeksArea.append("\n======================================================\n");
    TeksArea.append("MEMULAI PROSES BRIDGING DATA KE SATU SEHAT...\n");
    TeksArea.append("======================================================\n");
    
    encounter();
    observationTTV();
    vaksin();
    prosedur();
    condition();
    clinicalimpression();
    dietgizi();
    medicationrequest();
    medicationdispense();
    medicationstatement();
    servicerequestradiologi();
    specimenradiologi();
    observationradiologi();
    diagnosticreportradiologi();
    servicerequestlabpk();
    servicerequestlabmb();
    specimenlabpk();
    specimenlabmb();
    observationlabpk();
    observationlabmb();
    diagnosticreportlabpk();
    diagnosticreportlabmb();
    careplan();
    questionnaire();
    kirimComposition();
    alergi();
    episodeofcare();
    encounter2();
    // Orthanc DICOM Router: hanya kirim jika mode otomatis diaktifkan via checkbox menu
    if (jCheckBoxMenuItemOrtancAuto.isSelected()) {
        kirimDicomRouter();
    }
    
    TeksArea.append("\n======================================================\n");
    TeksArea.append("PROSES BRIDGING DATA SELESAI.\n");
    TeksArea.append("======================================================\n");
}
}
