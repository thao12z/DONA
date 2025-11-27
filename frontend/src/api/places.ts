import axios from 'axios';

const API_BASE_URL = '/api';

export interface Place {
  id: number;
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
}

export interface PlaceFilters {
  keyword?: string;
  country?: string;
  province?: string;
  city?: string;
  district?: string;
  ward?: string;
  search?: string;
  limit?: number;
  offset?: number;
}

export interface ScrapeRequest {
  keyword: string;
  country?: string;
  province?: string;
  city?: string;
  district?: string;
  ward?: string;
  maxResults?: number;
}

export const placesAPI = {
  async getPlaces(filters: PlaceFilters = {}) {
    const response = await axios.get(`${API_BASE_URL}/places`, { params: filters });
    return response.data;
  },

  async getFilterValues(field: string) {
    const response = await axios.get(`${API_BASE_URL}/places/filters/${field}`);
    return response.data;
  },

  async scrapePlaces(request: ScrapeRequest) {
    const response = await axios.post(`${API_BASE_URL}/places/scrape`, request);
    return response.data;
  },

  async deleteAllPlaces() {
    const response = await axios.delete(`${API_BASE_URL}/places`);
    return response.data;
  }
};
