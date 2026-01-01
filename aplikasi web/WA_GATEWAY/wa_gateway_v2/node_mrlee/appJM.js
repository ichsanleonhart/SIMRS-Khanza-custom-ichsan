// ====================================================================================
// APPJM.JS - TRUE HUMAN SIMULATOR (RANDOM SPEED & DELAY)
// Fitur: 
// 1. Variasi kecepatan ngetik (Mood Cepat/Lambat)
// 2. Random Start Delay (5-15 detik)
// 3. Mark as Read sebelum ngetik
// 4. Heartbeat Anti-Session Closed
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
    // Keep-Alive & Stealth Arguments
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-accelerated-2d-canvas",
      "--no-first-run",
      "--no-zygote",
      "--disable-gpu",
      "--keep-alive", 
      "--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    ],
  }
});

let lAuth = false;
let cqrCode = "not ready";
let rejectCalls = true;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// --- HELPER: HEARTBEAT DELAY ---
// Mencegah session closed saat menunggu lama
const smartTypingDelay = async (ms, chatObject) => {
    const chunk = 5000; // Ping setiap 5 detik
    let elapsed = 0;

    while (elapsed < ms) {
        const timeToSleep = Math.min(chunk, ms - elapsed);
        await sleep(timeToSleep);
        elapsed += timeToSleep;

        // HEARTBEAT
        try {
            if(chatObject) await chatObject.sendStateTyping();
        } catch (e) {
            // Abaikan error session di sini, biar flow utama yang handle
        }
    }
};

// --- HELPER: HITUNG WAKTU NGETIK (VARIASI MOOD) ---
const calculateVariableTypingTime = (textLength) => {
    // 50% Kemungkinan Admin Semangat (Cepat), 50% Lelah (Lambat)
    const isFastMood = Math.random() < 0.5;
    
    let msPerChar;
    if (isFastMood) {
        // Mode Cepat: 60ms - 90ms per karakter
        msPerChar = Math.floor(Math.random() * 30) + 60;
    } else {
        // Mode Lambat: 150ms - 250ms per karakter
        msPerChar = Math.floor(Math.random() * 100) + 150;
    }

    let totalTime = textLength * msPerChar;

    // Batas Minimal 3 detik, Maksimal 2 Menit
    if (totalTime < 3000) totalTime = 3000; 
    if (totalTime > 120000) totalTime = 120000; 

    return { totalTime, isFastMood, msPerChar };
};

// --- EVENT LISTENERS ---

client.on("qr", (qr) => {
  const qrcode = require('qrcode-terminal');
  console.log("Scan QR Code di bawah ini:");
  qrcode.generate(qr, { small: true });
  cqrCode = qr;
});

client.on("ready", () => {
  console.log("WA Gate is ready (True Human Simulator)!");
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
        
        // Random Thinking (1-3s)
        await sleep(Math.floor(Math.random() * 2000) + 1000);
        
        try {
            const chat = await msg.getChat();
            
            // Mark Read
            await chat.sendSeen();
            await sleep(1000);

            await chat.sendStateTyping(); 
            
            const response = await axios.post('http://localhost/wa_gateway/chatbot.php', {
                sender: senderNumber, message: messageBody
            });
            const replyText = response.data.reply;
            
            if (replyText) {
                // Gunakan Helper Variasi
                const { totalTime } = calculateVariableTypingTime(replyText.length);
                
                await smartTypingDelay(totalTime, chat);
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
app.get("/", (req, res) => res.status(200).json({ status: true, message: "WAG API (Humanized)" }));
app.get("/uptime", (req, res) => res.status(200).json({ status: true, message: "Uptime: " + process.uptime() }));
app.post("/WA-QrCode", (req, res) => {
  if (!lAuth) return res.status(200).json({ status: false, message: "QR Not Ready", qrBarCode: cqrCode });
  res.status(200).json({ status: true, message: "Already Login", qrBarCode: cqrCode });
});

// ============================================================================
// SEND MESSAGE (HUMANIZED)
// ============================================================================
app.post("/send-message", [body("number").notEmpty(), body("message").notEmpty()], async (req, res) => {
  const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
  if (!errors.isEmpty()) return res.status(422).json({ status: false, message: errors.mapped() });

  const number = req.body.number; 
  const message = req.body.message;

  console.log(`[BOT] Sending to: ${number}`);

  // 1. RANDOM START DELAY (5 - 15 Detik)
  // Jeda acak sebelum mulai memproses agar tidak robotik
  const startDelay = Math.floor(Math.random() * 10000) + 5000;
  console.log(`[BOT] Menunggu giliran (Random Delay): ${startDelay}ms`);
  await sleep(startDelay);

  try {
      const chat = await client.getChatById(number);
      
      // 2. MARK AS READ (Manusiawi)
      try {
          await chat.sendSeen();
          // Jeda baca sebentar (1-2 detik)
          await sleep(Math.floor(Math.random() * 1000) + 1000);
      } catch (e) { /* Ignore for new numbers */ }

      // 3. HITUNG DURASI NGETIK (VARIASI)
      const { totalTime, isFastMood, msPerChar } = calculateVariableTypingTime(message.length);
      console.log(`[BOT] Admin Ngetik (${isFastMood ? 'Cepat' : 'Lambat'} @ ${msPerChar}ms): ${totalTime}ms`);

      // 4. MULAI TYPING (+ HEARTBEAT)
      try {
          await chat.sendStateTyping();
          await smartTypingDelay(totalTime, chat);
      } catch (typingErr) {
          console.log("[BOT] Skip typing (No LID), lanjut kirim.");
      }

      // 5. STOP & KIRIM
      try { await chat.clearState(); } catch (e) {}
      
      const response = await chat.sendMessage(message);
      res.status(200).json({ status: true, message: "Sukses", response: response });

  } catch (err) {
      console.error("[BOT] Error Utama (Fallback Direct):", err.message);

      if (err.message.includes("Session closed")) {
          console.log("[BOT] Session mati. Restarting...");
          client.initialize(); 
      }
      
      // FALLBACK
      client.sendMessage(number, message)
        .then((response) => res.status(200).json({ status: true, message: "Sukses (Direct)", response: response }))
        .catch((err2) => res.status(500).json({ status: false, message: "Gagal Kirim", response: err2 }));
  }
});

// ============================================================================
// SEND FILE (HUMANIZED)
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

    // 1. RANDOM START DELAY (5 - 15 Detik)
    const startDelay = Math.floor(Math.random() * 10000) + 5000;
    await sleep(startDelay);

    try {
        const chat = await client.getChatById(number);
        
        // 2. MARK AS READ
        try {
             await chat.sendSeen();
             await sleep(Math.floor(Math.random() * 1000) + 1000);
        } catch (e) {}

        // 3. SIMULASI UPLOAD (Random 10 - 20 Detik)
        const uploadDelay = Math.floor(Math.random() * 10000) + 10000;
        console.log(`[BOT] Uploading file... (${uploadDelay}ms)`);
        
        try {
             await chat.sendStateRecording();
             await smartTypingDelay(uploadDelay, chat);
        } catch (e) {}

        try { await chat.clearState(); } catch (e) {}

        const response = await chat.sendMessage(media, { caption: caption });
        res.status(200).json({ status: true, message: "Sukses", response: response });

    } catch (innerErr) {
        throw innerErr; 
    }

  } catch (error) {
      console.error("[BOT] Error File (Fallback):", error.message);
      
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
    // Random cooldown juga buat group
    await sleep(Math.floor(Math.random() * 3000) + 3000); 

    client.sendMessage(chatId, message)
      .then((response) => res.status(200).json({ status: true, message: "Sukses", response: response }))
      .catch((err) => res.status(500).json({ status: false, message: "Gagal Kirim", response: err }));
  }
);

app.listen(port, () => {
  console.log(`WAG listening on port ${port} (Humanized Randomness)`);
});