// servidor-socket.js
const { Server } = require("socket.io");
const http = require("http");

const server = http.createServer();
const io = new Server(server, {
  cors: { origin: "*" }
});

io.on("connection", socket => {
  console.log("🔌 Cliente conectado");

  socket.on("disconnect", () => {
    console.log("❌ Cliente desconectado");
  });
});

server.listen(3000, () => {
  console.log("🚀 WebSocket corriendo en http://localhost:3000");
});

// Exportar función para emitir mensajes desde otros procesos
module.exports.io = io;
