/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package fungsi;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import java.io.FileReader;
import javax.swing.JOptionPane;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpMethod;
import org.springframework.http.MediaType;
import org.springframework.web.client.HttpClientErrorException;
import org.springframework.web.client.ResourceAccessException;
import org.springframework.http.ResponseEntity;

/**
 *
 * @author khanzasoft
 */
public class SatuSehatCekNIK {
    private String link="",json="";
    public String birthDate="",province="",provincename="",city="",cityname="",district="",districtname="",village="",
                  villagename="",rt="",rw="",line="",postalCode="",gender="",noktp="",idpasien="",maritalStatus="",
                  name="",phone="",email="";
    private ApiSatuSehat api=new ApiSatuSehat();
    private HttpHeaders headers ;
    private HttpEntity requestEntity;
    private ObjectMapper mapper = new ObjectMapper();
    private JsonNode root;
    private JsonNode response;
    private FileReader dataPropinsi,dataKabupaten,dataKecamatan,dataKelurahan;
        

    private String konekSatuSehatCek(String url) throws Exception {
        int maxRetries = 5;
        int attempt = 0;
        Exception lastException = null;

        while (attempt < maxRetries) {
            try {
                attempt++;
                headers = new HttpHeaders();
                headers.setContentType(MediaType.APPLICATION_JSON);
                headers.add("Authorization", "Bearer " + api.TokenSatuSehat());
                requestEntity = new HttpEntity(headers);
                
                System.out.println("URL : " + url);
                ResponseEntity<String> response = api.getRest().exchange(url, HttpMethod.GET, requestEntity, String.class);
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
                    System.out.println("Token Expired, refreshing... (Attempt " + attempt + ")");
                    api.TokenSatuSehat(); // Segarkan token
                    attempt--; // Coba ulangi tanpa hitung limit
                    continue;
                } else if (statusCode == 404) {
                    // Kalau 404 (Not Found), berarti memang pasien/dokter tidak ada di Satu Sehat. Jangan retry.
                    throw e;
                } else if (statusCode == 429) {
                    lastException = e;
                    System.out.println("Terlalu banyak request (429). Menunggu 15 detik... (Attempt " + attempt + ")");
                    try {
                        Thread.sleep(15000);
                    } catch (InterruptedException ie) {
                        Thread.currentThread().interrupt();
                    }
                } else {
                    System.out.println("   !! [SERVER ERROR CHECK] " + statusCode + ": " + e.getResponseBodyAsString());
                    throw e; 
                }
            } catch (ResourceAccessException e) {
                lastException = e;
                System.out.println("Koneksi gagal (ResourceAccessException). Menunggu 5 detik... (Attempt " + attempt + ")");
                try {
                    Thread.sleep(5000);
                } catch (InterruptedException ie) {
                    Thread.currentThread().interrupt();
                }
            } catch (Exception e) {
                lastException = e;
                System.out.println("Gagal terhubung: " + e.getMessage() + ". Menunggu 5 detik... (Attempt " + attempt + ")");
                try {
                    Thread.sleep(5000);
                } catch (InterruptedException ie) {
                    Thread.currentThread().interrupt();
                }
            }
        }
        throw new Exception("Gagal terhubung ke Satu Sehat (Cek NIK) setelah " + maxRetries + " percobaan. " + (lastException != null ? lastException.getMessage() : ""));
    }

    public SatuSehatCekNIK(){
        super();
        try {
            link=koneksiDB.URLFHIRSATUSEHAT();
        } catch (Exception e) {
            System.out.println("Notif : "+e);
        }  
        
        try {
            dataPropinsi = new FileReader("./cache/propinsi.iyem");
        } catch (Exception e) {
            System.out.println("Notif : "+e);
        } 
        
        try {
            dataKabupaten = new FileReader("./cache/kabupaten.iyem");
        } catch (Exception e) {
            System.out.println("Notif : "+e);
        } 
        
        try {
            dataKecamatan= new FileReader("./cache/kecamatan.iyem");
        } catch (Exception e) {
            System.out.println("Notif : "+e);
        } 
        
        try {
            dataKelurahan= new FileReader("./cache/kelurahan.iyem");
        } catch (Exception e) {
            System.out.println("Notif : "+e);
        } 
    }
    
    public void tampil(String cari) {
        try{
            birthDate="";province="";provincename="";city="";cityname="";district="";districtname="";village="";villagename="";
            rt="";rw="";line="";postalCode="";gender="";noktp="";idpasien="";maritalStatus="";name="";phone="";email="";
//             headers.setContentType(MediaType.APPLICATION_JSON);
//             headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
//             requestEntity = new HttpEntity(headers);
            System.out.println("URL : "+link+"/Patient?identifier=https://fhir.kemkes.go.id/id/nik|"+cari);
            json=konekSatuSehatCek(link+"/Patient?identifier=https://fhir.kemkes.go.id/id/nik|"+cari);
            System.out.println("JSON : "+json);
            root = mapper.readTree(json);
            for(JsonNode list:root.path("entry")){
                idpasien=list.path("resource").path("id").asText();
                noktp=cari;
                try{
//                     headers.setContentType(MediaType.APPLICATION_JSON);
//                     headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
//                     requestEntity = new HttpEntity(headers);
                    System.out.println("URL : "+link+"/Patient/"+idpasien);
                    json=konekSatuSehatCek(link+"/Patient/"+idpasien);
                    System.out.println("JSON : "+json);
                    root = mapper.readTree(json);
                    gender = root.path("gender").asText().toLowerCase().equals("male")?"Laki-laki":"Perempuan";
                    birthDate = root.path("birthDate").asText();
                    maritalStatus = root.path("maritalStatus").path("text").asText().toLowerCase().equals("married")?"Menikah":"Belum Menikah";
                    for(JsonNode listname:root.path("name")){
                        name=listname.path("text").asText();
                    }
                    for(JsonNode listtelecom:root.path("telecom")){
                        if(listtelecom.path("system").asText().equals("phone")){
                            phone=listtelecom.path("value").asText();
                        }else if(listtelecom.path("system").asText().equals("email")){
                            email=listtelecom.path("value").asText();
                        }
                    }
                    for(JsonNode listaddress:root.path("address")){
                        line=listaddress.path("line").get(0).asText();
                        postalCode=listaddress.path("postalCode").asText();
                        for(JsonNode listextension:listaddress.path("extension")){
                            for(JsonNode listextensionextension:listextension.path("extension")){
                                if(listextensionextension.path("url").asText().equals("province")){
                                    province=listextensionextension.path("valueCode").asText();
                                }else if(listextensionextension.path("url").asText().equals("city")){
                                    city=listextensionextension.path("valueCode").asText();
                                }else if(listextensionextension.path("url").asText().equals("district")){
                                    district=listextensionextension.path("valueCode").asText();
                                }else if(listextensionextension.path("url").asText().equals("village")){
                                    village=listextensionextension.path("valueCode").asText();
                                }else if(listextensionextension.path("url").asText().equals("rt")){
                                    rt=listextensionextension.path("valueCode").asText();
                                }else if(listextensionextension.path("url").asText().equals("rw")){
                                    rw=listextensionextension.path("valueCode").asText();
                                }
                            }
                        }
                    }
                }catch(Exception e){
                    System.out.println("Notifikasi : "+e);
                }
                response = mapper.readTree(dataKelurahan).path("kelurahan");
                for(JsonNode listkelurahan:response){
                    if(listkelurahan.path("id").asText().toLowerCase().equals(village)&&listkelurahan.path("id_kecamatan").asText().equals(district)){
                        villagename=listkelurahan.path("nama").asText();
                    }
                }
                response = mapper.readTree(dataKecamatan).path("kecamatan");
                for(JsonNode listkcamatan:response){
                    if(listkcamatan.path("id").asText().toLowerCase().equals(district)&&listkcamatan.path("id_kabupaten").asText().equals(city)){
                        districtname=listkcamatan.path("nama").asText();
                    }
                }
                response = mapper.readTree(dataKabupaten).path("kabupaten");
                for(JsonNode listkabupaten:response){
                    if(listkabupaten.path("id").asText().toLowerCase().equals(city)&&listkabupaten.path("id_propinsi").asText().equals(province)){
                        cityname=listkabupaten.path("nama").asText();
                    }
                }
                response = mapper.readTree(dataPropinsi).path("propinsi");
                for(JsonNode listpropinsi:response){
                    if(listpropinsi.path("id").asText().toLowerCase().equals(province)){
                        provincename=listpropinsi.path("nama").asText();
                    }
                }
            }
            
            if(name.equals("")){
                try{
//                     headers.setContentType(MediaType.APPLICATION_JSON);
//                     headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
//                     requestEntity = new HttpEntity(headers);
                    System.out.println("URL : "+link+"/Patient/"+cari);
                    json=konekSatuSehatCek(link+"/Patient/"+cari);
                    System.out.println("JSON : "+json);
                    root = mapper.readTree(json);
                    idpasien=cari;
                    gender = root.path("gender").asText().toLowerCase().equals("male")?"Laki-laki":"Perempuan";
                    birthDate = root.path("birthDate").asText();
                    maritalStatus = root.path("maritalStatus").path("text").asText().toLowerCase().equals("married")?"Menikah":"Belum Menikah";
                    for(JsonNode listname:root.path("name")){
                        name=listname.path("text").asText();
                    }
                    for(JsonNode listtelecom:root.path("telecom")){
                        if(listtelecom.path("system").asText().equals("phone")){
                            phone=listtelecom.path("value").asText();
                        }
                        if(listtelecom.path("system").asText().equals("email")){
                            email=listtelecom.path("value").asText();
                        }
                    }
                    for(JsonNode listnoktp:root.path("identifier")){
                        if(listnoktp.path("system").asText().equals("https://fhir.kemkes.go.id/id/nik")){
                            noktp=listnoktp.path("value").asText();
                        }
                    }
                    for(JsonNode listaddress:root.path("address")){
                        line=listaddress.path("line").get(0).asText();
                        postalCode=listaddress.path("postalCode").asText();
                        for(JsonNode listextension:listaddress.path("extension")){
                            for(JsonNode listextensionextension:listextension.path("extension")){
                                if(listextensionextension.path("url").asText().equals("province")){
                                    province=listextensionextension.path("valueCode").asText();
                                }
                                if(listextensionextension.path("url").asText().equals("city")){
                                    city=listextensionextension.path("valueCode").asText();
                                }
                                if(listextensionextension.path("url").asText().equals("district")){
                                    district=listextensionextension.path("valueCode").asText();
                                }
                                if(listextensionextension.path("url").asText().equals("village")){
                                    village=listextensionextension.path("valueCode").asText();
                                }
                                if(listextensionextension.path("url").asText().equals("rt")){
                                    rt=listextensionextension.path("valueCode").asText();
                                }
                                if(listextensionextension.path("url").asText().equals("rw")){
                                    rw=listextensionextension.path("valueCode").asText();
                                }
                            }
                        }
                    }
                }catch(Exception e){
                    System.out.println("Notifikasi : "+e);
                }
                response = mapper.readTree(dataKelurahan).path("kelurahan");
                for(JsonNode listkelurahan:response){
                    if(listkelurahan.path("id").asText().toLowerCase().equals(village)&&listkelurahan.path("id_kecamatan").asText().equals(district)){
                        villagename=listkelurahan.path("nama").asText();
                    }
                }
                response = mapper.readTree(dataKecamatan).path("kecamatan");
                for(JsonNode listkcamatan:response){
                    if(listkcamatan.path("id").asText().toLowerCase().equals(district)&&listkcamatan.path("id_kabupaten").asText().equals(city)){
                        districtname=listkcamatan.path("nama").asText();
                    }
                }
                response = mapper.readTree(dataKabupaten).path("kabupaten");
                for(JsonNode listkabupaten:response){
                    if(listkabupaten.path("id").asText().toLowerCase().equals(city)&&listkabupaten.path("id_propinsi").asText().equals(province)){
                        cityname=listkabupaten.path("nama").asText();
                    }
                }
                response = mapper.readTree(dataPropinsi).path("propinsi");
                for(JsonNode listpropinsi:response){
                    if(listpropinsi.path("id").asText().toLowerCase().equals(province)){
                        provincename=listpropinsi.path("nama").asText();
                    }
                }
            }
        }catch(Exception e){
            System.out.println("Notifikasi : "+e);
        }
        
        if(name.equals("")){
            JOptionPane.showMessageDialog(null,"Maaf, Belum Ada data di Server Satu Sehat");
        }
    }
    
    public String tampilIDPasien(String cari) {
        idpasien="";
        try{
//             headers.setContentType(MediaType.APPLICATION_JSON);
//             headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
//             requestEntity = new HttpEntity(headers);
            System.out.println("URL : "+link+"/Patient?identifier=https://fhir.kemkes.go.id/id/nik|"+cari);
            json=konekSatuSehatCek(link+"/Patient?identifier=https://fhir.kemkes.go.id/id/nik|"+cari);
            System.out.println("JSON : "+json);
            root = mapper.readTree(json);
            for(JsonNode list:root.path("entry")){
                idpasien=list.path("resource").path("id").asText();
            }
        }catch(Exception e){
            idpasien="";
            System.out.println("Notifikasi : "+e);
        }
        return idpasien;
    }
    
    public String tampilIDParktisi(String cari) {
        idpasien="";
        try{
//             headers.setContentType(MediaType.APPLICATION_JSON);
//             headers.add("Authorization", "Bearer "+api.TokenSatuSehat());
//             requestEntity = new HttpEntity(headers);
            System.out.println("URL : "+link+"/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|"+cari);
            json=konekSatuSehatCek(link+"/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|"+cari);
            System.out.println("JSON : "+json);
            root = mapper.readTree(json);
            response = root.path("entry");
            for(JsonNode list:response){
               idpasien=list.path("resource").path("id").asText();
            }
        }catch(Exception e){
            idpasien="";
            System.out.println("Notifikasi : "+e);
        }
        return idpasien;
    }
}
