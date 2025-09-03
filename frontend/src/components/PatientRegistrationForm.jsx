import React, { useState } from 'react';
import api from '../services/api';
import { useNavigate } from 'react-router-dom';

export default function PatientRegistrationForm({ onSuccess }) {
  const [form, setForm] = useState({
    first_name: '',
    last_name: '',
    middle_name: '',
    date_of_birth: '',
    gender: 'male',
    phone: '',
    email: '',
    nhif_number: '',
    id_number: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relationship: '',
    address_line_1: '',
    city: '',
    county: '',
    country: 'Kenya',
    blood_type: '',
    height: '',
    weight: '',
    insurance_provider: '',
    payment_method: 'cash',
    notes: '',
  });

  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState(null);
  const [message, setMessage] = useState(null);
  const [triageEnabled, setTriageEnabled] = useState(false);
  const [inpatient, setInpatient] = useState(false);
  const navigate = useNavigate();

  const [triage, setTriage] = useState({
    triage_level: 'non_urgent',
    chief_complaint: '',
    temperature: '',
    systolic_bp: '',
    diastolic_bp: '',
    heart_rate: '',
    respiratory_rate: '',
    oxygen_saturation: '',
  });

  function handleChange(e) {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    setErrors(null);
    setMessage(null);

    try {
      const res = await api.post('/patients', form);

      setMessage(res.data?.message || 'Patient created');
      setForm({
        first_name: '',
        last_name: '',
        middle_name: '',
        date_of_birth: '',
        gender: 'male',
        phone: '',
        email: '',
        nhif_number: '',
        id_number: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        emergency_contact_relationship: '',
        address_line_1: '',
        city: '',
        county: '',
        country: 'Kenya',
        blood_type: '',
        height: '',
        weight: '',
        insurance_provider: '',
        payment_method: 'cash',
        notes: '',
      });

      const created = res.data?.data || res.data;
      if (triageEnabled && created?.id) {
        try {
          const triagePayload = Object.assign({}, triage, { patient_id: created.id });
          await api.post('/triages', triagePayload);
        } catch (err) {
          // non-fatal: show message but continue
          console.error('Failed to create triage', err);
        }
      }

      if (inpatient && created?.id) {
        navigate(`/ipd?patient_id=${created.id}`);
        return;
      }

      if (onSuccess) onSuccess(res.data);
    } catch (err) {
      if (err.response) {
        setErrors(err.response.data.errors || err.response.data);
        setMessage(err.response.data.message || 'Failed to create patient');
      } else {
        setMessage('Network error');
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4 max-w-2xl">
      {message && <div className="p-2 bg-green-100 text-green-900">{message}</div>}
      {errors && typeof errors === 'object' && (
        <div className="p-2 bg-red-100 text-red-900">
          <ul>
            {Object.entries(errors).map(([k, v]) => (
              <li key={k}>{k}: {Array.isArray(v) ? v.join(', ') : String(v)}</li>
            ))}
          </ul>
        </div>
      )}

  <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-sm">First name*</label>
          <input name="first_name" value={form.first_name} onChange={handleChange} required className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">Last name*</label>
          <input name="last_name" value={form.last_name} onChange={handleChange} required className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">Middle name</label>
          <input name="middle_name" value={form.middle_name} onChange={handleChange} className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">Date of birth*</label>
          <input type="date" name="date_of_birth" value={form.date_of_birth} onChange={handleChange} required className="w-full border p-2" />
        </div>

        <div>
          <label className="block text-sm">Gender*</label>
          <select name="gender" value={form.gender} onChange={handleChange} className="w-full border p-2">
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label className="block text-sm">Phone</label>
          <input name="phone" value={form.phone} onChange={handleChange} className="w-full border p-2" />
        </div>

        <div>
          <label className="block text-sm">Email</label>
          <input type="email" name="email" value={form.email} onChange={handleChange} className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">NHIF number</label>
          <input name="nhif_number" value={form.nhif_number} onChange={handleChange} className="w-full border p-2" />
        </div>

        <div>
          <label className="block text-sm">ID number</label>
          <input name="id_number" value={form.id_number} onChange={handleChange} className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">Blood type</label>
          <input name="blood_type" value={form.blood_type} onChange={handleChange} className="w-full border p-2" />
        </div>

        <div>
          <label className="block text-sm">Height (cm)</label>
          <input name="height" value={form.height} onChange={handleChange} type="number" className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">Weight (kg)</label>
          <input name="weight" value={form.weight} onChange={handleChange} type="number" className="w-full border p-2" />
        </div>

        <div className="col-span-2">
          <label className="block text-sm">Address</label>
          <input name="address_line_1" value={form.address_line_1} onChange={handleChange} className="w-full border p-2" placeholder="Street address" />
        </div>

        <div>
          <label className="block text-sm">City</label>
          <input name="city" value={form.city} onChange={handleChange} className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">County</label>
          <input name="county" value={form.county} onChange={handleChange} className="w-full border p-2" />
        </div>

        <div>
          <label className="block text-sm">Insurance provider</label>
          <input name="insurance_provider" value={form.insurance_provider} onChange={handleChange} className="w-full border p-2" />
        </div>
        <div>
          <label className="block text-sm">Payment method</label>
          <select name="payment_method" value={form.payment_method} onChange={handleChange} className="w-full border p-2">
            <option value="cash">Cash</option>
            <option value="nhif">NHIF</option>
            <option value="insurance">Insurance</option>
            <option value="corporate">Corporate</option>
          </select>
        </div>

        <div className="col-span-2">
          <label className="block text-sm">Emergency contact</label>
          <div className="grid grid-cols-3 gap-2 mt-2">
            <input name="emergency_contact_name" value={form.emergency_contact_name} onChange={handleChange} placeholder="Name" className="border p-2" />
            <input name="emergency_contact_phone" value={form.emergency_contact_phone} onChange={handleChange} placeholder="Phone" className="border p-2" />
            <input name="emergency_contact_relationship" value={form.emergency_contact_relationship} onChange={handleChange} placeholder="Relationship" className="border p-2" />
          </div>
        </div>

        <div className="col-span-2">
          <label className="block text-sm">Notes</label>
          <textarea name="notes" value={form.notes} onChange={handleChange} className="w-full border p-2" />
        </div>
      </div>
        <div className="mt-2 space-y-2">
          <label className="inline-flex items-center">
            <input type="checkbox" checked={triageEnabled} onChange={e=>setTriageEnabled(e.target.checked)} className="mr-2" />
            Create a triage record for this registration
          </label>
          <label className="inline-flex items-center">
            <input type="checkbox" checked={inpatient} onChange={e=>setInpatient(e.target.checked)} className="mr-2" />
            Register as inpatient (go to admit)
          </label>
        </div>

        {triageEnabled && (
          <div className="p-3 border rounded bg-gray-50">
            <h4 className="font-medium mb-2">Triage / Initial Assessment</h4>
            <div className="grid grid-cols-2 gap-2">
              <select value={triage.triage_level} onChange={e=>setTriage({...triage, triage_level: e.target.value})} className="input">
                <option value="emergency">Emergency</option>
                <option value="urgent">Urgent</option>
                <option value="semi_urgent">Semi-urgent</option>
                <option value="non_urgent">Non-urgent</option>
                <option value="fast_track">Fast track</option>
              </select>
              <input placeholder="Chief complaint" value={triage.chief_complaint} onChange={e=>setTriage({...triage, chief_complaint: e.target.value})} className="input" />
              <input placeholder="Temperature" value={triage.temperature} onChange={e=>setTriage({...triage, temperature: e.target.value})} className="input" />
              <input placeholder="Systolic BP" value={triage.systolic_bp} onChange={e=>setTriage({...triage, systolic_bp: e.target.value})} className="input" />
              <input placeholder="Diastolic BP" value={triage.diastolic_bp} onChange={e=>setTriage({...triage, diastolic_bp: e.target.value})} className="input" />
              <input placeholder="Heart rate" value={triage.heart_rate} onChange={e=>setTriage({...triage, heart_rate: e.target.value})} className="input" />
              <input placeholder="Resp rate" value={triage.respiratory_rate} onChange={e=>setTriage({...triage, respiratory_rate: e.target.value})} className="input" />
              <input placeholder="O2 sat" value={triage.oxygen_saturation} onChange={e=>setTriage({...triage, oxygen_saturation: e.target.value})} className="input" />
            </div>
          </div>
        )}

      <div>
        <button type="submit" disabled={loading} className="px-4 py-2 bg-blue-600 text-white rounded">
          {loading ? 'Saving...' : 'Save Patient'}
        </button>
      </div>
    </form>
  );
}
