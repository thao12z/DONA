# 🎯 Chọn Hosting nào cho Google Maps Scraper?

## ❌ Hosting bạn KHÔNG thể dùng:

### Shared Hosting (cPanel) - AZdigi Hosting thường
- ❌ Chỉ chạy PHP, HTML
- ❌ Không có Node.js
- ❌ Không cài được Chromium
- ❌ Không chạy background processes

**→ Kết luận: KHÔNG thể chạy app này!**

---

## ✅ Các lựa chọn bạn CÓ THỂ dùng:

## So sánh nhanh:

| Tiêu chí | Render Free | Railway Free | AZdigi VPS 1 | AZdigi VPS 2 |
|----------|-------------|--------------|--------------|--------------|
| **Giá** | $0 | $0 ($5 credit) | ~150k/tháng | ~250k/tháng |
| **RAM** | 512MB | 8GB | 2GB | 4GB |
| **Sleep?** | Có (15 phút) | Không | Không | Không |
| **Scraping/lần** | 20-50 | 50-100 | 100-200 | 200+ |
| **Ultra Deep** | ❌ | ⚠️ OK | ✅ OK | ✅ Tốt |
| **Batch Mode** | ❌ | ⚠️ OK | ✅ OK | ✅ Tốt |
| **Setup** | Dễ | Dễ | Trung bình | Trung bình |
| **Giới hạn** | Nhiều | $5/tháng | Không | Không |

---

## 📊 Chi tiết từng lựa chọn:

### 1️⃣ Render.com (Miễn phí)

**Phù hợp với:**
- ✅ Người mới, chỉ muốn test
- ✅ Scraping ít (2-5 lần/ngày)
- ✅ Không cần Ultra Deep Mode
- ✅ Không cần chạy 24/7

**Ưu điểm:**
- 🆓 Hoàn toàn miễn phí
- 🚀 Deploy siêu đơn giản từ GitHub
- 🔒 SSL miễn phí
- 🌐 Subdomain miễn phí

**Nhược điểm:**
- ⏰ App sleep sau 15 phút không dùng (khởi động lại mất ~30 giây)
- 💾 RAM ít (512MB) → Chỉ scrape được 20-50 kết quả/lần
- ⛔ Không dùng được Ultra Deep Mode
- ⛔ Không dùng được Batch Mode

**Chi phí thực tế:** $0

**Hướng dẫn:** `DEPLOY-RENDER.md`

---

### 2️⃣ Railway.app ($5 credit miễn phí)

**Phù hợp với:**
- ✅ Scraping vừa phải (5-20 lần/ngày)
- ✅ Cần app chạy 24/7 không sleep
- ✅ Muốn UI đẹp, deploy nhanh
- ✅ Có thể dùng Ultra Deep Mode (thỉnh thoảng)

**Ưu điểm:**
- 💰 $5 credit miễn phí/tháng (không cần thẻ)
- 🚀 Deploy nhanh, UI đẹp nhất
- 💪 RAM 8GB (rất đủ!)
- 🌐 Không sleep
- 🔒 SSL + domain miễn phí

**Nhược điểm:**
- 💸 $5 credit có thể hết nếu scrape nhiều
- ⏰ Hết credit → app tắt đến tháng sau
- 💳 Nâng cấp mất $20/tháng

**Chi phí thực tế:**
- Dùng vừa phải: $0 (đủ $5 credit)
- Dùng nhiều: ~$10-15/tháng

**Hướng dẫn:** `DEPLOY-RAILWAY.md`

---

### 3️⃣ AZdigi VPS 1 (2GB RAM)

**Phù hợp với:**
- ✅ Scraping thường xuyên (20-50 lần/ngày)
- ✅ Cần ổn định, không giới hạn
- ✅ Muốn full control
- ✅ Có thể dùng Ultra Deep Mode

**Ưu điểm:**
- 💪 Full control, SSH access
- 🚀 Không giới hạn scraping
- 🌐 Chạy được Ultra Deep Mode (vừa phải)
- 🇻🇳 Server tại VN, support tiếng Việt
- 💰 Giá rẻ hơn hosting nước ngoài

**Nhược điểm:**
- 🛠️ Setup phức tạp hơn (cần biết SSH, Linux)
- 💸 Tốn tiền hàng tháng
- ⚠️ Ultra Deep Mode + Batch có thể hết RAM

**Chi phí thực tế:** ~150,000 VNĐ/tháng

**Hướng dẫn:** `DEPLOY-AZDIGI.md`

---

### 4️⃣ AZdigi VPS 2 (4GB RAM) ⭐ KHUYÊN DÙNG

**Phù hợp với:**
- ✅ Scraping nhiều (50+ lần/ngày)
- ✅ Dùng Ultra Deep Mode thường xuyên
- ✅ Dùng Batch Mode với nhiều keywords
- ✅ Cần hiệu năng cao nhất

**Ưu điểm:**
- 💪 RAM đủ cho mọi tình huống
- 🚀 Ultra Deep Mode + Batch Mode chạy mượt
- 🌐 Không giới hạn, không lo hết credit
- 🇻🇳 Server VN, support 24/7
- 📈 Scale được khi cần

**Nhược điểm:**
- 🛠️ Setup phức tạp (cần biết Linux)
- 💸 Chi phí cao nhất

**Chi phí thực tế:** ~250,000 VNĐ/tháng

**Hướng dẫn:** `DEPLOY-AZDIGI.md`

---

## 🎯 KHUYẾN NGHỊ THEO MỤC ĐÍCH:

### 🧪 Chỉ muốn test/thử nghiệm:
→ **Render Free** (miễn phí)
- Scrape 20-50 địa điểm/lần
- Test các tính năng cơ bản
- Không tốn tiền

### 💼 Dùng thật nhưng không nhiều (5-20 lần/ngày):
→ **Railway** ($5 credit/tháng)
- Scrape 50-100 địa điểm/lần
- App chạy 24/7
- UI đẹp, dễ dùng

### 🚀 Dùng thường xuyên (20-50 lần/ngày):
→ **AZdigi VPS 1** (150k/tháng)
- Scrape 100-200 địa điểm/lần
- Không giới hạn
- Ổn định

### 🔥 Dùng nhiều + Ultra Deep + Batch (50+ lần/ngày):
→ **AZdigi VPS 2** (250k/tháng) ⭐
- Scrape 200+ địa điểm/lần
- Ultra Deep Mode: 800-1400 địa điểm
- Batch Mode: 10+ keywords cùng lúc
- Hiệu năng tốt nhất

---

## 💡 Chiến lược tiết kiệm:

### Giai đoạn 1: Test (tháng 1-2)
**Dùng Render Free** để test và làm quen
- Chi phí: $0
- Mục tiêu: Hiểu cách dùng, test tính năng

### Giai đoạn 2: Mở rộng (tháng 3-4)
**Dùng Railway** nếu cần scrape nhiều hơn
- Chi phí: $0-10/tháng
- Mục tiêu: Thu thập dữ liệu thật

### Giai đoạn 3: Production (tháng 5+)
**Nâng cấp VPS** khi cần ổn định và không giới hạn
- Chi phí: 150-250k/tháng
- Mục tiêu: Vận hành lâu dài

---

## 🤔 FAQ

### Q: Tôi nên bắt đầu với cái nào?

**A:** Bắt đầu với **Render Free** để test. Nếu thấy OK và cần nhiều hơn, chuyển sang **Railway** hoặc **VPS**.

### Q: Render sleep có ảnh hưởng gì?

**A:** Khi app sleep, lần đầu truy cập sẽ chờ ~30 giây để khởi động. Sau đó chạy bình thường. Nếu scrape liên tục thì không bị sleep.

### Q: $5 credit Railway có đủ không?

**A:** Đủ nếu dùng vừa phải:
- Scrape 5-10 lần/ngày: ~$2-3/tháng
- Scrape 20-30 lần/ngày: ~$5-8/tháng

### Q: VPS AZdigi có khó setup không?

**A:** Cần biết cơ bản về SSH và Linux. Nhưng có hướng dẫn chi tiết trong `DEPLOY-AZDIGI.md`. Hoặc nhờ support AZdigi hỗ trợ.

### Q: Tôi có thể dùng cả Render + VPS không?

**A:** Có! Dùng Render để test, VPS để production.

### Q: Hosting cPanel của tôi thì sao?

**A:** Rất tiếc, không thể dùng. Cần nâng cấp lên VPS hoặc dùng Render/Railway.

---

## 📞 Hỗ trợ

**Render:**
- Docs: https://render.com/docs
- Discord: https://discord.gg/render

**Railway:**
- Docs: https://docs.railway.app
- Discord: https://discord.gg/railway

**AZdigi:**
- Hotline: 1900 2027
- Email: support@azdigi.com
- Ticket: my.azdigi.com

---

## 🎯 Kết luận

**TL;DR:**
- 🧪 Test → **Render Free**
- 💼 Dùng vừa → **Railway**
- 🚀 Dùng nhiều → **AZdigi VPS 2** ⭐

Chọn theo nhu cầu và túi tiền của bạn! 💪
