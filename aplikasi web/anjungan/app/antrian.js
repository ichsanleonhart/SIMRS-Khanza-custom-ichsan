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
videoPlayer.volume = 0.01;
// Mengatur Mute Video
videoPlayer.muted = false;
videoPlayer.poster = "video/poster.jpg";

// Panggil fungsi untuk mengatur video pertama saat halaman dimuat
setInitialVideo();

//=========================================================================
// Fungsi pemanggil
function Suara() {
  $.ajax({
    url: "app/antrian.php?p=panggil",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var nomorAntrian = $("#suara");
      nomorAntrian.empty();

      $.each(data, function (index, item) {
        // Suara notifikasi pemanggilan antrian

        var audio = document.getElementById("myAudio");
        audio.onended = function () {
          // Callback yang akan dijalankan setelah audio selesai

          responsiveVoice.speak(
            "nomor antrian "+item.nomor+", silahkan ke " +item.loket.toLowerCase(),
            "Indonesian Female",
            {
              pitch: 1,
              rate: 0.9,
              volume: 50,
            }
          );
        };
        // Memainkan suara notifikasi
        audio.play();
      });
    },
  });

  //=======================================================================

  //=======================================================================
  // Fungsi display nomor

  $.ajax({
    url: "app/antrian.php?p=nomor",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var nomorAntrian = $("#nomor");
      nomorAntrian.empty();
      // Mengosongkan data sebelum menambahkan yang baru
      // Loop melalui data dan menambahkannya ke tampilan
      $.each(data, function (index, item) {
        var antrian = $(
            "<div style='font-size: 40px;' class='btn-primary'>"+item.loket+"</div><b style='font-size: 80px;' class='text-danger'>"+item.nomor+"</b></br>"
        );
        nomorAntrian.append(antrian);
      });
    },
  });
  
  $.ajax({
    url: "app/antrian.php?p=loket1",
    type: "GET",
    dataType: "json",
    success: function (data) {
      var swiperWrapper = $("#datapoli");
      swiperWrapper.empty();
  
      // Loop melalui data dan tambahkan slide
      $.each(data, function(index, item) {
          var varpoli = $("<div class='col-lg-12 col-sm-12 text-center pb-1 pt-1'>"+
              "<div class='card pt-2 border border-success'>"+
              "<h4 class='text-danger'>" + item.loket + "</h4><h1><b>" + item.nomor + "</b></h1>");
          varpoli.append("</div></div>");
          swiperWrapper.append(varpoli);
      });
    },
  });
  //=========================================================================
}

//refresh otomatis setiap 5 detik
setInterval(Suara, 750);

$(document).ready(function () {
  Suara();
});


//=======================================================================

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

// Panggil fungsi updateClock setiap detik
setInterval(updateClock, 1000);
