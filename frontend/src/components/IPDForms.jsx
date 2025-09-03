import { useState, useEffect } from 'react';
import api from '../services/api';

export function AdmitForm({ initialPatientId = '' }) {
  const [wards, setWards] = useState([]);
  const [beds, setBeds] = useState([]);
  const [form, setForm] = useState({ patient_id: initialPatientId || '', ward_id: '', bed_id: '', attending_doctor_id: '', admission_reason: '' });

  useEffect(() => {
    const load = async () => {
      try {
        const w = await api.get('/wards');
        setWards(w.data.data || w.data);
      } catch (e) { console.error(e); }
    };
    load();
  }, []);

  useEffect(() => {
    const loadBeds = async () => {
      if (!form.ward_id) return;
      try {
        const res = await api.get(`/beds/ward/${form.ward_id}`);
        setBeds(res.data || []);
      } catch (e) { console.error(e); }
    };
    loadBeds();
  }, [form.ward_id]);

  const submit = async (e) => {
    e.preventDefault();
    try {
      await api.post('/ipd/admit', form);
      alert('Admitted');
    } catch (err) { console.error(err); alert(err?.response?.data?.message || 'Error'); }
  };

  return (
    <form onSubmit={submit} className="p-4 bg-card border border-border rounded">
      <h3 className="font-medium">Admit Patient</h3>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
        <input placeholder="Patient ID" value={form.patient_id} onChange={e=>setForm({...form, patient_id:e.target.value})} className="input" />
        <select value={form.ward_id} onChange={e=>setForm({...form, ward_id:e.target.value})} className="input">
          <option value="">Select ward</option>
          {wards.map(w=> <option key={w.id} value={w.id}>{w.name}</option>)}
        </select>
        <select value={form.bed_id} onChange={e=>setForm({...form, bed_id:e.target.value})} className="input">
          <option value="">Select bed</option>
          {beds.map(b=> <option key={b.id} value={b.id}>{b.bed_number}</option>)}
        </select>
        <input placeholder="Attending doctor ID" value={form.attending_doctor_id} onChange={e=>setForm({...form, attending_doctor_id:e.target.value})} className="input" />
        <input placeholder="Reason" value={form.admission_reason} onChange={e=>setForm({...form, admission_reason:e.target.value})} className="input col-span-2" />
      </div>
      <div className="mt-2">
        <button className="btn">Admit</button>
      </div>
    </form>
  );
}

export function TransferForm() {
  const [form, setForm] = useState({ from_bed_id: '', to_bed_id: '' });
  const submit = async (e) => {
    e.preventDefault();
    try { await api.post('/ipd/transfer', form); alert('Transferred'); } catch (err){ console.error(err); alert(err?.response?.data?.message || 'Error'); }
  };
  return (
    <form onSubmit={submit} className="p-4 bg-card border border-border rounded">
      <h3 className="font-medium">Transfer Patient</h3>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
        <input placeholder="From Bed ID" value={form.from_bed_id} onChange={e=>setForm({...form, from_bed_id:e.target.value})} className="input" />
        <input placeholder="To Bed ID" value={form.to_bed_id} onChange={e=>setForm({...form, to_bed_id:e.target.value})} className="input" />
      </div>
      <div className="mt-2">
        <button className="btn">Transfer</button>
      </div>
    </form>
  );
}

export function DischargeForm() {
  const [form, setForm] = useState({ encounter_id: '', discharge_summary: '' });
  const submit = async (e) => {
    e.preventDefault();
    try { await api.post('/ipd/discharge', form); alert('Discharged'); } catch (err){ console.error(err); alert(err?.response?.data?.message || 'Error'); }
  };
  return (
    <form onSubmit={submit} className="p-4 bg-card border border-border rounded">
      <h3 className="font-medium">Discharge Patient</h3>
      <div className="grid grid-cols-1 gap-2 mt-2">
        <input placeholder="Encounter ID" value={form.encounter_id} onChange={e=>setForm({...form, encounter_id:e.target.value})} className="input" />
        <textarea placeholder="Discharge summary" value={form.discharge_summary} onChange={e=>setForm({...form, discharge_summary:e.target.value})} className="input" />
      </div>
      <div className="mt-2">
        <button className="btn">Discharge</button>
      </div>
    </form>
  );
}
