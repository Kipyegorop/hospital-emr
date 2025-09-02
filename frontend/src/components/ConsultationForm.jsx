import { useState } from 'react';
import axios from 'axios';

const ConsultationForm = ({ appointmentId, patientId, doctorId }) => {
  const [form, setForm] = useState({
    appointment_id: appointmentId || '',
    patient_id: patientId || '',
    doctor_id: doctorId || '',
    chief_complaint: '',
    history_of_present_illness: '',
    physical_examination: '',
    assessment: '',
    treatment_plan: '',
    patient_history_update: '',
  });
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState(null);

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage(null);
    try {
      const res = await axios.post('/api/consultations', form);
      setMessage({ type: 'success', text: 'Consultation started' });
    } catch (err) {
      setMessage({ type: 'error', text: err.response?.data?.message || 'Failed' });
    } finally {
      setLoading(false);
    }
  };

  const handleComplete = async () => {
    if (!form.id) return;
    setLoading(true);
    try {
      const res = await axios.post(`/api/consultations/${form.id}/complete`, {
        assessment: form.assessment,
        treatment_plan: form.treatment_plan,
        notes: form.notes,
        patient_history_update: form.patient_history_update,
      });
      setMessage({ type: 'success', text: 'Consultation completed' });
    } catch (err) {
      setMessage({ type: 'error', text: err.response?.data?.message || 'Failed to complete' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      {message && <div className={`p-2 rounded ${message.type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{message.text}</div>}

      <div>
        <label className="block text-sm mb-1">Chief Complaint</label>
        <textarea name="chief_complaint" value={form.chief_complaint} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">History of Present Illness</label>
        <textarea name="history_of_present_illness" value={form.history_of_present_illness} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Physical Examination</label>
        <textarea name="physical_examination" value={form.physical_examination} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Assessment</label>
        <textarea name="assessment" value={form.assessment} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Treatment Plan</label>
        <textarea name="treatment_plan" value={form.treatment_plan} onChange={handleChange} className="input" />
      </div>

      <div>
        <label className="block text-sm mb-1">Patient History Update (optional)</label>
        <textarea name="patient_history_update" value={form.patient_history_update} onChange={handleChange} className="input" />
      </div>

      <div className="flex space-x-2">
        <button type="submit" className="btn btn-primary" disabled={loading}>{loading ? 'Saving...' : 'Start Consultation'}</button>
        <button type="button" onClick={handleComplete} className="btn btn-outline" disabled={loading}>Complete</button>
      </div>
    </form>
  );
};

export default ConsultationForm;
