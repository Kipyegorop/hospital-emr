import React, { useState, useEffect, useRef } from 'react';
import billingService from '../services/billing';
import api from '../services/api';

const blankLine = () => ({ description: '', quantity: 1, unit_price: 0, total: 0 });

const CreateBill = () => {
  const [patientQuery, setPatientQuery] = useState('');
  const [patientResults, setPatientResults] = useState([]);
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [items, setItems] = useState([blankLine()]);
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);
  const [showReceipt, setShowReceipt] = useState(false);
  const receiptRef = useRef();

  useEffect(() => {
    const timeout = setTimeout(() => {
      if (patientQuery.length >= 2) {
        api.get(`/patients/search/${encodeURIComponent(patientQuery)}`).then(res => {
          setPatientResults(res.data.data || res.data || []);
        }).catch(()=> setPatientResults([]));
      }
    }, 250);
    return () => clearTimeout(timeout);
  }, [patientQuery]);

  const updateLine = (index, key, value) => {
    const next = [...items];
    next[index][key] = value;
    next[index].total = (parseFloat(next[index].quantity || 0) * parseFloat(next[index].unit_price || 0)) || 0;
    setItems(next);
  };

  const addLine = () => setItems([...items, blankLine()]);
  const removeLine = (i) => setItems(items.filter((_, idx) => idx !== i));

  const subtotal = items.reduce((s, it) => s + (parseFloat(it.total || 0) || 0), 0);

  const submit = async (e) => {
    e.preventDefault();
    if (!selectedPatient) return alert('Select a patient');
    setLoading(true);
    try {
      const payload = {
        patient_id: selectedPatient.id,
        bill_type: 'comprehensive',
        bill_date: new Date().toISOString().slice(0,10),
        subtotal: subtotal,
        total_amount: subtotal,
        billable_items: items.map(it => ({ name: it.description, quantity: it.quantity, unit_price: it.unit_price, total: it.total })),
        description: notes,
      };
      const res = await billingService.create(payload);
      setShowReceipt(true);
      // load receipt data into state via detail call
      const detail = await billingService.get(res.data.id || res.data.data?.id || res.data);
      // temporarily store for print
      setTimeout(()=> window.open(`/bills/${detail.data.id}`, '_blank'), 200);
      window.location.href = '/bills';
    } catch (err) {
      console.error(err);
      alert('Failed to create bill');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold mb-4">Create Bill</h1>
      <form onSubmit={submit} className="space-y-4">
        <div>
          <label className="block text-sm">Search patient</label>
          <input value={patientQuery} onChange={e=>{ setPatientQuery(e.target.value); setSelectedPatient(null); }} placeholder="Type name or patient number" className="w-full border px-2 py-1" />
          {patientResults.length > 0 && !selectedPatient && (
            <ul className="border mt-1 max-h-40 overflow-auto bg-white">
              {patientResults.map(p => (
                <li key={p.id} className="p-2 hover:bg-gray-100 cursor-pointer" onClick={()=>{ setSelectedPatient(p); setPatientResults([]); setPatientQuery(p.full_name); }}>
                  {p.full_name} — {p.patient_number}
                </li>
              ))}
            </ul>
          )}
          {selectedPatient && <div className="text-sm text-gray-600 mt-1">Selected: {selectedPatient.full_name} ({selectedPatient.patient_number})</div>}
        </div>

        <div className="border rounded p-2">
          <h3 className="font-medium mb-2">Items</h3>
          {items.map((it, idx) => (
            <div key={idx} className="grid grid-cols-12 gap-2 items-center mb-2">
              <input className="col-span-6 border px-2 py-1" placeholder="Description" value={it.description} onChange={e=>updateLine(idx, 'description', e.target.value)} />
              <input className="col-span-2 border px-2 py-1" placeholder="Qty" type="number" value={it.quantity} onChange={e=>updateLine(idx, 'quantity', e.target.value)} />
              <input className="col-span-2 border px-2 py-1" placeholder="Unit price" type="number" value={it.unit_price} onChange={e=>updateLine(idx, 'unit_price', e.target.value)} />
              <div className="col-span-1">{it.total}</div>
              <div className="col-span-1">
                <button type="button" onClick={()=>removeLine(idx)} className="text-sm text-red-600">Remove</button>
              </div>
            </div>
          ))}
          <div>
            <button type="button" onClick={addLine} className="px-3 py-1 bg-gray-200 rounded">Add line</button>
          </div>
        </div>

        <div>
          <label className="block text-sm">Notes</label>
          <textarea value={notes} onChange={e=>setNotes(e.target.value)} className="w-full border px-2 py-1" />
        </div>

        <div className="flex items-center justify-between">
          <div className="text-lg font-medium">Subtotal: {subtotal.toFixed(2)}</div>
          <button disabled={loading} className="px-4 py-2 bg-blue-600 text-white rounded">{loading ? 'Creating...' : 'Create Bill'}</button>
        </div>
      </form>

      {/* Receipt print preview modal (simple) */}
      {showReceipt && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center">
          <div className="bg-white w-2/3 p-6 rounded">
            <div ref={receiptRef}>
              <h2 className="text-xl font-bold">Receipt (Preview)</h2>
              <p>Printable receipt will be available after creation.</p>
            </div>
            <div className="mt-4 text-right">
              <button onClick={()=>setShowReceipt(false)} className="px-3 py-1 bg-gray-200 rounded">Close</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CreateBill;
