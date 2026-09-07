const express = require('express');
const session = require('express-session');
const multer = require('multer');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const { DatabaseSync } = require('node:sqlite');

const app = express();
const PORT = Number(process.env.PORT || 3000);
const ROOT = __dirname;
const DATA_DIR = path.join(ROOT, 'data');
const UPLOAD_DIR = path.join(ROOT, 'uploads');
fs.mkdirSync(DATA_DIR, { recursive: true });
fs.mkdirSync(UPLOAD_DIR, { recursive: true });

const db = new DatabaseSync(path.join(DATA_DIR, 'absensi.db'));
db.exec(`
  PRAGMA foreign_keys = ON;
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, participant_number TEXT UNIQUE, email TEXT UNIQUE NOT NULL,
    phone TEXT, course TEXT, password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'participant', status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
  );
  CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL);
  CREATE TABLE IF NOT EXISTS attendances (
    id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL,
    attendance_date TEXT NOT NULL, session_type TEXT NOT NULL,
    latitude REAL NOT NULL, longitude REAL NOT NULL, accuracy REAL NOT NULL,
    distance REAL NOT NULL, photo_path TEXT NOT NULL, status TEXT NOT NULL,
    recorded_at TEXT NOT NULL, ip_address TEXT, user_agent TEXT,
    UNIQUE(user_id, attendance_date, session_type),
    FOREIGN KEY(user_id) REFERENCES users(id)
  );
`);

function hashPassword(password, salt = crypto.randomBytes(16).toString('hex')) {
  return `${salt}:${crypto.scryptSync(password, salt, 64).toString('hex')}`;
}
function verifyPassword(password, stored) {
  const [salt, key] = stored.split(':');
  const actual = crypto.scryptSync(password, salt, 64);
  return crypto.timingSafeEqual(actual, Buffer.from(key, 'hex'));
}

const defaults = {
  institution_name: 'PPKD Jakarta Barat',
  institution_address: 'Jl. Kamal Raya No. 2, Tegal Alur, Kalideres, Jakarta Barat',
  latitude: '-6.108139', longitude: '106.707762', radius: '20', max_accuracy: '35',
  morning_start: '06:30', morning_end: '09:00', afternoon_start: '15:00', afternoon_end: '18:00'
};
const insertSetting = db.prepare('INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)');
Object.entries(defaults).forEach(([key, value]) => insertSetting.run(key, value));
if (!db.prepare("SELECT id FROM users WHERE role='admin' LIMIT 1").get()) {
  db.prepare(`INSERT INTO users (name,email,password_hash,role,status) VALUES (?,?,?,?,?)`)
    .run('Administrator PPKD', process.env.ADMIN_EMAIL || 'admin@ppkd.local', hashPassword(process.env.ADMIN_PASSWORD || 'Admin123!'), 'admin', 'active');
}

app.use(express.urlencoded({ extended: false, limit: '2mb' }));
app.use(express.json({ limit: '2mb' }));
app.use(session({
  secret: process.env.SESSION_SECRET || 'ganti-secret-ini-di-production',
  resave: false, saveUninitialized: false,
  cookie: { httpOnly: true, sameSite: 'lax', maxAge: 8 * 60 * 60 * 1000 }
}));
app.use('/static', express.static(path.join(ROOT, 'public')));
app.use('/uploads', requireAuth, express.static(UPLOAD_DIR));

function esc(value = '') {
  return String(value).replace(/[&<>'"]/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[c]));
}
function settings() {
  return Object.fromEntries(db.prepare('SELECT key,value FROM settings').all().map(x => [x.key, x.value]));
}
function jakartaParts(date = new Date()) {
  const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Jakarta', year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit', hourCycle:'h23'
  }).formatToParts(date).filter(x => x.type !== 'literal').map(x => [x.type, x.value]));
  return { date: `${parts.year}-${parts.month}-${parts.day}`, time: `${parts.hour}:${parts.minute}:${parts.second}` };
}
function formatDateTime(value) {
  return new Intl.DateTimeFormat('id-ID', { timeZone:'Asia/Jakarta', dateStyle:'medium', timeStyle:'short' }).format(new Date(value));
}
function haversine(lat1, lon1, lat2, lon2) {
  const rad = d => d * Math.PI / 180;
  const a = Math.sin(rad(lat2-lat1)/2) ** 2 + Math.cos(rad(lat1))*Math.cos(rad(lat2))*Math.sin(rad(lon2-lon1)/2) ** 2;
  return 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}
function flash(req, type, message) { req.session.flash = { type, message }; }
function requireAuth(req, res, next) { if (!req.session.user) return res.redirect('/login'); next(); }
function requireRole(role) { return (req,res,next) => req.session.user?.role === role ? next() : res.status(403).send('Akses ditolak'); }

function layout(req, title, content) {
  const user = req.session.user;
  const notice = req.session.flash; delete req.session.flash;
  const nav = user ? `<nav><a class="brand" href="/${user.role === 'admin' ? 'admin' : 'dashboard'}"><span>PP</span> Presensi PPKD</a><div class="navlinks"><span>${esc(user.name)}</span><a href="/logout">Keluar</a></div></nav>` : `<nav><a class="brand" href="/"><span>PP</span> Presensi PPKD</a><div class="navlinks"><a href="/login">Masuk</a><a class="button small" href="/register">Daftar</a></div></nav>`;
  return `<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${esc(title)} · Presensi PPKD</title><link rel="stylesheet" href="/static/style.css"></head><body>${nav}<main>${notice ? `<div class="alert ${notice.type}">${esc(notice.message)}</div>` : ''}${content}</main><footer>PPKD Jakarta Barat · Sistem Presensi Peserta Pelatihan</footer><script src="/static/app.js"></script></body></html>`;
}

app.get('/', (req,res) => {
  if (req.session.user) return res.redirect(req.session.user.role === 'admin' ? '/admin' : '/dashboard');
  res.send(layout(req, 'Beranda', `<section class="hero"><div><div class="eyebrow">PRESENSI DIGITAL</div><h1>Hadir tepat waktu,<br><em>langsung dari lokasi.</em></h1><p>Absensi peserta pelatihan PPKD Jakarta Barat dengan verifikasi foto, lokasi, dan waktu secara real-time.</p><div class="actions"><a class="button" href="/login">Masuk sekarang</a><a class="button ghost" href="/register">Daftar peserta</a></div></div><div class="hero-card"><div class="phone"><div class="phone-head">Selamat pagi 👋</div><div class="status-ring"><b>20m</b><span>radius lokasi</span></div><div class="mock-row"><span>● Lokasi</span><strong>Terverifikasi</strong></div><div class="mock-row"><span>◷ Waktu</span><strong>WIB otomatis</strong></div><div class="mock-button">Ambil foto & absen</div></div></div></section><section class="features"><article><b>01</b><h3>Lokasi akurat</h3><p>Jarak dihitung ulang oleh server dari titik resmi PPKD.</p></article><article><b>02</b><h3>Foto langsung</h3><p>Bukti kehadiran diambil melalui kamera perangkat.</p></article><article><b>03</b><h3>Rekap transparan</h3><p>Peserta dan admin dapat memantau riwayat kehadiran.</p></article></section>`));
});

app.get('/register', (req,res) => res.send(layout(req, 'Daftar', authCard('Buat akun peserta','Daftarkan diri untuk mengikuti presensi pelatihan.', `<form method="post" action="/register" class="form"><label>Nama lengkap<input required name="name" autocomplete="name"></label><label>Nomor peserta<input required name="participant_number" placeholder="Contoh: PPKD-2026-001"></label><label>Program pelatihan<input required name="course" placeholder="Contoh: Teknik Komputer"></label><div class="form-grid"><label>Email<input required type="email" name="email"></label><label>Nomor WhatsApp<input required name="phone"></label></div><label>Kata sandi<input required minlength="8" type="password" name="password"></label><button class="button full">Kirim pendaftaran</button></form><p class="form-foot">Sudah terdaftar? <a href="/login">Masuk di sini</a></p>`))));
app.post('/register', (req,res) => {
  const { name, participant_number, course, email, phone, password } = req.body;
  if (![name,participant_number,course,email,phone,password].every(Boolean) || password.length < 8) { flash(req,'error','Data belum lengkap atau kata sandi kurang dari 8 karakter.'); return res.redirect('/register'); }
  try {
    db.prepare(`INSERT INTO users (name,participant_number,email,phone,course,password_hash) VALUES (?,?,?,?,?,?)`).run(name.trim(),participant_number.trim(),email.trim().toLowerCase(),phone.trim(),course.trim(),hashPassword(password));
    flash(req,'success','Pendaftaran berhasil. Tunggu akun disetujui admin sebelum masuk.'); res.redirect('/login');
  } catch { flash(req,'error','Email atau nomor peserta sudah digunakan.'); res.redirect('/register'); }
});
app.get('/login', (req,res) => res.send(layout(req, 'Masuk', authCard('Selamat datang','Masuk menggunakan akun peserta atau admin.', `<form method="post" action="/login" class="form"><label>Email<input required type="email" name="email" autocomplete="email"></label><label>Kata sandi<input required type="password" name="password" autocomplete="current-password"></label><button class="button full">Masuk</button></form><p class="form-foot">Belum punya akun? <a href="/register">Daftar peserta</a></p>`))));
function authCard(title, subtitle, body) { return `<section class="auth"><div class="auth-intro"><div class="eyebrow">PPKD JAKARTA BARAT</div><h1>${title}</h1><p>${subtitle}</p><div class="quote">“Disiplin hari ini adalah keahlian untuk masa depan.”</div></div><div class="card auth-card">${body}</div></section>`; }
app.post('/login', (req,res) => {
  const user = db.prepare('SELECT * FROM users WHERE email=?').get(String(req.body.email||'').toLowerCase());
  if (!user || !verifyPassword(req.body.password||'', user.password_hash)) { flash(req,'error','Email atau kata sandi salah.'); return res.redirect('/login'); }
  if (user.status !== 'active') { flash(req,'error','Akun belum disetujui atau sedang dinonaktifkan.'); return res.redirect('/login'); }
  req.session.user = { id:user.id, name:user.name, role:user.role }; res.redirect(user.role === 'admin' ? '/admin' : '/dashboard');
});
app.get('/logout', (req,res) => req.session.destroy(() => res.redirect('/login')));

app.get('/dashboard', requireAuth, requireRole('participant'), (req,res) => {
  const user = db.prepare('SELECT * FROM users WHERE id=?').get(req.session.user.id);
  const today = jakartaParts().date;
  const records = db.prepare('SELECT * FROM attendances WHERE user_id=? ORDER BY recorded_at DESC LIMIT 30').all(user.id);
  const todayRecords = Object.fromEntries(records.filter(x => x.attendance_date === today).map(x => [x.session_type,x]));
  const rows = records.map(x => `<tr><td>${esc(x.attendance_date)}</td><td><span class="pill">${x.session_type==='morning'?'Pagi':'Sore'}</span></td><td>${formatDateTime(x.recorded_at)}</td><td>${Math.round(x.distance)} m</td><td><span class="status ok">${esc(x.status)}</span></td></tr>`).join('') || `<tr><td colspan="5" class="empty">Belum ada riwayat absensi.</td></tr>`;
  res.send(layout(req,'Dashboard', `<header class="page-head"><div><div class="eyebrow">DASHBOARD PESERTA</div><h1>Halo, ${esc(user.name.split(' ')[0])}!</h1><p>${esc(user.course)} · ${esc(user.participant_number)}</p></div><div class="date-chip">${new Intl.DateTimeFormat('id-ID',{timeZone:'Asia/Jakarta',dateStyle:'full'}).format(new Date())}</div></header><section class="stats"><article><span>Kehadiran tercatat</span><strong>${records.length}</strong><small>30 catatan terbaru</small></article><article><span>Absen pagi hari ini</span><strong>${todayRecords.morning?'Sudah':'Belum'}</strong><small>${todayRecords.morning?formatDateTime(todayRecords.morning.recorded_at):'Silakan lakukan absensi'}</small></article><article><span>Absen sore hari ini</span><strong>${todayRecords.afternoon?'Sudah':'Belum'}</strong><small>${todayRecords.afternoon?formatDateTime(todayRecords.afternoon.recorded_at):'Silakan lakukan absensi'}</small></article></section><section class="dashboard-grid"><div class="card attendance-card"><div class="section-title"><div><h2>Absensi hari ini</h2><p>Aktifkan GPS dan izinkan akses kamera.</p></div><div id="liveClock" class="clock">--:--:-- WIB</div></div><form id="attendanceForm" method="post" action="/attendance" enctype="multipart/form-data"><div class="session-choice"><label><input type="radio" name="session_type" value="morning" ${todayRecords.morning?'disabled':''} checked><span>☀️ Pagi</span></label><label><input type="radio" name="session_type" value="afternoon" ${todayRecords.afternoon?'disabled':''}><span>🌤️ Sore</span></label></div><div class="camera-box"><video id="camera" autoplay playsinline></video><canvas id="snapshot" hidden></canvas><div id="cameraPlaceholder"><b>📷</b><span>Kamera belum aktif</span></div></div><input type="file" id="photo" name="photo" accept="image/jpeg,image/png" capture="user" hidden required><input type="hidden" name="latitude" id="latitude"><input type="hidden" name="longitude" id="longitude"><input type="hidden" name="accuracy" id="accuracy"><div id="locationState" class="location-state">⌖ Lokasi belum diperiksa</div><div class="actions"><button type="button" id="startCamera" class="button ghost">Aktifkan kamera</button><button type="button" id="takePhoto" class="button" disabled>Ambil foto</button></div><button id="submitAttendance" class="button full accent" disabled>Kirim absensi</button></form></div><aside class="card rules"><h2>Ketentuan</h2><ul><li>Berada maksimal <b>${esc(settings().radius)} meter</b> dari PPKD.</li><li>Foto harus diambil langsung saat absen.</li><li>Waktu mengikuti server zona WIB.</li><li>Satu kali absensi untuk setiap sesi.</li></ul><div class="privacy">Lokasi dan foto hanya digunakan sebagai bukti kehadiran.</div></aside></section><section class="card history"><div class="section-title"><div><h2>Riwayat kehadiran</h2><p>Catatan absensi terbaru Anda.</p></div></div><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Sesi</th><th>Waktu</th><th>Jarak</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table></div></section>`));
});

const upload = multer({ storage: multer.diskStorage({ destination:UPLOAD_DIR, filename:(req,file,cb)=>cb(null,`${req.session.user.id}-${Date.now()}-${crypto.randomBytes(4).toString('hex')}.jpg`) }), limits:{fileSize:5*1024*1024}, fileFilter:(req,file,cb)=>cb(null,['image/jpeg','image/png'].includes(file.mimetype)) });
app.post('/attendance', requireAuth, requireRole('participant'), upload.single('photo'), (req,res) => {
  const removePhoto = () => req.file && fs.unlink(req.file.path,()=>{});
  const s = settings(), lat=Number(req.body.latitude), lon=Number(req.body.longitude), accuracy=Number(req.body.accuracy), type=req.body.session_type;
  if (!req.file || !['morning','afternoon'].includes(type) || !Number.isFinite(lat) || !Number.isFinite(lon) || !Number.isFinite(accuracy)) { removePhoto(); flash(req,'error','Foto atau data lokasi tidak valid.'); return res.redirect('/dashboard'); }
  const now=jakartaParts(), time=now.time.slice(0,5), start=type==='morning'?s.morning_start:s.afternoon_start, end=type==='morning'?s.morning_end:s.afternoon_end;
  const distance=haversine(Number(s.latitude),Number(s.longitude),lat,lon);
  if (accuracy > Number(s.max_accuracy)) { removePhoto(); flash(req,'error',`Akurasi GPS terlalu rendah (${Math.round(accuracy)} m). Coba di area terbuka.`); return res.redirect('/dashboard'); }
  if (distance > Number(s.radius)) { removePhoto(); flash(req,'error',`Anda berada ${Math.round(distance)} meter dari titik PPKD. Batasnya ${s.radius} meter.`); return res.redirect('/dashboard'); }
  if (time < start || time > end) { removePhoto(); flash(req,'error',`Sesi ${type==='morning'?'pagi':'sore'} hanya dibuka pukul ${start}–${end} WIB.`); return res.redirect('/dashboard'); }
  try {
    db.prepare(`INSERT INTO attendances (user_id,attendance_date,session_type,latitude,longitude,accuracy,distance,photo_path,status,recorded_at,ip_address,user_agent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`).run(req.session.user.id,now.date,type,lat,lon,accuracy,distance,req.file.filename,'Hadir',new Date().toISOString(),req.ip,req.get('user-agent')||'');
    flash(req,'success',`Absensi ${type==='morning'?'pagi':'sore'} berhasil dicatat. Jarak ${Math.round(distance)} meter.`);
  } catch { removePhoto(); flash(req,'error','Absensi untuk sesi ini sudah pernah dicatat.'); }
  res.redirect('/dashboard');
});

app.get('/admin', requireAuth, requireRole('admin'), (req,res) => {
  const today=jakartaParts().date;
  const counts={ participants:db.prepare("SELECT COUNT(*) n FROM users WHERE role='participant'").get().n, pending:db.prepare("SELECT COUNT(*) n FROM users WHERE role='participant' AND status='pending'").get().n, present:db.prepare('SELECT COUNT(DISTINCT user_id) n FROM attendances WHERE attendance_date=?').get(today).n, records:db.prepare('SELECT COUNT(*) n FROM attendances WHERE attendance_date=?').get(today).n };
  const pending=db.prepare("SELECT * FROM users WHERE role='participant' ORDER BY created_at DESC").all();
  const recent=db.prepare(`SELECT a.*,u.name,u.participant_number,u.course FROM attendances a JOIN users u ON u.id=a.user_id ORDER BY a.recorded_at DESC LIMIT 50`).all();
  const participantRows=pending.map(u=>`<tr><td><b>${esc(u.name)}</b><small>${esc(u.email)}</small></td><td>${esc(u.participant_number||'-')}</td><td>${esc(u.course||'-')}</td><td><span class="status ${u.status==='active'?'ok':u.status==='pending'?'warn':'bad'}">${esc(u.status)}</span></td><td><form method="post" action="/admin/users/${u.id}/status" class="inline"><select name="status"><option value="active" ${u.status==='active'?'selected':''}>Aktif</option><option value="pending" ${u.status==='pending'?'selected':''}>Menunggu</option><option value="disabled" ${u.status==='disabled'?'selected':''}>Nonaktif</option></select><button class="mini">Simpan</button></form></td></tr>`).join('')||`<tr><td colspan="5" class="empty">Belum ada peserta.</td></tr>`;
  const attendanceRows=recent.map(a=>`<tr><td><b>${esc(a.name)}</b><small>${esc(a.participant_number)}</small></td><td>${esc(a.attendance_date)} · ${a.session_type==='morning'?'Pagi':'Sore'}</td><td>${formatDateTime(a.recorded_at)}</td><td>${Math.round(a.distance)} m <small>akurasi ${Math.round(a.accuracy)} m</small></td><td><a target="_blank" href="/uploads/${encodeURIComponent(a.photo_path)}">Lihat foto</a></td></tr>`).join('')||`<tr><td colspan="5" class="empty">Belum ada absensi.</td></tr>`;
  const s=settings();
  res.send(layout(req,'Panel Admin',`<header class="page-head"><div><div class="eyebrow">PANEL ADMINISTRATOR</div><h1>Ringkasan presensi</h1><p>Kelola peserta, lokasi, jadwal, dan bukti kehadiran.</p></div><div class="date-chip">${new Intl.DateTimeFormat('id-ID',{timeZone:'Asia/Jakarta',dateStyle:'full'}).format(new Date())}</div></header><section class="stats four"><article><span>Total peserta</span><strong>${counts.participants}</strong><small>Semua akun peserta</small></article><article><span>Perlu persetujuan</span><strong>${counts.pending}</strong><small>Pendaftaran baru</small></article><article><span>Hadir hari ini</span><strong>${counts.present}</strong><small>Peserta unik</small></article><article><span>Catatan hari ini</span><strong>${counts.records}</strong><small>Pagi dan sore</small></article></section><section class="card history"><div class="section-title"><div><h2>Manajemen peserta</h2><p>Aktifkan pendaftaran sebelum peserta dapat masuk.</p></div></div><div class="table-wrap"><table><thead><tr><th>Peserta</th><th>Nomor</th><th>Pelatihan</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>${participantRows}</tbody></table></div></section><section class="admin-grid"><div class="card history"><div class="section-title"><div><h2>Absensi terbaru</h2><p>50 catatan terakhir.</p></div><a class="button small ghost" href="/admin/export.csv">Ekspor CSV</a></div><div class="table-wrap"><table><thead><tr><th>Peserta</th><th>Sesi</th><th>Waktu</th><th>Lokasi</th><th>Bukti</th></tr></thead><tbody>${attendanceRows}</tbody></table></div></div><div class="card settings"><h2>Pengaturan lokasi & waktu</h2><form method="post" action="/admin/settings" class="form"><label>Nama instansi<input name="institution_name" value="${esc(s.institution_name)}"></label><label>Alamat<input name="institution_address" value="${esc(s.institution_address)}"></label><div class="form-grid"><label>Latitude<input type="number" step="any" name="latitude" value="${esc(s.latitude)}"></label><label>Longitude<input type="number" step="any" name="longitude" value="${esc(s.longitude)}"></label></div><div class="form-grid"><label>Radius (meter)<input type="number" min="5" name="radius" value="${esc(s.radius)}"></label><label>Maks. akurasi GPS<input type="number" min="5" name="max_accuracy" value="${esc(s.max_accuracy)}"></label></div><h3>Jadwal WIB</h3><div class="form-grid"><label>Pagi mulai<input type="time" name="morning_start" value="${esc(s.morning_start)}"></label><label>Pagi selesai<input type="time" name="morning_end" value="${esc(s.morning_end)}"></label><label>Sore mulai<input type="time" name="afternoon_start" value="${esc(s.afternoon_start)}"></label><label>Sore selesai<input type="time" name="afternoon_end" value="${esc(s.afternoon_end)}"></label></div><button class="button full">Simpan pengaturan</button></form></div></section>`));
});
app.post('/admin/users/:id/status',requireAuth,requireRole('admin'),(req,res)=>{ if(['active','pending','disabled'].includes(req.body.status)) db.prepare("UPDATE users SET status=? WHERE id=? AND role='participant'").run(req.body.status,req.params.id); flash(req,'success','Status peserta diperbarui.');res.redirect('/admin'); });
app.post('/admin/settings',requireAuth,requireRole('admin'),(req,res)=>{ const allowed=Object.keys(defaults); const up=db.prepare('INSERT OR REPLACE INTO settings (key,value) VALUES (?,?)'); const tx=db.transaction(()=>allowed.forEach(k=>{if(req.body[k]!==undefined)up.run(k,String(req.body[k]).trim())}));tx();flash(req,'success','Pengaturan berhasil disimpan.');res.redirect('/admin'); });
app.get('/admin/export.csv',requireAuth,requireRole('admin'),(req,res)=>{ const rows=db.prepare(`SELECT u.participant_number,u.name,u.course,a.attendance_date,a.session_type,a.recorded_at,a.distance,a.accuracy,a.status FROM attendances a JOIN users u ON u.id=a.user_id ORDER BY a.recorded_at DESC`).all(); const cols=['participant_number','name','course','attendance_date','session_type','recorded_at','distance','accuracy','status']; const csv=[cols.join(','),...rows.map(r=>cols.map(c=>`"${String(r[c]??'').replaceAll('"','""')}"`).join(','))].join('\r\n');res.set({'Content-Type':'text/csv; charset=utf-8','Content-Disposition':'attachment; filename="rekap-absensi.csv"'}).send('\ufeff'+csv); });

app.use((err,req,res,next)=>{ console.error(err); if(err instanceof multer.MulterError) { flash(req,'error','Ukuran foto maksimal 5 MB.'); return res.redirect('/dashboard'); } res.status(500).send(layout(req,'Kesalahan','<section class="card error-page"><h1>Terjadi kesalahan</h1><p>Silakan coba kembali.</p></section>')); });
if (require.main === module) app.listen(PORT,()=>console.log(`Presensi PPKD berjalan di http://localhost:${PORT}`));
module.exports={app,haversine,jakartaParts};
