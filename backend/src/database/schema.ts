import Database from 'better-sqlite3';
import path from 'path';
import fs from 'fs';

const dbDir = path.join(__dirname, '../../data');
if (!fs.existsSync(dbDir)) {
  fs.mkdirSync(dbDir, { recursive: true });
}

const dbPath = process.env.DATABASE_PATH || path.join(dbDir, 'scraper.db');
const db = new Database(dbPath);

// Enable foreign keys
db.pragma('foreign_keys = ON');

// Create tables
db.exec(`
  CREATE TABLE IF NOT EXISTS places (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    address TEXT,
    latitude REAL,
    longitude REAL,
    keyword TEXT NOT NULL,
    country TEXT,
    province TEXT,
    city TEXT,
    district TEXT,
    ward TEXT,
    quality_score INTEGER DEFAULT 0,
    has_phone INTEGER DEFAULT 0,
    has_address INTEGER DEFAULT 0,
    has_coords INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE INDEX IF NOT EXISTS idx_keyword ON places(keyword);
  CREATE INDEX IF NOT EXISTS idx_country ON places(country);
  CREATE INDEX IF NOT EXISTS idx_province ON places(province);
  CREATE INDEX IF NOT EXISTS idx_city ON places(city);
  CREATE INDEX IF NOT EXISTS idx_district ON places(district);
  CREATE INDEX IF NOT EXISTS idx_ward ON places(ward);
  CREATE INDEX IF NOT EXISTS idx_quality ON places(quality_score);
`);

export default db;
