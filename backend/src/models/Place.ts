import db from '../database/schema';

export interface Place {
  id?: number;
  name: string;
  phone?: string;
  address?: string;
  latitude?: number;
  longitude?: number;
  keyword: string;
  country?: string;
  province?: string;
  city?: string;
  district?: string;
  ward?: string;
  created_at?: string;
  updated_at?: string;
}

export interface PlaceFilters {
  keyword?: string;
  country?: string;
  province?: string;
  city?: string;
  district?: string;
  ward?: string;
  search?: string;
}

export class PlaceModel {
  static create(place: Place): number {
    const stmt = db.prepare(`
      INSERT INTO places (name, phone, address, latitude, longitude, keyword, country, province, city, district, ward)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `);

    const result = stmt.run(
      place.name,
      place.phone || null,
      place.address || null,
      place.latitude || null,
      place.longitude || null,
      place.keyword,
      place.country || null,
      place.province || null,
      place.city || null,
      place.district || null,
      place.ward || null
    );

    return result.lastInsertRowid as number;
  }

  static findAll(filters: PlaceFilters = {}, limit = 100, offset = 0): Place[] {
    let query = 'SELECT * FROM places WHERE 1=1';
    const params: any[] = [];

    if (filters.keyword) {
      query += ' AND keyword = ?';
      params.push(filters.keyword);
    }

    if (filters.country) {
      query += ' AND country = ?';
      params.push(filters.country);
    }

    if (filters.province) {
      query += ' AND province = ?';
      params.push(filters.province);
    }

    if (filters.city) {
      query += ' AND city = ?';
      params.push(filters.city);
    }

    if (filters.district) {
      query += ' AND district = ?';
      params.push(filters.district);
    }

    if (filters.ward) {
      query += ' AND ward = ?';
      params.push(filters.ward);
    }

    if (filters.search) {
      query += ' AND (name LIKE ? OR address LIKE ? OR phone LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }

    query += ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
    params.push(limit, offset);

    const stmt = db.prepare(query);
    return stmt.all(...params) as Place[];
  }

  static count(filters: PlaceFilters = {}): number {
    let query = 'SELECT COUNT(*) as count FROM places WHERE 1=1';
    const params: any[] = [];

    if (filters.keyword) {
      query += ' AND keyword = ?';
      params.push(filters.keyword);
    }

    if (filters.country) {
      query += ' AND country = ?';
      params.push(filters.country);
    }

    if (filters.province) {
      query += ' AND province = ?';
      params.push(filters.province);
    }

    if (filters.city) {
      query += ' AND city = ?';
      params.push(filters.city);
    }

    if (filters.district) {
      query += ' AND district = ?';
      params.push(filters.district);
    }

    if (filters.ward) {
      query += ' AND ward = ?';
      params.push(filters.ward);
    }

    if (filters.search) {
      query += ' AND (name LIKE ? OR address LIKE ? OR phone LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }

    const stmt = db.prepare(query);
    const result = stmt.get(...params) as { count: number };
    return result.count;
  }

  static getUniqueValues(column: 'country' | 'province' | 'city' | 'district' | 'ward'): string[] {
    const stmt = db.prepare(`SELECT DISTINCT ${column} FROM places WHERE ${column} IS NOT NULL ORDER BY ${column}`);
    const results = stmt.all() as any[];
    return results.map(r => r[column]);
  }

  static deleteAll(): void {
    db.prepare('DELETE FROM places').run();
  }
}
