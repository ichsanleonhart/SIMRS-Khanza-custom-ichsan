<?php
session_start();
require_once('../../conf/conf.php');

if (!isset($_SESSION['jadwal_login'])) { header("Location: login.php"); exit(); }

$id = validTeks($_GET['id']);
$bulan = validTeks($_GET['bulan']);
$tahun = validTeks($_GET['tahun']);

// Ambil Data Pegawai
$pegawai = fetch_assoc("SELECT p.nama, p.nik, p.jbtn, d.nama as dep 
                        FROM pegawai p 
                        JOIN departemen d ON p.departemen = d.dep_id 
                        WHERE p.id='$id'");

if(!$pegawai) die("Pegawai tidak ditemukan");

$nama_bulan = ["01"=>"Januari","02"=>"Februari","03"=>"Maret","04"=>"April","05"=>"Mei","06"=>"Juni","07"=>"Juli","08"=>"Agustus","09"=>"September","10"=>"Oktober","11"=>"November","12"=>"Desember"];
$jml_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Jadwal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .shift-select { transition: all 0.3s; }
        .bg-pagi { background-color: #dcfce7; } /* Hijau Muda */
        .bg-siang { background-color: #fef9c3; } /* Kuning Muda */
        .bg-malam { background-color: #dbeafe; } /* Biru Muda */
        .bg-libur { background-color: #fee2e2; } /* Merah Muda */
        /* Style Khusus Locked Cuti */
        .bg-locked { background-color: #f3f4f6; color: #9ca3af; cursor: not-allowed; border-color: #d1d5db; }
    </style>
</head>
<body class="bg-slate-50 pb-24">

    <div class="bg-white shadow border-b sticky top-0 z-20">
        <div class="max-w-3xl mx-auto px-4 py-3 flex justify-between items-center">
            <div>
                <h1 class="font-bold text-lg text-slate-800"><?php echo $pegawai['nama']; ?></h1>
                <p class="text-xs text-slate-500"><?php echo $pegawai['nik']; ?> - <?php echo $pegawai['dep']; ?></p>
            </div>
            <div class="text-right">
                <p class="font-bold text-blue-600"><?php echo $nama_bulan[$bulan]." ".$tahun; ?></p>
                <a href="index.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="text-xs text-slate-400 hover:text-blue-500">Kembali</a>
            </div>
        </div>
        
        <div class="flex border-t">
            <button onclick="switchMode('reguler')" id="tab-reguler" class="flex-1 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600 bg-blue-50">
                Jadwal Reguler
            </button>
            <button onclick="switchMode('tambahan')" id="tab-tambahan" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-50 transition">
                Jadwal Tambahan
            </button>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-4">
        
        <div class="bg-white p-3 rounded-lg shadow-sm border mb-4 flex gap-2 overflow-x-auto">
            <span class="text-xs font-bold py-2 text-slate-500">Isi Cepat:</span>
            <select id="quick-fill-val" class="text-xs border rounded px-2 py-1 bg-slate-50 w-32"></select>
            <button onclick="quickFill()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-1 rounded text-xs font-bold">Terapkan Semua</button>
            <button onclick="clearAll()" class="bg-red-100 hover:bg-red-200 text-red-600 px-3 py-1 rounded text-xs font-bold ml-auto">Reset</button>
        </div>

        <form id="form-jadwal" class="space-y-2">
            <?php for($i=1; $i<=$jml_hari; $i++): 
                $date = "$tahun-$bulan-$i";
                $dayName = date('D', strtotime($date));
                $isWeekend = ($dayName == 'Sun');
                $color = $isWeekend ? 'text-red-500' : 'text-slate-700';
            ?>
            <div class="flex items-center bg-white p-2 rounded border shadow-sm hover:shadow-md transition row-hari">
                <div class="w-12 text-center border-r mr-3">
                    <div class="text-lg font-bold <?php echo $color; ?>"><?php echo $i; ?></div>
                    <div class="text-[10px] uppercase text-slate-400"><?php echo $dayName; ?></div>
                </div>
                <div class="flex-1 relative">
                    <select name="h<?php echo $i; ?>" id="h<?php echo $i; ?>" onchange="colorize(this)" class="shift-select w-full p-2 text-sm border rounded bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Loading...</option>
                    </select>
                    <div id="lock-<?php echo $i; ?>" class="hidden absolute right-8 top-2.5 text-orange-500">
                        <i class="fa-solid fa-lock" title="Terkunci: Cuti HRD"></i>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </form>
    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white border-t p-4 shadow-lg z-30">
        <div class="max-w-3xl mx-auto">
            <button onclick="saveData()" id="btn-save" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg transition transform active:scale-95 flex justify-center items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> SIMPAN PERUBAHAN
            </button>
        </div>
    </div>

<script>
    const idPegawai = '<?php echo $id; ?>';
    const bulan = '<?php echo $bulan; ?>';
    const tahun = '<?php echo $tahun; ?>';
    let currentMode = 'reguler';
    let shiftOptions = [];

    document.addEventListener("DOMContentLoaded", async () => {
        await loadShiftOptions();
        await loadData();
    });

    async function loadShiftOptions() {
        try {
            let req = await fetch(`api_jadwal.php?act=get_shifts&id_pegawai=${idPegawai}`);
            let res = await req.json();
            if(res.status === 'success') {
                shiftOptions = res.data;
                let qFill = document.getElementById('quick-fill-val');
                qFill.innerHTML = '<option value="">- Pilih -</option>';
                shiftOptions.forEach(s => {
                    if(s.kode !== '') qFill.innerHTML += `<option value="${s.kode}">${s.label}</option>`;
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        } catch(e) { alert('Gagal memuat opsi shift'); }
    }

    function renderDropdowns(selectedValueObj = {}) {
        for(let i=1; i<=<?php echo $jml_hari; ?>; i++) {
            let select = document.getElementById(`h${i}`);
            let lockIcon = document.getElementById(`lock-${i}`);
            let currentVal = selectedValueObj[`h${i}`] || '';
            
            // Reset state
            select.disabled = false;
            select.innerHTML = '';
            lockIcon.classList.add('hidden');

            shiftOptions.forEach(opt => {
                let selected = (opt.kode === currentVal) ? 'selected' : '';
                select.innerHTML += `<option value="${opt.kode}" ${selected}>${opt.label}</option>`;
            });

            // LOGIC FRONTEND ANTI-FRAUD
            if(currentVal === 'Cuti') {
                select.disabled = true; // Matikan Dropdown
                select.classList.add('bg-locked');
                lockIcon.classList.remove('hidden'); // Tampilkan Gembok
                
                // Tambah hidden input agar value 'Cuti' tetap terbaca jika kita submit form standard (opsional)
                // Tapi karena kita pakai JSON payload manual di saveData(), kita ambil value dari property .value walau disabled
            } else {
                select.classList.remove('bg-locked');
            }

            colorize(select);
        }
    }

    async function loadData() {
        Swal.fire({title: 'Memuat Data...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
        try {
            let req = await fetch(`api_jadwal.php?act=get_schedule&id=${idPegawai}&bulan=${bulan}&tahun=${tahun}&jenis=${currentMode}`);
            let res = await req.json();
            
            if(res.found) {
                renderDropdowns(res.data);
            } else {
                renderDropdowns({});
            }
            Swal.close();
        } catch(e) { Swal.fire('Error', 'Gagal memuat data jadwal', 'error'); }
    }

    async function saveData() {
        let btn = document.getElementById('btn-save');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> MENYIMPAN...';
        btn.disabled = true;

        let formData = {};
        for(let i=1; i<=<?php echo $jml_hari; ?>; i++) {
            // Ambil value meskipun disabled
            formData[`h${i}`] = document.getElementById(`h${i}`).value;
        }

        let payload = {
            id: idPegawai,
            bulan: bulan,
            tahun: tahun,
            jenis: currentMode,
            data: formData
        };

        try {
            let req = await fetch('api_jadwal.php?act=save_schedule', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            let res = await req.json();

            if(res.status === 'success') {
                Swal.fire({icon: 'success', title: 'Tersimpan!', timer: 1500, showConfirmButton: false});
            } else {
                // Tampilkan pesan error dari Backend (Contoh: Fraud Detection)
                Swal.fire({
                    icon: 'error', 
                    title: 'Gagal Menyimpan', 
                    text: res.message,
                    footer: '<span class="text-red-500 font-bold">Perubahan ditolak oleh sistem keamanan.</span>'
                });
            }
        } catch(e) {
            Swal.fire('Error', 'Koneksi terputus', 'error');
        }
        
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> SIMPAN PERUBAHAN';
        btn.disabled = false;
    }

    // --- UTILITIES ---

    function switchMode(mode) {
        currentMode = mode;
        if(mode === 'reguler') {
            document.getElementById('tab-reguler').className = 'flex-1 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600 bg-blue-50';
            document.getElementById('tab-tambahan').className = 'flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-50 transition';
        } else {
            document.getElementById('tab-reguler').className = 'flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-50 transition';
            document.getElementById('tab-tambahan').className = 'flex-1 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600 bg-blue-50';
        }
        loadData();
    }

    function colorize(el) {
        let val = el.value.toLowerCase();
        // Reset base class
        el.className = 'shift-select w-full p-2 text-sm border rounded focus:ring-2 focus:ring-blue-500 outline-none ';
        
        // Cek jika disabled (Locked Cuti)
        if(el.disabled) {
            el.classList.add('bg-locked');
            return; 
        }

        if(val.includes('pagi')) el.classList.add('bg-pagi', 'text-green-800', 'border-green-200');
        else if(val.includes('siang')) el.classList.add('bg-siang', 'text-yellow-800', 'border-yellow-200');
        else if(val.includes('malam')) el.classList.add('bg-malam', 'text-blue-800', 'border-blue-200');
        else if(val.includes('libur')) el.classList.add('bg-libur', 'text-red-800', 'border-red-200');
        else if(val.includes('cuti')) el.classList.add('bg-orange-100', 'text-orange-800', 'border-orange-200', 'font-bold'); 
        else el.classList.add('bg-slate-50');
    }

    function quickFill() {
        let val = document.getElementById('quick-fill-val').value;
        if(!val) return;
        
        for(let i=1; i<=<?php echo $jml_hari; ?>; i++) {
            let el = document.getElementById(`h${i}`);
            // Jangan timpa jika terkunci
            if(!el.disabled) {
                el.value = val;
                colorize(el);
            }
        }
    }

    function clearAll() {
        if(!confirm('Kosongkan semua kolom (kecuali yang terkunci)?')) return;
        for(let i=1; i<=<?php echo $jml_hari; ?>; i++) {
            let el = document.getElementById(`h${i}`);
            if(!el.disabled) {
                el.value = '';
                colorize(el);
            }
        }
    }
</script>
</body>
</html>