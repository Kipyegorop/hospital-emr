import React, { useEffect, useState } from 'react';
import axios from 'axios';

const Exceptions = () => {
  const [exceptions, setExceptions] = useState([]);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await axios.get('/api/pharmacy/exceptions');
        setExceptions(res.data.data.exceptions || res.data.data || []);
      } catch (err) {
        console.error(err);
      }
    };
    load();
  }, []);

  return (
    <div className="p-6 space-y-4">
      <h1 className="text-2xl font-bold">Prescription Exceptions</h1>
      <div className="bg-card border border-border rounded-lg p-4">
        <ul>
          {exceptions.map((ex) => (
            <li key={ex.id} className="p-2 border-b border-border">
              <div className="flex justify-between items-start">
                <div>
                  <div className="font-medium">{ex.exception_type} for {ex.prescription?.patient?.full_name || ex.prescription?.patient_id}</div>
                  <div className="text-sm text-muted-foreground">Requested: {ex.requested_at}</div>
                  <div className="text-sm mt-1">Reason: {ex.reason_for_exception}</div>
                </div>
                <div className="text-right">
                  <div className="text-sm">Status: {ex.status}</div>
                  <div className="mt-2 flex flex-col gap-2">
                    <button onClick={async () => {
                      if (!confirm('Approve this exception?')) return;
                      try {
                        await axios.post(`/api/prescription-exceptions/${ex.id}/respond`, { action: 'approve', doctor_response: 'Approved' });
                        // reload
                        const res = await axios.get('/api/pharmacy/exceptions');
                        setExceptions(res.data.data.exceptions || res.data.data || []);
                      } catch (err) {
                        alert(err.response?.data?.message || 'Failed to approve');
                      }
                    }} className="btn btn-sm btn-success">Approve</button>
                    <button onClick={async () => {
                      const reason = prompt('Rejection reason');
                      if (!reason) return;
                      try {
                        await axios.post(`/api/prescription-exceptions/${ex.id}/respond`, { action: 'reject', doctor_response: 'Rejected', rejection_reason: reason });
                        const res = await axios.get('/api/pharmacy/exceptions');
                        setExceptions(res.data.data.exceptions || res.data.data || []);
                      } catch (err) {
                        alert(err.response?.data?.message || 'Failed to reject');
                      }
                    }} className="btn btn-sm btn-danger">Reject</button>
                  </div>
                </div>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

export default Exceptions;
