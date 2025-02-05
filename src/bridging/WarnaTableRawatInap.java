/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package fungsi;

import java.awt.Color;
import java.awt.Component;
import javax.swing.JTable;
import javax.swing.table.DefaultTableCellRenderer;

/**
 *
 * @author Owner
 */
public class WarnaTableRawatInap extends DefaultTableCellRenderer {
   public Component getTableCellRendererComponent(JTable table, Object value, boolean isSelected, boolean hasFocus, int row, int column){
        Component component = super.getTableCellRendererComponent(table, value, isSelected, hasFocus, row, column);
        if (row % 2 == 1){
            component.setBackground(new Color(240,240,240));
            component.setForeground(new Color(55,55,55));
        }else{
            component.setBackground(new Color(255,255,255));
            component.setForeground(new Color(55,55,55));
        } 
        if(table.getValueAt(row,6).toString().contains("BPJS")){
            component.setBackground(new Color(204,255,204));
            component.setForeground(new Color(50,40,50));
        }else if(table.getValueAt(row,6).toString().contains("UMUM")){
            component.setBackground(new Color(102,204,255));
            component.setForeground(new Color(0,0,102));
        }else if(table.getValueAt(row,6).toString().contains("Asuransi")){
            component.setBackground(new Color(255,204,255));
            component.setForeground(new Color(42,53,42));
        }else if(table.getValueAt(row,6).toString().contains("Perusahaan")){
            component.setBackground(new Color(255,204,204));
            component.setForeground(new Color(31,38,38));
        }else if(table.getValueAt(row,6).toString().contains("Inhealth")){
            component.setBackground(new Color(255,153,204));
            component.setForeground(new Color(55,36,36));
        }else if(table.getValueAt(row,6).toString().contains("PJPK")){
            component.setBackground(new Color(51,204,255));
            component.setForeground(new Color(0,0,102));
        }
        
        
         if (isSelected) {
            component.setForeground(new Color(3,5,36));
        }
        return component;
    }

}
