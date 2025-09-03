import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { useToast } from '../contexts/ToastContext';
import { Link } from 'react-router-dom';

const Wards = () => {
	const [wards, setWards] = useState([]);
	const [loading, setLoading] = useState(false);
		const [showAdd, setShowAdd] = useState(false);
		const [form, setForm] = useState({ name: '', department_id: '' });
		const [departments, setDepartments] = useState([]);
		const toast = useToast();

	const load = async () => {
		setLoading(true);
		try {
			const res = await api.get('/wards');
			setWards(res.data.data || []);
		} catch (err) { console.error(err); }
		finally { setLoading(false); }
	};

	useEffect(() => { load(); }, []);

		useEffect(() => {
			const loadDeps = async () => {
				try {
					const res = await api.get('/departments');
					setDepartments(res.data.data || []);
				} catch (e) { console.error(e); }
			};
			loadDeps();
		}, []);

		const addWard = async (e) => {
			e.preventDefault();
			try {
				await api.post('/wards', form);
				setForm({ name: '', department_id: '' });
				setShowAdd(false);
				load();
				toast.success('Ward created');
			} catch (e) { console.error(e); toast.error('Failed to create ward'); }
		};

	return (
		<div className="space-y-6">
			<div className="flex items-center justify-between">
				<div>
					<h1 className="text-3xl font-bold text-foreground">Wards & Beds</h1>
					<p className="text-muted-foreground">Manage wards and bed allocation</p>
				</div>
				<div>
					<button className="btn" onClick={() => setShowAdd(true)}>Add Ward</button>
				</div>
			</div>

				{showAdd && (
					<form onSubmit={addWard} className="p-4 bg-card border border-border rounded">
						<div className="grid grid-cols-1 md:grid-cols-3 gap-2">
							<input required placeholder="Ward name" className="input" value={form.name} onChange={e=>setForm({...form, name:e.target.value})} />
							<select className="input" value={form.department_id} onChange={e=>setForm({...form, department_id:e.target.value})}>
								<option value="">— Department (optional) —</option>
								{departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
							</select>
							<div className="flex items-center"><button className="btn">Create</button><button type="button" className="btn ml-2" onClick={()=>setShowAdd(false)}>Cancel</button></div>
						</div>
					</form>
				)}

			<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
				{loading ? <div>Loading wards...</div> : wards.map((ward) => (
					<div key={ward.id} className="bg-card border border-border rounded-lg p-4">
						<div className="flex justify-between items-start">
							<div>
								<h3 className="font-semibold">{ward.name} {ward.code ? `(${ward.code})` : ''}</h3>
								<p className="text-sm text-muted-foreground">Type: {ward.ward_type || 'General'}</p>
								<p className="text-sm text-muted-foreground">Beds: {ward.total_beds ?? 0} — Available: {ward.available_beds ?? 0}</p>
							</div>
							<div className="flex flex-col space-y-2">
								<Link className="btn" to={`/wards/${ward.id}/beds`}>View beds</Link>
								<Link className="btn" to={`/wards/${ward.id}`}>Details</Link>
							</div>
						</div>
					</div>
				))}
			</div>
		</div>
	);
};

export default Wards;
