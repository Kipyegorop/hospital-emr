import { useEffect, useState } from 'react';
import api from '../services/api';
import { AdmitForm, TransferForm, DischargeForm } from '../components/IPDForms';
import { useLocation } from 'react-router-dom';
import { VitalsForm, NursingAssessmentForm } from '../components/NursingForms';
import PatientTimeline from '../components/PatientTimeline';

function MedicationAdministrationForm({ initialPatientId = '' }) {
  const [form, setForm] = useState({ patient_id: initialPatientId, medication_id: '', dose: '', route: '', administered_at: '' });
  const [msg, setMsg] = useState(null);
  const submit = async (e) => {
    e.preventDefault();
  // validation
  if (!form.patient_id) { setMsg({type:'error', text:'Patient ID is required'}); return; }
  if (!form.medication_id && !form.medication_name) { setMsg({type:'error', text:'Provide medication id or name'}); return; }
  try { await api.post('/medication-administrations', form); setMsg({type:'success', text:'Recorded'}); setForm({...form, medication_id:'', dose:'', route:'', administered_at:''}); }
  catch (err) { setMsg({type:'error', text: err?.response?.data?.message || 'Failed'}); }
  };
  return (
    <form onSubmit={submit} className="p-4 bg-card border border-border rounded">
      <h3 className="font-medium">Medication Administration</h3>
      {msg && <div className={`p-2 ${msg.type==='error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{msg.text}</div>}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
        <input placeholder="Patient ID" value={form.patient_id} onChange={e=>setForm({...form, patient_id: e.target.value})} className="input" />
        <input placeholder="Medication ID" value={form.medication_id} onChange={e=>setForm({...form, medication_id: e.target.value})} className="input" />
        <input placeholder="Dose" value={form.dose} onChange={e=>setForm({...form, dose: e.target.value})} className="input" />
        <input placeholder="Route" value={form.route} onChange={e=>setForm({...form, route: e.target.value})} className="input" />
        <input placeholder="Administered at (ISO)" value={form.administered_at} onChange={e=>setForm({...form, administered_at: e.target.value})} className="input" />
      </div>
      <div className="mt-2"><button className="btn">Record Administration</button></div>
    </form>
  );
}

const IPD = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await api.get('/ipd');
        setData(res.data.data);
      } catch (e) {
        console.error('Failed to load IPD', e);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  const location = useLocation();
  const qs = new URLSearchParams(location.search);
  const prefillPatient = qs.get('patient_id') || '';

  if (loading) return <div className="p-6">Loading IPD...</div>;

  return (
    <div className="p-6 space-y-4">
      <h1 className="text-2xl font-bold">IPD (Inpatient Department)</h1>
      <p className="text-muted-foreground mt-2">{data?.description}</p>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="col-span-2">
          <div className="bg-card border border-border rounded p-4">
            <h2 className="font-medium">Key Metrics</h2>
            <ul className="list-disc list-inside mt-2">
              <li>Occupied beds: {data?.occupied_beds ?? 'N/A'}</li>
              <li>Available beds: {data?.available_beds ?? 'N/A'}</li>
              <li>Current admissions: {data?.current_admissions ?? 'N/A'}</li>
            </ul>
          </div>
        </div>
        <div className="space-y-4">
          <AdmitForm initialPatientId={prefillPatient} />
          <TransferForm />
          <DischargeForm />
          <div className="mt-2"><VitalsForm initialPatientId={prefillPatient} /></div>
          <div className="mt-2"><NursingAssessmentForm initialPatientId={prefillPatient} /></div>
          <div className="mt-2"><MedicationAdministrationForm initialPatientId={prefillPatient} /></div>
        </div>
      </div>

      <div className="lg:col-span-1">
        <PatientTimeline patientId={prefillPatient} />
      </div>
    </div>
  );
};

export default IPD;
