// ====================================================================================
// APPJM.JS - REVISI FINAL ANTI-CRASH & BYPASS CHECK
// ====================================================================================
const qrcode = require('qrcode-terminal'); 
const fs = require('fs');
const path = require('path');

const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");
const { phoneNumberFormatter } = require("./formatter");
const express = require("express");
const { body, validationResult } = require("express-validator");
const { response } = require("express");
const axios = require("axios");
const mime = require("mime-types");
const app = express();
const port = 8100;

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const client = new Client({
  restartOnAuthFail: true,
  webVersionCache: {
    type: 'remote',
    remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html'
  },
  puppeteer: {
    headless: true,
    protocolTimeout: 60000,
    args: [
      "--no-sandbox",
      "--no-default-browser-check",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-accelerated-2d-canvas",
      "--no-first-run",
      "--no-zygote",
      "--disable-gpu",
    ],
  },
  authStrategy: new LocalAuth(),
});

let cqrCode = "not ready";
let lAuth = false;

client.on("qr", (qr) => {
  console.log("Scan QR Code di bawah ini:");
  qrcode.generate(qr, { small: true });
});

client.on("loading_screen", (percent, message) => {
  console.log("LOADING.. chats", percent+"%");
});

client.on("authenticated", () => {
  lAuth = true;
  console.log("AUTHENTICATED");
});

client.on("auth_failure", (msg) => {
  lAuth = false;
  console.log("AUTHENTICATED Failure");
  console.error("AUTHENTICATION FAILURE", msg);
});

client.on("ready", () => {
  cqrCode = "WA Gate is ready";
  console.log("WA Gate is ready!");
});

client.on("message", async (msg) => {
  const conten = msg.body;

  if (conten === "ping") {
    client.sendMessage(msg.from, "Whatsapp ping (RSPON)");
  } else if (conten === "ping reply") {
    msg.reply("Whatsapp ping reply (RSPON)");
  } else if (conten === "!this group info") {
    let chat = await msg.getChat();
    if (chat.isGroup) {
      msg.reply(`
            *Group Details*
            Name: ${chat.name}
            ID : ${chat.id._serialized}
            Description: ${chat.description}
            Created At: ${chat.createdAt.toString()}
            Created By: ${chat.owner.user}
            Participant count: ${chat.participants.length}
        `);
    } else {
      msg.reply("for in Group Only!");
    }
  } else if (conten === "!reaction") {
    msg.react("👍");
  } else if (conten === "!all_groups_info") {
    client.getChats().then((chats) => {
      const groups = chats.filter((chat) => chat.isGroup);

      if (groups.length == 0) {
        msg.reply("You have no group yet.");
      } else {
        let replyMsg = "*THE GROUPS*\n\n";
        groups.forEach((group, i) => {
          replyMsg += `ID: ${group.id._serialized}\nName: ${group.name}\n\n`;
        });
        replyMsg += "use the group id to send a message to the group";
        msg.reply(replyMsg);
      }
    });
  }
});

client.on("disconnected", (reason) => {
  console.log("Client was logged out", reason);
  client.destroy();
  client.initialize();
  lAuth = false;
});

let rejectCalls = true;

client.on("call", async (call) => {
  if (rejectCalls) await call.reject();
  await client.sendMessage(
    call.from,
    `[${call.fromMe ? "Outgoing" : "Incoming"}] Phone call from ${call.from}, type ${call.isGroup ? "group" : ""} ${call.isVideo ? "video" : "audio"} call. ${rejectCalls ? "Please do not call/message this number!" : ""}`
  );
});

client.initialize();

app.get("/", (req, res) => {
  res.status(200).json({ status: true, message: "WAG API by Mr. Lee" });
});

app.post("/", (req, res) => {
  res.status(200).json({ status: true, message: "WAG API by Mr Lee" });
});

app.get("/uptime", (req, res) => {
  res.status(200).json({ status: true, message: "WhatApp GATEWAY uptime " + process.uptime() });
});

app.post("/StopWAG", (req, res) => {
  if (process.uptime() > 30) {
    res.status(200).json({ status: true, message: "Signal to Stop Nodejs, WAG now STOP" });
    process.exit();
  } else {
    res.status(422).json({ status: false, message: "Wait till 30s, upTime " + process.uptime() });
  }
});

app.post("/WA-QrCode", (req, res) => {
  if (!lAuth) {
    if (cqrCode === "not ready") {
      res.status(422).json({ status: false, message: "QR Not Ready", qrBarCode: cqrCode });
    } else {
      res.status(200).json({ status: true, message: "QR Ready", qrBarCode: cqrCode });
    }
  } else {
    res.status(200).json({ status: true, message: "Already Login", qrBarCode: cqrCode });
  }
});

// Helper Function: Check Number (Kita biarkan function ini ada, tapi tidak kita pakai untuk memblokir)
const checkRegisteredNumber = async function (number) {
  try {
    const isRegistered = await client.isRegisteredUser(number);
    return isRegistered;
  } catch (error) {
    return false;
  }
};

// ====================================================================================
// ENDPOINT: SEND MESSAGE (KHUSUS TEKS)
// Diperbaiki: Menghapus pengecekan nomor dan logika file yang bikin crash
// ====================================================================================
app.post("/send-message", [body("number").notEmpty(), body("message").notEmpty()], async (req, res) => {
  const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
  if (!errors.isEmpty()) {
    return res.status(422).json({ status: false, message: errors.mapped() });
  }

  const number = phoneNumberFormatter(req.body.number);
  const message = req.body.message;

  // --- BYPASS CHECK REGISTERED NUMBER ---
  // Kita matikan pengecekan ini agar tidak error 422 saat koneksi lambat.
  // const isRegisteredNumber = await checkRegisteredNumber(number);
  // if (!isRegisteredNumber) { ... }
  
  // Langsung kirim saja
  client.sendMessage(number, message)
    .then((response) => {
      res.status(200).json({ status: true, message: "Sukses", response: response });
    })
    .catch((err) => {
      res.status(500).json({ status: false, message: "Gagal Kirim", response: err });
    });
});

// ====================================================================================
// ENDPOINT: SEND FILE (KHUSUS MEDIA/PDF)
// Diperbaiki: Path file sudah benar menunjuk ke folder luar (../media)
// ====================================================================================
app.post("/send-file", [body("number").notEmpty(), body("namafile").notEmpty()], async (req, res) => {
  const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
  if (!errors.isEmpty()) {
    return res.status(422).json({ status: false, message: errors.mapped() });
  }

  const caption = req.body.caption;
  const number = phoneNumberFormatter(req.body.number);
  const namafile = req.body.namafile; 

  try {
    // --- BYPASS CHECK REGISTERED NUMBER ---
    // Sama, kita matikan pengecekan di sini juga.
    // const isRegisteredNumber = await checkRegisteredNumber(number);
    
    // Path folder media (Mundur satu folder dari node_mrlee)
    const filePath = path.join(__dirname, '../media', namafile);

    if (!fs.existsSync(filePath)) {
        console.error(`DIAGNOSTIK: File tidak ditemukan: ${filePath}`);
        return res.status(404).json({
            status: false,
            message: 'File tidak ditemukan di server.',
            path: filePath
        });
    }
    
    const fileData = fs.readFileSync(filePath, { encoding: 'base64' });
    const mimetype = mime.lookup(filePath);
    const media = new MessageMedia(mimetype, fileData, namafile);

    client.sendMessage(number, media, { caption: caption })
      .then((response) => {
        res.status(200).json({ status: true, message: "Sukses", response: response });
      })
      .catch((err) => {
        res.status(500).json({ status: false, message: "Gagal Kirim", response: err });
      });
  } catch (error) {
      console.error("Error kritis di endpoint /send-file:", error);
      res.status(500).json({ status: false, message: "Internal Server Error", error: error.message });
  }
});

// Endpoint lain (Send URL, Group) dibiarkan standar
app.post("/send-fileurl", [body("number").notEmpty(), body("fileurl").notEmpty()], async (req, res) => {
  const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
  if (!errors.isEmpty()) { return res.status(422).json({ status: false, message: errors.mapped() }); }

  const caption = req.body.caption;
  const number = phoneNumberFormatter(req.body.number);
  const cfile = req.body.fileurl;
  const media = await MessageMedia.fromUrl(cfile);

  client.sendMessage(number, media, { caption: caption })
    .then((response) => { res.status(200).json({ status: true, message: "Sukses", response: response }); })
    .catch((err) => { res.status(500).json({ status: false, message: "Gagal Kirim", response: err }); });
});

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
    const errors = validationResult(req).formatWith(({ msg }) => { return msg; });
    if (!errors.isEmpty()) { return res.status(422).json({ status: false, message: errors.mapped() }); }

    let chatId = req.body.id;
    const groupName = req.body.name;
    const message = req.body.message;

    if (!chatId) {
      const group = await findGroupByName(groupName);
      if (!group) { return res.status(422).json({ status: false, message: "No group found: " + groupName }); }
      chatId = group.id._serialized;
    }

    client.sendMessage(chatId, message)
      .then((response) => { res.status(200).json({ status: true, message: "Sukses", response: response }); })
      .catch((err) => { res.status(500).json({ status: false, message: "Gagal Kirim", response: err }); });
  }
);

app.listen(port, () => {
  console.log(`WAG listening on port ${port}`);
});

process.on("exit", (code) => {
  console.log(`Nodejs exit with code ${code}`);
});