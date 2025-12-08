function OnStoredInstance(instanceId, tags, metadata)
   -- 1. AMBIL DICOM TAGS
   local raw_acsn = tags['AccessionNumber']
   local patient_name = tags['PatientName'] or "No Name"

   print('>>> LUA CHECK: Masuk File ID ' .. instanceId)

   -- 2. SANITASI DATA (PENTING!)
   -- Bersihkan spasi depan/belakang dan karakter aneh
   local acsn = ""
   if raw_acsn ~= nil then
      acsn = raw_acsn:gsub("%s+", "") -- Hapus semua spasi
      acsn = string.upper(acsn)        -- Paksa huruf besar
   end

   -- 3. FILTER LAPIS PERTAMA: VALIDASI ACSN
   -- Jika kosong, atau terlalu pendek, atau BUKAN awalan PR... TENDANG!
   if acsn == "" then
      print('Status: IGNORING (ACSN Kosong/Nil - Menunggu Injector)')
      return -- Stop, jangan lakukan apa-apa
   end

   if string.len(acsn) < 5 then
      print('Status: IGNORING (ACSN Terlalu Pendek/Sampah: ' .. acsn .. ')')
      return
   end

   if string.sub(acsn, 1, 2) ~= 'PR' then
      print('Status: IGNORING (Bukan Order Khanza/PR: ' .. acsn .. ')')
      return
   end

   -- Jika sampai sini, berarti ACSN Valid (PRxxxx)
   print('Validasi: OK. Target ACSN: ' .. acsn .. ' (' .. patient_name .. ')')

   -- ============================================================
   -- 4. GATEKEEPER CHECK (Tanya PHP: "Boleh Lewat?")
   -- ============================================================
   -- URL ke Script PHP Gatekeeper (Pastikan file ini ada di htdocs/orthanc_injector)
   local url = 'http://127.0.0.1/orthanc_injector/gatekeeper_check.php?acsn=' .. acsn
   
   -- Timeout check (Mencegah Orthanc hang jika PHP macet)
   local response = nil
   local success, err = pcall(function()
      response = PerformHttpRequest('GET', url)
   end)

   if not success or response == nil then
      print('CRITICAL: Gagal menghubungi Gatekeeper PHP! Tahan gambar.')
      print('Error: ' .. tostring(err))
      return -- Tahan demi keamanan
   end

   -- Bersihkan respon PHP dari spasi/newline
   response = response:gsub("%s+", "")

   print('Gatekeeper Menjawab: [' .. response .. ']')

   -- 5. KEPUTUSAN FINAL (EKSEKUSI)
   if response == 'READY' then
      -- [HIJAU] PHP Konfirmasi ID ServiceRequest ADA di DB Lokal
      print('Action: >>> FORWARDING TO DICOM ROUTER <<<')
      
      -- Kirim ke Router (Pastikan nama modality sesuai orthanc.json)
      local send_success, send_err = pcall(function()
         SendToModality(instanceId, 'SATUSEHAT_ROUTER')
      end)

      if send_success then
         print('Result: SEND SUKSES')
      else
         print('Result: GAGAL KIRIM -> ' .. tostring(send_err))
      end

   elseif response == 'WAIT' then
      -- [KUNING] ID ServiceRequest BELUM ADA di DB Lokal
      print('Action: HOLD (Menunggu Java mengirim ServiceRequest)')
      print('Info: Gambar aman tersimpan di Orthanc.')

   else
      -- [MERAH] Respon Aneh
      print('Action: BLOCK (Respon Gatekeeper Tidak Dikenal)')
   end
   
   print('-------------------------------------------')
end