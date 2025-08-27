/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package fungsi;

import AESsecurity.EnkripsiAES;
import com.mysql.jdbc.jdbc2.optional.MysqlDataSource;
import java.io.FileInputStream;
import java.sql.Connection;
import java.util.Properties;
import javax.swing.JOptionPane;


/**
 *
 * @author khanzasoft
 */
public class koneksiDBWa_mgm {
    private static Connection connection=null;
    private static final Properties prop = new Properties();  
    private static final MysqlDataSource dataSource=new MysqlDataSource();
    private static String var="";
    
    public koneksiDBWa_mgm(){} 
    public static Connection condb(){ 
        if(connection == null){
            try{
                prop.loadFromXML(new FileInputStream("setting/database.xml"));
                dataSource.setURL("jdbc:mysql://"+EnkripsiAES.decrypt(prop.getProperty("HOSTWA1"))+":"+EnkripsiAES.decrypt(prop.getProperty("PORTWA1"))+"/"+EnkripsiAES.decrypt(prop.getProperty("DATABASEWA1"))+"?zeroDateTimeBehavior=convertToNull&amp;autoReconnect=true&maxAllowedPacket=1073741824");
                dataSource.setUser(EnkripsiAES.decrypt(prop.getProperty("USERWA1")));
                dataSource.setPassword(EnkripsiAES.decrypt(prop.getProperty("PASWA1")));
                connection=dataSource.getConnection();       
                System.out.println("  Koneksi Berhasil. Menyambungkan ke database bridging Gateway WA...!!!");
            }catch(Exception e){
                JOptionPane.showMessageDialog(null,"Koneksi ke server bridging Gateway WA terputus : "+e);
            }
        }
        return connection;        
    }
    
    // ##### METODE BARU DITAMBAHKAN DI SINI #####
    
    public static String HOSTWA1() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=EnkripsiAES.decrypt(prop.getProperty("HOSTWA1"));
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
    
    public static String IPFOLDERFILEWA1() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=EnkripsiAES.decrypt(prop.getProperty("IPFOLDERFILEWA1"));
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
   
   public static String PORTWEBWA1() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=EnkripsiAES.decrypt(prop.getProperty("PORTWEBWA1"));
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
    
    public static String FOLDERFILEWA1() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=prop.getProperty("FOLDERFILEWA1");
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
    
}
