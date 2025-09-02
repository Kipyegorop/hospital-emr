import { useState } from 'react';
import axios from 'axios';

const AppointmentForm = () => {
  const [form, setForm] = useState({
    patient_id: '',
    doctor_id: '',
    department_id: '',
    appointment_date: '',
    appointment_time: '',
    appointment_type: 'consultation',
    reason_for_visit: '',
  });

  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState(null);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage(null);
    try {
      const res = await axios.post('/api/appointments', form);
      setMessage({ type: 'success', text: 'Appointment booked' });
    } catch (err) {
      setMessage({ type: 'error', text: err.response?.data?.message || 'Failed to book' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      {message && (
        <div className={`p-2 rounded ${message.type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>
          {message.text}
        </div>
      )}

      <div>
        <label className="block text-sm mb-1">Patient ID</label>
        <input name="patient_id" value={form.patient_id} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Doctor ID</label>
        <input name="doctor_id" value={form.doctor_id} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Department ID</label>
        <input name="department_id" value={form.department_id} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Date</label>
        <input type="date" name="appointment_date" value={form.appointment_date} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Time</label>
        <input type="time" name="appointment_time" value={form.appointment_time} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Type</label>
        <select name="appointment_type" value={form.appointment_type} onChange={handleChange} className="input">
          <option value="consultation">Consultation</option>
          <option value="follow_up">Follow up</option>
          <option value="emergency">Emergency</option>
          <option value="routine_checkup">Routine checkup</option>
          <option value="specialist">Specialist</option>
        </select>
      </div>

      <div>
        <label className="block text-sm mb-1">Reason</label>
        <textarea name="reason_for_visit" value={form.reason_for_visit} onChange={handleChange} className="input" />
      </div>

      <div>
        <button type="submit" disabled={loading} className="btn btn-primary w-full">
          {loading ? 'Booking...' : 'Book Appointment'}
        </button>
      </div>
    </form>
  );
};

export default AppointmentForm;
