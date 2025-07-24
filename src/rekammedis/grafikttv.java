/*
 * Dimodifikasi untuk menangani berbagai jenis observasi
 * oleh AI Google
 * PERUBAHAN TERAKHIR: Fokus hanya pada 7 TTV Universal
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
import org.jfree.chart.plot.CategoryPlot;
import org.jfree.chart.plot.CombinedDomainCategoryPlot;
import org.jfree.chart.renderer.category.CategoryItemRendererState;
import org.jfree.chart.renderer.category.LineAndShapeRenderer;
import org.jfree.data.category.CategoryDataset;
import org.jfree.data.category.DefaultCategoryDataset;

public class grafikttv {

    public static CategoryDataset createDataset1(String symbol, String jenisObservasi) {
        DefaultCategoryDataset result = new DefaultCategoryDataset();
        
        // PERUBAHAN: Hanya mendefinisikan 7 seri data universal
        String seriesSuhu = "Suhu (°C)";
        String seriesSistole = "Sistole";
        String seriesDiastole = "Diastole";
        String seriesHR = "Heartrate";
        String seriesRR = "Respirasi";
        String seriesSPO2 = "SpO2";
        String seriesGCS = "GCS";
        
        String tableName = "";
        switch (jenisObservasi.toLowerCase()) {
            case "ranap":
                tableName = "catatan_observasi_ranap";
                break;
            case "kebidanan":
                tableName = "catatan_observasi_ranap_kebidanan";
                break;
            case "postpartum":
                tableName = "catatan_observasi_ranap_postpartum";
                break;
            default:
                return result; // Jika jenis tidak valid, kembalikan dataset kosong
        }

        try {
            Statement stat = koneksiDB.condb().createStatement();
            // PERUBAHAN: Query SQL tunggal yang mengambil 7 kolom universal
            String sql = "select concat(tgl_perawatan,' ',jam_rawat) as waktu, suhu, td, hr, rr, spo2, gcs from " + tableName + " " + symbol;
            
            ResultSet rs = stat.executeQuery(sql);
            // PERUBAHAN: Loop tunggal yang berlaku untuk semua jenis observasi
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
        CategoryDataset dataset1 = createDataset1(symbol, jenisObservasi);
        NumberAxis rangeAxis1 = new NumberAxis("Ukuran");
        rangeAxis1.setStandardTickUnits(NumberAxis.createIntegerTickUnits());
        
        CustomLineRenderer customRenderer = new CustomLineRenderer();
        customRenderer.setBaseToolTipGenerator(new StandardCategoryToolTipGenerator());
        customRenderer.setBaseItemLabelGenerator(new StandardCategoryItemLabelGenerator());
        customRenderer.setBaseItemLabelsVisible(true);

        Stroke anastesiStroke = new BasicStroke(2.0f);
        for (int i = 0; i < dataset1.getRowCount(); i++) {
            String seriesName = (String) dataset1.getRowKey(i);
            // PERUBAHAN: Switch disederhanakan, hanya menangani 7 TTV universal
            switch (seriesName) {
                case "Suhu (°C)": customRenderer.setSeriesPaint(i, Color.BLUE); break;
                case "Sistole":
                case "Diastole":
                    customRenderer.setSeriesPaint(i, Color.BLACK);
                    customRenderer.setSeriesLinesVisible(i, false);
                    break;
                case "Heartrate": customRenderer.setSeriesPaint(i, Color.RED); break;
                case "Respirasi": customRenderer.setSeriesPaint(i, Color.GREEN); break;
                case "SpO2": customRenderer.setSeriesPaint(i, Color.ORANGE); break;
                case "GCS": customRenderer.setSeriesPaint(i, Color.MAGENTA); break;
                default: customRenderer.setSeriesPaint(i, Color.CYAN); break;
            }
            customRenderer.setSeriesStroke(i, anastesiStroke);
        }
        
        CategoryPlot subplot1 = new CategoryPlot(dataset1, null, rangeAxis1, customRenderer);
        subplot1.setDomainGridlinesVisible(true);
        subplot1.setRangeGridlinesVisible(true);
        subplot1.setRangeGridlinePaint(Color.BLACK);
        subplot1.setDomainGridlinesVisible(true);
        subplot1.setDomainGridlinePaint(Color.BLACK);
        subplot1.setBackgroundPaint(Color.LIGHT_GRAY);

        CategoryAxis domainAxis = new CategoryAxis("Tanda Tanda Vital Pasien");
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

            if ("Sistole".equals(rowKey)) {
                int sistoleIndex = dataset.getRowIndex("Sistole");
                int diastoleIndex = dataset.getRowIndex("Diastole");

                if (sistoleIndex != -1 && diastoleIndex != -1) {
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
