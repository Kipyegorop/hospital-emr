import React, { useState } from 'react';
import axios from 'axios';

const DispenseForm = ({ prescription, onDone }) => {
  const [quantity, setQuantity] = useState(prescription.remaining_quantity ?? (prescription.quantity_prescribed - (prescription.quantity_dispensed || 0)) );
  const [batch, setBatch] = useState('');
  const [expiry, setExpiry] = useState('');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [exceptionReason, setExceptionReason] = useState('');
  const [exceptionType, setExceptionType] = useState('quantity_change');

  const submit = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await axios.post(`/api/prescriptions/${prescription.id}/dispense`, {
        quantity_to_dispense: parseInt(quantity, 10),
        batch_number: batch || null,
        expiry_date: expiry || null,
        pharmacy_notes: notes || null,
        counseling_provided: true,
        counseling_notes: notes || null,
      });
      setLoading(false);
      onDone?.();
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to dispense');
      setLoading(false);
    }
  };

  return (
    <div>
      <div className="space-y-2">
        <div className="text-sm">Medication: <strong>{prescription.medication_name}</strong></div>
        <div className="text-sm">Patient: <strong>{prescription.patient?.full_name || prescription.patient_id}</strong></div>
        <div className="text-sm">Remaining: <strong>{prescription.remaining_quantity ?? (prescription.quantity_prescribed - (prescription.quantity_dispensed || 0))}</strong></div>

        <div className="mt-2">
          <label className="text-sm">Quantity to dispense</label>
          <input type="number" value={quantity} onChange={(e) => setQuantity(e.target.value)} className="input w-full" />
        </div>

        <div>
          <label className="text-sm">Batch number (optional)</label>
          <input value={batch} onChange={(e) => setBatch(e.target.value)} className="input w-full" />
        </div>

        <div>
          <label className="text-sm">Expiry date (optional)</label>
          <input type="date" value={expiry} onChange={(e) => setExpiry(e.target.value)} className="input w-full" />
        </div>

        <div>
          <label className="text-sm">Pharmacy notes / counseling</label>
          <textarea value={notes} onChange={(e) => setNotes(e.target.value)} className="input w-full" />
        </div>

        {error && <div className="text-red-600">{error}</div>}

        <div className="flex gap-2">
          <button onClick={submit} disabled={loading} className="btn btn-primary">{loading ? 'Dispensing...' : 'Dispense'}</button>
          <button onClick={() => onDone?.()} className="btn">Cancel</button>
        </div>
        <div className="mt-4 border-t pt-3">
          <h4 className="font-semibold">Request Exception</h4>
          <div className="mt-2">
            <label className="text-sm">Type</label>
            <select value={exceptionType} onChange={(e) => setExceptionType(e.target.value)} className="input w-full">
              <option value="quantity_change">Quantity change</option>
              <option value="substitution">Substitution</option>
              <option value="dosage_change">Dosage change</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div className="mt-2">
            <label className="text-sm">Reason / Details</label>
            <textarea value={exceptionReason} onChange={(e) => setExceptionReason(e.target.value)} className="input w-full" />
          </div>
          <div className="mt-2">
            <button onClick={async () => {
              try {
                await axios.post(`/api/prescriptions/${prescription.id}/request-exception`, {
                  exception_type: exceptionType,
                  reason_for_exception: exceptionReason,
                  requested_quantity: quantity,
                });
                alert('Exception requested');
              } catch (err) {
                alert(err.response?.data?.message || 'Failed to request exception');
              }
            }} className="btn btn-outline btn-sm">Request Exception</button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DispenseForm;
