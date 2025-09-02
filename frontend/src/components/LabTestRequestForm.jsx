import { useState } from 'react';
import axios from 'axios';

const LabTestRequestForm = () => {
  const [form, setForm] = useState({ patient_id: '', test_type: '', priority: 'normal' });
  const [message, setMessage] = useState(null);

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await axios.post('/api/lab-tests', form);
      setMessage({ type: 'success', text: 'Lab test requested' });
    } catch (err) {
      setMessage({ type: 'error', text: err.response?.data?.message || 'Failed' });
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      {message && <div className={`p-2 rounded ${message.type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{message.text}</div>}

      <div>
        <label className="block text-sm mb-1">Patient ID</label>
        <input name="patient_id" value={form.patient_id} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Test Type</label>
        <input name="test_type" value={form.test_type} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Priority</label>
        <select name="priority" value={form.priority} onChange={handleChange} className="input">
          <option value="low">Low</option>
          <option value="normal">Normal</option>
          <option value="high">High</option>
        </select>
      </div>

      <div>
        <button className="btn btn-primary w-full" type="submit">Request Test</button>
      </div>
    </form>
  );
};

export default LabTestRequestForm;
