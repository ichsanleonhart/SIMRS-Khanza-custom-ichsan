// ====================================================================================
// APPJM.JS - STABLE HEARTBEAT (ANTI SESSION CLOSED)
// Fitur: Tidur Ayam (Chunked Delay) untuk mencegah Chrome putus koneksi saat ngetik lama.
// ====================================================================================
const fs = require('fs');
const path = require('path');
const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");
const express = require("express");
const { body, validationResult } = require("express-validator");
const axios = require("axios");
const mime = require("mime-types");

// --- LOAD CONFIG ---
const configPath = path.join(__dirname, '../config.json');
let config = { node_port: 8200 }; 

try {
    const rawData = fs.readFileSync(configPath);
    config = JSON.parse(rawData);
    console.log(`[CONFIG] Loaded config.json. Port: ${config.node_port}`);
} catch (e) {
    console.error("[CONFIG] Gagal baca config.json. Pakai default 8200.");
}

const port = config.node_port;

// --- STEALTH MODULE ---
const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ====================================================================================
// CLIENT SETUP
// ====================================================================================
const client = new Client({
  restartOnAuthFail: true,
  authStrategy: new LocalAuth(),
  webVersionCache: {
    type: 'remote',
    remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html'
  },
  puppeteer: {
    headless: true, // Server Mode
    executablePath: puppeteer.executablePath(),
    // Tambahkan argumen keep-alive
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-accelerated-2d-canvas",
      "--no-first-run",
      "--no-zygote",
      "--disable-gpu",
      "--keep-alive", // Coba paksa keep alive
      "--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    ],
  }
});

let lAuth = false;
let cqrCode = "not ready";
let rejectCalls = true;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// --- FUNGSI BARU: SMART DELAY (TIDUR AYAM) ---
// Memecah tidur panjang menjadi potongan kecil agar koneksi tidak putus
const smartTypingDelay = async (ms, chatObject) => {
    const chunk = 5000; // Cek setiap 5 detik
    let elapsed = 0;

    while (elapsed < ms) {
        // 1. Tidur sebentar (5 detik)
        const timeToSleep = Math.min(chunk, ms - elapsed);
        await sleep(timeToSleep);
        elapsed += timeToSleep;

        // 2. HEARTBEAT: Kirim ulang sinyal typing agar session tetap hangat
        // Ini mencegah "Session Closed" karena idle
        try {
            if(chatObject) await chatObject.sendStateTyping();
        } catch (e) {
            // Jika error di tengah jalan (misal session mati), throw error agar ditangkap parent
            throw new Error("Session died during typing");
        }
    }
};

// --- EVENT LISTENERS ---

client.on("qr", (qr) => {
  const qrcode = require('qrcode-terminal');
  console.log("Scan QR Code di bawah ini:");
  qrcode.generate(qr, { small: true });
  cqrCode = qr;
});

client.on("ready", () => {
  console.log("WA Gate is ready (Heartbeat Mode)!");
  cqrCode = "WA Gate is ready";
});

client.on("authenticated", () => { lAuth = true; console.log("AUTHENTICATED"); });

// RECOVERY: Jika session closed/disconnected, restart otomatis
client.on("disconnected", (reason) => {
    console.log("Client Disconnected (Reason: " + reason + "). Reinitializing...");
    client.initialize();
});

client.on("call", async (call) => {
  if (rejectCalls) await call.reject();
  await client.sendMessage(call.from, `[Auto Reply] Mohon maaf, nomor ini hanya untuk notifikasi WhatsApp.`);
});

// Listener Chatbot
client.on("message", async (msg) => {
    const messageBody = msg.body;
    const senderNumber = msg.from;

    if (messageBody === "ping") await msg.reply("Whatsapp ping (RSPON)");
    else if (messageBody === "!reaction") await msg.react("👍");
    else if (messageBody === "!all_groups_info") {
        const chats = await client.getChats();
        const groups = chats.filter((chat) => chat.isGroup);
        if (groups.length === 0) msg.reply("No group found.");
        else {
            let replyMsg = "*THE GROUPS*\n\n";
            groups.forEach((group) => { replyMsg += `ID: ${group.id._serialized}\nName: ${group.name}\n\n`; });
            msg.reply(replyMsg);
        }
    }
    // Chatbot #
    else if (messageBody.startsWith('#')) {
        console.log(`[BOT] Perintah: ${messageBody}`);
        await sleep(2000);
        
        try {
            const chat = await msg.getChat();
            await chat.sendStateTyping(); 
            
            const response = await axios.post('http://localhost/wa_gateway/chatbot.php', {
                sender: senderNumber, message: messageBody
            });
            const replyText = response.data.reply;
            
            if (replyText) {
                const typingTime = replyText.length * 75; 
                // Pakai Smart Delay
                await smartTypingDelay(typingTime, chat);
                
                await chat.clearState();
                msg.reply(replyText);
            }
        } catch (error) { 
            console.error(error); 
        }
    }
});

const checkRegisteredNumber = async function (number) { return true; };
client.initialize();

// --- API ENDPOINTS ---
app.get("/", (req, res) => res.status(200).json({ status: true, message: "WAG API (Heartbeat)" }));
app.get("/uptime", (req, res) => res.status(200).json({ status: true, message: "Uptime: " + process.uptime() }));
app.post("/WA-QrCode", (req, res) => {
  if (!lAuth) return res.status(200).json({ status: false, message: "QR Not Ready", qrBarCode: cqrCode });
  res.status(200).json({ status: true, message: "Already Login", qrBarCode: cqrCode });
});

// ============================================================================
// SEND MESSAGE (HEARTBEAT IMPLEMENTED)
// ============================================================================
app.post("/send-message", [body("number").notEmpty(), body("message").notEmpty()], async (req, res) => {
  const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
  if (!errors.isEmpty()) return res.status(422).json({ status: false, message: errors.mapped() });

  const number = req.body.number; 
  const message = req.body.message;

  console.log(`[BOT] Sending to: ${number}`);

  // Cooldown Global (5-10s)
  await sleep(Math.floor(Math.random() * 5000) + 5000);

  try {
      // 1. Cek User ID (Antisipasi No LID)
      // Jika nomor baru, client.getChatById biasanya aman, tapi sendStateTyping yang error
      const chat = await client.getChatById(number);
      
      // 2. Hitung Durasi
      // BATASI MAKSIMAL 90 DETIK (1.5 Menit) demi keamanan Session
      let typingDuration = message.length * 150;
      if (typingDuration > 90000) typingDuration = 90000; 
      if (typingDuration < 3000) typingDuration = 3000;
      
      console.log(`[BOT] Admin mengetik: ${typingDuration}ms`);
      
      // 3. MULAI TYPING DENGAN HEARTBEAT
      try {
          // Awal Typing
          await chat.sendStateTyping();
          
          // Tidur Ayam (Looping ping 5 detik)
          await smartTypingDelay(typingDuration, chat);
          
      } catch (typingErr) {
          console.log("[BOT] Skip typing (No LID/Error), lanjut kirim.");
      }

      // 4. Stop Typing & Kirim
      try { await chat.clearState(); } catch (e) {}
      
      const response = await chat.sendMessage(message);
      res.status(200).json({ status: true, message: "Sukses", response: response });

  } catch (err) {
      console.error("[BOT] Error Utama:", err.message);

      // CRITICAL RECOVERY:
      // Jika errornya "Session closed", kita harus restart client agar pesan berikutnya bisa masuk
      if (err.message.includes("Session closed")) {
          console.log("[BOT] Session mati. Merestart Client...");
          client.initialize(); 
      }
      
      // FALLBACK KIRIM PAKSA (Jika session masih hidup tapi getChatById gagal)
      client.sendMessage(number, message)
        .then((response) => res.status(200).json({ status: true, message: "Sukses (Direct)", response: response }))
        .catch((err2) => res.status(500).json({ status: false, message: "Gagal Kirim", response: err2 }));
  }
});

// ============================================================================
// SEND FILE (HEARTBEAT IMPLEMENTED)
// ============================================================================
app.post("/send-file", [body("number").notEmpty(), body("namafile").notEmpty()], async (req, res) => {
  const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
  if (!errors.isEmpty()) return res.status(422).json({ status: false, message: errors.mapped() });

  const caption = req.body.caption;
  const number = req.body.number; 
  const namafile = req.body.namafile; 

  console.log(`[BOT] Sending File to: ${number}`);

  try {
    const filePath = path.join(__dirname, '../media', namafile);
    if (!fs.existsSync(filePath)) return res.status(404).json({ status: false, message: 'File 404', path: filePath });

    const fileData = fs.readFileSync(filePath, { encoding: 'base64' });
    const mimetype = mime.lookup(filePath);
    const media = new MessageMedia(mimetype, fileData, namafile);

    await sleep(5000); // Cooldown

    try {
        const chat = await client.getChatById(number);
        
        // Simulasi Upload (Max 30 detik)
        const uploadDelay = Math.floor(Math.random() * 10000) + 10000; 
        
        try {
             await chat.sendStateRecording();
             // Gunakan Smart Delay juga di sini
             await smartTypingDelay(uploadDelay, chat);
        } catch (e) {}

        try { await chat.clearState(); } catch (e) {}

        const response = await chat.sendMessage(media, { caption: caption });
        res.status(200).json({ status: true, message: "Sukses", response: response });

    } catch (innerErr) {
        throw innerErr; 
    }

  } catch (error) {
      console.error("[BOT] Error File:", error.message);
      
      // Recovery Session Closed
      if (error.message.includes("Session closed")) {
           client.initialize();
      }

      // Fallback Direct
      try {
          const filePath = path.join(__dirname, '../media', namafile);
          const fileData = fs.readFileSync(filePath, { encoding: 'base64' });
          const mimetype = mime.lookup(filePath);
          const media = new MessageMedia(mimetype, fileData, namafile);

          client.sendMessage(number, media, { caption: caption })
            .then((resp) => res.status(200).json({ status: true, message: "Sukses (Direct)", response: resp }))
            .catch((err2) => res.status(500).json({ status: false, message: "Gagal Kirim", response: err2 }));
      } catch (fatal) {
          res.status(500).json({ status: false, message: "Fatal Error", error: fatal.message });
      }
  }
});

// Group Manual Endpoint
const findGroupByName = async function (name) {
  const group = await client.getChats().then((chats) => {
    return chats.find((chat) => chat.isGroup && chat.name.toLowerCase() == name.toLowerCase());
  });
  return group;
};

app.post("/send-group", [
    body("id").custom((value, { req }) => { if (!value && !req.body.name) { throw new Error("Invalid value"); } return true; }),
    body("message").notEmpty(),
  ], async (req, res) => {
    let chatId = req.body.id;
    const groupName = req.body.name;
    const message = req.body.message;
    if (!chatId) {
      const group = await findGroupByName(groupName);
      if (!group) { return res.status(422).json({ status: false, message: "No group found: " + groupName }); }
      chatId = group.id._serialized;
    }
    await sleep(5000); 
    client.sendMessage(chatId, message)
      .then((response) => res.status(200).json({ status: true, message: "Sukses", response: response }))
      .catch((err) => res.status(500).json({ status: false, message: "Gagal Kirim", response: err }));
  }
);

app.listen(port, () => {
  console.log(`WAG listening on port ${port} (Heartbeat Mode)`);
});