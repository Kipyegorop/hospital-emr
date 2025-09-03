import { useEffect, useState } from 'react';
import api from '../../services/api';
import { useToast } from '../../contexts/ToastContext';
import { useConfirm } from '../../contexts/ConfirmContext';

export default function DoctorSettings() {
  const [list, setList] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [form, setForm] = useState({ first_name: '', last_name: '', department_id: '' });
  const [editing, setEditing] = useState(null);
  const [msg, setMsg] = useState(null);
  const toast = useToast();
  const confirm = useConfirm();

  const load = async () => {
  try { const res = await api.get('/users?role=doctor'); setList(res.data.data || []); }
  catch (e) { toast.error('Failed to load doctors'); }
  };

  const loadDeps = async () => {
    try { const res = await api.get('/departments'); setDepartments(res.data.data || []); } catch (e) { /* ignore */ }
  };

  useEffect(()=>{ load(); }, []);
  useEffect(()=>{ loadDeps(); }, []);

  const create = async (e) => {
    e.preventDefault();
    if (!form.first_name || !form.last_name) { setMsg({type:'error', text:'Name required'}); return; }
    try {
      await api.post('/users', { ...form, role: 'doctor', password: 'changeme' });
      setForm({ first_name:'', last_name:'', department_id:'' });
      load(); toast.success('Doctor created (default password changeme)');
    } catch (e) { toast.error(e?.response?.data?.message || 'Failed'); }
  };

  const saveEdit = async (e) => {
    e.preventDefault();
    if (!editing || !editing.id) return;
    try { await api.put(`/users/${editing.id}`, editing); setEditing(null); load(); setMsg({type:'success', text:'Updated'}); }
    catch (e) { setMsg({type:'error', text:'Update failed'}); }
  };

  const remove = async (id) => {
    const ok = await confirm({ title: 'Delete', message: 'Delete user?' });
    if (!ok) return;
    try { await api.delete(`/users/${id}`); load(); toast.success('Deleted'); }
    catch (e) { toast.error('Delete failed'); }
  };

  return (
    <div className="space-y-4">
      <h2 className="font-medium">Doctors</h2>
      {msg && <div className={`p-2 ${msg.type==='error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{msg.text}</div>}
      <form onSubmit={create} className="grid grid-cols-1 md:grid-cols-3 gap-2">
        <input placeholder="First name" className="input" value={form.first_name} onChange={e=>setForm({...form, first_name:e.target.value})} />
        <input placeholder="Last name" className="input" value={form.last_name} onChange={e=>setForm({...form, last_name:e.target.value})} />
        <select className="input" value={form.department_id} onChange={e=>setForm({...form, department_id:e.target.value})}>
          <option value="">-- Department (optional) --</option>
          {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
        </select>
        <div><button className="btn">Create</button></div>
      </form>

      {editing && (
        <form onSubmit={saveEdit} className="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
          <input placeholder="First name" className="input" value={editing.first_name} onChange={e=>setEditing({...editing, first_name:e.target.value})} />
          <input placeholder="Last name" className="input" value={editing.last_name} onChange={e=>setEditing({...editing, last_name:e.target.value})} />
          <select className="input" value={editing.department_id} onChange={e=>setEditing({...editing, department_id:e.target.value})}>
            <option value="">-- Department (optional) --</option>
            {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
          </select>
          <div className="flex space-x-2"><button className="btn">Save</button><button type="button" className="btn" onClick={()=>setEditing(null)}>Cancel</button></div>
        </form>
      )}

      <div className="bg-card border border-border rounded p-2">
        <h3 className="font-semibold">Existing doctors</h3>
        <ul className="list-disc list-inside mt-2">
          {list.map(u => (
            <li key={u.id} className="flex justify-between items-center py-1">
              <div>{u.full_name || `${u.first_name} ${u.last_name}`}</div>
              <div className="flex space-x-2"><button className="btn" onClick={() => setEditing(u)}>Edit</button><button className="btn" onClick={() => remove(u.id)}>Delete</button></div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
