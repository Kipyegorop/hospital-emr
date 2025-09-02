import api from './api';

export default {
  list(params) {
    return api.get('/bills', { params });
  },
  get(id) {
    return api.get(`/bills/${id}`);
  },
  create(payload) {
    return api.post('/bills', payload);
  },
  pay(id, payload) {
    return api.post(`/bills/${id}/pay`, payload);
  },
  waive(id, payload) {
    return api.post(`/bills/${id}/waive`, payload);
  },
  receipt(id) {
    return api.get(`/bills/${id}/receipt`);
  }
};
