
        <?php
            // AJAX: get today's shift (jam_masuk) based on barcode and schedules
            if(isset($_GET['getShift']) && isset($_GET['barcode'])){
                if (ob_get_level()) { ob_end_clean(); }
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                $barcodeReq = trim($_GET['barcode']);
                $today = date('Y-m-d');
                $jamMasuk = '';
                try{
                    // Resolve pegawai id from barcode/nik/id
                    $idpeg = getOne("select id from barcode where barcode='".$barcodeReq."'");
                    if(empty($idpeg)){
                        $idpeg = getOne("select id from pegawai where nik='".$barcodeReq."' limit 1");
                    }
                    if(empty($idpeg)){
                        $idpeg = getOne("select id from pegawai where id='".$barcodeReq."' limit 1");
                    }
                    
                    if(!empty($idpeg)){
                        // Logic: ambil shift pada jadwal_pegawai kolom h{hari}, lalu dapatkan jam dari jam_jaga sesuai departemen
                        $hariIndex = date('j');
                        $bulan = date('m');
                        $tahun = date('Y');
                        $depId = getOne("select departemen from pegawai where id='".$idpeg."'");
                        // ambil shift code di jadwal_pegawai.h{j}
                        $col = 'h'.$hariIndex;
                        $shiftCode = getOne("select $col from jadwal_pegawai where id='".$idpeg."' and bulan='".$bulan."' and tahun='".$tahun."' limit 1");
                        if(!empty($shiftCode)){
                            $jamMasuk = getOne("select TIME_FORMAT(jam_masuk,'%H:%i:%s') from jam_jaga where shift='".$shiftCode."' and dep_id='".$depId."' limit 1");
                        }
                        // Priority 2: jadwal_tambahan pada tanggal hari ini
                        if(empty($jamMasuk)){
                            $jamMasuk = getOne("select TIME_FORMAT(jam_masuk,'%H:%i:%s') from jadwal_tambahan where id='".$idpeg."' and tanggal='".$today."' limit 1");
                        }
                        // Fallback: jam default departemen
                        if(empty($jamMasuk)){
                            $jamMasuk = getOne("select TIME_FORMAT(jj.jam_masuk,'%H:%i:%s') from jam_jaga jj where jj.dep_id='".$depId."' limit 1");
                        }
                    }
                    echo json_encode(['ok'=>true,'jam_masuk'=>$jamMasuk?:'']);
                }catch(Exception $e){
                    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
                }
                exit;
            }
            // AJAX handler: enroll new face images
            if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['enroll'])){
                // Clear any previous output
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Set proper headers
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                
                // Debug: Log the request
                error_log('Enrollment request received: ' . print_r($_POST, true));
                
                try {
                    // First, let's test with a simple response
                    $labelRaw = isset($_POST['label']) ? $_POST['label'] : '';
                    $images = isset($_POST['images']) ? $_POST['images'] : [];
                    
                    // Simple test response first
                    echo json_encode([
                        'ok' => true, 
                        'test' => true,
                        'message' => 'Test response received',
                        'label' => $labelRaw,
                        'imageCount' => is_array($images) ? count($images) : 1,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    exit;
                    
                    // Original code (commented out for testing)
                    /*
                    $label = preg_replace('/[^a-zA-Z0-9_-]/','_', trim($labelRaw));
                    
                    // Handle both array and single image formats
                    if(!is_array($images)){
                        $images = [$images];
                    }
                    
                    $saved = [];
                    if($label === '' || empty($images)){
                        echo json_encode(['ok'=>false,'message'=>'Label atau gambar tidak valid']);
                        exit;
                    }
                    
                    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'faces' . DIRECTORY_SEPARATOR . $label;
                    if(!is_dir($baseDir)){
                        if(!@mkdir($baseDir, 0775, true)){
                            echo json_encode(['ok'=>false,'message'=>'Gagal membuat folder']);
                            exit;
                        }
                    }
                    
                    $idx = 1;
                    foreach($images as $dataUri){
                        if(!is_string($dataUri)) continue;
                        if(strpos($dataUri, ';base64,') === false) continue;
                        
                        $parts = explode(';base64,', $dataUri);
                        if(count($parts) !== 2) continue;
                        
                        $mime = $parts[0];
                        $ext = 'jpeg';
                        if(stripos($mime,'png')!==false){ $ext = 'png'; }
                        
                        $bin = base64_decode($parts[1]);
                        if($bin === false) continue;
                        
                        $filename = date('Ymd_His') . '_' . $idx . '.' . $ext;
                        $target = $baseDir . DIRECTORY_SEPARATOR . $filename;
                        
                        if(@file_put_contents($target, $bin)!==false){
                            $saved[] = 'faces/'.$label.'/'.$filename;
                            $idx++;
                        }
                    }
                    
                    if(count($saved) > 0){
                        echo json_encode(['ok'=>true,'saved'=>$saved,'message'=>'Berhasil menyimpan ' . count($saved) . ' foto']);
                    } else {
                        echo json_encode(['ok'=>false,'message'=>'Gagal menyimpan foto']);
                    }
                    */
                } catch (Exception $e) {
                    echo json_encode(['ok'=>false,'message'=>'Error: ' . $e->getMessage()]);
                }
                exit;
            }
        ?>
        <div id="post">
            <h1 class="title text-2xl md:text-3xl font-extrabold tracking-tight">::[ Input Presensi Pegawai (Face Recognition) ]::</h1>
            <div class="entry">
            <style type="text/css">
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
                body, .title, .entry, .tbl_form, select.text7, input.text7, input.text { font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
                #post{background:linear-gradient(135deg,#0f172a 0%, #1e293b 35%, #0b1220 100%); border-radius:14px; box-shadow:0 20px 40px rgba(0,0,0,0.35); padding:16px 16px 20px; border:1px solid rgba(255,255,255,0.06);} 
                .title{margin:8px 8px 18px; font-weight:800; letter-spacing:.3px; background:linear-gradient(135deg,#60a5fa 0%, #a78bfa 50%, #34d399 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;}
                .tbl_form{border-collapse:separate; border-spacing:0 0;}
                .tbl_form .head{background:transparent;}
                .tbl_form .head4 td:first-child{color:#cbd5e1; font-weight:600;}
                .cam-card{background:#0f172a; border:1px solid rgba(255,255,255,0.08); border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.25); padding:12px; position:relative; max-width: 420px;}
                #my_camera{background:linear-gradient(135deg,#1e293b,#0f172a); border-radius:10px; overflow:hidden;}
                #results.snapshot-preview{margin-top:10px; background:#0b1220; border:1px solid rgba(51,65,85,0.6); border-radius:10px; min-height:40px; display:flex; align-items:center; justify-content:center; padding:8px;}
                #results img{max-width:100%; height:auto; border-radius:8px;}
                .button{background:linear-gradient(135deg,#2563eb,#1d4ed8); border:none !important; color:#fff !important; padding:10px 16px; border-radius:8px; font-weight:bold; letter-spacing:.3px; box-shadow:0 6px 18px rgba(29,78,216,.35); transition:transform .15s ease, box-shadow .15s ease;}
                .button:hover{transform:translateY(-1px); box-shadow:0 10px 22px rgba(29,78,216,.45);}
                .button:disabled{background:#6b7280 !important; box-shadow:none; cursor:not-allowed;}
                select.text7, input.text7, input.text{width:100%; max-width:380px; padding:8px 10px; border-radius:8px; border:1px solid rgba(0,0,0,0.2);}
                @media (max-width: 768px){
                  .tbl_form .head{display:block;}
                  .tbl_form .head > td{display:block; width:100% !important; box-sizing:border-box;}
                }
            </style>
            <form name="frmPresensi" id="frmPresensi" method="post" onsubmit="return validasiIsi();">
            <table  width='100%' border='0' align='center' cellpadding='0' cellspacing='0' class='tbl_form'>
              <tr class='head'>
                <td width=50% align="center" style=" background: transparent ;">
                    <div class="cam-card">
                        <div id="my_camera">
                            <div id="loadingIndicator" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 15px 25px; border-radius: 8px; font-size: 14px; z-index: 20; display: none; text-align: center;">
                                <div style="margin-bottom: 8px;">🔄 Loading Face Recognition...</div>
                                <div style="font-size: 12px; opacity: 0.8;">Processing known faces...</div>
                            </div>
                        </div>
                        <div id="results" class="snapshot-preview"><img id="snapshotPreview" alt="Snapshot" style="max-width:100%;height:auto;border-radius:8px;display:none;" /></div>
                    </div>
                    <input type="hidden" name="image" class="image-tag" onkeydown="setDefault(this, document.getElementById('MsgIsi1'));" id="TxtIsi1">  
                    <input type="hidden" name="BtnSimpan" id="HiddenBtnSimpan" value=""> 
               </td>
                <td width=50%>
                      <table width="100%" align="center" class="md:pl-6">
                          <tr class="head4">
                                <td width="31%" >Jam Masuk Departemen</td><td width="">:</td>
                                <td width="67%">
                                    <input type="text" id="TxtIsi2Display" class="text7 bg-slate-800 text-slate-100 border border-slate-700 rounded-md px-3 py-2 w-full" value="" readonly disabled />
                                    <input type="hidden" name="jam_masuk" id="TxtIsi2" value="" />
                                    <span id="MsgIsi2" style="color:#CC0000; font-size:10px;"></span>
                                </td>
                          </tr>
                          <tr class="head4">
                              <td width="31%" >Nmr.Kartu</td><td width="">:</td>
                              <td width="67%">
                                <input name="barcode_display" class="text7 bg-slate-800 text-slate-100 border border-slate-700 rounded-md px-3 py-2 w-full" type=password id="TxtIsi3" value="" size="20" maxlength="70" disabled/>
                                <input type="hidden" name="barcode" id="TxtIsi3Hidden" value="" />
                              <span id="MsgIsi3" style="color:#CC0000; font-size:10px;"></span>
                              </td>
                          </tr>
                      </table>
                      <div align="center" class="mt-3"><input id="btnSimpan" name=BtnSimpan type=submit class="button" value="Simpan" onClick="return take_snapshot(event)" disabled/>&nbsp;<input name=BtnKosong type=reset class="button" value="Kosong"/>&nbsp;<button type="button" id="btnEnrollTrigger" class="button" style="background:#f59e0b;">Daftarkan Wajah</button></div><br/>
               </td>
              </tr>
            </table> 
            <!-- Enrollment Modal for registering unknown faces -->
            <div id="enrollModal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.8); z-index:99999; align-items:center; justify-content:center;">
                <div style="background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:12px; width:92%; max-width:420px; padding:16px; color:#e2e8f0; box-shadow:0 20px 40px rgba(0,0,0,0.5);">
                    <div style="font-weight:800; font-size:18px; margin-bottom:10px; color:#60a5fa;">Daftarkan Wajah</div>
                    <div style="font-size:13px; color:#94a3b8; margin-bottom:15px;">Masukkan ID/NIP/NIK pegawai, lalu ambil 3-5 foto dengan posisi wajah stabil.</div>
                    <input type="text" id="enrollLabel" placeholder="ID/NIP/NIK" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.2); background:#0b1220; color:#e2e8f0; margin-bottom:15px; font-size:14px;" />
                    <div id="enrollShots" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:15px; min-height:50px; border:1px dashed rgba(255,255,255,0.2); padding:8px; border-radius:6px;"></div>
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button type="button" id="btnEnrollSnap" class="button" style="background:#10b981;">Ambil Foto</button>
                        <button type="button" id="btnEnrollSave" class="button" disabled>Simpan</button>
                        <button type="button" id="btnEnrollClose" class="button" style="background:#6b7280;">Tutup</button>
                    </div>
                </div>
            </div>
            <?php
                // Build known faces list from presensi/faces/<label>/*.jpg|jpeg|png
                // OPTIMIZED: Only load first 2 images per face for faster initial loading
                $facesBaseDir = __DIR__ . DIRECTORY_SEPARATOR . 'faces';
                $knownFaces = [];
                if (is_dir($facesBaseDir)) {
                    foreach (scandir($facesBaseDir) as $label) {
                        if ($label === '.' || $label === '..') { continue; }
                        $labelDir = $facesBaseDir . DIRECTORY_SEPARATOR . $label;
                        if (!is_dir($labelDir)) { continue; }
                        $images = [];
                        $count = 0;
                        foreach (scandir($labelDir) as $img) {
                            if ($img === '.' || $img === '..') { continue; }
                            if (preg_match('/\.(jpe?g|png)$/i', $img)) {
                                // Web path relative to this PHP file
                                $images[] = 'faces/'. rawurlencode($label) .'/'. rawurlencode($img);
                                $count++;
                                // OPTIMIZATION: Only load first 2 images per face for faster initial loading
                                if ($count >= 2) break;
                            }
                        }
                        if (!empty($images)) {
                            $knownFaces[] = [ 'label' => $label, 'images' => $images ];
                        }
                    }
                }
            ?>
            <script language="JavaScript">
                // OPTIMIZATION: Start with empty faces, load on demand
                window.KNOWN_FACES = [];
                <?php
                    // Build label → {name, nik} map to enrich face labels on overlay
                    $labelNameMap = [];
                    foreach ($knownFaces as $kf) {
                        $lab = isset($kf['label']) ? $kf['label'] : '';
                        if ($lab === '') { continue; }
                        // Try resolve by id, then by nik
                        $res = getOne("select concat(nama,'|',nik) from pegawai where id='".$lab."' limit 1");
                        if (empty($res)) {
                            $res = getOne("select concat(nama,'|',nik) from pegawai where nik='".$lab."' limit 1");
                        }
                        if (!empty($res)) {
                            $parts = explode('|', $res);
                            $labelNameMap[$lab] = [ 'name' => $parts[0], 'nik' => isset($parts[1]) ? $parts[1] : '' ];
                        }
                    }
                ?>
                window.ID_NAME_MAP = <?php echo json_encode($labelNameMap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
                // OPTIMIZATION: Smaller webcam resolution for faster processing
                Webcam.set({
                    width: 320,
                    height: 240,
                    image_format: 'jpeg',
                    jpeg_quality: 70,
                    fps: 15
                });

                Webcam.attach( '#my_camera' );

                // Face detection gating
                var faceModelsLoaded = false;
                var recognitionModelLoaded = false;
                var facesLoaded = false;
                var faceMatcher = null;
                
                // Audio notifications
                (function setupAudioNotifications(){
                    try{
                        var base = 'audio/';
                        var files = {
                            'Jadwal Tidak Sesuai 1': 'Jadwal Anda Tidak Sesuai Jam Masuk Departemen.mp3',
                            'Jadwal Tidak Sesuai 2': 'Jadwal Anda Tidak Sesuai Jam Masuk Departemen2.mp3',
                            'Jadwal Belum Setting 1': 'Jadwal Belum di Setting.mp3',
                            'Jadwal Belum Setting 2': 'Jadwal Belum di Setting2.mp3',
                            'Jadwal Kosong 1': 'Jadwal Hari Ini Kosong.mp3',
                            'Jadwal Kosong 2': 'Jadwal Hari Ini Kosong2.mp3',
                            'Jam Masuk Tidak Sesuai 1': 'Jam Masuk Departemen Tidak Sesuai.mp3',
                            'Jam Masuk Tidak Sesuai 2': 'Jam Masuk Departemen Tidak Sesuai2.mp3',
                            'NIK Tidak Ditemukan': 'NIK Tidak Ditemukan.mp3',
                            'Pilih Jam Masuk': 'PilihJamMasuk.mp3',
                            'Selamat Bekerja': 'SelamatBekerja.mp3',
                            'Terimakasih': 'Terimakasih.mp3',
                            'Notifikasi': 'notifikasi.mp3',
                            'Anda Berhasil Absen Pulang': 'Anda Berhasil Absen Pulang.mp3',
                            'Anda Sudah Absen Pulang Sebelumnya': 'Anda Sudah Absen Pulang Sebelumnya.mp3',
                            'Wajah tidak terdaftar': 'Wajah tidak terdaftar.mp3',
                            'Hanya satu wajah': 'Hanya satu wajah.mp3',
                        };
                        window.AUDIO_MAP = {};
                        Object.keys(files).forEach(function(key){
                            var a = new Audio(base + files[key]);
                            a.preload = 'auto';
                            a.volume = 1.0;
                            window.AUDIO_MAP[key] = a;
                        });
                        window.playAudio = function(key){
                            try{
                                var el = window.AUDIO_MAP && window.AUDIO_MAP[key];
                                if(el){ el.currentTime = 0; el.play().catch(function(){}); }
                            }catch(_e){}
                        };
                    }catch(_e){}
                })();
                
                // Global buildFaceMatcher function
                async function buildFaceMatcher(){
                    var KNOWN_FACES = window.KNOWN_FACES || [];
                    var recognitionThreshold = 0.5;
                    
                    if(!Array.isArray(KNOWN_FACES) || KNOWN_FACES.length === 0){ return null; }
                    if(!recognitionModelLoaded){ 
                        console.log('Recognition model not ready yet, skipping face matcher build');
                        return null; 
                    }
                    var labeled = [];
                    console.log('Building face matcher for', KNOWN_FACES.length, 'faces...');
                    
                    // OPTIMIZATION: Process faces in smaller batches to avoid blocking
                    var batchSize = 3;
                    for(var i=0;i<KNOWN_FACES.length;i+=batchSize){
                        var batch = KNOWN_FACES.slice(i, i+batchSize);
                        var batchPromises = batch.map(async function(item){
                            var descriptors = [];
                            // Process all available images for each face label
                            var maxImages = item.images.length;
                            for(var j=0;j<maxImages;j++){
                                try{
                                    var img = await faceapi.fetchImage(item.images[j]);
                                    var det = await faceapi.detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ 
                                        inputSize: 96, // Smaller input size for faster processing
                                        scoreThreshold: 0.3 
                                    })).withFaceLandmarks().withFaceDescriptor();
                                    if(det && det.descriptor){ 
                                        descriptors.push(det.descriptor); 
                                    }
                                }catch(e){ 
                                    console.log('Error processing image:', item.images[j], e.message);
                                }
                            }
                            if(descriptors.length){
                                return new faceapi.LabeledFaceDescriptors(item.label, descriptors);
                            }
                            return null;
                        });
                        
                        var batchResults = await Promise.all(batchPromises);
                        batchResults.forEach(function(result){
                            if(result) labeled.push(result);
                        });
                        
                        // OPTIMIZATION: Allow UI to update between batches
                        if(i + batchSize < KNOWN_FACES.length){
                            await new Promise(resolve => setTimeout(resolve, 10));
                        }
                    }
                    
                    console.log('Face matcher built with', labeled.length, 'labeled faces');
                    return labeled.length ? new faceapi.FaceMatcher(labeled, recognitionThreshold) : null;
                }
                
                // Global loadFacesOnDemand function
                async function loadFacesOnDemand(){
                    if(facesLoaded) return;
                    try {
                        console.log('Loading faces on demand...');
                        var response = await fetch('load_faces.php');
                        var data = await response.json();
                        if(data.ok && data.faces){
                            window.KNOWN_FACES = data.faces;
                            facesLoaded = true;
                            console.log('Loaded', data.count, 'faces on demand');
                            
                            // Build matcher after loading (only if recognition model is ready)
                            if(!faceMatcher && recognitionModelLoaded){
                                var loadingEl = document.getElementById('loadingIndicator');
                                if(loadingEl) loadingEl.style.display = 'block';
                                
                                buildFaceMatcher().then(function(m){ 
                                    faceMatcher = m; 
                                    console.log('Face matcher ready!');
                                    if(loadingEl) loadingEl.style.display = 'none';
                                }).catch(function(e){
                                    console.error('Face matcher build failed:', e);
                                    if(loadingEl) loadingEl.style.display = 'none';
                                });
                            } else if(!recognitionModelLoaded) {
                                console.log('Recognition model not ready, will build matcher later');
                            }
                        }
                    } catch(e) {
                        console.error('Failed to load faces:', e);
                    }
                }
                var faceDetected = false;
                var faceScoreThreshold = 1; // tingkat keyakinan minimal
                var shiftLocked = false; // kunci jam masuk jika berasal dari jadwal
                var lockedJamValue = ''; // nilai jam yang terkunci (HH:MM:SS)
                var lastShiftFetchId = '';
                var lastShiftFetchAt = 0;
                function canFetchShift(nowTs, currentId){
                    if(currentId !== lastShiftFetchId){ return true; }
                    return (nowTs - lastShiftFetchAt) > 700; // throttle minimal 700ms
                }
                
                // Helper to select first non-empty option
                function selectFirstNonEmptyOption(sel){
                    if(!sel || !sel.options){ return false; }
                    for(var i=0;i<sel.options.length;i++){
                        if((sel.options[i].value||'').trim() !== ''){
                            sel.selectedIndex = i;
                            return true;
                        }
                    }
                    return false;
                }

                // Debounce helper
                function debounce(fn, wait){
                    var t; return function(){
                        var ctx=this, args=arguments; clearTimeout(t);
                        t=setTimeout(function(){ fn.apply(ctx,args); }, wait);
                    };
                }

                // Reset handler: clear fields and unlock auto-fill so detection refills them
                (function attachResetHandler(){
                    var resetBtn = document.querySelector('input[name="BtnKosong"]');
                    if(!resetBtn){ return; }
                    resetBtn.addEventListener('click', function(){
                        // Let native reset run first
                        setTimeout(function(){
                            try{
                                var barcodeInput = document.getElementById('TxtIsi3');
                                var barcodeHidden = document.getElementById('TxtIsi3Hidden');
                                var shiftHidden = document.getElementById('TxtIsi2');
                                var shiftDisplay = document.getElementById('TxtIsi2Display');
                                var msg2 = document.getElementById('MsgIsi2');
                                var previewEl = document.getElementById('snapshotPreview');
                                var btn = document.getElementById('btnSimpan');
                                if(barcodeInput){ barcodeInput.value=''; }
                                if(barcodeHidden){ barcodeHidden.value=''; }
                                if(shiftHidden){ shiftHidden.value=''; }
                                if(shiftDisplay){ shiftDisplay.value=''; }
                                if(msg2){ msg2.textContent=''; }
                                if(previewEl){ previewEl.src=''; previewEl.style.display='none'; }
                                if(btn){ btn.disabled = true; btn.value = 'Simpan'; }
                                // Unlock state so next detection/input can refill
                                shiftLocked = false;
                                lockedJamValue = '';
                                lastAutoFilledId = '';
                            }catch(e){}
                        }, 0);
                    });
                })();
                // Ensure proper positioning for face detection overlay
                (function ensureOverlayStyles(){
                    var css = '#my_camera{position:relative;overflow:hidden} #faceOverlay{position:absolute;left:0;top:0;pointer-events:none;z-index:10}';
                    var st = document.createElement('style'); st.type='text/css'; st.appendChild(document.createTextNode(css));
                    document.head.appendChild(st);
                })();
                (function loadFaceApi(){
                    var s = document.createElement('script');
                    s.src = 'https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js';
                    s.defer = true;
                    s.onload = async function(){
                        try{
                            var base = 'https://justadudewhohacks.github.io/face-api.js/models';
                            console.log('Loading face detection models...');
                            
                            // OPTIMIZATION: Load essential models first, recognition model in background
                            await Promise.all([
                                faceapi.nets.tinyFaceDetector.loadFromUri(base),
                                faceapi.nets.faceLandmark68Net.loadFromUri(base)
                            ]);
                            
                            faceModelsLoaded = true;
                            console.log('Essential models loaded, starting face detection...');
                            startFaceLoop();
                            
                            // Load recognition model in background
                            faceapi.nets.faceRecognitionNet.loadFromUri(base).then(function(){
                                recognitionModelLoaded = true;
                                console.log('Recognition model loaded in background');
                                
                                // Build face matcher if faces are already loaded
                                if(facesLoaded && !faceMatcher){
                                    var loadingEl = document.getElementById('loadingIndicator');
                                    if(loadingEl) loadingEl.style.display = 'block';
                                    
                                    buildFaceMatcher().then(function(m){ 
                                        faceMatcher = m; 
                                        console.log('Face matcher ready!');
                                        if(loadingEl) loadingEl.style.display = 'none';
                                    }).catch(function(e){
                                        console.error('Face matcher build failed:', e);
                                        if(loadingEl) loadingEl.style.display = 'none';
                                    });
                                }
                            }).catch(function(e){
                                console.error('Recognition model failed to load:', e);
                            });
                        }catch(e){
                            console.error('FaceAPI load error', e);
                        }
                    };
                    document.head.appendChild(s);
                })();

                function startFaceLoop(){
                    // Known faces prepared from server (optional). Put sample faces at presensi/faces/<label>/*.jpeg
                    var KNOWN_FACES = window.KNOWN_FACES || [];
                    var recognitionThreshold = 0.2; // lower is stricter
                    var unknownState = false;
                    var lastFaceCount = -1; // Track face count changes for canvas optimization
                    var lastRecognitionTime = 0; // Throttle recognition calls
                    var recognitionInterval = 5000; // Only do full recognition every 2 seconds


                    var btn = document.getElementById('btnSimpan');
                    var tryGetVideo = function(){
                        var v = document.querySelector('#my_camera video');
                        if(!v){ setTimeout(tryGetVideo, 300); return; }
                        // OPTIMIZATION: Use smaller input size for faster detection
                        var options = new faceapi.TinyFaceDetectorOptions({ inputSize: 96, scoreThreshold: 0.2 });
                        // buat canvas overlay
                        var container = document.getElementById('my_camera');
                        var canvas = document.createElement('canvas');
                        canvas.id = 'faceOverlay';
                        canvas.style.position = 'absolute';
                        canvas.style.left = '0';
                        canvas.style.top = '0';
                        canvas.style.pointerEvents = 'none';
                        canvas.style.zIndex = '10';
                        container.style.position = 'relative';
                        container.appendChild(canvas);
                        var ctx = canvas.getContext('2d');

                        function resizeCanvas(){
                            // Get actual video dimensions
                            var videoRect = v.getBoundingClientRect();
                            var containerRect = container.getBoundingClientRect();
                            
                            // OPTIMIZATION: Limit canvas size for better performance
                            canvas.width = Math.min(v.clientWidth || 370, 480);
                            canvas.height = Math.min(v.clientHeight || 300, 360);
                            canvas.style.width = canvas.width + 'px';
                            canvas.style.height = canvas.height + 'px';
                        }
                        
                        // Wait for video to load and get proper dimensions
                        v.addEventListener('loadedmetadata', function() {
                            resizeCanvas();
                        });
                        
                        resizeCanvas();
                        window.addEventListener('resize', resizeCanvas);

                        // OPTIMIZATION: Start lazy loading faces in background
                        console.log('Starting lazy face loading...');
                        loadFacesOnDemand();

                        // Enrollment modal controls
                        var modal = document.getElementById('enrollModal');
                        var btnSnap = document.getElementById('btnEnrollSnap');
                        var btnSave = document.getElementById('btnEnrollSave');
                        var btnClose = document.getElementById('btnEnrollClose');
                        var shotsWrap = document.getElementById('enrollShots');
                        var inputLabel = document.getElementById('enrollLabel');
                        var pendingShots = [];

                        function openEnroll(){ 
                            console.log('Opening enrollment modal...', modal);
                            if(modal){ 
                                modal.style.display = 'flex'; 
                                console.log('Modal opened');
                            } else {
                                console.error('Modal element not found');
                            }
                        }
                        function closeEnroll(){ 
                            if(modal){ modal.style.display = 'none'; } 
                            pendingShots = []; 
                            if(shotsWrap){ shotsWrap.innerHTML = ''; } 
                            if(btnSave){ btnSave.disabled = true; } 
                        }
                        if(btnClose){ btnClose.onclick = closeEnroll; }
                        if(btnSnap){
                            btnSnap.onclick = function(){
                                Webcam.snap(function(data_uri){
                                    pendingShots.push(data_uri);
                                    var img = new Image(); img.src = data_uri; img.style.width='60px'; img.style.height='45px'; img.style.objectFit='cover'; img.style.borderRadius='6px';
                                    if(shotsWrap){ shotsWrap.appendChild(img); }
                                    if(btnSave){ btnSave.disabled = pendingShots.length < 3; }
                                });
                            };
                        }
                        if(btnSave){
                            btnSave.onclick = async function(){
                                var label = (inputLabel && inputLabel.value ? inputLabel.value : '').trim();
                                if(label==='' || pendingShots.length<3){ 
                                    alert('Masukkan ID dan ambil minimal 3 foto');
                                    return; 
                                }
                                
                                // Disable button during submission
                                btnSave.disabled = true;
                                btnSave.value = 'Menyimpan...';
                                
                                try{
                                    console.log('Submitting enrollment data:', {label: label, imageCount: pendingShots.length});
                                    
                                    // Create form data
                                    var formData = new FormData();
                                    formData.append('enroll', '1');
                                    formData.append('label', label);
                                    
                                    // Add images as individual form fields
                                    pendingShots.forEach(function(img, index) {
                                        formData.append('images[]', img);
                                    });
                                    
                                    var resp = await fetch('enroll_face.php', { 
                                        method: 'POST', 
                                        body: formData,
                                        cache: 'no-cache'
                                    });
                                    
                                    console.log('Response status:', resp.status);
                                    console.log('Response headers:', resp.headers);
                                    
                                    // Get response text first to debug
                                    var responseText = await resp.text();
                                    console.log('Raw response length:', responseText.length);
                                    console.log('Raw response (first 500 chars):', responseText.substring(0, 500));
                                    
                                    if(resp.ok) {
                                        try {
                                            var json = JSON.parse(responseText);
                                            console.log('Parsed JSON:', json);
                                            if(json && json.ok){
                                                alert('Wajah berhasil didaftarkan! Silakan refresh halaman secara manual untuk melihat wajah yang baru didaftarkan.');
                                            } else {
                                                alert('Error: ' + (json.message || 'Gagal menyimpan wajah'));
                                            }
                                        } catch (parseError) {
                                            console.error('JSON parse error:', parseError);
                                            console.error('Response was:', responseText);
                                            alert('Error: Server returned invalid JSON. Check console for details.');
                                        }
                                    } else {
                                        alert('Error: Server returned ' + resp.status + '\nResponse: ' + responseText.substring(0, 200));
                                    }
                                }catch(e){
                                    console.error('Enrollment error:', e);
                                    alert('Error: ' + e.message);
                                } finally {
                                    // Re-enable button
                                    btnSave.disabled = false;
                                    btnSave.value = 'Simpan';
                                }
                            };
                        }
                        
                        // Manual enroll trigger button
                        var btnEnrollTrigger = document.getElementById('btnEnrollTrigger');
                        if(btnEnrollTrigger){
                            btnEnrollTrigger.onclick = function(){
                                openEnroll();
                            };
                        }
                        
                        // Auto-fill form function
                        var lastAutoFilledId = '';
                        function autoFillForm(faceId){
                            if(faceId && faceId !== lastAutoFilledId){
                                lastAutoFilledId = faceId;
                                console.log('Auto-filling form for face ID:', faceId);
                                
                                // Show notification with name if available
                                (function(){
                                    var display = faceId;
                                    try{
                                        if(window.ID_NAME_MAP && window.ID_NAME_MAP[faceId]){
                                            var m = window.ID_NAME_MAP[faceId];
                                            var bracket = (m.nik && m.nik !== '') ? m.nik : faceId;
                                            display = m.name + ' [' + bracket + ']';
                                        }
                                    }catch(_e){}
                                    showNotification('Wajah dikenali: ' + display, 'success');
                                })();
                                
                                // Fill barcode field
                                var barcodeInput = document.getElementById('TxtIsi3');
                                if(barcodeInput){
                                    barcodeInput.value = faceId;
                                    barcodeInput.style.backgroundColor = '#10b981';
                                    barcodeInput.style.color = '#ffffff';
                                    barcodeInput.style.border = '2px solid #059669';
                                    
                                    // Reset color after 3 seconds
                                    setTimeout(function(){
                                        barcodeInput.style.backgroundColor = '';
                                        barcodeInput.style.color = '';
                                        barcodeInput.style.border = '';
                                    }, 3000);
                                }
                                
                                // Try to get default shift for this employee
                                getEmployeeShift(faceId);
                            }
                        }
                        
                        // Show notification function
                        function showNotification(message, type){
                            // Remove existing notification
                            var existing = document.getElementById('faceNotification');
                            if(existing){
                                existing.remove();
                            }
                            
                            var notification = document.createElement('div');
                            notification.id = 'faceNotification';
                            notification.style.cssText = `
                                position: fixed;
                                top: 20px;
                                right: 20px;
                                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                                color: white;
                                padding: 12px 20px;
                                border-radius: 8px;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                                z-index: 10000;
                                font-weight: bold;
                                animation: slideIn 0.3s ease-out;
                            `;
                            notification.textContent = message;
                            
                            // Add CSS animation
                            if(!document.getElementById('notificationCSS')){
                                var style = document.createElement('style');
                                style.id = 'notificationCSS';
                                style.textContent = `
                                    @keyframes slideIn {
                                        from { transform: translateX(100%); opacity: 0; }
                                        to { transform: translateX(0); opacity: 1; }
                                    }
                                `;
                                document.head.appendChild(style);
                            }
                            
                            document.body.appendChild(notification);
                            
                            // Auto remove after 3 seconds
                            setTimeout(function(){
                                if(notification.parentNode){
                                    notification.style.animation = 'slideIn 0.3s ease-out reverse';
                                    setTimeout(function(){
                                        notification.remove();
                                    }, 300);
                                }
                            }, 3000);
                        }
                        
                        // Get employee shift information
                        function getEmployeeShift(employeeId){
                            var shiftHidden = document.getElementById('TxtIsi2');
                            var shiftDisplay = document.getElementById('TxtIsi2Display');
                            if(!shiftHidden || !shiftDisplay){ return; }
                            console.log('Fetching scheduled shift for ID:', employeeId);
                            fetch('get_shift.php?barcode=' + encodeURIComponent(employeeId), { cache: 'no-cache' })
                                .then(function(resp){ return resp.json(); })
                                .then(function(data){
                                    console.log('getShift response:', data);
                                    if(!data || !data.ok){
                                        // No schedule: clear display
                                        shiftHidden.value = '';
                                        shiftDisplay.value = '';
                                        playAudio && playAudio('Jadwal Belum Setting 1');
                                        return;
                                    }
                                    var jam = (data.jam_masuk || '').trim();
                                    if(!jam){
                                        shiftHidden.value = '';
                                        shiftDisplay.value = '';
                                        playAudio && playAudio('Jadwal Kosong 1');
                                        return;
                                    }
                                    // Set hidden + display
                                    shiftHidden.value = jam;
                                    shiftDisplay.value = jam;
                                    
                                    // Lock the shift to prevent user changes
                                    shiftLocked = true;
                                    lockedJamValue = jam; // keep HH:MM:SS
                                    var msg = document.getElementById('MsgIsi2');
                                    if(msg){ msg.textContent = 'Jam masuk dikunci berdasarkan jadwal.'; }
                                    
                                    // Visual feedback
                                    shiftDisplay.style.backgroundColor = '#10b981';
                                    shiftDisplay.style.color = '#ffffff';
                                    setTimeout(function(){
                                        shiftDisplay.style.backgroundColor = '';
                                        shiftDisplay.style.color = '';
                                    }, 2000);
                                    // Play success guidance
                                    playAudio && playAudio('Notifikasi');
                                })
                                .catch(function(err){ console.error('getShift error:', err); });
                        }

                        function firstNonEmptyIndex(selectEl){
                            if(!selectEl) return -1;
                            for(var i=0;i<selectEl.options.length;i++){
                                var v = (selectEl.options[i].value||'').trim();
                                if(v !== '') return i;
                            }
                            return -1;
                        }

                        // Auto-trigger shift fill when barcode changes (manual input or scanner)
                        (function attachBarcodeAutoShift(){
                            var barcodeInput = document.getElementById('TxtIsi3');
                            if(!barcodeInput){ return; }
                            var trigger = debounce(function(){
                                var idVal = (barcodeInput.value||'').trim();
                                var shiftSelect = document.getElementById('TxtIsi2');
                                if(!idVal || !shiftSelect){ return; }
                                if((shiftSelect.value||'').trim() === '' || !shiftLocked){
                                    // Allow overwrite
                                    shiftLocked = false;
                                    getEmployeeShift(idVal);
                                }
                            }, 400);
                            barcodeInput.addEventListener('input', trigger);
                            barcodeInput.addEventListener('change', trigger);
                            barcodeInput.addEventListener('blur', trigger);
                        })();

                        // Initial auto-fill on page load if barcode already present or to preselect
                        (function initialAutoFill(){
                            var barcodeInput = document.getElementById('TxtIsi3');
                            if(barcodeInput && (barcodeInput.value||'').trim() !== ''){
                                getEmployeeShift(barcodeInput.value.trim());
                            }
                        })();

                        // Re-populate shift when user clears/selects empty option
                        (function attachShiftAutoRefill(){
                            // Display field is readonly, no change handler needed
                        })();

                        // OPTIMIZATION: Use faster interval for basic detection, slower for recognition
                        setInterval(async function(){
                            try{
                                // OPTIMIZATION: Start with basic detection only, add recognition later
                                var results = await faceapi.detectAllFaces(v, options);
                                var faceCount = results ? results.length : 0;
                                // Always clear canvas each frame to prevent stacked/overlapping boxes
                                ctx.clearRect(0,0,canvas.width,canvas.height);
                                
                                // OPTIMIZATION: Only do heavy recognition if face matcher is ready and we have faces
                                // Throttle recognition calls to improve performance
                                var now = Date.now();
                                if(faceMatcher && recognitionModelLoaded && faceCount > 0 && (now - lastRecognitionTime) > recognitionInterval){
                                    try {
                                        results = await faceapi
                                            .detectAllFaces(v, options)
                                            .withFaceLandmarks()
                                            .withFaceDescriptors();
                                        lastRecognitionTime = now;
                                    } catch(e) {
                                        console.error('Recognition error:', e);
                                        // Fallback to basic detection if recognition fails
                                        results = await faceapi.detectAllFaces(v, options);
                                    }
                                }
                                
                                // Check face count conditions
                                if(faceCount === 0){
                                    faceDetected = false;
                                    if(btn){ 
                                        btn.disabled = true; 
                                        btn.value = 'Arahkan Wajah ke Kamera'; 
                                    }
                                }else if(faceCount === 1){
                                    faceDetected = true;
                                    if(btn){ 
                                        btn.disabled = false; 
                                        btn.value = 'Simpan'; 
                                    }
                                }else{
                                    // Multiple faces detected
                                    faceDetected = false;
                                    if(btn){ 
                                        btn.disabled = true; 
                                        btn.value = 'Hanya Satu Wajah Diperbolehkan'; 
                                    }
                                    // Play audio once when state changes to multiple faces
                                    if(typeof playAudio === 'function' && lastFaceCount !== faceCount){
                                        try{ playAudio('Hanya satu wajah'); }catch(_e){}
                                    }
                                }
                                
                                // Render face boxes whenever faces are detected
                                if(faceCount > 0){
                                    // Calculate scale factors
                                    var scaleX = canvas.width / v.videoWidth;
                                    var scaleY = canvas.height / v.videoHeight;
                                    
                                    results.forEach(function(res, index){
                                        // Support both shapes: FaceDetection (res.box) and WithFaceDescriptor (res.detection.box)
                                        var rawBox = (res && res.detection && res.detection.box) ? res.detection.box : (res && res.box ? res.box : null);
                                        if(!rawBox){ return; }
                                        // Scale coordinates to match canvas size
                                        var scaledX = rawBox.x * scaleX;
                                        var scaledY = rawBox.y * scaleY;
                                        var scaledWidth = rawBox.width * scaleX;
                                        var scaledHeight = rawBox.height * scaleY;
                                        
                                        // Different colors for single vs multiple faces
                                        var strokeColor, fillColor;
                                        if(faceCount === 1){
                                            strokeColor = 'rgba(0,255,0,0.9)'; // Green for single face
                                            fillColor = 'rgba(0,255,0,0.9)';
                                        }else{
                                            strokeColor = 'rgba(255,0,0,0.9)'; // Red for multiple faces
                                            fillColor = 'rgba(255,0,0,0.9)';
                                        }
                                        
                                        // Draw bounding box
                                        ctx.strokeStyle = strokeColor;
                                        ctx.lineWidth = 3;
                                        ctx.strokeRect(scaledX, scaledY, scaledWidth, scaledHeight);
                                        
                                        // Add corner markers for better visibility
                                        var cornerSize = 8;
                                        ctx.fillStyle = fillColor;
                                        // Top-left corner
                                        ctx.fillRect(scaledX - cornerSize/2, scaledY - cornerSize/2, cornerSize, cornerSize);
                                        // Top-right corner
                                        ctx.fillRect(scaledX + scaledWidth - cornerSize/2, scaledY - cornerSize/2, cornerSize, cornerSize);
                                        // Bottom-left corner
                                        ctx.fillRect(scaledX - cornerSize/2, scaledY + scaledHeight - cornerSize/2, cornerSize, cornerSize);
                                        // Bottom-right corner
                                        ctx.fillRect(scaledX + scaledWidth - cornerSize/2, scaledY + scaledHeight - cornerSize/2, cornerSize, cornerSize);
                                        
                                        // Recognition label (if available)
                                        var labelText = '';
                                        var detectedFaceId = '';
                                        // OPTIMIZATION: Only do recognition if face matcher is ready and we have descriptor
                                        if(faceMatcher && recognitionModelLoaded && res.descriptor){
                                            try {
                                                var best = faceMatcher.findBestMatch(res.descriptor);
                                                var pretty = '';
                                                if(best && best.label){
                                                    var mapped = (window.ID_NAME_MAP && window.ID_NAME_MAP[best.label]) ? window.ID_NAME_MAP[best.label] : null;
                                                    var score = (typeof best.distance === 'number') ? best.distance.toFixed(2) : '';
                                                    if(mapped){
                                                        // Show: Nama [NIK] (score)
                                                        var bracket = mapped.nik && mapped.nik !== '' ? mapped.nik : best.label;
                                                        pretty = mapped.name + ' [' + bracket + ']' + (score ? (' ('+score+')') : '');
                                                    }else{
                                                        pretty = best.label + (score ? (' ('+score+')') : '');
                                                    }
                                                }
                                                labelText = pretty || (best ? best.toString() : '');
                                                detectedFaceId = (best && best.label && best.label !== 'unknown') ? best.label : '';
                                            } catch(e) {
                                                console.error('Face recognition error:', e);
                                                labelText = 'Recognition error';
                                            }
                                        } else if(!recognitionModelLoaded) {
                                            // Show loading message when recognition model is not ready
                                            labelText = 'Loading recognition...';
                                        } else if(!faceMatcher) {
                                            // Show loading message when face matcher is not ready
                                            labelText = 'Building face database...';
                                        }
                                            
                                        // Auto-fill form if face is recognized
                                        if(detectedFaceId && detectedFaceId !== 'unknown'){
                                            autoFillForm(detectedFaceId);
                                        }
                                        if(labelText){
                                            ctx.fillStyle = 'rgba(0,0,0,0.7)';
                                            var text = labelText;
                                            ctx.font = 'bold 12px Arial';
                                            var tw = ctx.measureText(text).width + 10;
                                            ctx.fillRect(scaledX, Math.max(0, scaledY - 18), tw, 18);
                                            ctx.fillStyle = '#FFFFFF';
                                            ctx.fillText(text, scaledX + 5, Math.max(12, scaledY - 4));
                                        }
                                        // Add face number for multiple faces
                                        if(faceCount > 1){
                                            ctx.fillStyle = 'rgba(255,255,255,0.9)';
                                            ctx.font = 'bold 12px Arial';
                                            ctx.fillText((index + 1).toString(), scaledX + 5, scaledY + 15);
                                        }
                                    });
                                    
                                    // Status text based on face count
                                    if(faceCount === 1){
                                        // If matcher exists, gate to known faces only
                                        var isKnown = true;
                                        if(faceMatcher && recognitionModelLoaded && results[0] && results[0].descriptor){
                                            try {
                                                var best0 = faceMatcher.findBestMatch(results[0].descriptor);
                                                isKnown = (best0.label !== 'unknown');
                                                console.log('Face recognition result:', best0.label, 'isKnown:', isKnown);
                                            } catch(e) {
                                                console.error('Face recognition error in status check:', e);
                                                isKnown = false;
                                            }
                                            if(!isKnown){
                                                if(btn){ btn.disabled = true; btn.value = 'Wajah Tidak Dikenali'; }
                                                // Do NOT auto-open modal; show notification only (button is always visible)
                                                showNotification('⚠ Wajah tidak terdaftar. Silakan daftarkan wajah Anda.', 'error');
                                                // Play unknown face warning
                                                if(typeof playAudio === 'function'){ playAudio('Wajah tidak terdaftar'); }
                                            }
                                            // Auto-fill jam masuk segera saat wajah dikenal
                                            if(isKnown){
                                                var shiftSelect = document.getElementById('TxtIsi2');
                                                var barcodeInput = document.getElementById('TxtIsi3');
                                                var barcodeHidden = document.getElementById('TxtIsi3Hidden');
                                                // Ensure barcode reflects recognized face id
                                                var recognizedId = best0.label || '';
                                                if(barcodeInput && recognizedId && recognizedId !== 'unknown'){
                                                    // Set barcode and emit input event so listeners react
                                                    barcodeInput.value = recognizedId;
                                                    try{ barcodeInput.dispatchEvent(new Event('input', { bubbles:true })); }catch(e){}
                                                }
                                                if(barcodeHidden && recognizedId && recognizedId !== 'unknown'){
                                                    barcodeHidden.value = recognizedId;
                                                }
                                                var currentId = (barcodeHidden && barcodeHidden.value) ? barcodeHidden.value.trim() : ((barcodeInput && barcodeInput.value) ? barcodeInput.value.trim() : '');
                                                var nowTs = Date.now();
                                                var needFetch = false;
                                                var shiftHidden = document.getElementById('TxtIsi2');
                                                var currentVal = (shiftHidden && shiftHidden.value) ? shiftHidden.value.trim() : '';
                                                if(!currentVal || !shiftLocked){ needFetch = true; }
                                                if(needFetch && currentId && canFetchShift(nowTs, currentId)){
                                                    shiftLocked = false; // allow overwrite
                                                    lastShiftFetchId = currentId;
                                                    lastShiftFetchAt = nowTs;
                                                    getEmployeeShift(currentId);
                                                }
                                            }
                                        }
                                        // OPTIMIZATION: Only render single face status when face count changes
                                        if(lastFaceCount !== faceCount){
                                            ctx.fillStyle = 'rgba(0,0,0,0.7)';
                                            var statusText = '';
                                            var statusColor = '#00FF00';
                                            
                                            if(!recognitionModelLoaded) {
                                                statusText = 'Loading recognition...';
                                                statusColor = '#FFCC00';
                                            } else if(!faceMatcher) {
                                                statusText = 'Building face database...';
                                                statusColor = '#FFCC00';
                                            } else if(isKnown) {
                                                statusText = '✓ Wajah terdeteksi';
                                                statusColor = '#00FF00';
                                            } else {
                                                statusText = '⚠ Daftarkan wajah Anda (tidak terdaftar)';
                                                statusColor = '#FFA500';
                                            }
                                            
                                            ctx.fillRect(8, 8, statusText.length * 8 + 16, 32);
                                            ctx.fillStyle = statusColor;
                                            ctx.font = 'bold 14px Arial';
                                            ctx.fillText(statusText, 16, 28);
                                        }
                                    }else if(lastFaceCount !== faceCount){
                                        // OPTIMIZATION: Only render multiple faces warning when face count changes
                                        // Multiple faces warning
                                        ctx.fillStyle = 'rgba(0,0,0,0.8)';
                                        ctx.fillRect(8, 8, 300, 40);
                                        ctx.fillStyle = '#FF0000';
                                    ctx.font = 'bold 14px Arial';
                                        ctx.fillText('⚠ Terdeteksi ' + faceCount + ' wajah!', 16, 25);
                                        ctx.fillText('Hanya satu wajah diperbolehkan', 16, 42);
                                    }
                                }else if(lastFaceCount !== faceCount){
                                    // OPTIMIZATION: Only render status text when face count changes
                                    // Status text when no face detected
                                    ctx.fillStyle = 'rgba(0,0,0,0.7)';
                                    ctx.fillRect(8, 8, 250, 32);
                                    ctx.fillStyle = '#FFCC00';
                                    ctx.font = 'bold 14px Arial';
                                    ctx.fillText('⚠ Arahkan wajah ke kamera', 16, 28);
                                }
                                // Update last face count at the end of the frame
                                lastFaceCount = faceCount;
                            }catch(err){
                                console.debug('Face detection error:', err);
                            }
                        }, 100); // OPTIMIZATION: Faster interval for better responsiveness
                    };
                    tryGetVideo();
                }

                async function take_snapshot(e) {
                    if(e && e.preventDefault){ e.preventDefault(); }
                    if(!faceModelsLoaded || !faceDetected){
                        alert('Pastikan wajah terlihat jelas di kamera sebelum menyimpan.');
                        return false;
                    }
                    
                    // Additional check for multiple faces
                    var btn = document.getElementById('btnSimpan');
                    if(btn && btn.value === 'Hanya Satu Wajah Diperbolehkan'){
                        alert('Terdeteksi lebih dari satu wajah! Hanya satu orang yang diperbolehkan melakukan presensi pada satu waktu.');
                        return false;
                    }
                    if(btn && btn.value === 'Wajah Tidak Dikenali'){
                        alert('Wajah tidak dikenali. Pastikan sudah terdaftar di sistem.');
                        return false;
                    }
                    
                    // Ensure jam_masuk is populated automatically before confirm
                    try{
                        var hiddenJam = document.getElementById('TxtIsi2');
                        var barcodeInput = document.getElementById('TxtIsi3');
                        if(hiddenJam && (!hiddenJam.value || hiddenJam.value.trim()==='') && barcodeInput && barcodeInput.value.trim()!==''){
                            // Attempt fetch once
                            await new Promise(function(resolve){
                                getEmployeeShift(barcodeInput.value.trim());
                                setTimeout(resolve, 500); // brief wait for async fill
                            });
                        }
                    }catch(_e){}

                    // Show confirmation dialog
                    var barcodeValue = document.getElementById('TxtIsi3').value;
                    var barcodeHiddenEl = document.getElementById('TxtIsi3Hidden');
                    var barcodeKey = (barcodeHiddenEl && barcodeHiddenEl.value) ? barcodeHiddenEl.value : barcodeValue;
                    var barcodeWithName = barcodeValue;
                    try{
                        if(window.ID_NAME_MAP && window.ID_NAME_MAP[barcodeKey]){
                            var m = window.ID_NAME_MAP[barcodeKey];
                            var bracket = (m.nik && m.nik !== '') ? m.nik : barcodeKey;
                            barcodeWithName = m.name + ' [' + bracket + ']';
                        }
                    }catch(_e){}
                    var shiftValue = document.getElementById('TxtIsi2').value;
                    if(!shiftValue || shiftValue.trim()===''){
                        alert('Jam Masuk belum otomatis terisi dari jadwal. Pastikan ID/Barcode benar dan ada jadwal hari ini.');
                        return false;
                    }
                    var currentTime = new Date().toLocaleTimeString();
                    
                    var confirmMessage = 'Konfirmasi Presensi:\n\n';
                    confirmMessage += 'ID Pegawai: ' + barcodeWithName + '\n';
                    confirmMessage += 'Jam Masuk: ' + shiftValue + '\n';
                    confirmMessage += 'Waktu Sekarang: ' + currentTime + '\n\n';
                    confirmMessage += 'Apakah Anda yakin ingin menyimpan absen?';
                    
                    if(!confirm(confirmMessage)){
                        return false;
                    }
                    
                    var form = document.getElementById('frmPresensi');
                    var previewEl = document.getElementById('snapshotPreview');
                    Webcam.snap(function(data_uri){
                        $(".image-tag").val(data_uri);
                        if(previewEl){ previewEl.src = data_uri; previewEl.style.display = 'block'; }
                        var hiddenBtn = document.getElementById('HiddenBtnSimpan');
                        if(hiddenBtn){ hiddenBtn.value = '1'; }
                        
                        // Submit via AJAX to prevent page refresh
                        if(form){
                            var formData = new FormData(form);
                            fetch(window.location.href, {
                                method: 'POST',
                                body: formData,
                                cache: 'no-cache'
                            }).then(function(response) {
                                return response.text();
                            }).then(function(html) {
                                // Parse the response and extract the message
                                var parser = new DOMParser();
                                var doc = parser.parseFromString(html, 'text/html');
                                var messageDiv = doc.querySelector('div[style*="background:#10b981"], div[style*="background:#3b82f6"], div[style*="background:#f59e0b"]');
                                
                                if(messageDiv) {
                                    // Insert message into current page
                                    var container = document.querySelector('#post .entry');
                                    if(container) {
                                        container.insertBefore(messageDiv, container.firstChild);
                                    }
                                    // Play audio cues depending on success type
                                    try {
                                        if(typeof playAudio === 'function'){
                                            var txt = (messageDiv.textContent||'').toLowerCase();
                                            if(txt.indexOf('presensi pulang berhasil') !== -1){
                                                playAudio('Anda Berhasil Absen Pulang');
                                            } else if(txt.indexOf('presensi masuk berhasil') !== -1){
                                                playAudio('Selamat Bekerja');
                                            } else {
                                                playAudio('Anda Sudah Absen Pulang Sebelumnya');
                                            }
                                        }
                                    } catch(_e){}
                                    
                                    // Clear form fields after successful submission
                                    setTimeout(function(){
                                        // Clear form fields only (keep camera and detection running)
                                        var barcodeInput = document.getElementById('TxtIsi3');
                                        var barcodeHidden = document.getElementById('TxtIsi3Hidden');
                                        var shiftHidden = document.getElementById('TxtIsi2');
                                        var shiftDisplay = document.getElementById('TxtIsi2Display');
                                        var msg2 = document.getElementById('MsgIsi2');
                                        var previewEl = document.getElementById('snapshotPreview');
                                        var btn = document.getElementById('btnSimpan');
                                        
                                        if(barcodeInput) barcodeInput.value = '';
                                        if(barcodeHidden) barcodeHidden.value = '';
                                        if(shiftHidden) shiftHidden.value = '';
                                        if(shiftDisplay) shiftDisplay.value = '';
                                        if(msg2) msg2.textContent = '';
                                        if(previewEl) { previewEl.src = ''; previewEl.style.display = 'none'; }
                                        if(btn) { btn.disabled = true; btn.value = 'Simpan'; }
                                        
                                        // Reset state variables
                                        if(typeof shiftLocked !== 'undefined') shiftLocked = false;
                                        if(typeof lockedJamValue !== 'undefined') lockedJamValue = '';
                                        if(typeof lastAutoFilledId !== 'undefined') lastAutoFilledId = '';
                                        
                                        // Reset face detection state
                                        if(typeof faceDetected !== 'undefined') faceDetected = false;
                                        if(typeof lastFaceCount !== 'undefined') lastFaceCount = -1;
                                        
                                        // Clear canvas overlay
                                        var canvas = document.getElementById('faceOverlay');
                                        if(canvas) {
                                            var ctx = canvas.getContext('2d');
                                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                                        }
                                        
                                        // Remove message after 5 seconds
                                        if(messageDiv.parentNode) {
                                            setTimeout(function(){ messageDiv.remove(); }, 5000);
                                        }
                                    }, 2000);
                                }
                            }).catch(function(error) {
                                console.error('Submission error:', error);
                                alert('Terjadi kesalahan saat menyimpan presensi. Silakan coba lagi.');
                            });
                        }
                    });
                    return false;
                }
            </script>
             <!-- Blokir F5 dan Ctrl/Cmd+R agar tidak dapat refresh halaman -->
            <script language="JavaScript">
                // Prevent refresh (F5, Ctrl+R / Cmd+R) and disable right-click
                (function preventRefreshAndContextMenu(){
                    try{
                        document.addEventListener('keydown', function(e){
                            var key = (e.key||'').toLowerCase();
                            if(key === 'f5' || (e.ctrlKey && key === 'r') || (e.metaKey && key === 'r')){
                                e.preventDefault();
                                e.stopPropagation();
                                return false;
                            }
                        }, true);
                        document.addEventListener('contextmenu', function(e){ e.preventDefault(); }, true);
                        // Warn on unload/reload attempts
                        window.addEventListener('beforeunload', function(e){
                            e.preventDefault();
                            e.returnValue = '';
                        });
                        // Try to neutralize browser back/forward accidental reloads
                        if(window.history && window.history.pushState){
                            window.history.pushState(null, document.title, window.location.href);
                            window.addEventListener('popstate', function(){
                                window.history.pushState(null, document.title, window.location.href);
                            });
                        }
                    }catch(_e){}
                })();
            </script>
            <!-- simpan presensi -->
            <?php
                $BtnSimpan=isset($_POST['BtnSimpan'])?$_POST['BtnSimpan']:NULL;
                if (isset($BtnSimpan)) {
                    $jam_masuk      = validTeks3(trim($_POST['jam_masuk']));  
                    $barcode        = validTeks(trim($_POST['barcode']));
                    
                    $_sqlbar        = "select id from barcode where barcode='$barcode'";
                    $hasilbar       = bukaquery($_sqlbar);
                    @$barisbar      = mysqli_fetch_array($hasilbar);  
                    @$idpeg         = $barisbar["id"];
                    
                    $_sqljamdatang  = "select jam_jaga.shift,CURRENT_DATE() as hariini,pegawai.departemen from jam_jaga inner join pegawai on pegawai.departemen=jam_jaga.dep_id 
                                       where jam_jaga.jam_masuk='$jam_masuk' and pegawai.id='$idpeg'";
                    $hasiljamdatang = bukaquery($_sqljamdatang);
                    @$barisjamdatang = mysqli_fetch_array($hasiljamdatang);  
                    @$shift          = $barisjamdatang["shift"];
                    @$hariini        = $barisjamdatang["hariini"];
                    @$departemen     = $barisjamdatang["departemen"];
                    
                    $_sqlketerlambatan = "select * from set_keterlambatan";
                    $hasilketerlmabatan=  bukaquery($_sqlketerlambatan);
                    @$barisketerlambatan=  mysqli_fetch_array($hasilketerlmabatan);
                    @$toleransi      = $barisketerlambatan[0];
                    @$terlambat1     = $barisketerlambatan[1];
                    @$terlambat2     = $barisketerlambatan[2];
                    
                    // Build photo storage path: foto/<tahun>/<bulan>/<nik>/
                    @$nikpegRaw      = getOne("select nik from pegawai where id='".$idpeg."'");
                    @$nikpeg         = preg_replace('/[^a-zA-Z0-9_-]/','_', (string)$nikpegRaw);
                    if(empty($nikpeg)){ $nikpeg = 'ID'.$idpeg; }
                    @$tahunNow       = date('Y');
                    @$bulanNow       = date('m');
                    // Build desired directory
                    @$desiredDir     = __DIR__ . DIRECTORY_SEPARATOR . 'foto' . DIRECTORY_SEPARATOR . $tahunNow . DIRECTORY_SEPARATOR . $bulanNow . DIRECTORY_SEPARATOR . $nikpeg;
                    // Ensure directory exists (try 0777 for broad write permission)
                    if(!is_dir($desiredDir)){
                        @mkdir($desiredDir, 0775, true);
                    }
                    if(!is_dir($desiredDir)){
                        @error_log('PRESENSI: mkdir failed for '.$desiredDir);
                    }
                    // Use intended foto directory strictly (no fallback to current directory)
                    @$baseDirAbs     = $desiredDir;
                    @$filenameOnly   = $hariini.$shift.$idpeg.".jpeg";
                    @$absPath        = $baseDirAbs . DIRECTORY_SEPARATOR . $filenameOnly;
                    // Clean existing file with same name
                    if(file_exists($absPath)){
                        @unlink($absPath);
                    }
                    
                    @$img            = $_POST["image"];
                    @$image_parts    = explode(";base64,", $img);
                    @$image_type_aux = explode("image/", $image_parts[0]);
                    @$image_type     = $image_type_aux[1];
                    @$image_base64   = base64_decode($image_parts[1]);
                    // Save and set relative path to store in DB (with fallback)
                    @$relativeDir    = 'foto/'.$tahunNow.'/'.$bulanNow.'/'.$nikpeg;
                    @$file           = $relativeDir.'/'.$filenameOnly;
                    // Validate base64 buffer
                    if(!is_string($image_base64) || strlen($image_base64)===0){
                        @error_log('PRESENSI: Empty image buffer for id='.$idpeg.' file='.$absPath);
                    }
                    @$saved          = (is_string($image_base64) && strlen($image_base64)>0) ? @file_put_contents($absPath, $image_base64, LOCK_EX) : false;
                    if($saved!==false){ @error_log('PRESENSI: Saved photo '.$absPath.' bytes='.$saved); }
                    // If save failed, keep logging but do not fallback elsewhere
                    if($saved === false || !file_exists($absPath)){
                        @error_log('PRESENSI: Save FAILED at '.$absPath);
                    }
                    
                    //echo "Jam Masuk : ".$jam_masuk." ID : ".$idpeg."departemen : $departemen  Shift : $shift";
                    
                    $jam="now()";
                    if(!empty($jam_masuk)){
                        $jam="CONCAT(CURRENT_DATE(),' $jam_masuk')";
                    }
                    
                    $_sqlvalid        = "select id from rekap_presensi where id='$idpeg' and shift='$shift' and jam_datang like '%$hariini%'";
                    $hasilvalid       = bukaquery($_sqlvalid);
                    @$barisvalid      = mysqli_fetch_array($hasilvalid);  
                    @$idvalid         = $barisvalid["id"];  
                    
                    if(!empty($idvalid)){
                        $namaPegawai = getOne("select nama from pegawai where id='$idpeg'");
                        echo "<div style='background:#f59e0b;color:white;padding:15px;border-radius:8px;margin:10px 0;text-align:center;font-weight:bold;'>";
                        echo "⚠ Anda sudah presensi untuk tanggal " . date('Y-m-d') . "<br/>";
                        echo "Nama: " . $namaPegawai . "<br/>";
                        echo "Silakan tunggu atau hubungi admin jika ada kesalahan.</div>";
                        
                        // Message will be handled by AJAX response
                    }elseif((!empty($idpeg))&&(!empty($shift))&&(empty($idvalid))) {
                        $_sqlcek        = "select id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, photo from temporary_presensi where id='$idpeg'";
                        $hasilcek       = bukaquery($_sqlcek);
                        @$bariscek       = mysqli_fetch_array($hasilcek);  
                        @$idcek          = $bariscek["id"];         
                        
                        
                        if(empty($idcek)){
                            if(empty($img)){
                                echo "<font size='9'>Pilih shift dulu !!!!!!!</font>";
                            }else{
                                Tambah2("temporary_presensi","'$idpeg','$shift',NOW(),NULL,
                                if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($toleransi*60),if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($terlambat1*60),if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($terlambat2*60),'Terlambat II','Terlambat I'),'Terlambat Toleransi'),'Tepat Waktu'),
                                if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($toleransi*60),SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC($jam)),''),'','$file'", 
                                " Presensi Masuk jam $jam_masuk ".getOne("select if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($toleransi*60),concat('Keterlambatan ',SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC($jam))),'')"));
                                // Show success message without refresh
                                $namaPegawai = getOne("select nama from pegawai where id='$idpeg'");
                                $statusPresensi = getOne("select if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($toleransi*60),if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($terlambat1*60),if(TIME_TO_SEC(now())-TIME_TO_SEC($jam)>($terlambat2*60),'Terlambat II','Terlambat I'),'Terlambat Toleransi'),'Tepat Waktu')");
                                echo "<div style='background:#10b981;color:white;padding:15px;border-radius:8px;margin:10px 0;text-align:center;font-weight:bold;'>";
                                echo "✓ Presensi Masuk Berhasil!<br/>";
                                echo "Nama: " . $namaPegawai . "<br/>";
                                echo "Status: " . $statusPresensi . "<br/>";
                                echo "Jam: " . date('H:i:s') . "</div>";
                                
                                // Message will be handled by AJAX response
                            }                            
                        }elseif(!empty($idcek)){  
                            $jamdatang=getOne("select jam_jaga.jam_masuk from jam_jaga inner join pegawai on pegawai.departemen=jam_jaga.dep_id where jam_jaga.shift='$shift' and pegawai.id='$idcek'");
                            $jampulang=getOne("select jam_jaga.jam_pulang from jam_jaga inner join pegawai on pegawai.departemen=jam_jaga.dep_id where jam_jaga.shift='$shift' and pegawai.id='$idcek'");

                            $jam="now()";
                            if(!empty($jamdatang)){
                                $jam="CONCAT(CURRENT_DATE(),' $jamdatang')";
                            }
                            $jam2="now()";
                            if(!empty($jampulang)){
                                 $jam2="CONCAT(CURRENT_DATE(),' $jampulang')";
                            }
                            $masuk=getOne("select jam_datang from temporary_presensi where id='$idcek'");
                            $pulang="now()";

                            Ubah2(" temporary_presensi "," jam_pulang=NOW(),status=if(TIME_TO_SEC('$masuk')-TIME_TO_SEC($jam)>($toleransi*60),if(TIME_TO_SEC('$masuk')-TIME_TO_SEC($jam)>($terlambat1*60),if(TIME_TO_SEC('$masuk')-TIME_TO_SEC($jam)>($terlambat2*60),
                                   concat('Terlambat II',if(TIME_TO_SEC($pulang)-TIME_TO_SEC($jam2)<0,' & PSW',' ')),concat('Terlambat I',if(TIME_TO_SEC($pulang)-TIME_TO_SEC($jam2)<0,' & PSW',' '))),
                                   concat('Terlambat Toleransi',if(TIME_TO_SEC($pulang)-TIME_TO_SEC($jam2)<0,' & PSW',' '))),concat('Tepat Waktu',if(TIME_TO_SEC($pulang)-TIME_TO_SEC($jam2)<0,' & PSW',' '))),
                                   durasi=(SEC_TO_TIME(unix_timestamp(now()) - unix_timestamp(jam_datang))) where id='$idpeg'  ");                            
                            $_sqlcek        = "select id, shift, jam_datang, jam_pulang, status, keterlambatan, durasi, photo from temporary_presensi where id='$idpeg'";
                            $hasilcek       = bukaquery($_sqlcek);
                            $bariscek       = mysqli_fetch_array($hasilcek);  
                            $idcek          = $bariscek["id"];                                                      
                            $shift          = $bariscek["shift"];
                            $jam_datang     = $bariscek["jam_datang"];
                            $jam_pulang     = $bariscek["jam_pulang"];
                            $status         = $bariscek["status"];
                            $keterlambatan  = $bariscek["keterlambatan"];
                            $durasi         = $bariscek["durasi"];
                            Tambah2("rekap_presensi","'$idcek','$shift','$jam_datang','$jam_pulang','$status','$keterlambatan','$durasi','','$file'", " Presensi Pulang jam $jam_pulang" );
                            hapusinput(" delete from temporary_presensi where id ='$idcek' ");
                            
                            // Show success message without refresh
                            $namaPegawai = getOne("select nama from pegawai where id='$idcek'");
                            echo "<div style='background:#3b82f6;color:white;padding:15px;border-radius:8px;margin:10px 0;text-align:center;font-weight:bold;'>";
                            echo "✓ Presensi Pulang Berhasil!<br/>";
                            echo "Nama: " . $namaPegawai . "<br/>";
                            echo "Status: " . $status . "<br/>";
                            echo "Durasi: " . $durasi . "<br/>";
                            echo "Jam Pulang: " . date('H:i:s') . "</div>";
                            
                            // Message will be handled by AJAX response
                        } 
                    }elseif (empty($idpeg)||empty($shift)){
                        echo "<b>ID Pegawai atau Jam Masuk ada yang salah, Silahkan pilih berdasarkan shift departemen anda</b>";
                    }
                }
            ?>
            </form>
           </div>
        </div>
