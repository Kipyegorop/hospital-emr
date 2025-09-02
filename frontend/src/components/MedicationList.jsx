import React, { useState } from 'react';
import axios from 'axios';

const MedicationList = ({ medications = [] }) => {
  const [adjustingId, setAdjustingId] = useState(null);
  const [adjustment, setAdjustment] = useState(0);
  const [reason, setReason] = useState('');
  const [loadingId, setLoadingId] = useState(null);

  const submitAdjust = async (medication) => {
    setLoadingId(medication.id);
    try {
      await axios.post(`/api/medications/${medication.id}/adjust-stock`, {
        adjustment: parseInt(adjustment, 10),
        reason,
      });
      // Simple UX: reload page
      window.location.reload();
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || 'Failed to adjust stock');
    } finally {
      setLoadingId(null);
      setAdjustingId(null);
      setAdjustment(0);
      setReason('');
    }
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {medications.map((m) => (
        <div key={m.id} className="bg-card border border-border rounded-lg p-4">
          <div className="flex justify-between items-start">
            <div>
              <h3 className="font-semibold">{m.name} {m.brand ? `(${m.brand})` : ''}</h3>
              <p className="text-sm text-muted-foreground">{m.generic_name || ''} • {m.form} {m.strength || ''}</p>
            </div>
            <div className="text-right">
              <div className="text-lg font-semibold">{m.current_stock ?? 0}</div>
              <div className="text-xs text-muted-foreground">Unit: {m.unit || '-'}</div>
            </div>
          </div>
          <div className="mt-2 text-sm text-muted-foreground">
            <div>Price: {m.selling_price ?? '-'} • Cost: {m.unit_cost ?? '-'}</div>
            <div className={`mt-1 ${m.current_stock <= (m.reorder_level ?? 0) ? 'text-red-600' : 'text-green-600'}`}>
              {m.current_stock <= (m.reorder_level ?? 0) ? 'Low stock' : 'Stock OK'}
            </div>
            {m.expiry_date && <div className="mt-1">Expiry: {new Date(m.expiry_date).toLocaleDateString()}</div>}
          </div>

          <div className="mt-4 flex gap-2">
            <button onClick={() => setAdjustingId(m.id)} className="btn btn-sm">Adjust Stock</button>
            <a className="btn btn-sm" href={`/medications/${m.id}`}>Details</a>
          </div>

          {adjustingId === m.id && (
            <div className="mt-3 border-t pt-3">
              <label className="text-sm">Adjustment (+ to add, - to remove)</label>
              <input type="number" value={adjustment} onChange={(e) => setAdjustment(e.target.value)} className="input w-full mt-1" />
              <label className="text-sm mt-2">Reason</label>
              <input value={reason} onChange={(e) => setReason(e.target.value)} className="input w-full mt-1" />
              <div className="mt-2 flex gap-2">
                <button onClick={() => submitAdjust(m)} disabled={loadingId === m.id} className="btn btn-primary btn-sm">{loadingId === m.id ? 'Saving...' : 'Save'}</button>
                <button onClick={() => setAdjustingId(null)} className="btn btn-sm">Cancel</button>
              </div>
            </div>
          )}
        </div>
      ))}
    </div>
  );
};

export default MedicationList;
