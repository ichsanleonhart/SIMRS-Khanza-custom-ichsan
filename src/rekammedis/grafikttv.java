/*
 * Dimodifikasi untuk menangani berbagai jenis observasi
 * oleh AI Google
 * PERUBAHAN TERAKHIR: Memisahkan grafik menjadi 4 subplot
 */
package grafikanalisa;

import fungsi.koneksiDB;
import fungsi.sekuel;
import fungsi.validasi;
import java.awt.BasicStroke;
import java.awt.Color;
import java.awt.Font;
import java.awt.Graphics2D;
import java.awt.Stroke;
import java.awt.geom.Line2D;
import java.awt.geom.Rectangle2D;
import java.io.File;
import java.io.IOException;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.Properties;
import javax.swing.JPanel;
import org.jfree.chart.ChartUtilities;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.axis.CategoryAxis;
import org.jfree.chart.axis.CategoryLabelPositions;
import org.jfree.chart.axis.NumberAxis;
import org.jfree.chart.axis.ValueAxis;
import org.jfree.chart.labels.StandardCategoryItemLabelGenerator;
import org.jfree.chart.labels.StandardCategoryToolTipGenerator;
import org.jfree.chart.plot.CategoryPlot;
import org.jfree.chart.plot.CombinedDomainCategoryPlot;
import org.jfree.chart.renderer.category.CategoryItemRendererState;
import org.jfree.chart.renderer.category.LineAndShapeRenderer;
import org.jfree.data.category.CategoryDataset;
import org.jfree.data.category.DefaultCategoryDataset;

public class grafikttv {

    public static CategoryDataset createDataset1(String symbol, String jenisObservasi) {
        DefaultCategoryDataset result = new DefaultCategoryDataset();
        
        String seriesSuhu = "Suhu (°C)";
        String seriesSistole = "Sistole";
        String seriesDiastole = "Diastole";
        String seriesHR = "Heartrate";
        String seriesRR = "Respirasi";
        String seriesSPO2 = "SpO2";
        String seriesGCS = "GCS";
        
        String tableName = "";
        String sql = "";

        switch (jenisObservasi.toLowerCase()) {
            case "ranap":
                tableName = "catatan_observasi_ranap";
                sql = "select concat(tgl_perawatan,' ',jam_rawat) as waktu, suhu, td, hr, rr, spo2, gcs from " + tableName + " " + symbol;
                break;
            case "kebidanan":
                tableName = "catatan_observasi_ranap_kebidanan";
                sql = "select concat(tgl_perawatan,' ',jam_rawat) as waktu, suhu, td, hr, rr, spo2, gcs from " + tableName + " " + symbol;
                break;
            case "postpartum":
                tableName = "catatan_observasi_ranap_postpartum";
                sql = "select concat(tgl_perawatan,' ',jam_rawat) as waktu, suhu, td, hr, rr, spo2, gcs from " + tableName + " " + symbol;
                break;
            case "igd":
                tableName = "catatan_observasi_igd";
                sql = "select concat(tgl_perawatan,' ',jam_rawat) as waktu, suhu, td, hr, rr, spo2, gcs from " + tableName + " " + symbol;
                break;
            case "bayi":
                tableName = "catatan_observasi_bayi";
                sql = "select concat(tgl_perawatan,' ',jam_rawat) as waktu, suhu, td, hr, rr, spo2, gcs from " + tableName + " " + symbol;
                break;            
            case "pemeriksaan_ranap":
                tableName = "pemeriksaan_ranap";
                sql = "select concat(tgl_perawatan,' ',jam_rawat) as waktu, suhu_tubuh as suhu, tensi as td, nadi as hr, respirasi as rr, spo2, gcs from " + tableName + " " + symbol;
                break;
            default:
                return result; 
        }

        try {
            Statement stat = koneksiDB.condb().createStatement();
            ResultSet rs = stat.executeQuery(sql);
            
            while (rs.next()) {
                String waktu = rs.getString("waktu");
                Double[] tensi = safeParseTensi(rs.getString("td"));
                
                result.addValue(safeParseDouble(rs.getString("suhu")), seriesSuhu, waktu);
                result.addValue(tensi[0], seriesSistole, waktu);
                result.addValue(tensi[1], seriesDiastole, waktu);
                result.addValue(safeParseDouble(rs.getString("hr")), seriesHR, waktu);
                result.addValue(safeParseDouble(rs.getString("rr")), seriesRR, waktu);
                result.addValue(safeParseDouble(rs.getString("spo2")), seriesSPO2, waktu);
                result.addValue(safeParseDouble(rs.getString("gcs")), seriesGCS, waktu);
            }

        } catch (SQLException e) {
            System.out.println("Notifikasi Grafik TTV: " + e);
        }
        return result;
    }

    private static JFreeChart createChart(String symbol, String jenisObservasi) {
        CategoryDataset datasetUtama = createDataset1(symbol, jenisObservasi);
        
        // --- 1. MEMISAHKAN DATASET MENJADI 4 BAGIAN ---
        DefaultCategoryDataset datasetTensi = new DefaultCategoryDataset();
        DefaultCategoryDataset datasetHrSpo2 = new DefaultCategoryDataset();
        DefaultCategoryDataset datasetSuhu = new DefaultCategoryDataset();
        DefaultCategoryDataset datasetRespGcs = new DefaultCategoryDataset();

        for (int i = 0; i < datasetUtama.getRowCount(); i++) {
            String seriesName = (String) datasetUtama.getRowKey(i);
            for (int j = 0; j < datasetUtama.getColumnCount(); j++) {
                Comparable<String> category = datasetUtama.getColumnKey(j);
                Number value = datasetUtama.getValue(i, j);
                
                switch (seriesName) {
                    case "Sistole":
                    case "Diastole":
                        datasetTensi.addValue(value, seriesName, category);
                        break;
                    case "Heartrate":
                    case "SpO2":
                        datasetHrSpo2.addValue(value, seriesName, category);
                        break;
                    case "Suhu (°C)":
                        datasetSuhu.addValue(value, seriesName, category);
                        break;
                    case "Respirasi":
                    case "GCS":
                        datasetRespGcs.addValue(value, seriesName, category);
                        break;
                }
            }
        }

        Stroke aStroke = new BasicStroke(2.0f);

        // --- 2. MEMBUAT 4 SUBPLOT TERPISAH ---

        // SUBPLOT 1: Tensi (Sistole & Diastole)
        NumberAxis rangeAxisTensi = new NumberAxis("Tekanan (mmHg)");
        rangeAxisTensi.setRange(40.0, 200.0);
        CustomLineRenderer rendererTensi = new CustomLineRenderer();
        rendererTensi.setBaseToolTipGenerator(new StandardCategoryToolTipGenerator());
        rendererTensi.setBaseItemLabelGenerator(new StandardCategoryItemLabelGenerator());
        rendererTensi.setBaseItemLabelsVisible(true);
        rendererTensi.setSeriesPaint(0, Color.BLACK); // Sistole
        rendererTensi.setSeriesPaint(1, Color.BLACK); // Diastole
        rendererTensi.setSeriesStroke(0, aStroke);
        rendererTensi.setSeriesStroke(1, aStroke);
        rendererTensi.setSeriesLinesVisible(0, false); // Digambar manual
        rendererTensi.setSeriesLinesVisible(1, false); // Digambar manual
        CategoryPlot subplotTensi = new CategoryPlot(datasetTensi, null, rangeAxisTensi, rendererTensi);
        subplotTensi.setBackgroundPaint(Color.LIGHT_GRAY);
        subplotTensi.setDomainGridlinesVisible(true);
        subplotTensi.setRangeGridlinesVisible(true);
        subplotTensi.setDomainGridlinePaint(Color.BLACK);
        subplotTensi.setRangeGridlinePaint(Color.BLACK);

        // SUBPLOT 2: Heartrate & SpO2
        NumberAxis rangeAxisHrSpo2 = new NumberAxis("Denyut & Saturasi");
        rangeAxisHrSpo2.setRange(40.0, 200.0);
        LineAndShapeRenderer rendererHrSpo2 = new LineAndShapeRenderer();
        rendererHrSpo2.setBaseToolTipGenerator(new StandardCategoryToolTipGenerator());
        rendererHrSpo2.setBaseItemLabelGenerator(new StandardCategoryItemLabelGenerator());
        rendererHrSpo2.setBaseItemLabelsVisible(true);
        rendererHrSpo2.setSeriesPaint(0, Color.RED);     // Heartrate
        rendererHrSpo2.setSeriesPaint(1, Color.ORANGE);  // SpO2
        rendererHrSpo2.setSeriesStroke(0, aStroke);
        rendererHrSpo2.setSeriesStroke(1, aStroke);
        CategoryPlot subplotHrSpo2 = new CategoryPlot(datasetHrSpo2, null, rangeAxisHrSpo2, rendererHrSpo2);
        subplotHrSpo2.setBackgroundPaint(Color.LIGHT_GRAY);
        subplotHrSpo2.setDomainGridlinesVisible(true);
        subplotHrSpo2.setRangeGridlinesVisible(true);
        subplotHrSpo2.setDomainGridlinePaint(Color.BLACK);
        subplotHrSpo2.setRangeGridlinePaint(Color.BLACK);

        // SUBPLOT 3: Suhu
        NumberAxis rangeAxisSuhu = new NumberAxis("Suhu (°C)");
        rangeAxisSuhu.setRange(35.0, 41.0);
        LineAndShapeRenderer rendererSuhu = new LineAndShapeRenderer();
        rendererSuhu.setBaseToolTipGenerator(new StandardCategoryToolTipGenerator());
        rendererSuhu.setBaseItemLabelGenerator(new StandardCategoryItemLabelGenerator());
        rendererSuhu.setBaseItemLabelsVisible(true);
        rendererSuhu.setSeriesPaint(0, Color.BLUE);
        rendererSuhu.setSeriesStroke(0, aStroke);
        CategoryPlot subplotSuhu = new CategoryPlot(datasetSuhu, null, rangeAxisSuhu, rendererSuhu);
        subplotSuhu.setBackgroundPaint(Color.LIGHT_GRAY);
        subplotSuhu.setDomainGridlinesVisible(true);
        subplotSuhu.setRangeGridlinesVisible(true);
        subplotSuhu.setDomainGridlinePaint(Color.BLACK);
        subplotSuhu.setRangeGridlinePaint(Color.BLACK);

        // SUBPLOT 4: Respirasi & GCS
        NumberAxis rangeAxisRespGcs = new NumberAxis("Resp & GCS");
        rangeAxisRespGcs.setRange(0, 40.0);
        LineAndShapeRenderer rendererRespGcs = new LineAndShapeRenderer();
        rendererRespGcs.setBaseToolTipGenerator(new StandardCategoryToolTipGenerator());
        rendererRespGcs.setBaseItemLabelGenerator(new StandardCategoryItemLabelGenerator());
        rendererRespGcs.setBaseItemLabelsVisible(true);
        rendererRespGcs.setSeriesPaint(0, Color.GREEN);   // Respirasi
        rendererRespGcs.setSeriesPaint(1, Color.MAGENTA); // GCS
        rendererRespGcs.setSeriesStroke(0, aStroke);
        rendererRespGcs.setSeriesStroke(1, aStroke);
        CategoryPlot subplotRespGcs = new CategoryPlot(datasetRespGcs, null, rangeAxisRespGcs, rendererRespGcs);
        subplotRespGcs.setBackgroundPaint(Color.LIGHT_GRAY);
        subplotRespGcs.setDomainGridlinesVisible(true);
        subplotRespGcs.setRangeGridlinesVisible(true);
        subplotRespGcs.setDomainGridlinePaint(Color.BLACK);
        subplotRespGcs.setRangeGridlinePaint(Color.BLACK);

        // --- 3. MENGGABUNGKAN SEMUA PLOT ---
        CategoryAxis domainAxis = new CategoryAxis("Tanda Tanda Vital Pasien");
        domainAxis.setCategoryLabelPositions(CategoryLabelPositions.UP_45);
        CombinedDomainCategoryPlot plot = new CombinedDomainCategoryPlot(domainAxis);
        
        // Menambahkan semua subplot dengan bobot (tinggi relatif) yang berbeda
        plot.add(subplotTensi, 2); 
        plot.add(subplotHrSpo2, 2);
        plot.add(subplotRespGcs, 1);
        plot.add(subplotSuhu, 1);
        plot.setGap(10.0);

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

    public static void createDemoPanel(String symbol, String namafile, String jenisObservasi) {
        JFreeChart chart = createChart(symbol, jenisObservasi);

        Properties systemProp = System.getProperties();
        String currentDir = systemProp.getProperty("user.dir");
        String outputPath = currentDir + "/gambargrafik/" + namafile;

        saveChartAsImage(chart, outputPath, 1280, 720);
    }

    public static class CustomLineRenderer extends LineAndShapeRenderer {
        @Override
        public void drawItem(Graphics2D g2, CategoryItemRendererState state,
                Rectangle2D dataArea, CategoryPlot plot,
                CategoryAxis domainAxis, ValueAxis rangeAxis,
                CategoryDataset dataset, int row, int column,
                int pass) {
            
            super.drawItem(g2, state, dataArea, plot, domainAxis, rangeAxis, dataset, row, column, pass);

            String rowKey = (String) dataset.getRowKey(row);

            if ("Sistole".equals(rowKey) || "Diastole".equals(rowKey)) {
                Number currentValue = dataset.getValue(row, column);
                if (currentValue == null) return;

                Number previousValue = null;
                int previousColumn = -1;
                for (int i = column - 1; i >= 0; i--) {
                    if (dataset.getValue(row, i) != null) {
                        previousValue = dataset.getValue(row, i);
                        previousColumn = i;
                        break;
                    }
                }

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

            // PERUBAHAN: Indeks diubah karena dataset ini hanya berisi Tensi
            if (row == 0) { // Hanya perlu dijalankan saat menggambar Sistole
                int sistoleIndex = 0; // Sistole sekarang di indeks 0
                int diastoleIndex = 1; // Diastole sekarang di indeks 1

                Number sistoleNumber = dataset.getValue(sistoleIndex, column);
                Number diastoleNumber = dataset.getValue(diastoleIndex, column);

                if (sistoleNumber != null && diastoleNumber != null) {
                    double x = domainAxis.getCategoryMiddle(column, dataset.getColumnCount(), dataArea, plot.getDomainAxisEdge());
                    double ySistole = rangeAxis.valueToJava2D(sistoleNumber.doubleValue(), dataArea, plot.getRangeAxisEdge());
                    double yDiastole = rangeAxis.valueToJava2D(diastoleNumber.doubleValue(), dataArea, plot.getRangeAxisEdge());

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
