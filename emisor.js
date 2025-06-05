const http = require('http');
const { Server } = require("socket.io");

const io = new Server(3000, { cors: { origin: "*" } });

const servidorHttp = http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host}`);
  if (url.pathname === "/emit") {
    const canal = url.searchParams.get("canal");
    const mensaje = url.searchParams.get("mensaje");
    io.emit(canal, mensaje);
    res.end("OK");
  } else {
    res.writeHead(404);
    res.end("Not Found");
  }
});

servidorHttp.listen(3001, () => console.log("🛰️ Emisor corriendo en http://localhost:3001"));
