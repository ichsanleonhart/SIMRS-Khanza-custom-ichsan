/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package khanzahmsanjungan;

/**
 *
 * @author LENOVO
 */
public class DataObject {
    private String field1;
    private String field2;
    private String field3;
    private String field4;
    private String kddokterbpjs;
    private String kdpolibpjs;

    public DataObject(String field1, String field2, String field3, String field4,String kddokterbpjs,String kdpolibpjs) {
        this.field1 = field1;
        this.field2 = field2;
        this.field3 = field3;
        this.field4 = field4;
        this.kddokterbpjs = kddokterbpjs;
        this.kdpolibpjs = kdpolibpjs;
    }
 
    // Tambahkan getter dan setter sesuai kebutuhan
    public String getField1() {
        return field1;
    }

    public void setField1(String field1) {
        this.field1 = field1;
    }

    public String getField2() {
        return field2;
    }
    public void setField2(String field2) {
        this.field2 = field2;
    }
    
     public String getField3() {
        return field3;
    }
    public void setField3(String field3) {
        this.field3 = field3;
    }
    
    public String getField4() {
        return field4;
    }
    public void setField4(String field4) {
        this.field4 = field4;
    }
    public String getkddokterbpjs() {
        return kddokterbpjs;
    }
    public void setkddokterbpjs(String kddokterbpjs) {
        this.kddokterbpjs = kddokterbpjs;
    }
    public String getkdpolibpjs() {
        return kdpolibpjs;
    }
    public void setkdpolibpjs(String kdpolibpjs) {
        this.kdpolibpjs = kdpolibpjs;
    }

    @Override
    public String toString() {
        return "DataObject{" +
                "nama='" + field1 + '\'' +
                ", field2='" + field2 + '\'' +
                 ", field3='" + field3 + '\'' +
                ", field4='" + field4 + '\'' +
                ", kddokterbpjs='" + kddokterbpjs + '\'' +
                ", kdpolibpjs='" + kdpolibpjs + '\'' +
                '}';
    }
    
}
