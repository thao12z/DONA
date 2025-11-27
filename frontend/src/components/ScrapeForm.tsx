import React, { useState } from 'react';
import { placesAPI, ScrapeRequest } from '../api/places';

interface ScrapeFormProps {
  onScrapeComplete: () => void;
}

export const ScrapeForm: React.FC<ScrapeFormProps> = ({ onScrapeComplete }) => {
  const [formData, setFormData] = useState<ScrapeRequest>({
    keyword: '',
    country: '',
    province: '',
    city: '',
    district: '',
    ward: '',
    maxResults: 20
  });

  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.keyword.trim()) {
      setMessage({ type: 'error', text: 'Vui lòng nhập từ khóa' });
      return;
    }

    setLoading(true);
    setMessage(null);

    try {
      const result = await placesAPI.scrapePlaces(formData);
      setMessage({
        type: 'success',
        text: `Đã cào thành công ${result.data.saved} địa điểm!`
      });
      onScrapeComplete();

      // Reset form
      setFormData({
        keyword: '',
        country: '',
        province: '',
        city: '',
        district: '',
        ward: '',
        maxResults: 20
      });
    } catch (error: any) {
      setMessage({
        type: 'error',
        text: error.response?.data?.error || 'Có lỗi xảy ra khi cào dữ liệu'
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="card">
      <h2>🔍 Cào dữ liệu Google Maps</h2>

      {message && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>Từ khóa *</label>
          <input
            type="text"
            name="keyword"
            value={formData.keyword}
            onChange={handleChange}
            placeholder="Ví dụ: nhà hàng, khách sạn, spa..."
            required
          />
        </div>

        <div className="form-row">
          <div className="form-group">
            <label>Quốc gia</label>
            <input
              type="text"
              name="country"
              value={formData.country}
              onChange={handleChange}
              placeholder="Việt Nam"
            />
          </div>

          <div className="form-group">
            <label>Tỉnh/Thành phố</label>
            <input
              type="text"
              name="province"
              value={formData.province}
              onChange={handleChange}
              placeholder="Hồ Chí Minh"
            />
          </div>

          <div className="form-group">
            <label>Thành phố/Huyện</label>
            <input
              type="text"
              name="city"
              value={formData.city}
              onChange={handleChange}
              placeholder="Thành phố Thủ Đức"
            />
          </div>
        </div>

        <div className="form-row">
          <div className="form-group">
            <label>Quận/Huyện</label>
            <input
              type="text"
              name="district"
              value={formData.district}
              onChange={handleChange}
              placeholder="Quận 1"
            />
          </div>

          <div className="form-group">
            <label>Phường/Xã</label>
            <input
              type="text"
              name="ward"
              value={formData.ward}
              onChange={handleChange}
              placeholder="Phường Bến Nghé"
            />
          </div>

          <div className="form-group">
            <label>Số lượng tối đa</label>
            <input
              type="number"
              name="maxResults"
              value={formData.maxResults}
              onChange={handleChange}
              min="1"
              max="100"
            />
          </div>
        </div>

        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Đang cào dữ liệu...' : 'Bắt đầu cào'}
        </button>
      </form>

      {loading && <div className="spinner"></div>}
    </div>
  );
};
