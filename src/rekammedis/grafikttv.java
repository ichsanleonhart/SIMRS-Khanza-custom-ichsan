/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package grafikanalisa;

import fungsi.koneksiDB;
import fungsi.sekuel;
import fungsi.validasi;
import java.awt.BasicStroke;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.Font;
import java.awt.Graphics2D;
import java.awt.Stroke;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.geom.Line2D;
import java.awt.geom.Rectangle2D;
import java.io.File;
import java.io.IOException;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.Properties;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JPanel;
import org.jfree.chart.ChartPanel;
import org.jfree.chart.ChartUtilities;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.axis.CategoryAxis;
import org.jfree.chart.axis.CategoryLabelPositions;
import org.jfree.chart.axis.NumberAxis;
import org.jfree.chart.axis.ValueAxis;
import org.jfree.chart.labels.StandardCategoryItemLabelGenerator;
import org.jfree.chart.labels.StandardCategoryToolTipGenerator;
import org.jfree.chart.plot.CategoryMarker;
import org.jfree.chart.plot.CategoryPlot;
import org.jfree.chart.plot.CombinedDomainCategoryPlot;
import org.jfree.chart.renderer.category.CategoryItemRendererState;
import org.jfree.chart.renderer.category.LineAndShapeRenderer;
import org.jfree.data.category.CategoryDataset;
import org.jfree.data.category.DefaultCategoryDataset;

/**
 *
 * @author Via
 */
public class grafikttv extends JDialog {

    sekuel Sequel = new sekuel();
    validasi Valid = new validasi();

    public grafikttv(String title, String symbol, String namafile) {
        setTitle(title);
        JPanel chartPanel = createDemoPanel(symbol, namafile);
        chartPanel.setSize(screen.width, screen.height);
        setContentPane(chartPanel);
        setModal(true);
        setIconImage(new ImageIcon(super.getClass().getResource("/picture/addressbook-edit24.png")).getImage());
        pack();
        setDefaultCloseOperation(DISPOSE_ON_CLOSE);
    }

    Dimension screen = Toolkit.getDefaultToolkit().getScreenSize();

    public static CategoryDataset createDataset1(String symbol) {
        DefaultCategoryDataset result = new DefaultCategoryDataset();
        String series1 = "Suhu";
        String series2 = "Sistole";
        String series22 = "Diastole";
        String series3 = "Heartrate";
        String series4 = "Respirasi";
        String series5 = "SpO2";
        String series6 = "GCS";
        try {
            Statement stat = koneksiDB.condb().createStatement();
            ResultSet rs = stat.executeQuery("select concat(catatan_observasi_ranap.tgl_perawatan,' ',catatan_observasi_ranap.jam_rawat) as waktu, catatan_observasi_ranap.suhu as suhu,catatan_observasi_ranap.td as tensi ,catatan_observasi_ranap.hr as heartrate,catatan_observasi_ranap.rr as respirasi,catatan_observasi_ranap.spo2 as spo2,catatan_observasi_ranap.gcs as nyeri  from catatan_observasi_ranap "
                    + symbol + "");
            while (rs.next()) {
                String tksbr = rs.getString(1);
                
                Double suhu = safeParseDouble(rs.getString(2));
                Double[] tensi = safeParseTensi(rs.getString(3));
                Double heartrate = safeParseDouble(rs.getString(4));
                Double respirasi = safeParseDouble(rs.getString(5));
                Double spo2 = safeParseDouble(rs.getString(6));
                Double nyeri = safeParseDouble(rs.getString(7));

                result.addValue(suhu, series1, tksbr);
                result.addValue(tensi[0], series2, tksbr);
                result.addValue(tensi[1], series22, tksbr);
                result.addValue(heartrate, series3, tksbr);
                result.addValue(respirasi, series4, tksbr);
                result.addValue(spo2, series5, tksbr);
                result.addValue(nyeri, series6, tksbr);
            }
        } catch (SQLException e) {
            System.out.println("Notifikasi : " + e);
        }
        return result;
    }

    private static JFreeChart createChart(String symbol) {
        CategoryDataset dataset1 = createDataset1(symbol);
        NumberAxis rangeAxis1 = new NumberAxis("Ukuran");
        rangeAxis1.setStandardTickUnits(NumberAxis.createIntegerTickUnits());
        
        CustomLineRenderer customRenderer = new CustomLineRenderer();
        customRenderer.setBaseToolTipGenerator(new StandardCategoryToolTipGenerator());
        customRenderer.setSeriesPaint(0, Color.BLUE);
        customRenderer.setSeriesPaint(1, Color.BLACK); // Sistole
        customRenderer.setSeriesPaint(2, Color.BLACK); // Diastole
        customRenderer.setSeriesPaint(3, Color.RED);
        customRenderer.setSeriesPaint(4, Color.GREEN);
        customRenderer.setSeriesPaint(5, Color.ORANGE);
        customRenderer.setSeriesPaint(6, Color.MAGENTA);
        customRenderer.setBaseItemLabelGenerator(new StandardCategoryItemLabelGenerator());
        customRenderer.setBaseItemLabelsVisible(true);
        
        // PERUBAHAN 1: Nonaktifkan garis default untuk Sistole dan Diastole
        // Ini agar kita bisa menggambarnya secara manual di renderer.
        customRenderer.setSeriesLinesVisible(1, false);
        customRenderer.setSeriesLinesVisible(2, false);

        Stroke stroke = new BasicStroke(2.0f);
        for (int i = 0; i < dataset1.getRowCount(); i++) {
            customRenderer.setSeriesStroke(i, stroke);
        }
        
        CategoryPlot subplot1 = new CategoryPlot(dataset1, null, rangeAxis1, customRenderer);
        subplot1.setDomainGridlinesVisible(true);
        subplot1.setRangeGridlinesVisible(true);
        subplot1.setRangeGridlinePaint(Color.BLACK);
        subplot1.setDomainGridlinesVisible(true);
        subplot1.setDomainGridlinePaint(Color.BLACK);
        subplot1.setBackgroundPaint(Color.LIGHT_GRAY);

        CategoryMarker marker = new CategoryMarker("Diastole");
        marker.setPaint(Color.GRAY);
        marker.setStroke(new BasicStroke(1.5f));
        marker.setAlpha(0.7f);
        subplot1.addDomainMarker(marker);

        CategoryAxis domainAxis = new CategoryAxis("Tanda Tanda Vital Pasien(data dari form Observasi Rawat Inap)");
        domainAxis.setCategoryLabelPositions(CategoryLabelPositions.UP_45);
        CombinedDomainCategoryPlot plot = new CombinedDomainCategoryPlot(domainAxis);
        plot.add(subplot1, 1);
        JFreeChart result = new JFreeChart("", new Font("SansSerif", Font.BOLD, 8), plot, true);
        return result;
    }

    private static void saveChartAsImage(JFreeChart chart, String filename, int width, int height) {
        try {
            ChartUtilities.saveChartAsJPEG(new File(filename), chart, width, height);
        } catch (IOException e) {
            System.err.println("Error saving chart: " + e.getMessage());
        }
    }

    public static JPanel createDemoPanel(String symbol, String namafile) {
        validasi.hapusFileDalamFolder("/gambargrafik/");
        JFreeChart chart = createChart(symbol);

        Properties systemProp = System.getProperties();
        String currentDir = systemProp.getProperty("user.dir");
        String outputPath = currentDir + "/gambargrafik/" + namafile + ".jpg";

        saveChartAsImage(chart, outputPath, 1280, 720);

        ChartPanel chartPanel = new ChartPanel(chart);
        chartPanel.setMouseZoomable(true);
        chartPanel.setDomainZoomable(true);
        chartPanel.setRangeZoomable(true);

        JPanel zoomControlPanel = new JPanel(new FlowLayout(FlowLayout.CENTER));

        JButton zoomInButton = new JButton("Zoom In");
        zoomInButton.addActionListener((ActionEvent e) -> {
            chartPanel.zoomInBoth(chartPanel.getWidth() / 2.0, chartPanel.getHeight() / 2.0);
        });
        zoomControlPanel.add(zoomInButton);

        JButton zoomOutButton = new JButton("Zoom Out");
        zoomOutButton.addActionListener((ActionEvent e) -> {
            chartPanel.zoomOutBoth(chartPanel.getWidth() / 2.0, chartPanel.getHeight() / 2.0);
        });
        zoomControlPanel.add(zoomOutButton);

        JButton resetZoomButton = new JButton("Reset Zoom");
        resetZoomButton.addActionListener((ActionEvent e) -> {
            chartPanel.restoreAutoBounds();
        });
        zoomControlPanel.add(resetZoomButton);

        JPanel mainPanel = new JPanel(new BorderLayout());
        mainPanel.add(chartPanel, BorderLayout.CENTER);
        mainPanel.add(zoomControlPanel, BorderLayout.SOUTH);

        return mainPanel;
    }
    
    /**
     * PERUBAHAN 2: Renderer kustom yang sekarang juga menggambar garis penghubung
     * untuk Sistole dan Diastole secara manual.
     */
    public static class CustomLineRenderer extends LineAndShapeRenderer {
        @Override
        public void drawItem(Graphics2D g2, CategoryItemRendererState state,
                Rectangle2D dataArea, CategoryPlot plot,
                CategoryAxis domainAxis, ValueAxis rangeAxis,
                CategoryDataset dataset, int row, int column,
                int pass) {
            
            // Panggil super.drawItem untuk menggambar bentuk (shapes) dan label.
            // Untuk seri 1 & 2, ini tidak akan menggambar garis karena sudah kita nonaktifkan.
            super.drawItem(g2, state, dataArea, plot, domainAxis, rangeAxis, dataset, row, column, pass);

            // --- LOGIKA MENGGAMBAR GARIS PENGHUBUNG MANUAL ---
            // Hanya berlaku untuk seri Sistole (1) dan Diastole (2)
            if (row == 1 || row == 2) {
                Number currentValue = dataset.getValue(row, column);
                if (currentValue == null) {
                    return; // Jangan lakukan apa-apa jika titik saat ini null
                }

                // Cari titik valid sebelumnya
                Number previousValue = null;
                int previousColumn = -1;
                for (int i = column - 1; i >= 0; i--) {
                    Number tempValue = dataset.getValue(row, i);
                    if (tempValue != null) {
                        previousValue = tempValue;
                        previousColumn = i;
                        break;
                    }
                }

                // Jika ada titik valid sebelumnya, gambar garis penghubung
                if (previousValue != null) {
                    double x0 = domainAxis.getCategoryMiddle(previousColumn, dataset.getColumnCount(), dataArea, plot.getDomainAxisEdge());
                    double y0 = rangeAxis.valueToJava2D(previousValue.doubleValue(), dataArea, plot.getRangeAxisEdge());
                    double x1 = domainAxis.getCategoryMiddle(column, dataset.getColumnCount(), dataArea, plot.getDomainAxisEdge());
                    double y1 = rangeAxis.valueToJava2D(currentValue.doubleValue(), dataArea, plot.getRangeAxisEdge());
                    
                    g2.setPaint(getItemPaint(row, column));
                    g2.setStroke(getItemStroke(row, column));
                    g2.draw(new Line2D.Double(x0, y0, x1, y1));
                }
            }

            // --- LOGIKA MENGGAMBAR GARIS VERTIKAL TENSI ---
            // Hanya proses jika barisnya adalah Sistole (indeks 1) untuk efisiensi
            if (row == 1) {
                Number sistoleNumber = dataset.getValue(1, column);
                Number diastoleNumber = dataset.getValue(2, column);

                if (sistoleNumber != null && diastoleNumber != null) {
                    double sistoleValue = sistoleNumber.doubleValue();
                    double diastoleValue = diastoleNumber.doubleValue();

                    double x = domainAxis.getCategoryMiddle(column, dataset.getColumnCount(), dataArea, plot.getDomainAxisEdge());
                    double ySistole = rangeAxis.valueToJava2D(sistoleValue, dataArea, plot.getRangeAxisEdge());
                    double yDiastole = rangeAxis.valueToJava2D(diastoleValue, dataArea, plot.getRangeAxisEdge());

                    g2.setPaint(Color.DARK_GRAY);
                    g2.setStroke(new BasicStroke(1.5f));
                    g2.draw(new Line2D.Double(x, ySistole, x, yDiastole));

                    int[] xUpArrow = {(int) x - 5, (int) x + 5, (int) x};
                    int[] yUpArrow = {(int) ySistole + 10, (int) ySistole + 10, (int) ySistole};
                    g2.fillPolygon(xUpArrow, yUpArrow, 3);

                    int[] xDownArrow = {(int) x - 5, (int) x + 5, (int) x};
                    int[] yDownArrow = {(int) yDiastole - 10, (int) yDiastole - 10, (int) yDiastole};
                    g2.fillPolygon(xDownArrow, yDownArrow, 3);
                }
            }
        }
    }

    private static Double safeParseDouble(String value) {
        if (value == null || value.trim().isEmpty() || value.trim().equals("-")) {
            return null;
        }
        try {
            value = value.replace(',', '.');
            value = value.replaceAll("[^0-9.-]", "");
            if (value.trim().isEmpty()) {
                return null;
            }
            return Double.valueOf(value);
        } catch (NumberFormatException e) {
            return null;
        }
    }

    private static Double[] safeParseTensi(String value) {
        if (value == null || value.trim().isEmpty() || value.trim().equals("-")) {
            return new Double[]{null, null};
        }
        try {
            value = value.replace(',', '.');
            value = value.replaceAll("[^0-9.-/]", "");
            String[] parts = value.split("/");

            if (parts.length == 2) {
                Double systolic = safeParseDouble(parts[0]);
                Double diastolic = safeParseDouble(parts[1]);
                return new Double[]{systolic, diastolic};
            }
        } catch (Exception e) {
            // Biarkan dan kembalikan null di bawah
        }
        return new Double[]{null, null};
    }
}
