import React, { useState, useEffect } from 'react';
import { placesAPI, Place, PlaceFilters } from '../api/places';

interface PlacesListProps {
  refreshTrigger: number;
}

export const PlacesList: React.FC<PlacesListProps> = ({ refreshTrigger }) => {
  const [places, setPlaces] = useState<Place[]>([]);
  const [loading, setLoading] = useState(false);
  const [total, setTotal] = useState(0);
  const [filters, setFilters] = useState<PlaceFilters>({
    limit: 50,
    offset: 0
  });

  const [filterOptions, setFilterOptions] = useState({
    countries: [] as string[],
    provinces: [] as string[],
    cities: [] as string[],
    districts: [] as string[]
  });

  useEffect(() => {
    loadFilterOptions();
  }, [refreshTrigger]);

  useEffect(() => {
    loadPlaces();
  }, [filters, refreshTrigger]);

  const loadFilterOptions = async () => {
    try {
      const [countries, provinces, cities, districts] = await Promise.all([
        placesAPI.getFilterValues('country'),
        placesAPI.getFilterValues('province'),
        placesAPI.getFilterValues('city'),
        placesAPI.getFilterValues('district')
      ]);

      setFilterOptions({
        countries: countries.data,
        provinces: provinces.data,
        cities: cities.data,
        districts: districts.data
      });
    } catch (error) {
      console.error('Error loading filter options:', error);
    }
  };

  const loadPlaces = async () => {
    setLoading(true);
    try {
      const response = await placesAPI.getPlaces(filters);
      setPlaces(response.data);
      setTotal(response.pagination.total);
    } catch (error) {
      console.error('Error loading places:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFilters(prev => ({ ...prev, [name]: value || undefined, offset: 0 }));
  };

  const handlePageChange = (direction: 'next' | 'prev') => {
    setFilters(prev => ({
      ...prev,
      offset: direction === 'next'
        ? (prev.offset || 0) + (prev.limit || 50)
        : Math.max(0, (prev.offset || 0) - (prev.limit || 50))
    }));
  };

  const handleDeleteAll = async () => {
    if (!window.confirm('Bạn có chắc muốn xóa toàn bộ dữ liệu?')) {
      return;
    }

    try {
      await placesAPI.deleteAllPlaces();
      loadPlaces();
      loadFilterOptions();
    } catch (error) {
      alert('Có lỗi khi xóa dữ liệu');
    }
  };

  const handleExportCSV = () => {
    const params = new URLSearchParams();
    if (filters.keyword) params.append('keyword', filters.keyword);
    if (filters.country) params.append('country', filters.country);
    if (filters.province) params.append('province', filters.province);
    if (filters.city) params.append('city', filters.city);
    if (filters.district) params.append('district', filters.district);
    if (filters.search) params.append('search', filters.search);

    window.open(`/api/places/export/csv?${params.toString()}`, '_blank');
  };

  const handleExportJSON = () => {
    const params = new URLSearchParams();
    if (filters.keyword) params.append('keyword', filters.keyword);
    if (filters.country) params.append('country', filters.country);
    if (filters.province) params.append('province', filters.province);
    if (filters.city) params.append('city', filters.city);
    if (filters.district) params.append('district', filters.district);
    if (filters.search) params.append('search', filters.search);

    window.open(`/api/places/export/json?${params.toString()}`, '_blank');
  };

  const currentPage = Math.floor((filters.offset || 0) / (filters.limit || 50)) + 1;
  const totalPages = Math.ceil(total / (filters.limit || 50));

  return (
    <div className="card">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2>📊 Dữ liệu đã thu thập ({total})</h2>
        {total > 0 && (
          <div style={{ display: 'flex', gap: '10px' }}>
            <button className="btn btn-primary" onClick={handleExportCSV}>
              📥 Export CSV
            </button>
            <button className="btn btn-primary" onClick={handleExportJSON}>
              📥 Export JSON
            </button>
            <button className="btn btn-danger" onClick={handleDeleteAll}>
              🗑️ Xóa tất cả
            </button>
          </div>
        )}
      </div>

      {/* Filters */}
      <div className="form-row" style={{ marginBottom: '20px' }}>
        <div className="form-group">
          <label>Tìm kiếm</label>
          <input
            type="text"
            name="search"
            value={filters.search || ''}
            onChange={handleFilterChange}
            placeholder="Tên, địa chỉ, SĐT..."
          />
        </div>

        <div className="form-group">
          <label>Quốc gia</label>
          <select name="country" value={filters.country || ''} onChange={handleFilterChange}>
            <option value="">Tất cả</option>
            {filterOptions.countries.map(c => (
              <option key={c} value={c}>{c}</option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label>Tỉnh/Thành</label>
          <select name="province" value={filters.province || ''} onChange={handleFilterChange}>
            <option value="">Tất cả</option>
            {filterOptions.provinces.map(p => (
              <option key={p} value={p}>{p}</option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label>Thành phố</label>
          <select name="city" value={filters.city || ''} onChange={handleFilterChange}>
            <option value="">Tất cả</option>
            {filterOptions.cities.map(c => (
              <option key={c} value={c}>{c}</option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label>Quận/Huyện</label>
          <select name="district" value={filters.district || ''} onChange={handleFilterChange}>
            <option value="">Tất cả</option>
            {filterOptions.districts.map(d => (
              <option key={d} value={d}>{d}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Table */}
      {loading ? (
        <div className="loading">
          <div className="spinner"></div>
          <p>Đang tải dữ liệu...</p>
        </div>
      ) : places.length === 0 ? (
        <div className="empty-state">
          <p>Chưa có dữ liệu. Hãy bắt đầu cào dữ liệu từ Google Maps!</p>
        </div>
      ) : (
        <>
          <div className="table-container">
            <table>
              <thead>
                <tr>
                  <th>Tên</th>
                  <th>Số điện thoại</th>
                  <th>Địa chỉ</th>
                  <th>Tọa độ</th>
                  <th>Từ khóa</th>
                  <th>Vị trí</th>
                </tr>
              </thead>
              <tbody>
                {places.map(place => (
                  <tr key={place.id}>
                    <td><strong>{place.name}</strong></td>
                    <td>{place.phone || '-'}</td>
                    <td>{place.address || '-'}</td>
                    <td>
                      {place.latitude && place.longitude ? (
                        <a
                          href={`https://www.google.com/maps?q=${place.latitude},${place.longitude}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          style={{ color: '#667eea' }}
                        >
                          {place.latitude.toFixed(6)}, {place.longitude.toFixed(6)}
                        </a>
                      ) : '-'}
                    </td>
                    <td><span style={{ background: '#e3f2fd', padding: '4px 8px', borderRadius: '4px' }}>{place.keyword}</span></td>
                    <td style={{ fontSize: '0.9rem', color: '#666' }}>
                      {[place.ward, place.district, place.city, place.province, place.country]
                        .filter(Boolean)
                        .join(', ')}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          <div className="pagination">
            <button
              onClick={() => handlePageChange('prev')}
              disabled={(filters.offset || 0) === 0}
            >
              ← Trang trước
            </button>
            <span>
              Trang {currentPage} / {totalPages}
            </span>
            <button
              onClick={() => handlePageChange('next')}
              disabled={(filters.offset || 0) + (filters.limit || 50) >= total}
            >
              Trang sau →
            </button>
          </div>
        </>
      )}
    </div>
  );
};
