import { useState } from 'react';
import api from '../services/api';

export function VitalsForm({ initialPatientId = '' }) {
  const [form, setForm] = useState({ patient_id: initialPatientId, temperature: '', systolic_bp: '', diastolic_bp: '', heart_rate: '', respiratory_rate: '', oxygen_saturation: '', weight: '', height: '' });
  const [message, setMessage] = useState(null);

  const submit = async (e) => {
    e.preventDefault();
    // Basic client-side validation
    if (!form.patient_id) { setMessage({type:'error', text:'Patient ID is required'}); return; }
    const temp = form.temperature ? parseFloat(form.temperature) : null;
    if (temp !== null && (temp < 30 || temp > 45)) { setMessage({type:'error', text:'Temperature out of range'}); return; }
    try {
      await api.post('/vitals', form);
      setMessage({ type: 'success', text: 'Vitals recorded' });
      // clear non-id fields
      setForm({ ...form, temperature:'', systolic_bp:'', diastolic_bp:'', heart_rate:'', respiratory_rate:'', oxygen_saturation:'', weight:'', height:'' });
    } catch (err) { setMessage({ type: 'error', text: err?.response?.data?.message || 'Failed' }); }
  };

  return (
    <form onSubmit={submit} className="p-4 bg-card border border-border rounded">
      <h3 className="font-medium">Record Vitals</h3>
      {message && <div className={`p-2 ${message.type==='error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{message.text}</div>}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
        <input placeholder="Patient ID" value={form.patient_id} onChange={e=>setForm({...form, patient_id: e.target.value})} className="input" />
        <input placeholder="Temperature" value={form.temperature} onChange={e=>setForm({...form, temperature: e.target.value})} className="input" />
        <input placeholder="Systolic BP" value={form.systolic_bp} onChange={e=>setForm({...form, systolic_bp: e.target.value})} className="input" />
        <input placeholder="Diastolic BP" value={form.diastolic_bp} onChange={e=>setForm({...form, diastolic_bp: e.target.value})} className="input" />
        <input placeholder="Heart rate" value={form.heart_rate} onChange={e=>setForm({...form, heart_rate: e.target.value})} className="input" />
        <input placeholder="Resp rate" value={form.respiratory_rate} onChange={e=>setForm({...form, respiratory_rate: e.target.value})} className="input" />
        <input placeholder="O2 sat" value={form.oxygen_saturation} onChange={e=>setForm({...form, oxygen_saturation: e.target.value})} className="input" />
        <input placeholder="Weight" value={form.weight} onChange={e=>setForm({...form, weight: e.target.value})} className="input" />
        <input placeholder="Height" value={form.height} onChange={e=>setForm({...form, height: e.target.value})} className="input" />
      </div>
      <div className="mt-2"><button className="btn">Save Vitals</button></div>
    </form>
  );
}

export function NursingAssessmentForm({ initialPatientId = '' }) {
  const [form, setForm] = useState({ patient_id: initialPatientId, assessment: '', care_plan: '', observations: '' });
  const [message, setMessage] = useState(null);

  const submit = async (e) => {
    e.preventDefault();
    if (!form.patient_id) { setMessage({type:'error', text:'Patient ID is required'}); return; }
    if (!form.assessment && !form.observations && !form.care_plan) { setMessage({type:'error', text:'Provide at least one of assessment, care plan, or observations'}); return; }
    try {
      await api.post('/nursing-assessments', form);
      setMessage({ type: 'success', text: 'Assessment saved' });
      setForm({ ...form, assessment:'', care_plan:'', observations:'' });
    } catch (err) { setMessage({ type: 'error', text: err?.response?.data?.message || 'Failed' }); }
  };

  return (
    <form onSubmit={submit} className="p-4 bg-card border border-border rounded">
      <h3 className="font-medium">Nursing Assessment</h3>
      {message && <div className={`p-2 ${message.type==='error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{message.text}</div>}
      <div className="grid grid-cols-1 gap-2 mt-2">
        <input placeholder="Patient ID" value={form.patient_id} onChange={e=>setForm({...form, patient_id: e.target.value})} className="input" />
        <textarea placeholder="Assessment" value={form.assessment} onChange={e=>setForm({...form, assessment: e.target.value})} className="input" />
        <textarea placeholder="Care Plan" value={form.care_plan} onChange={e=>setForm({...form, care_plan: e.target.value})} className="input" />
        <textarea placeholder="Observations" value={form.observations} onChange={e=>setForm({...form, observations: e.target.value})} className="input" />
      </div>
      <div className="mt-2"><button className="btn">Save Assessment</button></div>
    </form>
  );
}
