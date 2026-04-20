import sys

def main():
    file_path = 'KhanzaHMSServiceSatuSehat_ichsan/src/khanzahmsservicesatusehat/frmUtama.java'
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    start_idx = content.find('    private void vaksin() {')
    end_idx = content.find('    // MODUL PROSEDUR')

    if start_idx == -1 or end_idx == -1:
        print("Could not find boundaries")
        return

    new_vaksin = """    private void vaksin() {
        try {
            TeksArea.append("\\n------------------------------------------------------\\n");
            TeksArea.append("MULAI PROSES KIRIM VAKSIN (IMMUNIZATION)\\n");
            TeksArea.append("------------------------------------------------------\\n");

            ps = koneksi.prepareStatement(
                    "select detail_pemberian_obat.no_rawat,pasien.nm_pasien,pasien.no_ktp,satu_sehat_encounter.id_encounter,satu_sehat_mapping_vaksin.vaksin_code,satu_sehat_mapping_vaksin.vaksin_system,"
                    + "satu_sehat_mapping_vaksin.kode_brng,satu_sehat_mapping_vaksin.vaksin_display,satu_sehat_mapping_vaksin.route_code,satu_sehat_mapping_vaksin.route_system,"
                    + "satu_sehat_mapping_vaksin.route_display,satu_sehat_mapping_vaksin.dose_quantity_code,satu_sehat_mapping_vaksin.dose_quantity_system,"
                    + "satu_sehat_mapping_vaksin.dose_quantity_unit,"
                    + "if(detail_pemberian_obat.no_batch='' or detail_pemberian_obat.no_batch is null, if(detail_pemberian_obat.no_faktur='' or detail_pemberian_obat.no_faktur is null, '000000', SUBSTRING(detail_pemberian_obat.no_faktur, 1, 6)), detail_pemberian_obat.no_batch) as no_batch,"
                    + "detail_pemberian_obat.tgl_perawatan,detail_pemberian_obat.jam,"
                    + "detail_pemberian_obat.jml,ifnull(aturan_pakai.aturan,'') as aturan,satu_sehat_mapping_lokasi_ralan.id_lokasi_satusehat,poliklinik.nm_poli,pegawai.no_ktp as ktppraktisi,"
                    + "ifnull(satu_sehat_immunization.id_immunization,'') as id_immunization,"
                    + "if(detail_pemberian_obat.no_faktur='' or detail_pemberian_obat.no_faktur is null,'000000',detail_pemberian_obat.no_faktur) as no_faktur, "
                    + "ifnull(data_batch.tgl_kadaluarsa, databarang.expire) as tgl_kadaluarsa "
                    + "from detail_pemberian_obat "
                    + "inner join reg_periksa on detail_pemberian_obat.no_rawat=reg_periksa.no_rawat "
                    + "inner join pasien on reg_periksa.no_rkm_medis=pasien.no_rkm_medis "
                    + "inner join satu_sehat_encounter on satu_sehat_encounter.no_rawat=reg_periksa.no_rawat "
                    + "inner join satu_sehat_mapping_vaksin on satu_sehat_mapping_vaksin.kode_brng=detail_pemberian_obat.kode_brng "
                    + "inner join databarang on databarang.kode_brng=detail_pemberian_obat.kode_brng "
                    + "left join aturan_pakai on aturan_pakai.tgl_perawatan=detail_pemberian_obat.tgl_perawatan and aturan_pakai.jam=detail_pemberian_obat.jam and aturan_pakai.no_rawat=detail_pemberian_obat.no_rawat and aturan_pakai.kode_brng=detail_pemberian_obat.kode_brng "
                    + "inner join satu_sehat_mapping_lokasi_ralan on satu_sehat_mapping_lokasi_ralan.kd_poli=reg_periksa.kd_poli "
                    + "inner join poliklinik on poliklinik.kd_poli=satu_sehat_mapping_lokasi_ralan.kd_poli "
                    + "inner join pegawai on reg_periksa.kd_dokter=pegawai.nik "
                    + "left join nota_jalan on nota_jalan.no_rawat=reg_periksa.no_rawat "
                    + "left join nota_inap on nota_inap.no_rawat=reg_periksa.no_rawat "
                    + "left join data_batch on data_batch.no_batch=detail_pemberian_obat.no_batch and data_batch.kode_brng=detail_pemberian_obat.kode_brng and data_batch.no_faktur=detail_pemberian_obat.no_faktur "
                    + "left join satu_sehat_immunization on satu_sehat_immunization.no_rawat=detail_pemberian_obat.no_rawat and satu_sehat_immunization.tgl_perawatan=detail_pemberian_obat.tgl_perawatan and satu_sehat_immunization.jam=detail_pemberian_obat.jam and satu_sehat_immunization.kode_brng=detail_pemberian_obat.kode_brng "
                    + "where (nota_jalan.tanggal between ? and ? OR nota_inap.tanggal between ? and ?) "
                    + "and LENGTH(pasien.no_ktp) = 16 and pasien.no_ktp REGEXP '^[0-9]+$' "
                    + "and LENGTH(pegawai.no_ktp) = 16 and pegawai.no_ktp REGEXP '^[0-9]+$' "
                    + "and ifnull(satu_sehat_immunization.id_immunization,'')=''");
            try {
                ps.setString(1, Tanggal1.getText());
                ps.setString(2, Tanggal2.getText());
                ps.setString(3, Tanggal1.getText());
                ps.setString(4, Tanggal2.getText());
                rs = ps.executeQuery();
                while (rs.next()) {
                    if (isEmergencyStop) break;
                    
                    TeksArea.append("\\n[PROSES VAKSIN] No.Rawat: " + rs.getString("no_rawat") + " | " + rs.getString("vaksin_display") + "\\n");

                    try {
                        idpasien = cekViaSatuSehat.tampilIDPasien(rs.getString("no_ktp"));
                        iddokter = cekViaSatuSehat.tampilIDParktisi(rs.getString("ktppraktisi"));

                        if (idpasien.isEmpty() || iddokter.isEmpty()) {
                            TeksArea.append("   !! [SKIP] ID Pasien/Dokter tidak ditemukan di Satu Sehat.\\n");
                            continue;
                        }

                        // Sanitasi Nama Vaksin
                        String namaVaksin = rs.getString("vaksin_display").replaceAll("(\\r\\n|\\r|\\n|\\n\\r)", " ").replaceAll("\\\"", "'");
                        
                        // Logika Dosis (Ambil angka dari aturan pakai)
                        String doseStr = rs.getString("aturan").replaceAll("[^0-9]", "");
                        if(doseStr.isEmpty()) doseStr = "1";
                        
                        // Logika Fallback Tanggal Kadaluarsa
                        String tglKadaluarsa = rs.getString("tgl_kadaluarsa");
                        if (tglKadaluarsa == null || tglKadaluarsa.trim().isEmpty() || tglKadaluarsa.equals("0000-00-00")) {
                            java.util.Calendar cal = java.util.Calendar.getInstance();
                            cal.add(java.util.Calendar.YEAR, 5);
                            tglKadaluarsa = tanggalFormat.format(cal.getTime());
                            TeksArea.append("   !! [INFO] Tgl Kadaluarsa kosong. Menggunakan default bypass 5 tahun ke depan: " + tglKadaluarsa + "\\n");
                        }

                        headers = new HttpHeaders();
                        headers.setContentType(MediaType.APPLICATION_JSON);
                        headers.add("Authorization", "Bearer " + api.TokenSatuSehat());

                        json = "{"
                                + "\\\"resourceType\\\": \\\"Immunization\\\","
                                + "\\\"status\\\": \\\"completed\\\","
                                + "\\\"vaccineCode\\\": {"
                                    + "\\\"coding\\\": [{"
                                        + "\\\"system\\\": \\\"" + rs.getString("vaksin_system") + "\\\","
                                        + "\\\"code\\\": \\\"" + rs.getString("vaksin_code") + "\\\","
                                        + "\\\"display\\\": \\\"" + namaVaksin + "\\\""
                                    + "}]"
                                + "},"
                                + "\\\"patient\\\": {\\\"reference\\\": \\\"Patient/" + idpasien + "\\\"},"
                                + "\\\"encounter\\\": {\\\"reference\\\": \\\"Encounter/" + rs.getString("id_encounter") + "\\\"},"
                                + "\\\"occurrenceDateTime\\\": \\\"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam") + "+07:00\\\","
                                + "\\\"recorded\\\": \\\"" + rs.getString("tgl_perawatan") + "T" + rs.getString("jam") + "+07:00\\\","
                                + "\\\"primarySource\\\": true,"
                                + "\\\"reasonCode\\\": [{\\\"coding\\\": [{\\\"system\\\": \\\"http://snomed.info/sct\\\",\\\"code\\\": \\\"281657000\\\",\\\"display\\\": \\\"Vaccination needed\\\"}]}],"
                                + "\\\"location\\\": {\\\"reference\\\": \\\"Location/" + rs.getString("id_lokasi_satusehat") + "\\\",\\\"display\\\": \\\"" + rs.getString("nm_poli") + "\\\"},"
                                + "\\\"lotNumber\\\": \\\"" + rs.getString("no_batch") + "\\\","
                                + "\\\"expirationDate\\\": \\\"" + tglKadaluarsa + "\\\","
                                + "\\\"route\\\": {"
                                    + "\\\"coding\\\": [{"
                                        + "\\\"system\\\": \\\"" + rs.getString("route_system") + "\\\","
                                        + "\\\"code\\\": \\\"" + rs.getString("route_code") + "\\\","
                                        + "\\\"display\\\": \\\"" + rs.getString("route_display") + "\\\""
                                    + "}]"
                                + "},"
                                + "\\\"doseQuantity\\\": {"
                                    + "\\\"value\\\": " + rs.getString("jml") + ","
                                    + "\\\"unit\\\": \\\"" + rs.getString("dose_quantity_unit") + "\\\","
                                    + "\\\"system\\\": \\\"" + rs.getString("dose_quantity_system") + "\\\","
                                    + "\\\"code\\\": \\\"" + rs.getString("dose_quantity_code") + "\\\""
                                + "},"
                                + "\\\"performer\\\": [{"
                                    + "\\\"function\\\": {\\\"coding\\\": [{\\\"system\\\": \\\"http://terminology.hl7.org/CodeSystem/v2-0443\\\",\\\"code\\\": \\\"AP\\\",\\\"display\\\": \\\"Administering Provider\\\"}]},"
                                    + "\\\"actor\\\": {\\\"reference\\\": \\\"Practitioner/" + iddokter + "\\\"}"
                                + "}],"
                                + "\\\"protocolApplied\\\": [{\\\"doseNumberPositiveInt\\\": " + doseStr + "}]"
                                + "}";

                        processSendVaksin(json, rs);

                    } catch (Exception e) {
                        TeksArea.append("   !! [ERROR SYSTEM] " + e.getMessage() + "\\n");
                    }
                    jeda();
                }
            } finally {
                if (rs != null) rs.close();
                if (ps != null) ps.close();
            }
        } catch (Exception e) {
            TeksArea.append("!! ERROR UTAMA VAKSIN: " + e.getMessage() + "\\n");
        }
    }

    private void processSendVaksin(String jsonPayload, ResultSet rs) throws Exception {
        requestEntity = new HttpEntity(jsonPayload, headers);
        try {
            String responseJson = konekSatuSehat(link + "/Immunization", HttpMethod.POST, requestEntity);
            root = mapper.readTree(responseJson);
            JsonNode responseId = root.path("id");
            if (!responseId.asText().equals("")) {
                Sequel.menyimpantf2("satu_sehat_immunization", "?,?,?,?,?,?,?", "Imunisasi/Vaksin", 7, new String[]{
                    rs.getString("no_rawat"), rs.getString("tgl_perawatan"), rs.getString("jam"),
                    rs.getString("kode_brng"), rs.getString("no_batch"), rs.getString("no_faktur"), responseId.asText()
                });
                TeksArea.append("   [SUKSES] ID Satu Sehat: " + responseId.asText() + "\\n");
            }
        } catch (HttpClientErrorException e) {
            if (e.getStatusCode().value() == 401) {
                TeksArea.append("   !! [TOKEN EXPIRED] Retrying...\\n");
                headers.set("Authorization", "Bearer " + api.TokenSatuSehat());
                requestEntity = new HttpEntity(jsonPayload, headers);
                String responseJsonRetry = konekSatuSehat(link + "/Immunization", HttpMethod.POST, requestEntity);
                root = mapper.readTree(responseJsonRetry);
                if (!root.path("id").asText().isEmpty()) TeksArea.append("   [RETRY SUKSES]\\n");
            } else {
                TeksArea.append("   !! [API ERROR] " + e.getResponseBodyAsString() + "\\n");
            }
        }
    }
"""

    new_content = content[:start_idx] + new_vaksin + "\n" + content[end_idx:]
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print("Success")

if __name__ == "__main__":
    main()
