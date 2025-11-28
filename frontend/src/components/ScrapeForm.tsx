import React, { useState } from 'react';
import { placesAPI, ScrapeRequest } from '../api/places';

interface ScrapeFormProps {
  onScrapeComplete: () => void;
}

// Presets cho các loại hình kinh doanh phổ biến
const BUSINESS_PRESETS = [
  { label: '🍜 Hàng quán', keywords: ['nhà hàng', 'quán ăn', 'quán cơm', 'quán phở', 'quán bún', 'quán lẩu', 'cà phê', 'quán cafe'] },
  { label: '🏪 Tạp hoá', keywords: ['tạp hoá', 'cửa hàng tạp hoá', 'siêu thị mini', 'cửa hàng tiện lợi', 'mini mart'] },
  { label: '🏭 Đại lý', keywords: ['đại lý', 'nhà phân phối', 'cửa hàng đại lý', 'điểm bán'] },
  { label: '💊 Nhà thuốc', keywords: ['nhà thuốc', 'hiệu thuốc', 'pharmacy', 'quầy thuốc'] },
  { label: '✂️ Tiệm tóc/Spa', keywords: ['salon tóc', 'tiệm cắt tóc', 'spa', 'nail', 'thẩm mỹ viện'] },
  { label: '🏨 Khách sạn', keywords: ['khách sạn', 'hotel', 'resort', 'nhà nghỉ', 'homestay'] },
  { label: '🏥 Y tế', keywords: ['phòng khám', 'bệnh viện', 'nha khoa', 'clinic'] },
  { label: '🎓 Giáo dục', keywords: ['trường học', 'trung tâm', 'học viện', 'đào tạo'] },
  { label: '🛒 Siêu thị', keywords: ['siêu thị', 'trung tâm thương mại', 'mart', 'shopping center'] },
];

export const ScrapeForm: React.FC<ScrapeFormProps> = ({ onScrapeComplete }) => {
  const [formData, setFormData] = useState<ScrapeRequest>({
    keyword: '',
    country: '',
    province: '',
    city: '',
    district: '',
    ward: '',
    maxResults: 50
  });

  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [selectedPreset, setSelectedPreset] = useState<string>('');
  const [batchMode, setBatchMode] = useState(false);
  const [batchKeywords, setBatchKeywords] = useState<string>('');
  const [deepMode, setDeepMode] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handlePresetSelect = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const presetLabel = e.target.value;
    setSelectedPreset(presetLabel);

    if (presetLabel) {
      const preset = BUSINESS_PRESETS.find(p => p.label === presetLabel);
      if (preset) {
        setBatchMode(true);
        setBatchKeywords(preset.keywords.join('\n'));
      }
    } else {
      setBatchMode(false);
      setBatchKeywords('');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const keywords = batchMode
      ? batchKeywords.split('\n').filter(k => k.trim())
      : [formData.keyword];

    if (keywords.length === 0 || keywords.every(k => !k.trim())) {
      setMessage({ type: 'error', text: 'Vui lòng nhập ít nhất 1 từ khóa' });
      return;
    }

    setLoading(true);
    setMessage(null);

    try {
      let totalScraped = 0;
      let totalSaved = 0;
      let allQuality = {
        withPhone: 0,
        withAddress: 0,
        withCoords: 0,
        total: 0
      };

      for (let i = 0; i < keywords.length; i++) {
        const keyword = keywords[i].trim();
        if (!keyword) continue;

        setMessage({
          type: 'success',
          text: `Đang cào từ khóa ${i + 1}/${keywords.length}: "${keyword}"...`
        });

        const result = await placesAPI.scrapePlaces({
          ...formData,
          keyword,
          maxResults: deepMode ? 100 : formData.maxResults
        });

        totalScraped += result.data.scraped;
        totalSaved += result.data.saved;
        allQuality.withPhone += result.data.quality.withPhone;
        allQuality.withAddress += result.data.quality.withAddress;
        allQuality.withCoords += result.data.quality.withCoords;
        allQuality.total += result.data.scraped;

        // Small delay between keywords
        if (i < keywords.length - 1) {
          await new Promise(resolve => setTimeout(resolve, 2000));
        }
      }

      const avgPhoneRate = allQuality.total > 0 ? Math.round((allQuality.withPhone / allQuality.total) * 100) : 0;
      const avgAddressRate = allQuality.total > 0 ? Math.round((allQuality.withAddress / allQuality.total) * 100) : 0;
      const avgCoordsRate = allQuality.total > 0 ? Math.round((allQuality.withCoords / allQuality.total) * 100) : 0;
      const overallQuality = Math.round((avgPhoneRate + avgAddressRate + avgCoordsRate) / 3);

      const qualityText = `✅ HOÀN THÀNH!

📊 Tổng kết:
• Đã cào: ${totalScraped} địa điểm từ ${keywords.length} từ khóa
• Đã lưu: ${totalSaved} địa điểm

📈 Chất lượng dữ liệu:
• Có SĐT: ${allQuality.withPhone}/${allQuality.total} (${avgPhoneRate}%)
• Có địa chỉ: ${allQuality.withAddress}/${allQuality.total} (${avgAddressRate}%)
• Có tọa độ: ${allQuality.withCoords}/${allQuality.total} (${avgCoordsRate}%)
• Điểm TB: ${overallQuality}/100

${overallQuality >= 70 ? '✅ Chất lượng TỐT!' : overallQuality >= 50 ? '⚠️ Chất lượng TRUNG BÌNH' : '❌ Chất lượng THẤP - nên cào lại với vị trí cụ thể hơn'}`;

      setMessage({
        type: overallQuality >= 50 ? 'success' : 'error',
        text: qualityText
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
        maxResults: 50
      });
      setBatchKeywords('');
      setSelectedPreset('');
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
        {/* Preset selector */}
        <div className="form-group">
          <label>⚡ Chọn loại hình kinh doanh (nhanh)</label>
          <select
            value={selectedPreset}
            onChange={handlePresetSelect}
            className="form-control"
          >
            <option value="">-- Hoặc nhập từ khóa thủ công --</option>
            {BUSINESS_PRESETS.map(preset => (
              <option key={preset.label} value={preset.label}>
                {preset.label} ({preset.keywords.length} từ khóa)
              </option>
            ))}
          </select>
        </div>

        {/* Batch mode */}
        {batchMode ? (
          <div className="form-group">
            <label>
              Danh sách từ khóa (mỗi dòng 1 từ khóa)
              <button
                type="button"
                onClick={() => { setBatchMode(false); setBatchKeywords(''); setSelectedPreset(''); }}
                style={{ marginLeft: '10px', fontSize: '0.85rem' }}
              >
                ✕ Huỷ batch
              </button>
            </label>
            <textarea
              value={batchKeywords}
              onChange={(e) => setBatchKeywords(e.target.value)}
              rows={8}
              style={{ width: '100%', padding: '10px', fontFamily: 'monospace' }}
              placeholder="nhà hàng&#10;quán ăn&#10;quán cơm&#10;..."
            />
            <small style={{ color: '#666' }}>
              Sẽ cào {batchKeywords.split('\n').filter(k => k.trim()).length} từ khóa
            </small>
          </div>
        ) : (
          <div className="form-group">
            <label>
              Từ khóa *
              <button
                type="button"
                onClick={() => setBatchMode(true)}
                style={{ marginLeft: '10px', fontSize: '0.85rem' }}
              >
                + Batch mode (nhiều từ khóa)
              </button>
            </label>
            <input
              type="text"
              name="keyword"
              value={formData.keyword}
              onChange={handleChange}
              placeholder="Ví dụ: nhà hàng, khách sạn, spa..."
              required
            />
          </div>
        )}

        {/* Location */}
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
            <label>Số lượng/từ khóa</label>
            <input
              type="number"
              name="maxResults"
              value={formData.maxResults}
              onChange={handleChange}
              min="1"
              max="200"
            />
          </div>
        </div>

        {/* Deep mode */}
        <div className="form-group">
          <label style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <input
              type="checkbox"
              checked={deepMode}
              onChange={(e) => setDeepMode(e.target.checked)}
            />
            <span>
              🔬 <strong>Deep Scraping Mode</strong> - Cào kỹ hơn (100 kết quả/từ khóa, mất nhiều thời gian hơn)
            </span>
          </label>
        </div>

        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Đang cào dữ liệu...' : batchMode ? `Bắt đầu cào (${batchKeywords.split('\n').filter(k => k.trim()).length} từ khóa)` : 'Bắt đầu cào'}
        </button>
      </form>

      {loading && (
        <div style={{ marginTop: '20px' }}>
          <div className="spinner"></div>
          <p style={{ textAlign: 'center', color: '#666', marginTop: '10px' }}>
            ⏳ Đang cào... Có thể mất vài phút. Vui lòng không tắt trang!
          </p>
        </div>
      )}
    </div>
  );
};
