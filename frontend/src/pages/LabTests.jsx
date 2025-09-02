import React, { useEffect, useState } from 'react';
import axios from 'axios';
import LabTestRequestForm from '../components/LabTestRequestForm';

const LabTests = () => {
	const [tests, setTests] = useState([]);

	useEffect(() => {
		const load = async () => {
			try {
				const res = await axios.get('/api/lab-tests');
				setTests(res.data.data.data || res.data.data);
			} catch (err) {
				console.error(err);
			}
		};
		load();
	}, []);

	return (
		<div className="space-y-6">
			<div className="flex items-center justify-between">
				<div>
					<h1 className="text-3xl font-bold text-foreground">Laboratory</h1>
					<p className="text-muted-foreground">Request tests and manage results</p>
				</div>
			</div>

			<div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
				<div className="lg:col-span-2">
					<div className="bg-card border border-border rounded-lg p-4">
						<h2 className="font-semibold mb-2">Recent Requests</h2>
						<ul>
							{tests.map((t) => (
								<li key={t.id} className="p-2 border-b border-border">
									<div className="flex justify-between">
										<div>
											<strong>{t.test_type}</strong> for {t.patient?.full_name || t.patient_id}
										</div>
										<div className="text-sm text-muted-foreground">{t.status}</div>
									</div>
								</li>
							))}
						</ul>
					</div>
				</div>
				<div>
					<div className="bg-card border border-border rounded-lg p-4">
						<h2 className="font-semibold mb-2">Request Test</h2>
						<LabTestRequestForm />
					</div>
				</div>
			</div>
		</div>
	);
};

export default LabTests;
