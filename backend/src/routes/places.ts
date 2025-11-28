import { Router, Request, Response } from 'express';
import { PlaceModel, PlaceFilters } from '../models/Place';
import { GoogleMapsScraper, ScrapeOptions } from '../scraper/googleMaps';

const router = Router();
const scraper = new GoogleMapsScraper();

// Get all places with filters
router.get('/', async (req: Request, res: Response) => {
  try {
    const filters: PlaceFilters = {
      keyword: req.query.keyword as string,
      country: req.query.country as string,
      province: req.query.province as string,
      city: req.query.city as string,
      district: req.query.district as string,
      ward: req.query.ward as string,
      search: req.query.search as string
    };

    const limit = parseInt(req.query.limit as string) || 100;
    const offset = parseInt(req.query.offset as string) || 0;

    const places = PlaceModel.findAll(filters, limit, offset);
    const total = PlaceModel.count(filters);

    res.json({
      success: true,
      data: places,
      pagination: {
        total,
        limit,
        offset,
        hasMore: offset + limit < total
      }
    });
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Get unique filter values
router.get('/filters/:field', async (req: Request, res: Response) => {
  try {
    const { field } = req.params;
    const validFields = ['country', 'province', 'city', 'district', 'ward'];

    if (!validFields.includes(field)) {
      return res.status(400).json({
        success: false,
        error: 'Invalid field'
      });
    }

    const values = PlaceModel.getUniqueValues(field as any);

    res.json({
      success: true,
      data: values
    });
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Scrape new places
router.post('/scrape', async (req: Request, res: Response) => {
  try {
    const options: ScrapeOptions = {
      keyword: req.body.keyword,
      country: req.body.country,
      province: req.body.province,
      city: req.body.city,
      district: req.body.district,
      ward: req.body.ward,
      maxResults: req.body.maxResults || 20
    };

    if (!options.keyword) {
      return res.status(400).json({
        success: false,
        error: 'Keyword is required'
      });
    }

    // Start scraping with retry
    const places = await scraper.scrapeWithRetry(options, 2);

    // Calculate quality metrics
    const placesWithQuality = places.map(p => ({
      ...p,
      quality_score: PlaceModel.calculateQuality(p)
    }));

    // Quality analysis
    let withPhone = 0, withAddress = 0, withCoords = 0;
    placesWithQuality.forEach(p => {
      if (p.phone) withPhone++;
      if (p.address) withAddress++;
      if (p.latitude && p.longitude) withCoords++;
    });

    const avgQuality = placesWithQuality.reduce((sum, p) => sum + (p.quality_score || 0), 0) / placesWithQuality.length || 0;

    // Save to database
    let savedCount = 0;
    for (const place of places) {
      try {
        PlaceModel.create({
          ...place,
          keyword: options.keyword,
          country: options.country,
          province: options.province,
          city: options.city,
          district: options.district,
          ward: options.ward
        });
        savedCount++;
      } catch (err) {
        console.error('Error saving place:', err);
      }
    }

    res.json({
      success: true,
      data: {
        scraped: places.length,
        saved: savedCount,
        places: placesWithQuality,
        quality: {
          avgQuality: Math.round(avgQuality),
          withPhone,
          withAddress,
          withCoords,
          phoneRate: places.length > 0 ? Math.round((withPhone / places.length) * 100) : 0,
          addressRate: places.length > 0 ? Math.round((withAddress / places.length) * 100) : 0,
          coordsRate: places.length > 0 ? Math.round((withCoords / places.length) * 100) : 0
        }
      }
    });
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Delete all places
router.delete('/', async (req: Request, res: Response) => {
  try {
    PlaceModel.deleteAll();
    res.json({
      success: true,
      message: 'All places deleted'
    });
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Export to CSV
router.get('/export/csv', async (req: Request, res: Response) => {
  try {
    const filters: PlaceFilters = {
      keyword: req.query.keyword as string,
      country: req.query.country as string,
      province: req.query.province as string,
      city: req.query.city as string,
      district: req.query.district as string,
      ward: req.query.ward as string,
      search: req.query.search as string
    };

    const places = PlaceModel.findAll(filters, 100000, 0); // Get all

    // Generate CSV
    const headers = ['Tên', 'Số điện thoại', 'Địa chỉ', 'Vĩ độ', 'Kinh độ', 'Từ khóa', 'Quốc gia', 'Tỉnh', 'Thành phố', 'Quận', 'Phường'];
    const rows = places.map(p => [
      p.name || '',
      p.phone || '',
      p.address || '',
      p.latitude || '',
      p.longitude || '',
      p.keyword || '',
      p.country || '',
      p.province || '',
      p.city || '',
      p.district || '',
      p.ward || ''
    ]);

    const csv = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    ].join('\n');

    res.setHeader('Content-Type', 'text/csv; charset=utf-8');
    res.setHeader('Content-Disposition', 'attachment; filename=places.csv');
    res.send('\uFEFF' + csv); // Add BOM for Excel UTF-8
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Export to JSON
router.get('/export/json', async (req: Request, res: Response) => {
  try {
    const filters: PlaceFilters = {
      keyword: req.query.keyword as string,
      country: req.query.country as string,
      province: req.query.province as string,
      city: req.query.city as string,
      district: req.query.district as string,
      ward: req.query.ward as string,
      search: req.query.search as string
    };

    const places = PlaceModel.findAll(filters, 100000, 0); // Get all

    res.setHeader('Content-Type', 'application/json');
    res.setHeader('Content-Disposition', 'attachment; filename=places.json');
    res.json(places);
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Get statistics
router.get('/stats', async (req: Request, res: Response) => {
  try {
    const total = PlaceModel.count({});
    const countries = PlaceModel.getUniqueValues('country');
    const provinces = PlaceModel.getUniqueValues('province');

    res.json({
      success: true,
      data: {
        total,
        countries: countries.length,
        provinces: provinces.length
      }
    });
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Get quality report
router.get('/quality', async (req: Request, res: Response) => {
  try {
    const filters: PlaceFilters = {
      keyword: req.query.keyword as string
    };

    const report = PlaceModel.getQualityReport(filters);

    res.json({
      success: true,
      data: report
    });
  } catch (error: any) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

export default router;
