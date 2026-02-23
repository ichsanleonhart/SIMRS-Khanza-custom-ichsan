// Step 1: Cari pasien
async function cariPasien() {
    const identitas = document.getElementById('identitas').value;
    if (!identitas) return alert("Isi No. KTP atau RM");

    try {
        const res = await fetch(`api_pasien.php?identitas=${encodeURIComponent(identitas)}`);
        if (!res.ok) throw new Error("HTTP error " + res.status);

        let data;
        try {
            data = await res.json();
        } catch (err) {
            throw new Error("Response bukan JSON valid");
        }

        if (!data || data.error) {
            alert(data.error || "Pasien tidak ditemukan");
            return;
        }

        // simpan no_rkm_medis dan nm_pasien di sessionStorage
        sessionStorage.setItem("no_rkm_medis", data.no_rkm_medis);
        sessionStorage.setItem("nm_pasien", data.nm_pasien);

        // ambil daftar penjab aktif (status=1) untuk dropdown
        const resPenjab = await fetch("api_penjab.php");
        const penjabList = await resPenjab.json();

        let options = `<option value="">-- Pilih Jenis Bayar --</option>`;
        penjabList.forEach(p => {
            const selected = (p.kd_pj === data.kd_pj) ? "selected" : "";
            options += `<option value="${p.kd_pj}" ${selected}>${p.png_jawab}</option>`;
        });

        const jenisBayarHTML = `
            <select id="jenisBayar">${options}</select>
            <br>No. Peserta: ${data.no_peserta || "-"}
        `;

        // tampilkan data pasien
        document.getElementById('dataPasien').innerHTML = `
            <h3>Data Pasien</h3>
            <table class="pasien-table">
                <tr><td class="label">No. Rekam Medis</td><td class="colon">:</td><td>${data.no_rkm_medis || "-"}</td></tr>
                <tr><td class="label">Nama Pasien</td><td class="colon">:</td><td>${data.nm_pasien || "-"}</td></tr>
                <tr><td class="label">No. KTP</td><td class="colon">:</td><td>${data.no_ktp || "-"}</td></tr>
                <tr><td class="label">Jenis Kelamin</td><td class="colon">:</td><td>${data.jk || "-"}</td></tr>
                <tr><td class="label">Tempat/Tgl Lahir</td><td class="colon">:</td>
                    <td>${data.tmp_lahir || "-"}, ${data.tgl_lahir || "-"} Umur: ${data.umur || "-"}</td></tr>
                <tr><td class="label">Nama Ibu Kandung</td><td class="colon">:</td><td>${data.nm_ibu || "-"}</td></tr>
                <tr><td class="label">Alamat</td><td class="colon">:</td>
                    <td>
                        ${data.alamat || "-"}<br>
                        Kelurahan ${data.nama_kelurahan || ""}, 
                        Kecamatan ${data.nama_kecamatan || ""}, 
                        Kabupaten ${data.nama_kabupaten || ""}, 
                        Propinsi ${data.nama_propinsi || ""}
                    </td></tr>
                <tr><td class="label">No. Telp.</td><td class="colon">:</td><td>${data.no_tlp || "-"}</td></tr>
                <tr><td class="label">Email</td><td class="colon">:</td><td>${data.email || "-"}</td></tr>
                <tr><td class="label">Golongan Darah</td><td class="colon">:</td><td>${data.gol_darah || "-"}</td></tr>
                <tr><td class="label">Pekerjaan</td><td class="colon">:</td><td>${data.pekerjaan || "-"}</td></tr>
                <tr><td class="label">Status Pernikahan</td><td class="colon">:</td><td>${data.stts_nikah || "-"}</td></tr>
                <tr><td class="label">Jenis Bayar</td><td class="colon">:</td><td>${jenisBayarHTML}</td></tr>
                <tr><td class="label">Agama</td><td class="colon">:</td><td>${data.agama || "-"}</td></tr>
                <tr><td class="label">Pendidikan</td><td class="colon">:</td><td>${data.pnd || "-"}</td></tr>
                <tr><td class="label">Suku Bangsa</td><td class="colon">:</td><td>${data.nama_suku_bangsa || "-"}</td></tr>
            </table>
        `;

        // sembunyikan form input & judul utama
        document.getElementById('formPasien').classList.add("hidden");
        document.getElementById('anjunganTitle').classList.add("hidden");

        // tampilkan layout pasien + poli
        document.getElementById('pasienPoli').classList.remove("hidden");

        // load daftar poli
        loadPoli();
    } catch (e) {
        alert("Error cari pasien: " + e.message);
    }
}

// Step 2: Load daftar poli hari ini
async function loadPoli() {
    try {
        const res = await fetch("api_jadwal.php");
        const jadwal = await res.json();

        const poliMap = new Map();
        jadwal.forEach(j => {
            poliMap.set(j.kd_poli, j.nm_poli);
        });

        let html = `
            <h3>Pilih Poli Tujuan</h3>
            <button class="btn-back" onclick="kembaliKeForm()">← Kembali</button>
            <div class='grid'>
        `;
        poliMap.forEach((nm_poli, kd_poli) => {
            html += `
                <div class="card" onclick="loadJadwalPoli('${kd_poli}')">
                    <h4>${nm_poli}</h4>
                </div>
            `;
        });
        html += "</div>";

        document.getElementById('jadwalPoli').innerHTML = html;
    } catch (e) {
        alert("Error load poli: " + e.message);
    }
}

// Step 3: Load jadwal dokter berdasarkan poli
async function loadJadwalPoli(kd_poli) {
    try {
        const res = await fetch(`api_jadwal.php?kd_poli=${encodeURIComponent(kd_poli)}`);
        const jadwal = await res.json();

        let html = `
            <h3>Jadwal Dokter Poli</h3>
            <button class="btn-back" onclick="loadPoli()">← Kembali ke Daftar Poli</button>
            <div class='grid'>
        `;

        jadwal.forEach(j => {
            sessionStorage.setItem("nm_dokter_" + j.kd_dokter, j.nm_dokter);
            sessionStorage.setItem("nm_poli_" + j.kd_poli, j.nm_poli);

            html += `
                <div class="card" onclick="daftar('${j.kd_dokter}','${j.kd_poli}')">
                    ${j.photo ? `<img src="${j.photo}" alt="Foto Dokter">` : ""}
                    <div class="info">
                        <h4>${j.nm_dokter}</h4>
                        <p>Poli: ${j.nm_poli}</p>
                        <p>Jam: ${j.mulai} - ${j.selesai}</p>
                        <p>Sisa Kuota: <span class="kuota-merah">${j.sisa_kuota}</span></p>
                    </div>
                </div>
            `;
        });
        html += "</div>";

        document.getElementById('jadwalPoli').innerHTML = html;
    } catch (e) {
        alert("Error load jadwal poli: " + e.message);
    }
}

// Step 4: Draft registrasi
async function daftar(kd_dokter, kd_poli) {
    const no_rkm = sessionStorage.getItem("no_rkm_medis");
    const kd_pj = document.getElementById("jenisBayar")?.value || "";

    if (!no_rkm || !kd_pj) {
        alert("Lengkapi data pasien dan jenis bayar dulu");
        return;
    }

    const nm_dokter = sessionStorage.getItem("nm_dokter_" + kd_dokter) || kd_dokter;
    const nm_poli = sessionStorage.getItem("nm_poli_" + kd_poli) || kd_poli;
    const nm_pasien = sessionStorage.getItem("nm_pasien");
    const nm_pj = document.querySelector("#jenisBayar option:checked")?.textContent || kd_pj;

    const data = {
        nm_pasien,
        nm_dokter,
        nm_poli,
        nm_pj,
        nama_instansi: sessionStorage.getItem("nama_instansi"),
        alamat_instansi: sessionStorage.getItem("alamat_instansi"),
        kabupaten: sessionStorage.getItem("kabupaten")
    };

    tampilkanDraft(data, kd_dokter, kd_poli);
}

function tampilkanDraft(data, kd_dokter, kd_poli) {
    const html = `
      <div class="box">
        <h3>Draft Bukti Registrasi</h3>
        <table>
          <tr><td>Nama Pasien</td><td>:</td><td>${data.nm_pasien}</td></tr>
          <tr><td>Poli</td><td>:</td><td>${data.nm_poli}</td></tr>
          <tr><td>Dokter</td><td>:</td><td>${data.nm_dokter}</td></tr>
          <tr><td>Jenis Bayar</td><td>:</td><td>${data.nm_pj}</td></tr>
          <tr><td>Jam Daftar</td><td>:</td><td>${new Date().toLocaleTimeString("id-ID", { hour12: false })}</td></tr>
        </table>

        <p style="margin-top:15px; font-weight:bold; color:white;">
          ⚠ Mohon periksa kembali data di atas.  
          Dengan menekan tombol <i>Simpan & Cetak</i>, Anda menyatakan bahwa data registrasi sudah benar.
        </p>

        <div style="text-align:center; margin-top:20px;">
          <button onclick="editForm()">⬅ Kembali</button>
          <button onclick="simpanRegistrasi()">💾 Simpan & Cetak</button>
        </div>
      </div>
    `;
    document.getElementById("draftBukti").innerHTML = html;

    document.getElementById("pasienPoli").classList.add("hidden");
    document.getElementById("draftBukti").classList.remove("hidden");

    sessionStorage.setItem("draft_kd_dokter", kd_dokter);
    sessionStorage.setItem("draft_kd_poli", kd_poli);
}

function editForm() {
    document.getElementById("draftBukti").classList.add("hidden");
    document.getElementById("pasienPoli").classList.remove("hidden");
}

async function simpanRegistrasi() {
    const kd_dokter = sessionStorage.getItem("draft_kd_dokter");
    const kd_poli = sessionStorage.getItem("draft_kd_poli");
    const no_rkm = sessionStorage.getItem("no_rkm_medis");
    const kd_pj = document.getElementById("jenisBayar")?.value || "";

    const res = await fetch(
        `api_registrasi.php?kd_dokter=${kd_dokter}&kd_poli=${kd_poli}&no_rkm_medis=${no_rkm}&kd_pj=${kd_pj}`
    );
    const reg = await res.json();

    if (reg.error) {
        alert("Registrasi gagal: " + reg.error);
        return;
    }

    // arahkan ke cetak_bukti.php dengan no_rawat hasil registrasi
    window.location.href = `cetak_bukti.php?no_rawat=${reg.no_rawat}`;
}

// Fungsi navigasi kembali
function kembaliKeForm() {
    document.getElementById('pasienPoli').classList.add("hidden");
    document.getElementById('formPasien').classList.remove("hidden");
    document.getElementById('anjunganTitle').classList.remove("hidden");

    // bersihkan input agar fresh
    document.getElementById('identitas').value = "";
    sessionStorage.clear();
}
