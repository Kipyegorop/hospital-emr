import { useEffect, useState } from 'react';
import api from '../services/api';

const OPD = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await api.get('/opd');
        setData(res.data.data);
      } catch (e) {
        console.error('Failed to load OPD', e);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  if (loading) return <div className="p-6">Loading OPD...</div>;

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold">OPD (Outpatient Department)</h1>
      <p className="text-muted-foreground mt-2">{data?.description}</p>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <div className="bg-card border border-border rounded p-4">
          <h2 className="font-medium">Upcoming Appointments</h2>
          <ul className="mt-2">
            {data?.lists?.upcoming_appointments?.length ? data.lists.upcoming_appointments.map(a => (
              <li key={a.id}>{a.appointment_time} - {a.patient?.full_name || a.patient_id} ({a.status})</li>
            )) : <li className="text-muted-foreground">No upcoming appointments</li>}
          </ul>
        </div>

        <div className="bg-card border border-border rounded p-4">
          <h2 className="font-medium">Triage Queue</h2>
          <ul className="mt-2">
            {data?.lists?.triage_list?.length ? data.lists.triage_list.map(t => (
              <li key={t.id}>#{t.queue_number} - {t.patient?.full_name || t.patient_id} ({t.triage_level})</li>
            )) : <li className="text-muted-foreground">No patients in triage</li>}
          </ul>
        </div>

        <div className="bg-card border border-border rounded p-4">
          <h2 className="font-medium">Pending Consultations</h2>
          <ul className="mt-2">
            {data?.lists?.pending_consultations?.length ? data.lists.pending_consultations.map(c => (
              <li key={c.id}>{c.created_at} - {c.patient?.full_name || c.patient_id} ({c.status})</li>
            )) : <li className="text-muted-foreground">No pending consultations</li>}
          </ul>
        </div>
      </div>
    </div>
  );
};

export default OPD;
