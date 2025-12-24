// ====================================================================================
// APPJM.JS - OVERWORKED ADMIN EDITION (EXTREME HUMAN SIMULATION)
// Fitur: Ngetik lambat, jeda random 5-15s, tanpa batas waktu ngetik.
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
    protocolTimeout: 300000, // Timeout puppeteer diperpanjang (5 menit)
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-accelerated-2d-canvas",
      "--no-first-run",
      "--no-zygote",
      "--disable-gpu",
      "--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    ],
  }
});

let lAuth = false;
let cqrCode = "not ready";
let rejectCalls = true;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// --- EVENT LISTENERS ---

client.on("qr", (qr) => {
  const qrcode = require('qrcode-terminal');
  console.log("Scan QR Code di bawah ini:");
  qrcode.generate(qr, { small: true });
  cqrCode = qr;
});

client.on("ready", () => {
  console.log("WA Gate is ready (Mode: Admin Kewalahan)!");
  cqrCode = "WA Gate is ready";
});

client.on("authenticated", () => { lAuth = true; console.log("AUTHENTICATED"); });

// Reject Call
client.on("call", async (call) => {
  if (rejectCalls) await call.reject();
  await client.sendMessage(call.from, `[Auto Reply] Mohon maaf, nomor ini hanya untuk notifikasi WhatsApp (Tidak menerima telepon).`);
});

// Listener Chatbot & Legacy
client.on("message", async (msg) => {
    const messageBody = msg.body;
    const senderNumber = msg.from;

    // --- Legacy Features ---
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
    else if (messageBody === "!this group info") {
        let chat = await msg.getChat();
        if (chat.isGroup) {
            msg.reply(`*Group Details*\nName: ${chat.name}\nID: ${chat.id._serialized}`);
        } else {
            msg.reply("Group only!");
        }
    }

    // --- Chatbot # (Mode Berpikir Keras) ---
    else if (messageBody.startsWith('#')) {
        console.log(`[BOT] Perintah: ${messageBody}`);
        
        // Simulasi Berpikir/Mencari Data (2-5 detik)
        await sleep(Math.floor(Math.random() * 3000) + 2000);
        
        const chat = await msg.getChat();
        await chat.sendStateTyping(); 

        try {
            const response = await axios.post('http://localhost/wa_gateway/chatbot.php', {
                sender: senderNumber, message: messageBody
            });
            const replyText = response.data.reply;
            if (replyText) {
                // Rumus Admin Lambat: 150ms per karakter
                // Contoh: 200 karakter = 30 detik ngetik.
                const typingTime = replyText.length * 150; 
                
                // Tambah sedikit variasi random biar gak robot banget (+- 2 detik)
                const finalTime = typingTime + Math.floor(Math.random() * 2000);

                console.log(`[CHATBOT] Ngetik jawaban selama: ${finalTime}ms`);
                await sleep(finalTime);
                
                await chat.clearState();
                msg.reply(replyText);
            } else await chat.clearState();
        } catch (error) { await chat.clearState(); }
    }
});

const checkRegisteredNumber = async function (number) { return true; };
client.initialize();

// ====================================================================================
// API ENDPOINTS
// ====================================================================================

app.get("/", (req, res) => res.status(200).json({ status: true, message: "WAG API (Busy Admin Mode)" }));
app.get("/uptime", (req, res) => res.status(200).json({ status: true, message: "Uptime: " + process.uptime() }));
app.post("/WA-QrCode", (req, res) => {
  if (!lAuth) return res.status(200).json({ status: false, message: "QR Not Ready", qrBarCode: cqrCode });
  res.status(200).json({ status: true, message: "Already Login", qrBarCode: cqrCode });
});

// ============================================================================
// SEND MESSAGE (BROADCAST: ADMIN SIBUK)
// ============================================================================
app.post("/send-message", [body("number").notEmpty(), body("message").notEmpty()], async (req, res) => {
  const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
  if (!errors.isEmpty()) return res.status(422).json({ status: false, message: errors.mapped() });

  const number = req.body.number; 
  const message = req.body.message;

  console.log(`[BOT] Sending to: ${number}`);

  try {
      // 1. COOLDOWN ANTI-SPAM (5 - 15 Detik)
      // Jeda sebelum mulai berinteraksi dengan chat ini
      const cooldown = Math.floor(Math.random() * 10000) + 5000;
      console.log(`[BOT] Cooldown (Istirahat/Ganti Chat): ${cooldown}ms`);
      await sleep(cooldown);

      // 2. Simulasi Mengetik
      const chat = await client.getChatById(number);
      await chat.sendStateTyping();
      
      // Rumus Admin Lambat: 150ms per karakter
      // TANPA BATAS MAKSIMAL. 
      // Jika pesan 1000 huruf = 150 detik (2.5 menit).
      // Minimal ngetik 3 detik (untuk pesan pendek kayak "Ok")
      let typingDuration = message.length * 150;
      if (typingDuration < 3000) typingDuration = 3000;

      console.log(`[BOT] Admin sedang mengetik selama: ${typingDuration}ms`);
      await sleep(typingDuration);
      
      // 3. Stop Typing & Kirim
      await chat.clearState();
      const response = await chat.sendMessage(message);
      
      res.status(200).json({ status: true, message: "Sukses", response: response });

  } catch (err) {
      console.error("[BOT] Gagal simulasi (mungkin nomor baru), kirim paksa...", err.message);
      client.sendMessage(number, message)
        .then((response) => res.status(200).json({ status: true, response: response }))
        .catch((err2) => res.status(500).json({ status: false, response: err2 }));
  }
});

// ============================================================================
// SEND FILE (ADMIN UPLOAD FILE)
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

    // 1. COOLDOWN ANTI-SPAM (5 - 15 Detik)
    const cooldown = Math.floor(Math.random() * 10000) + 5000;
    await sleep(cooldown);

    // 2. Simulasi "Recording/Uploading"
    const chat = await client.getChatById(number);
    await chat.sendStateRecording(); // Icon mic/upload
    
    // Simulasi cari file + upload (10 - 20 detik)
    // Admin agak gaptek, nyari filenya lama
    const uploadDelay = Math.floor(Math.random() * 10000) + 10000; 
    console.log(`[BOT] Admin mencari/upload file selama: ${uploadDelay}ms`);
    await sleep(uploadDelay);

    await chat.clearState();

    // 3. Kirim File
    const response = await chat.sendMessage(media, { caption: caption });
    res.status(200).json({ status: true, message: "Sukses", response: response });

  } catch (error) {
      res.status(500).json({ status: false, message: "Internal Server Error", error: error.message });
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
    
    // JANGAN LUPA COOLDOWN UNTUK GROUP MANUAL JUGA
    await sleep(5000); 

    client.sendMessage(chatId, message)
      .then((response) => res.status(200).json({ status: true, message: "Sukses", response: response }))
      .catch((err) => res.status(500).json({ status: false, message: "Gagal Kirim", response: err }));
  }
);

app.listen(port, () => {
  console.log(`WAG listening on port ${port} (Busy Admin Mode)`);
});