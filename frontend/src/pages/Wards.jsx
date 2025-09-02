import React, { useEffect, useState } from 'react';
import axios from 'axios';

const Wards = () => {
	const [wards, setWards] = useState([]);

	useEffect(() => {
		const load = async () => {
			try {
				const res = await axios.get('/api/wards');
				setWards(res.data.data);
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
					<h1 className="text-3xl font-bold text-foreground">Wards & Beds</h1>
					<p className="text-muted-foreground">Manage wards and bed allocation</p>
				</div>
			</div>

			<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
				{wards.map((ward) => (
					<div key={ward.id} className="bg-card border border-border rounded-lg p-4">
						<h3 className="font-semibold">{ward.name} ({ward.code})</h3>
						<p className="text-sm text-muted-foreground">Type: {ward.ward_type} — Available beds: {ward.available_beds}/{ward.total_beds}</p>
					</div>
				))}
			</div>
		</div>
	);
};

export default Wards;
