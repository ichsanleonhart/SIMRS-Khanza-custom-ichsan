//=========================================================================
// Menampilkan data rumah sakit
$(document).ready(function () {
  $.ajax({
    url: "app/antrian.php?p=pengaturan",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var email = $("#email");
      email.html(data.email);
      var namars = $("#namars");
      namars.html(data.nama_instansi);
      var namars2 = $("#namars2");
      namars2.html(data.nama_instansi);
      var text = $("#text");
      text.html(data.text);
    },
  });
});

// //==================pengaturan video ===============
const videoPlayer = document.getElementById("myVideo");
// ganti path video , bisa juga menggunakan url video
const videos = [
  "video/video-profil1.mp4",
  "video/video-profil2.mp4",
  "video/video-profil3.mp4",
];
// Ganti dengan daftar video yang Anda inginkan
let currentVideoIndex = 0;
// Fungsi untuk mengatur video pertama sebagai sumber awal
function setInitialVideo() {
  videoPlayer.src = videos[currentVideoIndex];
}

// Event listener saat video selesai diputar
videoPlayer.addEventListener("ended", () => {
  currentVideoIndex = (currentVideoIndex + 1) % videos.length;
  videoPlayer.src = videos[currentVideoIndex];
  videoPlayer.play();
});
// Mengatur volume ke 20%
videoPlayer.volume = 0.1;
// Mengatur Mute Video
videoPlayer.muted = false;
videoPlayer.poster = "video/poster.jpg";

// Panggil fungsi untuk mengatur video pertama saat halaman dimuat
setInitialVideo();
//==========membuat jam=============
function updateClock() {
  var currentTime = new Date();
  var hours = currentTime.getHours();
  var minutes = currentTime.getMinutes();
  var seconds = currentTime.getSeconds();

  // Format waktu dengan tambahkan "0" di depan angka jika kurang dari 10
  hours = (hours < 10 ? "0" : "") + hours;
  minutes = (minutes < 10 ? "0" : "") + minutes;
  seconds = (seconds < 10 ? "0" : "") + seconds;

  var timeString = "" + hours + ":" + minutes + ":" + seconds;

  // Update elemen HTML dengan waktu yang telah diformat
  document.getElementById("clock").innerHTML = timeString;
}

function updateClock2() {
  var currentTime = new Date();
  var getdate =
    currentTime.getFullYear() +
    "-" +
    (currentTime.getMonth() + 1) +
    "-" +
    currentTime.getDate();
  var hours = currentTime.getHours();
  var minutes = currentTime.getMinutes();
  var seconds = currentTime.getSeconds();

  // Format waktu dengan tambahkan "0" di depan angka jika kurang dari 10
  hours = (hours < 10 ? "0" : "") + hours;
  minutes = (minutes < 10 ? "0" : "") + minutes;
  seconds = (seconds < 10 ? "0" : "") + seconds;

  var timeString = "" + hours + ":" + minutes + ":" + seconds;

  // Update elemen HTML dengan waktu yang telah diformat
  document.getElementById("jam").innerHTML = getdate + " " + timeString;
}

function ceknomorantrian() {
  $.ajax({
    url: "app/antrian.php?p=ceknomorterakhir",
    type: "GET",
    dataType: "json",
    success: function (data) {
      $.each(data, function (index, item) {
        if (typeof item.nomor == "undefined") {
          document.getElementById("nomor").innerHTML = 1;
        } else {
          document.getElementById("nomor").innerHTML = +item.nomor + +1;
        }
      });
    },
  });
}

// Panggil fungsi updateClock setiap detik
setInterval(updateClock, 1000);
setInterval(updateClock2, 1000);
setInterval(ceknomorantrian, 1000);

function cetakantrian(divId) {
  $.ajax({
    url: "app/antrian.php?p=getnomor",
    type: "GET",
    dataType: "json",
    success: function (data) {
      console.log(data);
      $.each(data, function (index, item) {
        document.getElementById("nomor").innerHTML = item.nomor;
      });
    },
  });

  var printContents = document.getElementById(divId).innerHTML;
  var originalContents = document.body.innerHTML;
  document.body.innerHTML = printContents;
  window.print();
  document.body.innerHTML = originalContents;

  Swal.fire({
    title: "Sukses",
    text: "Antrian anda sedang di cetak",
    icon: "success",
    confirmButtonText: "oke",
    timer: 1000,
  });
}
