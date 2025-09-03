import { useEffect, useState } from 'react';
import api from '../services/api';

export default function PatientTimeline({ patientId }) {
  const [history, setHistory] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [detail, setDetail] = useState(null);

  useEffect(() => {
    if (!patientId) return;
    let mounted = true;
    const load = async () => {
      setLoading(true);
      setError(null);
      try {
        const res = await api.get(`/patients/${patientId}/history`);
        if (!mounted) return;
        setHistory(res.data.data || {});
      } catch (e) {
        if (!mounted) return;
        setError(e?.response?.data?.message || 'Failed to load history');
      } finally { if (mounted) setLoading(false); }
    };
    load();
    return () => { mounted = false; };
  }, [patientId]);

  if (!patientId) return (
    <div className="bg-card border border-border rounded p-4">
      <h3 className="font-medium">Patient Timeline</h3>
      <p className="text-sm text-muted-foreground">No patient selected. Open a patient or provide ?patient_id=... in the URL to view timeline.</p>
    </div>
  );

  return (
    <div className="bg-card border border-border rounded p-4">
      <h3 className="font-medium">Patient Timeline</h3>
      {loading && <div className="text-sm mt-2">Loading timeline...</div>}
      {error && <div className="text-sm text-red-700 mt-2">{error}</div>}
      {!loading && history && (
  <div className="space-y-3 mt-3">
          {['appointments','consultations','prescriptions','lab_tests','vitals','nursing_assessments','medication_administrations','bills'].map((section) => (
            history[section] && history[section].length > 0 ? (
              <div key={section}>
                <h4 className="font-semibold capitalize">{section.replace('_',' ')}</h4>
                <ul className="list-disc list-inside mt-1 text-sm">
                  {history[section].slice(0,6).map((item) => (
                    <li key={item.id} className="py-1">
                      <div className="text-xs text-muted-foreground">{item.created_at || item.appointment_date || item.administration_time || item.recorded_at || item.requested_at || item.start_time || ''}</div>
                      <div className="flex items-center justify-between">
                        <div className="truncate pr-2">{item.title || item.description || item.medication_name || item.chief_complaint || item.encounter_number || (item.doctor?.full_name ?? item.doctor_name) || item.notes || JSON.stringify(item)}</div>
                        <button className="text-xs text-primary ml-2" onClick={() => setDetail(item)}>Details</button>
                      </div>
                    </li>
                  ))}
                </ul>
                {history[section].length > 6 && <div className="text-xs text-muted-foreground">... and {history[section].length - 6} more</div>}
              </div>
            ) : null
          ))}
          {Object.keys(history).length === 0 && <div className="text-sm text-muted-foreground">No history available.</div>}
        </div>
      )}
      {/* Details modal */}
      {detail && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-card border border-border rounded p-4 max-w-2xl w-full">
            <div className="flex justify-between items-center">
              <h4 className="font-semibold">Detail</h4>
              <button className="btn" onClick={() => setDetail(null)}>Close</button>
            </div>
            <pre className="mt-3 overflow-auto text-xs max-h-96 bg-muted p-2 rounded">{JSON.stringify(detail, null, 2)}</pre>
          </div>
        </div>
      )}
    </div>
  );
}
