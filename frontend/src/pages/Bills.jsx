import React, { useEffect, useState } from 'react';
import billingService from '../services/billing';

const Bills = () => {
	const [bills, setBills] = useState([]);
	const [loading, setLoading] = useState(false);

	useEffect(() => {
		setLoading(true);
		billingService.list().then(res => {
			setBills(res.data.data || res.data || []);
		}).catch(() => setBills([])).finally(() => setLoading(false));
	}, []);

	const pay = async (bill) => {
		const amount = parseFloat(prompt('Amount to pay', bill.balance_due || bill.total_amount));
		if (!amount || amount <= 0) return;
		await billingService.pay(bill.id, { amount, payment_method: 'cash' });
		// reload
		const res = await billingService.get(bill.id);
		setBills(bills.map(b => b.id === bill.id ? res.data : b));
	};

	const waive = async (bill) => {
		const amount = parseFloat(prompt('Amount to waive', '0'));
		if (amount === null || isNaN(amount)) return;
		await billingService.waive(bill.id, { amount });
		const res = await billingService.get(bill.id);
		setBills(bills.map(b => b.id === bill.id ? res.data : b));
	};

	if (loading) return <div className="p-8">Loading...</div>;

	return (
		<div className="p-8">
			<h1 className="text-3xl font-bold mb-4">Bills</h1>
			{bills.length === 0 && <p>No bills found.</p>}
			<div className="space-y-4">
				{bills.map(b => (
					<div key={b.id} className="p-4 border rounded flex justify-between items-center">
						<div>
							<div className="font-medium">{b.bill_number} — {b.patient?.full_name || 'Unknown'}</div>
							<div className="text-sm text-gray-600">Total: {b.total_amount} | Paid: {b.amount_paid} | Due: {b.balance_due}</div>
						</div>
						<div className="space-x-2">
							<button onClick={() => pay(b)} className="px-3 py-1 bg-green-500 text-white rounded">Pay</button>
							<button onClick={() => waive(b)} className="px-3 py-1 bg-yellow-500 text-white rounded">Waive</button>
						</div>
					</div>
				))}
			</div>
		</div>
	);
};

export default Bills;
