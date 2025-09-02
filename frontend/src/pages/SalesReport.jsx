import React, { useEffect, useState } from 'react';
import axios from 'axios';

const SalesReport = () => {
  const [report, setReport] = useState({});
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');

  const load = async () => {
    try {
      const res = await axios.get('/api/pharmacy/sales-report', { params: { date_from: from || undefined, date_to: to || undefined } });
      setReport(res.data.data || res.data);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => { load(); }, []);

  return (
    <div className="p-6 space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Pharmacy Sales Report</h1>
          <p className="text-muted-foreground">Summary of pharmacy sales and statistics</p>
        </div>
        <div className="flex gap-2">
          <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="input" />
          <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="input" />
          <button onClick={load} className="btn btn-primary">Load</button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="bg-card border border-border rounded-lg p-4">
          <h3 className="font-semibold">Statistics</h3>
          <div className="mt-2 text-sm text-muted-foreground">
            <div>Total Sales: {report.statistics?.total_sales ?? 0}</div>
            <div>Total Revenue: {report.statistics?.total_revenue ?? 0}</div>
            <div>Total Profit: {report.statistics?.total_profit ?? 0}</div>
          </div>
        </div>

        <div className="bg-card border border-border rounded-lg p-4">
          <h3 className="font-semibold">Recent Sales</h3>
          <ul>
            {(report.sales || []).map((s) => (
              <li key={s.id} className="p-2 border-b border-border">
                <div className="flex justify-between">
                  <div>{s.medication_name} — {s.patient?.full_name || s.customer_name}</div>
                  <div className="text-sm">{s.total_amount}</div>
                </div>
                <div className="mt-2">
                  <button onClick={async () => {
                    const qty = prompt('Quantity to return', '1');
                    const reason = prompt('Reason for return', 'Returned by patient');
                    if (!qty || !reason) return;
                    try {
                      await axios.post(`/api/pharmacy-sales/${s.id}/return`, { quantity: parseInt(qty, 10), reason });
                      alert('Return processed');
                      load();
                    } catch (err) {
                      alert(err.response?.data?.message || 'Failed to return');
                    }
                  }} className="btn btn-sm btn-ghost">Process Return</button>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
};

export default SalesReport;
