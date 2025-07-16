import { Database } from 'bun:sqlite';

const db = new Database('plagiarism.sqlite');

// function để tạo các table cần thiết khi server start
export const initializeDB = () => {
  try {
    // Bảng theo dõi các job kiểm tra
    db.exec(`
      CREATE TABLE IF NOT EXISTS jobs (
        id TEXT PRIMARY KEY,
        status TEXT NOT NULL CHECK(status IN ('queued', 'processing', 'completed', 'error')),
        score INTEGER,
        result TEXT,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);

    // Table lưu trữ nội dung các bài nộp để so sánh (cho phương pháp offline)
    db.exec(`
      CREATE TABLE IF NOT EXISTS submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        content TEXT NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);
    
    console.log('✅ Database initialized successfully.');
  } catch (error) {
    console.error('❌ Failed to initialize database:', error);
  }
};

export default db;