import React, { useEffect, useState } from 'react';
import axios from 'axios';

const StockHistory = () => {
  const [history, setHistory] = useState([]);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await axios.get('/api/medications');
        // For now, fetch latest adjustments per medication by calling a hypothetical endpoint
        // We'll try a simple approach: for each medication, load adjustments
        const meds = res.data.data.data || res.data.data || [];
        const adjustments = [];
        for (const m of meds.slice(0, 50)) {
          try {
            const r = await axios.get(`/api/medications/${m.id}`);
            // medication details endpoint may include recent adjustments if implemented
            adjustments.push({ medication: m, details: r.data.data });
          } catch (e) {
            console.warn(e);
          }
        }
        setHistory(adjustments);
      } catch (err) {
        console.error(err);
      }
    };
    load();
  }, []);

  return (
    <div className="p-6 space-y-4">
      <h1 className="text-2xl font-bold">Stock Adjustment History</h1>
      <div className="bg-card border border-border rounded-lg p-4">
        <ul>
          {history.map((h, idx) => (
            <li key={idx} className="p-2 border-b border-border">
              <div className="font-medium">{h.medication.name}</div>
              <div className="text-sm text-muted-foreground">Current stock: {h.medication.current_stock}</div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

export default StockHistory;
