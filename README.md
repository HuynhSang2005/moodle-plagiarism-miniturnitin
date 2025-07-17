# MiniTurnitin – Plugin kiểm tra đạo văn cho Moodle

MiniTurnitin là một plugin Moodle tích hợp với một API tự phát triển nhằm kiểm tra mức độ trùng lặp văn bản của các bài nộp trong mô-đun `mod_assign`. Dự án bao gồm:

1. **Plugin Moodle**: lắng nghe sự kiện nộp bài, trích xuất nội dung từ file `.txt`, `.pdf`, `.docx` và gửi đến API.
2. **API Server**: nhận nội dung văn bản, so sánh với các bài đã nộp trước đó và trả về điểm số đạo văn.

---

## 🧩 Tính năng chính

- Tự động gửi văn bản đã nộp đến server kiểm tra đạo văn
- Trích xuất nội dung từ `.pdf`, `.docx`, `.txt` (dùng `pdfparser` và `phpword`)
- Cron job Moodle theo dõi trạng thái job (5 phút/lần)
- API RESTful kiểm tra và trả kết quả mức độ đạo văn
- Hỗ trợ kiểm tra token (Bearer) và validation bằng `Zod`

---

## 📁 Cấu trúc thư mục

```

moodle-dev/
├── miniturnitin/       # Plugin Moodle
└── src/                # API kiểm tra đạo văn

miniturnitin/
├── classes/
│   └── observer.php             # Lắng nghe event và gửi job
├── db/
│   ├── install.xml              # Định nghĩa bảng CSDL
│   ├── tasks.php                # Đăng ký cron job
│   ├── task/check_status_task.php
│   └── events.php              # Đăng ký event
├── lang/en/plagiarism_miniturnitin.php
├── settings.php                # Giao diện cấu hình admin
├── version.php
└── vendor/                     # Thư viện bên thứ 3: phpword, pdfparser...

src/
├── api/
│   ├── controllers/    # Xử lý logic submit & status
│   ├── middlewares/    # Middleware xác thực & validate
│   ├── routes/v1/      # Định tuyến các endpoint /api/v1/jobs
│   └── validators/     # Schema validate bằng Zod
├── services/           # DB + xử lý kiểm tra đạo văn
├── index.ts            # Entry point Express server

```

---

## 🔧 Cài đặt Plugin Moodle

1. **Sao chép plugin vào Moodle**:
```

cp -r moodle-dev/miniturnitin \<moodle\_root>/plagiarism/miniturnitin

```

2. **Truy cập trang Admin Moodle** để hoàn tất cài đặt:
```

http\://<your-moodle-site>/admin

````

3. **Cấu hình Plugin tại**:
`Site administration → Plugins → Plagiarism → MiniTurnitin`

Thiết lập:
- `API URL`: URL đến server API
- `API Secret Key`: token Bearer để xác thực

4. **Kích hoạt plugin ở mod_assign**:
Trong phần "Cài đặt ngăn ngừa đạo văn" khi tạo bài tập.

---

## 🚀 Khởi chạy API Server

```bash
# Cài dependency
bun install

# Khởi chạy server
bun run index.ts
````

Server sẽ chạy tại: `http://localhost:3000`

---

## 📡 API Endpoints

### Gửi bài kiểm tra đạo văn

`POST /api/v1/jobs`

**Headers**:
`Authorization: Bearer <API_SECRET_KEY>`

**Body**:

```json
{
  "text": "Văn bản cần kiểm tra..."
}
```

**Response** `202 Accepted`:

```json
{
  "jobId": "uuid-...",
  "message": "Job has been accepted and is queued for processing."
}
```

---

### Lấy trạng thái & kết quả kiểm tra

`GET /api/v1/jobs/{jobId}`

**Headers**:
`Authorization: Bearer <API_SECRET_KEY>`

**Response** `200 OK`:

```json
{
  "id": "uuid-...",
  "status": "completed",
  "score": 45,
  "createdAt": "2025-07-17T12:00:00Z"
}
```

---

## 🛠 Công nghệ sử dụng

| Thành phần       | Công nghệ                           |
| ---------------- | ----------------------------------- |
| Moodle Plugin    | PHP, PDFParser, PhpWord             |
| API Server       | TypeScript (Bun.js), Express        |
| DB               | SQLite                              |
| Xử lý tương đồng | `string-similarity` (findBestMatch) |
| Validation       | Zod                                 |
| Auth             | Bearer Token Middleware             |

---

## 🔍 Cách tính điểm đạo văn

1. Văn bản được tách thành các câu (bằng dấu `.?!;`)
2. So sánh từng câu với các câu trong tất cả các bài nộp trước đó
3. Sử dụng `findBestMatch` để đo mức độ tương đồng
4. Nếu tương đồng ≥ 80%, tính là "trùng"
5. Tỷ lệ số câu trùng được quy đổi thành phần trăm (`score`)

---

## 🧠 Hạn chế & Ghi chú

* Không hỗ trợ nhiều file nộp → chỉ lấy file đầu tiên
* Không hỗ trợ `.doc` hoặc `.rtf`
* API hiện chỉ xử lý nội bộ (không phân quyền người dùng)
* Chưa có UI hiển thị điểm trên Moodle (phải kiểm tra CSDL thủ công)

