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
public class koneksiDBWa_marketing {
    private static Connection connection=null;
    private static final Properties prop = new Properties();  
    private static final MysqlDataSource dataSource=new MysqlDataSource();
    private static String var="";
    
    public koneksiDBWa_marketing(){} 
    public static Connection condb(){ 
        if(connection == null){
            try{
                prop.loadFromXML(new FileInputStream("setting/database.xml"));
                dataSource.setURL("jdbc:mysql://"+EnkripsiAES.decrypt(prop.getProperty("HOSTWAMARKETING"))+":"+EnkripsiAES.decrypt(prop.getProperty("PORTWAMARKETING"))+"/"+EnkripsiAES.decrypt(prop.getProperty("DATABASEWAMARKETING"))+"?zeroDateTimeBehavior=convertToNull&amp;autoReconnect=true&maxAllowedPacket=1073741824");
                dataSource.setUser(EnkripsiAES.decrypt(prop.getProperty("USERWAMARKETING")));
                dataSource.setPassword(EnkripsiAES.decrypt(prop.getProperty("PASWAMARKETING")));
                connection=dataSource.getConnection();       
                System.out.println("  Koneksi Berhasil. Menyambungkan ke database bridging Gateway WA...!!!");
            }catch(Exception e){
                JOptionPane.showMessageDialog(null,"Koneksi ke server bridging Gateway WA terputus : "+e);
            }
        }
        return connection;        
    }
    
    public static String HOSTWAMARKETING() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=EnkripsiAES.decrypt(prop.getProperty("HOSTWA1"));
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
    
    public static String IPFOLDERFILEWAMARKETING() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=EnkripsiAES.decrypt(prop.getProperty("IPFOLDERFILEWAMARKETING"));
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
   
   public static String PORTWEBWAMARKETING() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=EnkripsiAES.decrypt(prop.getProperty("PORTWEBWA1"));
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
    
    public static String FOLDERFILEWAMARKETING() {
        try (FileInputStream fis = new FileInputStream("setting/database.xml")) {
            prop.loadFromXML(fis);
            var=prop.getProperty("FOLDERFILEWA1");
        }catch(Exception e){
            var=""; 
        }
        return var;
    }
    
}
