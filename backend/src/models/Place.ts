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
  quality_score?: number;
  has_phone?: boolean;
  has_address?: boolean;
  has_coords?: boolean;
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
  minQuality?: number;
}

export interface QualityReport {
  total: number;
  withPhone: number;
  withAddress: number;
  withCoords: number;
  avgQuality: number;
  excellent: number;
  good: number;
  poor: number;
}

export class PlaceModel {
  // Calculate quality score (0-100)
  static calculateQuality(place: Partial<Place>): number {
    let score = 20; // Base score for having a name

    if (place.phone && place.phone.length >= 8) score += 30;
    if (place.address && place.address.length >= 10) score += 30;
    if (place.latitude && place.longitude) score += 20;

    return score;
  }
  static create(place: Place): number {
    const qualityScore = this.calculateQuality(place);

    const stmt = db.prepare(`
      INSERT INTO places (
        name, phone, address, latitude, longitude,
        keyword, country, province, city, district, ward,
        quality_score, has_phone, has_address, has_coords
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
      place.ward || null,
      qualityScore,
      place.phone ? 1 : 0,
      place.address ? 1 : 0,
      (place.latitude && place.longitude) ? 1 : 0
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

    if (filters.minQuality !== undefined) {
      query += ' AND quality_score >= ?';
      params.push(filters.minQuality);
    }

    query += ' ORDER BY quality_score DESC, created_at DESC LIMIT ? OFFSET ?';
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

    if (filters.minQuality !== undefined) {
      query += ' AND quality_score >= ?';
      params.push(filters.minQuality);
    }

    const stmt = db.prepare(query);
    const result = stmt.get(...params) as { count: number };
    return result.count;
  }

  static getQualityReport(filters: PlaceFilters = {}): QualityReport {
    let query = `
      SELECT
        COUNT(*) as total,
        SUM(CASE WHEN has_phone = 1 THEN 1 ELSE 0 END) as withPhone,
        SUM(CASE WHEN has_address = 1 THEN 1 ELSE 0 END) as withAddress,
        SUM(CASE WHEN has_coords = 1 THEN 1 ELSE 0 END) as withCoords,
        AVG(quality_score) as avgQuality,
        SUM(CASE WHEN quality_score >= 80 THEN 1 ELSE 0 END) as excellent,
        SUM(CASE WHEN quality_score >= 60 AND quality_score < 80 THEN 1 ELSE 0 END) as good,
        SUM(CASE WHEN quality_score < 60 THEN 1 ELSE 0 END) as poor
      FROM places WHERE 1=1
    `;
    const params: any[] = [];

    if (filters.keyword) {
      query += ' AND keyword = ?';
      params.push(filters.keyword);
    }

    const stmt = db.prepare(query);
    const result = stmt.get(...params) as any;

    return {
      total: result.total || 0,
      withPhone: result.withPhone || 0,
      withAddress: result.withAddress || 0,
      withCoords: result.withCoords || 0,
      avgQuality: Math.round(result.avgQuality || 0),
      excellent: result.excellent || 0,
      good: result.good || 0,
      poor: result.poor || 0
    };
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
